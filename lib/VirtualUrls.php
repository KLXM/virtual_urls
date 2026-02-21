<?php

class VirtualUrls
{
    /**
     * Handle virtual URL resolution via YREWRITE_PREPARE EP.
     *
     * @param rex_extension_point $ep
     * @return array{article_id: int, clang?: int}|null
     */
    public static function handle(rex_extension_point $ep): ?array
    {
        $url = $ep->getParam('url');
        $domain = $ep->getParam('domain');

        $url = trim($url, '/');
        $segments = explode('/', $url);

        // Get profiles
        $sql = rex_sql::factory();
        $profiles = $sql->getArray('SELECT * FROM ' . rex::getTable('virtual_urls_profiles'));

        foreach ($profiles as $profile) {
            $trigger = $profile['trigger_segment'];

            // Find trigger in segments
            $triggerIndex = array_search($trigger, $segments);

            if ($triggerIndex !== false && isset($segments[$triggerIndex + 1])) {
                $slug = $segments[$triggerIndex + 1];

                // Ensure the path ends here
                if (count($segments) > $triggerIndex + 2) {
                    continue;
                }

                // Now check if slug exists in dataset
                $table = $profile['table_name'];
                $field = $profile['url_field'];

                $dataset = rex_yform_manager_dataset::query($table)
                    ->where($field, $slug)
                    ->findOne();

                if ($dataset) {
                    // Match found!

                    // Determine the article_id to render
                    $articleId = (int) $profile['article_id'];

                    // Try to resolve the path UP TO the trigger to a real article
                    $checkPath = implode('/', array_slice($segments, 0, $triggerIndex + 1));
                    $mountId = self::getArticleIdByPath($checkPath, $domain);

                    if ($mountId) {
                        $articleId = $mountId;
                    }

                    // Store data for usage in module/template
                    rex::setProperty('virtual_urls.data', $dataset);
                    rex::setProperty('virtual_urls.profile', $profile);

                    // Return article_id to YRewrite's path resolver
                    return ['article_id' => $articleId];
                }
            }
        }

        return null;
    }
    
    /**
     * @param rex_yrewrite_domain|null $domain
     */
    private static function getArticleIdByPath(string $path, $domain = null): ?int
    {
        if (!class_exists('rex_yrewrite')) {
            return null;
        }

        if (!$domain) {
            $domain = rex_yrewrite::getCurrentDomain();
        }
        if (!$domain) {
            return null;
        }

        $url = trim($path, '/') . '/';
        $result = rex_yrewrite::getArticleIdByUrl($domain, $url);

        if ($result !== false && is_array($result)) {
            return (int) array_key_first($result);
        }

        return null;
    }

    public static function getCurrentData()
    {
        return rex::getProperty('virtual_urls.data');
    }

    public static function getCurrentProfile()
    {
        return rex::getProperty('virtual_urls.profile');
    }
}
