<?php

// 1. Register YForm Value (Global, needed in Backend & Frontend)
if (rex_addon::get('yform')->isAvailable()) {
    // 2. Register Cache Buster (Global, needed in Backend mainly)
    VirtualUrlsCache::init();
}

// 3. Frontend / YRewrite integration
if (rex_addon::get('yrewrite')->isAvailable()) {
    
    // Routing Logic (Frontend only)
    if (!rex::isBackend()) {
        rex_extension::register('PACKAGES_INCLUDED', function () {
            VirtualUrls::handle();
        }, rex_extension::LATE);
    }
    
    // Sitemap Integration (Global, as sitemap generation might be triggered from anywhere)
    rex_extension::register('YREWRITE_SITEMAP_PARSED', [VirtualUrlsSitemap::class, 'addToSitemap']);
}
