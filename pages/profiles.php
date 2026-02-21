<?php

$func = rex_request('func', 'string');
$id = rex_request('id', 'int');

if ($func === 'delete') {
    $sql = rex_sql::factory();
    $sql->setQuery('DELETE FROM ' . rex::getTable('virtual_urls_profiles') . ' WHERE id = :id', ['id' => $id]);
    echo rex_view::success('Profil gelöscht');
    rex_response::sendRedirect(rex_url::currentBackendPage());
}

if ($func === 'edit' || $func === 'add') {
    $form = rex_form::factory(rex::getTable('virtual_urls_profiles'), '', $func === 'edit' ? 'id=' . $id : '', 'post', false);

    // Domain-Auswahl aus YRewrite
    $field = $form->addSelectField('domain');
    $field->setLabel('Domain');
    $field->setNotice('Die Domain, für die dieses Profil gilt. "Alle Domains" = Profil greift überall.');
    $select = $field->getSelect();
    $select->addOption('Alle Domains', '');
    if (rex_addon::get('yrewrite')->isAvailable()) {
        foreach (rex_yrewrite::getDomains() as $domain) {
            $name = $domain->getName();
            if ($name !== 'default') {
                $select->addOption($name, $name);
            }
        }
    }

    $field = $form->addTextField('table_name');
    $field->setLabel('YForm Tabelle');
    $field->setNotice('z.B. rex_news');

    $field = $form->addTextField('trigger_segment');
    $field->setLabel('URL Trigger Segment');
    $field->setNotice('Der Teil der URL, der die virtuelle URL einleitet, z.B. news');

    $field = $form->addTextField('url_field');
    $field->setLabel('Slug Feld Name');
    $field->setNotice('z.B. code oder url');

    $field = $form->addLinkmapField('article_id');
    $field->setLabel('Renderer Artikel');

    $field = $form->addLinkmapField('default_category_id');
    $field->setLabel('Standard Kategorie für Sitemap');
    $field->setNotice('Unter dieser Kategorie werden die URLs in der Sitemap ausgegeben (Canonical Basis)');

    $field = $form->addTextField('relation_field');
    $field->setLabel('Relation Feld (Optional)');
    $field->setNotice('Feld in der Datentabelle, das auf die Relationstabelle verweist, z.B. category_id');

    $field = $form->addTextField('relation_table');
    $field->setLabel('Relation Tabelle (Optional)');
    $field->setNotice('Die Tabelle der Relation, z.B. rex_news_category');

    $field = $form->addTextField('relation_slug_field');
    $field->setLabel('Relation Slug Feld (Optional)');
    $field->setNotice('Feld in der Relationstabelle für den URL-Teil, z.B. name oder url_slug. Wird automatisch normalisiert.');

    $field = $form->addTextField('sitemap_filter');
    $field->setLabel('Sitemap Filter (SQL Where)');
    $field->setNotice('z.B. status = 1 AND date <= "###NOW -1 DAY###"');

    $content = $form->get();

    $fragment = new rex_fragment();
    $fragment->setVar('class', 'edit', false);
    $fragment->setVar('title', ($func === 'edit') ? 'Profil bearbeiten' : 'Profil erstellen', false);
    $fragment->setVar('body', $content, false);
    echo $fragment->parse('core/page/section.php');
} else {
    // Liste
    $list = rex_list::factory('SELECT * FROM ' . rex::getTable('virtual_urls_profiles'));

    $list->addTableAttribute('class', 'table-striped');

    $list->setColumnLabel('id', 'ID');
    $list->setColumnLabel('domain', 'Domain');
    $list->setColumnLabel('table_name', 'Tabelle');
    $list->setColumnLabel('trigger_segment', 'URL Trigger');
    $list->setColumnLabel('url_field', 'Slug Feld');
    $list->setColumnLabel('article_id', 'Renderer Artikel');
    $list->removeColumn('relation_field');
    $list->removeColumn('relation_table');
    $list->removeColumn('relation_slug_field');
    $list->removeColumn('default_category_id');
    $list->removeColumn('sitemap_filter');

    $list->setColumnFormat('domain', 'custom', static function ($params) {
        $value = $params['list']->getValue('domain');
        return $value !== '' ? rex_escape($value) : '<em>Alle Domains</em>';
    });

    $list->addColumn('edit', '<i class="rex-icon rex-icon-edit"></i> Bearbeiten');
    $list->setColumnLayout('edit', ['<th class="rex-table-action" colspan="2">###VALUE###</th>', '<td class="rex-table-action">###VALUE###</td>']);
    $list->setColumnParams('edit', ['func' => 'edit', 'id' => '###id###']);

    $list->addColumn('delete', '<i class="rex-icon rex-icon-delete"></i> Löschen');
    $list->setColumnLayout('delete', ['', '<td class="rex-table-action">###VALUE###</td>']);
    $list->setColumnParams('delete', ['func' => 'delete', 'id' => '###id###']);
    $list->addLinkAttribute('delete', 'onclick', "return confirm('Profil wirklich löschen?');");

    $content = $list->get();

    $fragment = new rex_fragment();
    $fragment->setVar('content', $content, false);
    $content = $fragment->parse('core/page/section.php');

    echo '<a href="' . rex_url::currentBackendPage(['func' => 'add']) . '" class="btn btn-primary">Neues Profil anlegen</a><br><br>';
    echo $content;
}
