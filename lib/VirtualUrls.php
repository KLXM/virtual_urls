<?php

class VirtualUrls
{
    public static function handle()
    {
        if (rex::isBackend() || rex::isSetup()) {
            return;
        }

        $path = preg_replace('/^\//', '', $_SERVER['REQUEST_URI']);
        
        // Remove query string
        if (($pos = strpos($path, '?')) !== false) {
            $path = substr($path, 0, $pos);
        }

        $path = trim($path, '/');
        $segments = explode('/', $path);
        
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
                    
                    // 1. Determine the Context Article (Mount Point)
                    $mountId = null;
                    
                    // Construct the path that represents the structure UP TO the trigger (inclusive)
                    $checkPath = implode('/', array_slice($segments, 0, $triggerIndex + 1));
                    
                    // Try to resolve this path to a real article
                    $mountId = self::getArticleIdByPath($checkPath);

                    if ($mountId) {
                        rex::setProperty('article_id', $mountId);
                    } else {
                        // Fallback to configured renderer
                        rex::setProperty('article_id', $profile['article_id']);
                    }
                    
                    // 2. Store data for usage in module/template
                    rex::setProperty('virtual_urls.data', $dataset);
                    rex::setProperty('virtual_urls.profile', $profile);
                    
                    return;
                }
            }
        }
    }
    
    private static function getArticleIdByPath($path)
    {
        if (!class_exists('rex_yrewrite')) {
            return null;
        }
        
        $checkPath = '/' . trim($path, '/');
        $paths = rex_yrewrite::getPaths();
        
        if (isset($paths[$checkPath])) {
            return $paths[$checkPath];
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
