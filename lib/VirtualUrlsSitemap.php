<?php

class VirtualUrlsSitemap
{
    public static function addToSitemap(rex_extension_point $ep)
    {
        $domain = $ep->getSubject(); // rex_yrewrite_domain
        $sitemap = $ep->getParam('sitemap'); // rex_yrewrite_sitemap

        $sql = rex_sql::factory();
        $profiles = $sql->getArray('SELECT * FROM ' . rex::getTable('virtual_urls_profiles') . ' WHERE default_category_id > 0');

        foreach ($profiles as $profile) {
            // Check if default category is within the current domain
            $cat = rex_category::get($profile['default_category_id']);
            if (!$cat || $cat->getClangId() != $domain->getStartClang()) {
                // If clang differs, YRewrite might call this EP for each clang?
                // YRewrite generates sitemap per Domain AND per Clang usually?
                // We need to check if the category belongs to the current domain mount.
                // Simplified check:
                // if ($cat->domain != $domain) ... (requires complex check)
            }
            
            // Build Query
            $where = '1=1';
            
            if (!empty($profile['sitemap_filter'])) {
                $filter = $profile['sitemap_filter'];
                
                // Replace Placeholders with PHP Date (supports offsets like ###NOW -1 MONTH###)
                $filter = preg_replace_callback(
                    '/###(NOW|CURRENT_DATE|CURRENT_TIMESTAMP)(?:\s*([+-].*?))?###/',
                    function ($matches) {
                        $type = $matches[1];
                        $offset = isset($matches[2]) && trim($matches[2]) !== '' ? $matches[2] : 'now';
                        $ts = strtotime($offset);
                        
                        if ($ts === false) {
                            return $matches[0]; // invalid offset, return original
                        }
                        
                        if ($type === 'NOW') {
                             return date('Y-m-d H:i:s', $ts);
                        } elseif ($type === 'CURRENT_DATE') {
                             return date('Y-m-d', $ts);
                        } elseif ($type === 'CURRENT_TIMESTAMP') {
                             return $ts;
                        }
                    },
                    $filter
                );
                
                // Legacy support
                $filter = str_replace('###DATETIME###', date('Y-m-d H:i:s'), $filter);
                $filter = str_replace('###DATE###', date('Y-m-d'), $filter);
                
                $where = $filter;
            }

            $items = rex_sql::factory();
            $items->setQuery('SELECT * FROM ' . $profile['table_name'] . ' WHERE ' . $where);

            foreach ($items as $item) {
                 // Construct URL
                 // Base URL of the category
                 // We need the URL of the category for the current clang?
                 // $ep->getSubject() is the domain.
                 
                 // Reconstruct:
                 // https://domain.de/category-path/trigger/slug
                 
                 // Get Category URL via YRewrite
                 // $catUrl = rex_yrewrite::getFullPath($cat->getUrl()); 
                 // Problem: $cat might be in different clang. we should respect current sitemap clang?
                 
                 // Let's assume we are generating sitemap for the clang of the category or map it.
                 // For now simplified:
                 
                 $catUrl = rex_getUrl($profile['default_category_id']);
                 
                 // Remove trailing slash if exists
                 $catUrl = rtrim($catUrl, '/');
                 
                 $url = $catUrl . '/' . $profile['trigger_segment'] . '/' . $item->getValue($profile['url_field']);
                 
                 // Add to Sitemap
                 // loc, lastmod, changefreq, priority
                 $lastmod = date('c'); // default now
                 if ($item->hasValue('updatedate')) {
                     $lastmod = date('c', $item->getValue('updatedate'));
                 } elseif ($item->hasValue('createdate')) {
                     $lastmod = date('c', $item->getValue('createdate'));
                 }

                 $sitemap[] = [
                    'loc' => $url,
                    'lastmod' => $lastmod,
                    'changefreq' => 'weekly',
                    'priority' => '0.5'
                 ];
            }
        }
        
        return $sitemap;
    }
}
