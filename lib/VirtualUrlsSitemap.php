<?php

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
            ' WHERE default_category_id > 0 AND (domain = :domain OR domain = :empty)',
            ['domain' => $domain->getName(), 'empty' => '']
        );

        foreach ($profiles as $profile) {
            // Check if the profile's category belongs to this domain
            $categoryId = (int) $profile['default_category_id'];
            $articleDomain = rex_yrewrite::getDomainByArticleId($categoryId);
            if (!$articleDomain || $articleDomain->getName() !== $domain->getName()) {
                continue;
            }

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
                $catUrl = rtrim(rex_yrewrite::getFullUrlByArticleId($categoryId), '/');
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

                $sitemap[] =
                    "\n" . '<url>' .
                    "\n\t" . '<loc>' . rex_escape($fullUrl) . '</loc>' .
                    "\n\t" . '<lastmod>' . $lastmod . '</lastmod>' .
                    "\n\t" . '<changefreq>weekly</changefreq>' .
                    "\n\t" . '<priority>0.5</priority>' .
                    "\n" . '</url>';
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
