<?php

use FriendsOfRedaxo\VirtualUrl\VirtualUrlsHelper;

$package = rex_addon::get('virtual_urls');

// URL-Tester
$testResult = null;
$testUrl = rex_request('test_url', 'string', '');
$testDomain = rex_request('test_domain', 'string', '');

if ($testUrl !== '') {
    $testResult = VirtualUrlsHelper::testUrl($testUrl, $testDomain !== '' ? $testDomain : null);
}

// --- URL-Tester Formular ---
$formContent = '';

$formContent .= '<form method="get" action="' . rex_url::currentBackendPage() . '">';
$formContent .= rex_url::currentBackendPage() !== '' ? '<input type="hidden" name="page" value="' . rex_request('page', 'string') . '">' : '';

$formContent .= '<div class="row">';

// Domain-Auswahl
$formContent .= '<div class="col-md-3">';
$formContent .= '<div class="form-group">';
$formContent .= '<label for="test_domain">Domain</label>';
$formContent .= '<select name="test_domain" id="test_domain" class="form-control">';
$formContent .= '<option value="">Alle Domains</option>';
if (rex_addon::get('yrewrite')->isAvailable()) {
    foreach (rex_yrewrite::getDomains() as $domain) {
        $name = $domain->getName();
        if ($name !== 'default') {
            $selected = $testDomain === $name ? ' selected' : '';
            $formContent .= '<option value="' . rex_escape($name) . '"' . $selected . '>' . rex_escape($name) . '</option>';
        }
    }
}
$formContent .= '</select>';
$formContent .= '</div>';
$formContent .= '</div>';

// URL-Eingabe
$formContent .= '<div class="col-md-7">';
$formContent .= '<div class="form-group">';
$formContent .= '<label for="test_url">URL zum Testen</label>';
$formContent .= '<input type="text" name="test_url" id="test_url" class="form-control" value="' . rex_escape($testUrl) . '" placeholder="/news/mein-artikel oder /news/sport/mein-artikel">';
$formContent .= '</div>';
$formContent .= '</div>';

// Submit
$formContent .= '<div class="col-md-2">';
$formContent .= '<div class="form-group">';
$formContent .= '<label>&nbsp;</label>';
$formContent .= '<button type="submit" class="btn btn-primary btn-block">Testen</button>';
$formContent .= '</div>';
$formContent .= '</div>';

$formContent .= '</div>';
$formContent .= '</form>';

// Ergebnis
if ($testResult !== null) {
    if ($testResult['resolved']) {
        $formContent .= '<div class="alert alert-success">';
        $formContent .= '<strong><i class="rex-icon fa-check"></i> Aufgelöst!</strong><br>';
        $formContent .= rex_escape($testResult['message']);

        if ($testResult['dataset'] !== null) {
            $formContent .= '<br><br><strong>Dataset-Daten:</strong>';
            $formContent .= '<table class="table table-condensed" style="margin-top:5px; background:rgba(255,255,255,0.5);">';
            $formContent .= '<tr><td><strong>Tabelle</strong></td><td>' . rex_escape($testResult['profile']['table_name']) . '</td></tr>';
            $formContent .= '<tr><td><strong>Datensatz-ID</strong></td><td>' . $testResult['dataset']->getId() . '</td></tr>';
            $formContent .= '<tr><td><strong>Renderer Artikel</strong></td><td>' . $testResult['article_id'] . '</td></tr>';
            if ($testResult['relation_id'] !== null) {
                $formContent .= '<tr><td><strong>Relation-ID</strong></td><td>' . $testResult['relation_id'] . '</td></tr>';
            }
            $formContent .= '</table>';
        }
        $formContent .= '</div>';
    } else {
        $formContent .= '<div class="alert alert-danger">';
        $formContent .= '<strong><i class="rex-icon fa-times"></i> Nicht aufgelöst</strong><br>';
        $formContent .= rex_escape($testResult['message']);
        if ($testResult['profile'] !== null) {
            $formContent .= '<br><small>Passendes Profil: ' . rex_escape($testResult['profile']['trigger_segment']) . ' (' . rex_escape($testResult['profile']['table_name']) . ')</small>';
        }
        $formContent .= '</div>';
    }
}

$fragment = new rex_fragment();
$fragment->setVar('class', 'info', false);
$fragment->setVar('title', 'URL-Tester', false);
$fragment->setVar('body', $formContent, false);
echo $fragment->parse('core/page/section.php');

// --- Alle registrierten URLs ---
$profiles = VirtualUrlsHelper::getAllProfiles();

if (count($profiles) > 0) {
    foreach ($profiles as $profile) {
        $hasRelation = trim($profile['relation_field'] ?? '') !== ''
            && trim($profile['relation_table'] ?? '') !== ''
            && trim($profile['relation_slug_field'] ?? '') !== '';

        $urlPattern = '/<strong>' . rex_escape($profile['trigger_segment']) . '</strong>/';
        if ($hasRelation) {
            $urlPattern .= '<em>&lt;' . rex_escape($profile['relation_slug_field']) . '&gt;</em>/';
        }
        $urlPattern .= '<em>&lt;' . rex_escape($profile['url_field']) . '&gt;</em>';

        $urlList = VirtualUrlsHelper::getUrlList($profile['table_name'], '', '', -1);

        $content = '<p><strong>URL-Schema:</strong> ' . $urlPattern . '</p>';
        $content .= '<p><strong>Domain:</strong> ' . ($profile['domain'] !== '' ? rex_escape($profile['domain']) : '<em>Alle</em>') . ' &middot; ';
        $content .= '<strong>Renderer:</strong> Artikel ' . (int) $profile['article_id'] . '</p>';

        if (count($urlList) > 0) {
            $content .= '<table class="table table-hover table-condensed">';
            $content .= '<thead><tr><th>ID</th><th>Slug</th><th>URL</th></tr></thead>';
            $content .= '<tbody>';

            $maxItems = min(count($urlList), 50);
            for ($i = 0; $i < $maxItems; $i++) {
                $item = $urlList[$i];
                $content .= '<tr>';
                $content .= '<td>' . $item['id'] . '</td>';
                $content .= '<td><code>' . rex_escape($item['slug']) . '</code></td>';
                $content .= '<td><a href="' . rex_escape($item['url']) . '" target="_blank">' . rex_escape($item['url']) . '</a></td>';
                $content .= '</tr>';
            }
            $content .= '</tbody></table>';

            if (count($urlList) > 50) {
                $content .= '<p class="text-muted">... und ' . (count($urlList) - 50) . ' weitere URLs</p>';
            }
        } else {
            $content .= '<p class="text-muted">Keine URLs für dieses Profil gefunden.</p>';
        }

        $fragment = new rex_fragment();
        $fragment->setVar('class', 'default', false);
        $fragment->setVar('title', rex_escape($profile['table_name']) . ' – ' . rex_escape($profile['trigger_segment']), false);
        $fragment->setVar('body', $content, false);
        $fragment->setVar('collapse', true, false);
        echo $fragment->parse('core/page/section.php');
    }
} else {
    echo rex_view::info('Noch keine Profile angelegt. <a href="' . rex_url::backendPage('virtual_urls/profiles', ['func' => 'add']) . '">Jetzt Profil erstellen</a>.');
}
