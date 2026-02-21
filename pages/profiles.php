<?php

$func = rex_request('func', 'string');
$id = rex_request('id', 'int');

if ($func === 'delete') {
    $sql = rex_sql::factory();
    $sql->setQuery('DELETE FROM ' . rex::getTable('virtual_urls_profiles') . ' WHERE id = :id', ['id' => $id]);
    echo rex_view::success('Profil gelöscht');
    rex_response::sendRedirect(rex_url::currentBackendPage());
}

if ($func === 'status') {
    $status = rex_request('status', 'int', 0);
    $sql = rex_sql::factory();
    $sql->setQuery('UPDATE ' . rex::getTable('virtual_urls_profiles') . ' SET status = :status WHERE id = :id', ['status' => $status, 'id' => $id]);
    echo rex_view::success('Status geändert');
    $func = ''; // Zurück zur Liste
}

if ($func === 'edit' || $func === 'add') {
    $form = rex_form::factory(rex::getTable('virtual_urls_profiles'), '', $func === 'edit' ? 'id=' . $id : '1=1', 'post', false);

    $field = $form->addSelectField('status');
    $field->setLabel('Status');
    $select = $field->getSelect();
    $select->addOption('Aktiv', 1);
    $select->addOption('Inaktiv', 0);

    // Sprache
    $field = $form->addSelectField('clang_id');
    $field->setLabel('Sprache');
    $field->setNotice('Die Sprache, für die dieses Profil gilt. "Alle Sprachen" = sprachunabhängig.');
    $select = $field->getSelect();
    $select->addOption('Alle Sprachen', '-1');
    foreach (rex_clang::getAll() as $clang) {
        $select->addOption($clang->getName(), $clang->getId());
    }

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

    $field = $form->addSelectField('table_name');
    $field->setLabel('YForm Tabelle');
    $field->setNotice('Die Tabelle, aus der die Datensätze geladen werden sollen.');
    $field->setAttribute('id', 'virtual-urls-table-name');
    $select = $field->getSelect();
    $select->addOption('Bitte wählen...', '');
    $tables = rex_sql::factory()->getArray('SHOW TABLES');
    foreach ($tables as $table) {
        $tableName = current($table);
        if (str_starts_with($tableName, rex::getTablePrefix())) {
            $select->addOption($tableName, $tableName);
        }
    }

    $field = $form->addTextField('trigger_segment');
    $field->setLabel('URL Trigger Segment');
    $field->setNotice('Der Teil der URL, der die virtuelle URL einleitet, z.B. news');

    $field = $form->addSelectField('url_field');
    $field->setLabel('Slug Feld Name');
    $field->setNotice('z.B. code oder url');
    $field->setAttribute('class', 'form-control virtual-urls-column-select');
    $field->setAttribute('data-selected', $func === 'edit' ? $form->getSql()->getValue('url_field') : '');
    $select = $field->getSelect();
    $select->addOption('Bitte Tabelle wählen...', '');

    $field = $form->addLinkmapField('article_id');
    $field->setLabel('Renderer Artikel');

    $field = $form->addLinkmapField('default_category_id');
    $field->setLabel('Standard Kategorie für Sitemap');
    $field->setNotice('Unter dieser Kategorie werden die URLs in der Sitemap ausgegeben (Canonical Basis)');

    $field = $form->addSelectField('relation_field');
    $field->setLabel('Relation Feld (Optional)');
    $field->setNotice('Feld in der Datentabelle, das auf die Relationstabelle verweist, z.B. category_id');
    $field->setAttribute('class', 'form-control virtual-urls-column-select');
    $field->setAttribute('data-selected', $func === 'edit' ? $form->getSql()->getValue('relation_field') : '');
    $select = $field->getSelect();
    $select->addOption('Bitte Tabelle wählen...', '');

    $field = $form->addSelectField('relation_table');
    $field->setLabel('Relation Tabelle (Optional)');
    $field->setNotice('Die Tabelle der Relation, z.B. rex_news_category');
    $field->setAttribute('id', 'virtual-urls-relation-table');
    $select = $field->getSelect();
    $select->addOption('Keine Relation', '');
    foreach ($tables as $table) {
        $tableName = current($table);
        if (str_starts_with($tableName, rex::getTablePrefix())) {
            $select->addOption($tableName, $tableName);
        }
    }

    $field = $form->addSelectField('relation_slug_field');
    $field->setLabel('Relation Slug Feld (Optional)');
    $field->setNotice('Feld in der Relationstabelle für den URL-Teil, z.B. name oder url_slug. Wird automatisch normalisiert.');
    $field->setAttribute('class', 'form-control virtual-urls-relation-column-select');
    $field->setAttribute('data-selected', $func === 'edit' ? $form->getSql()->getValue('relation_slug_field') : '');
    $select = $field->getSelect();
    $select->addOption('Bitte Relationstabelle wählen...', '');

    $field = $form->addTextField('sitemap_filter');
    $field->setLabel('Sitemap Filter (SQL Where)');
    $field->setNotice('z.B. status = 1 AND date <= "###NOW -1 DAY###"');

    $field = $form->addSelectField('sitemap_changefreq');
    $field->setLabel('Sitemap Changefreq');
    $field->setNotice('Wie oft ändert sich der Inhalt voraussichtlich?');
    $select = $field->getSelect();
    $select->addOptions(['always' => 'always', 'hourly' => 'hourly', 'daily' => 'daily', 'weekly' => 'weekly', 'monthly' => 'monthly', 'yearly' => 'yearly', 'never' => 'never']);

    $field = $form->addSelectField('sitemap_priority');
    $field->setLabel('Sitemap Priority');
    $field->setNotice('Priorität der URLs im Vergleich zu anderen URLs auf der Website (0.0 bis 1.0).');
    $select = $field->getSelect();
    $select->addOptions(['1.0' => '1.0', '0.9' => '0.9', '0.8' => '0.8', '0.7' => '0.7', '0.6' => '0.6', '0.5' => '0.5', '0.4' => '0.4', '0.3' => '0.3', '0.2' => '0.2', '0.1' => '0.1', '0.0' => '0.0']);

    // SEO Felder
    $form->addFieldset('SEO Einstellungen');

    $field = $form->addSelectField('seo_title_field');
    $field->setLabel('SEO Title Feld');
    $field->setNotice('Spalte in der YForm-Tabelle für den Meta-Title (z.B. title oder name). Leer lassen für Standard.');
    $field->setAttribute('class', 'form-control virtual-urls-column-select');
    $field->setAttribute('data-selected', $func === 'edit' ? $form->getSql()->getValue('seo_title_field') : '');
    $select = $field->getSelect();
    $select->addOption('Bitte Tabelle wählen...', '');

    $field = $form->addSelectField('seo_description_field');
    $field->setLabel('SEO Description Feld');
    $field->setNotice('Spalte in der YForm-Tabelle für die Meta-Description (z.B. description oder text). HTML wird entfernt und Text gekürzt.');
    $field->setAttribute('class', 'form-control virtual-urls-column-select');
    $field->setAttribute('data-selected', $func === 'edit' ? $form->getSql()->getValue('seo_description_field') : '');
    $select = $field->getSelect();
    $select->addOption('Bitte Tabelle wählen...', '');

    $field = $form->addSelectField('seo_image_field');
    $field->setLabel('SEO Image Feld');
    $field->setNotice('Spalte in der YForm-Tabelle für das Meta-Image (z.B. image oder teaser_image).');
    $field->setAttribute('class', 'form-control virtual-urls-column-select');
    $field->setAttribute('data-selected', $func === 'edit' ? $form->getSql()->getValue('seo_image_field') : '');
    $select = $field->getSelect();
    $select->addOption('Bitte Tabelle wählen...', '');

    $content = $form->get();

    // JavaScript für Ajax-Spalten-Laden
    $ajaxUrl = rex_url::backendPage('virtual_urls/ajax.columns');
    $script = <<<JS
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tableInput = document.getElementById('virtual-urls-table-name');
    const relationTableInput = document.getElementById('virtual-urls-relation-table');
    const columnSelects = document.querySelectorAll('.virtual-urls-column-select');
    const relationColumnSelects = document.querySelectorAll('.virtual-urls-relation-column-select');

    function loadColumns(tableName, selects) {
        if (!tableName) {
            selects.forEach(select => {
                select.innerHTML = '<option value="">Bitte Tabelle wählen...</option>';
            });
            return;
        }

        fetch('{$ajaxUrl}&table=' + encodeURIComponent(tableName))
            .then(response => response.json())
            .then(columns => {
                selects.forEach(select => {
                    const selectedValue = select.getAttribute('data-selected');
                    let options = '<option value="">- Bitte wählen -</option>';
                    columns.forEach(col => {
                        const isSelected = (col === selectedValue) ? ' selected' : '';
                        options += '<option value="' + col + '"' + isSelected + '>' + col + '</option>';
                    });
                    select.innerHTML = options;
                });
            });
    }

    if (tableInput) {
        tableInput.addEventListener('change', () => loadColumns(tableInput.value, columnSelects));
        // Initial load
        if (tableInput.value) {
            loadColumns(tableInput.value, columnSelects);
        }
    }

    if (relationTableInput) {
        relationTableInput.addEventListener('change', () => loadColumns(relationTableInput.value, relationColumnSelects));
        // Initial load
        if (relationTableInput.value) {
            loadColumns(relationTableInput.value, relationColumnSelects);
        }
    }
});
</script>
JS;

    $fragment = new rex_fragment();
    $fragment->setVar('class', 'edit', false);
    $fragment->setVar('title', ($func === 'edit') ? 'Profil bearbeiten' : 'Profil erstellen', false);
    $fragment->setVar('body', $content . $script, false);
    echo $fragment->parse('core/page/section.php');
} else {
    // Liste
    $list = rex_list::factory('SELECT * FROM ' . rex::getTable('virtual_urls_profiles'));

    $list->addTableAttribute('class', 'table-striped');

    $list->setColumnLabel('id', 'ID');
    $list->setColumnLabel('status', 'Status');
    $list->setColumnLabel('clang_id', 'Sprache');
    $list->setColumnLabel('domain', 'Domain');
    $list->setColumnLabel('table_name', 'Tabelle');
    $list->setColumnLabel('trigger_segment', 'URL Trigger');

    // Unnötige Spalten ausblenden
    $list->removeColumn('url_field');
    $list->removeColumn('article_id');
    $list->removeColumn('relation_field');
    $list->removeColumn('relation_table');
    $list->removeColumn('relation_slug_field');
    $list->removeColumn('default_category_id');
    $list->removeColumn('sitemap_filter');
    $list->removeColumn('sitemap_changefreq');
    $list->removeColumn('sitemap_priority');
    $list->removeColumn('seo_title_field');
    $list->removeColumn('seo_description_field');
    $list->removeColumn('seo_image_field');

    $list->setColumnFormat('status', 'custom', static function ($params) {
        $status = (int) $params['list']->getValue('status');
        $id = (int) $params['list']->getValue('id');
        if ($status === 1) {
            return '<a href="' . $params['list']->getUrl(['func' => 'status', 'id' => $id, 'status' => 0]) . '"><span class="rex-online"><i class="rex-icon rex-icon-active-true"></i> Online</span></a>';
        }
        return '<a href="' . $params['list']->getUrl(['func' => 'status', 'id' => $id, 'status' => 1]) . '"><span class="rex-offline"><i class="rex-icon rex-icon-active-false"></i> Offline</span></a>';
    });

    $list->setColumnFormat('clang_id', 'custom', static function ($params) {
        $value = (int) $params['list']->getValue('clang_id');
        if ($value === -1) {
            return '<em>Alle Sprachen</em>';
        }
        $clang = rex_clang::get($value);
        return $clang ? $clang->getName() : 'Unbekannt';
    });

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
