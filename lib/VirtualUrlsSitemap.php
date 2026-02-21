<?php

namespace FriendsOfRedaxo\VirtualUrl;

use rex;
use rex_clang;
use rex_escape;
use rex_extension_point;
use rex_media;
use rex_media_manager;
use rex_sql;
use rex_string;
use rex_yrewrite;
use rex_yrewrite_domain;

class VirtualUrlsSitemap
{
    /**
     * Adds virtual URLs to the YRewrite sitemap.
     *
     * EP: YREWRITE_DOMAIN_SITEMAP
     * Subject: array of XML strings
     * Params: ['domain' => rex_yrewrite_domain]
     *
     * @param rex_extension_point $ep
     * @return list<string>
     */
    public static function addToSitemap(rex_extension_point $ep): array
    {
        /** @var list<string> $sitemap */
        $sitemap = $ep->getSubject();

        /** @var rex_yrewrite_domain $domain */
        $domain = $ep->getParam('domain');

        $sql = rex_sql::factory();
        $profiles = $sql->getArray(
            'SELECT * FROM ' . rex::getTable('virtual_urls_profiles') . 
            ' WHERE status = 1 AND default_category_id > 0 AND (domain = :domain OR domain = :empty)',
            ['domain' => $domain->getName(), 'empty' => '']
        );

        // Deduplicate: if a profile is clang-specific, skip the "all languages" variant for the same table+trigger
        $specificClangs = [];
        foreach ($profiles as $p) {
            if ((int) ($p['clang_id'] ?? -1) >= 0) {
                $specificClangs[$p['table_name'] . '|' . $p['trigger_segment']][] = (int) $p['clang_id'];
            }
        }

        foreach ($profiles as $profile) {
            // Check if the profile's category belongs to this domain
            $categoryId = (int) $profile['default_category_id'];
            $articleDomain = rex_yrewrite::getDomainByArticleId($categoryId);
            if (!$articleDomain || $articleDomain->getName() !== $domain->getName()) {
                continue;
            }

            // Determine clang for URLs
            $profileClang = (int) ($profile['clang_id'] ?? -1);

            $hasRelation = trim($profile['relation_field'] ?? '') !== '' 
                && trim($profile['relation_table'] ?? '') !== '' 
                && trim($profile['relation_slug_field'] ?? '') !== '';

            // Build WHERE clause
            $where = '1=1';

            if (trim($profile['sitemap_filter'] ?? '') !== '') {
                $where = self::replaceDatePlaceholders($profile['sitemap_filter']);
            }

            // Pre-load relation slugs if needed
            $relationSlugs = [];
            if ($hasRelation) {
                $relSql = rex_sql::factory();
                $relRows = $relSql->getArray(
                    'SELECT id, ' . $relSql->escapeIdentifier($profile['relation_slug_field']) . ' FROM ' . $profile['relation_table']
                );
                foreach ($relRows as $relRow) {
                    $relationSlugs[(int) $relRow['id']] = rex_string::normalize((string) $relRow[$profile['relation_slug_field']], '-', '_');
                }
            }

            $items = rex_sql::factory();
            $items->setQuery('SELECT * FROM ' . $profile['table_name'] . ' WHERE ' . $where);

            foreach ($items as $item) {
                // Build full URL: category-path/trigger/[relation-slug/]slug
                $clangForUrl = $profileClang >= 0 ? $profileClang : rex_clang::getStartId();
                $catUrl = rtrim(rex_yrewrite::getFullUrlByArticleId($categoryId, $clangForUrl), '/');
                $slug = $item->getValue($profile['url_field']);

                if ($hasRelation) {
                    $relationId = (int) $item->getValue($profile['relation_field']);
                    $relationSlug = $relationSlugs[$relationId] ?? null;
                    if ($relationSlug === null) {
                        continue; // Skip items with unknown relation
                    }
                    $fullUrl = $catUrl . '/' . $profile['trigger_segment'] . '/' . $relationSlug . '/' . $slug;
                } else {
                    $fullUrl = $catUrl . '/' . $profile['trigger_segment'] . '/' . $slug;
                }

                // Determine lastmod
                $lastmod = date(DATE_W3C);
                if ($item->hasValue('updatedate') && $item->getValue('updatedate')) {
                    $ts = strtotime($item->getValue('updatedate'));
                    if ($ts !== false) {
                        $lastmod = date(DATE_W3C, $ts);
                    }
                } elseif ($item->hasValue('createdate') && $item->getValue('createdate')) {
                    $ts = strtotime($item->getValue('createdate'));
                    if ($ts !== false) {
                        $lastmod = date(DATE_W3C, $ts);
                    }
                }

                $sitemapEntry =
                    "\n" . '<url>' .
                    "\n\t" . '<loc>' . rex_escape($fullUrl) . '</loc>' .
                    "\n\t" . '<lastmod>' . $lastmod . '</lastmod>';

                // SEO Image
                $imageField = $profile['seo_image_field'] ?? '';
                if ($imageField && $item->hasValue($imageField)) {
                    $image = $item->getValue($imageField);
                    if ($image) {
                        $images = explode(',', $image);
                        $image = array_shift($images);
                        $media = rex_media::get($image);
                        if ($media) {
                            $mediaUrl = rtrim($domain->getUrl(), '/') . rex_media_manager::getUrl('yrewrite_seo_image', $image);
                            $sitemapEntry .= "\n\t" . '<image:image>' .
                                "\n\t\t" . '<image:loc>' . rex_escape($mediaUrl) . '</image:loc>';
                            if ($media->getTitle()) {
                                $sitemapEntry .= "\n\t\t" . '<image:title>' . rex_escape($media->getTitle()) . '</image:title>';
                            }
                            $sitemapEntry .= "\n\t" . '</image:image>';
                        }
                    }
                }

                $changefreq = $profile['sitemap_changefreq'] ?? 'weekly';
                $priority = $profile['sitemap_priority'] ?? '0.5';

                $sitemapEntry .=
                    "\n\t" . '<changefreq>' . rex_escape($changefreq) . '</changefreq>' .
                    "\n\t" . '<priority>' . rex_escape($priority) . '</priority>' .
                    "\n" . '</url>';

                $sitemap[] = $sitemapEntry;
            }
        }

        return $sitemap;
    }

    /**
     * Replace date placeholders like ###NOW###, ###NOW -1 MONTH###, ###CURRENT_DATE###, etc.
     */
    private static function replaceDatePlaceholders(string $filter): string
    {
        $filter = preg_replace_callback(
            '/###(NOW|CURRENT_DATE|CURRENT_TIMESTAMP)(?:\s*([+-].*?))?###/',
            static function (array $matches): string {
                $type = $matches[1];
                $offset = isset($matches[2]) && trim($matches[2]) !== '' ? trim($matches[2]) : 'now';
                $ts = strtotime($offset);

                if ($ts === false) {
                    return $matches[0];
                }

                return match ($type) {
                    'NOW' => date('Y-m-d H:i:s', $ts),
                    'CURRENT_DATE' => date('Y-m-d', $ts),
                    'CURRENT_TIMESTAMP' => (string) $ts,
                    default => $matches[0],
                };
            },
            $filter
        ) ?? $filter;

        // Legacy placeholders
        $filter = str_replace('###DATETIME###', date('Y-m-d H:i:s'), $filter);
        $filter = str_replace('###DATE###', date('Y-m-d'), $filter);

        return $filter;
    }
}
