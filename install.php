<?php

// Tabelle anlegen/aktualisieren via rex_sql_table
rex_sql_table::get(rex::getTable('virtual_urls_profiles'))
    ->ensureColumn(new rex_sql_column('id', 'int(11) unsigned', false, null, 'auto_increment'))
    ->ensureColumn(new rex_sql_column('clang_id', 'int(11)', false, '-1'))
    ->ensureColumn(new rex_sql_column('domain', 'varchar(255)', false, ''))
    ->ensureColumn(new rex_sql_column('table_name', 'varchar(255)', false, ''))
    ->ensureColumn(new rex_sql_column('trigger_segment', 'varchar(255)', false, ''))
    ->ensureColumn(new rex_sql_column('url_field', 'varchar(255)', false, ''))
    ->ensureColumn(new rex_sql_column('article_id', 'int(11) unsigned', false, '0'))
    ->ensureColumn(new rex_sql_column('relation_field', 'varchar(255)', true))
    ->ensureColumn(new rex_sql_column('relation_table', 'varchar(255)', true))
    ->ensureColumn(new rex_sql_column('relation_slug_field', 'varchar(255)', true))
    ->ensureColumn(new rex_sql_column('default_category_id', 'int(11) unsigned', true))
    ->ensureColumn(new rex_sql_column('sitemap_filter', 'varchar(255)', true))
    ->setPrimaryKey('id')
    ->ensure();
