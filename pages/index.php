<?php

/** @var rex_addon $this */

echo rex_view::title($this->i18n('Virtual URLs'));

$func = rex_request('func', 'string');
$id = rex_request('id', 'int');

if ($func == '') {
    $list = rex_list::factory('SELECT * FROM ' . rex::getTable('virtual_urls_profiles'));
    
    $list->addTableAttribute('class', 'table-striped');
    
    $list->setColumnLabel('id', 'ID');
    $list->setColumnLabel('table_name', 'Tabelle');
    $list->setColumnLabel('trigger_segment', 'URL Trigger');
    $list->setColumnLabel('url_field', 'Slug Feld');
    $list->setColumnLabel('article_id', 'Renderer Artikel');
    
    $list->addColumn('edit', '<i class="rex-icon rex-icon-edit"></i> Edit');
    $list->setColumnLayout('edit', ['<th class="rex-table-action" colspan="2">###VALUE###</th>', '<td class="rex-table-action">###VALUE###</td>']);
    $list->setColumnParams('edit', ['func' => 'edit', 'id' => '###id###']);
    
    $list->addColumn('delete', '<i class="rex-icon rex-icon-delete"></i> Delete');
    $list->setColumnLayout('delete', ['', '<td class="rex-table-action">###VALUE###</td>']);
    $list->setColumnParams('delete', ['func' => 'delete', 'id' => '###id###']);
    $list->addLinkAttribute('delete', 'onclick', "return confirm('Are you sure?');");
    
    $content = $list->get();
    
    $fragment = new rex_fragment();
    $fragment->setVar('content', $content, false);
    $content = $fragment->parse('core/page/section.php');
    
    echo '<a href="' . rex_url::currentBackendPage(['func' => 'add']) . '" class="btn btn-primary">Neues Profil anlegen</a><br><br>';
    echo $content;
    
} elseif ($func == 'edit' || $func == 'add') {
    
    $form = rex_form::factory(rex::getTable('virtual_urls_profiles'), '', 'id=' . $id);
    
    $field = $form->addTextField('table_name');
    $field->setLabel('YForm Tabelle (z.B. rex_news)');
    
    $field = $form->addTextField('trigger_segment');
    $field->setLabel('URL Trigger Segment (z.B. news)');
    $field->setNotice('Der Teil der URL, der die virtuelle URL einleitet');
    
    $field = $form->addTextField('url_field');
    $field->setLabel('Slug Feld Name (z.B. code oder url)');
    
    $field = $form->addLinkmapField('article_id');
    $field->setLabel('Renderer Artikel');

    $field = $form->addLinkmapField('default_category_id');
    $field->setLabel('Standard Kategorie für Sitemap');
    $field->setNotice('Unter dieser Kategorie werden die URLs in der Sitemap ausgegeben (Canonical Basis)');
    
    $field = $form->addTextField('relation_field');
    $field->setLabel('Relation/Mount Feld (Optional, z.B. category_id)');
    
    $field = $form->addTextField('sitemap_filter');
    $field->setLabel('Sitemap Filter (SQL Where)');
    $field->setNotice('z.B. status = 1 AND date <= "###NOW -1 DAY###"');
    
    $content = $form->get();
    
    $fragment = new rex_fragment();
    $fragment->setVar('class', 'edit', false);
    $fragment->setVar('title', ($func == 'edit') ? 'Profil bearbeiten' : 'Profil erstellen', false);
    $fragment->setVar('body', $content, false);
    echo $fragment->parse('core/page/section.php');
} elseif ($func == 'delete') {
    $sql = rex_sql::factory();
    $sql->setQuery('DELETE FROM ' . rex::getTable('virtual_urls_profiles') . ' WHERE id = ' . $id);
    echo rex_view::success('Profil gelöscht');
    rex_response::sendRedirect(rex_url::currentBackendPage());
}
