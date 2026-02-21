<?php

$sql = rex_sql::factory();

$sql->setQuery('CREATE TABLE IF NOT EXISTS ' . rex::getTable('virtual_urls_profiles') . ' (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    table_name VARCHAR(255) NOT NULL,
    trigger_segment VARCHAR(255) NOT NULL,
    url_field VARCHAR(255) NOT NULL,
    article_id INT(11) UNSIGNED NOT NULL,
    relation_field VARCHAR(255) DEFAULT NULL,
    default_category_id INT(11) UNSIGNED DEFAULT NULL,
    sitemap_filter VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
