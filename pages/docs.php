<?php

$content = '
<h3>URL erzeugen</h3>
<p>Gibt die vollständige URL für einen Datensatz zurück:</p>
<pre><code>$url = VirtualUrlsHelper::getUrl(\'rex_news\', 42);
// z.B. "/news/mein-artikel" oder "/news/sport/mein-artikel"</code></pre>

<h3>URL aus Dataset-Objekt</h3>
<p>Wenn du bereits ein YForm-Dataset hast:</p>
<pre><code>$dataset = rex_yform_manager_dataset::get(42, \'rex_news\');
$url = VirtualUrlsHelper::getUrlByDataset($dataset);
</code></pre>

<h3>HTML-Link erzeugen</h3>
<pre><code>// Einfacher Link
$link = VirtualUrlsHelper::getLink(\'rex_news\', 42, \'Zum Artikel\');
// &lt;a href="/news/mein-artikel"&gt;Zum Artikel&lt;/a&gt;

// Mit CSS-Klassen
$link = VirtualUrlsHelper::getLink(\'rex_news\', 42, \'Mehr lesen\', [\'class\' => \'btn btn-primary\']);
// &lt;a href="/news/mein-artikel" class="btn btn-primary"&gt;Mehr lesen&lt;/a&gt;
</code></pre>

<h3>URL-Liste abrufen</h3>
<p>Alle URLs einer Tabelle holen (z.B. für Übersichtsseiten):</p>
<pre><code>// Alle aktiven News
$urls = VirtualUrlsHelper::getUrlList(\'rex_news\', \'status = 1\', \'date DESC\');

foreach ($urls as $item) {
    echo \'&lt;li&gt;&lt;a href="\' . $item[\'url\'] . \'"&gt;\' . $item[\'dataset\']->getValue(\'title\') . \'&lt;/a&gt;&lt;/li&gt;\';
    // $item[\'id\']      - Datensatz-ID
    // $item[\'url\']     - Vollständige URL
    // $item[\'slug\']    - Nur der Slug-Teil
    // $item[\'dataset\'] - rex_yform_manager_dataset Objekt
}</code></pre>

<h3>Aktuellen Datensatz im Modul abrufen</h3>
<p>Im Renderer-Artikel (Modul-Output):</p>
<pre><code>$data = VirtualUrls::getCurrentData();
$profile = VirtualUrls::getCurrentProfile();

if ($data) {
    echo $data->getValue(\'title\');
    echo $data->getValue(\'text\');
}
</code></pre>

<h3>URL testen (programmatisch)</h3>
<pre><code>$result = VirtualUrlsHelper::testUrl(\'/news/sport/mein-artikel\', \'wdfv.de\');

if ($result[\'resolved\']) {
    echo \'Datensatz ID: \' . $result[\'dataset\']->getId();
    echo \'Artikel: \' . $result[\'article_id\'];
} else {
    echo \'Fehler: \' . $result[\'message\'];
}</code></pre>

<hr>

<h3>URL-Schema</h3>
<table class="table table-bordered">
<thead>
<tr><th>Typ</th><th>Schema</th><th>Beispiel</th></tr>
</thead>
<tbody>
<tr>
    <td>Ohne Relation</td>
    <td><code>/&lt;artikel-pfad&gt;/&lt;trigger&gt;/&lt;slug&gt;</code></td>
    <td><code>/spielberechtigungen/xnews/mein-artikel</code></td>
</tr>
<tr>
    <td>Mit Relation</td>
    <td><code>/&lt;artikel-pfad&gt;/&lt;trigger&gt;/&lt;relation-slug&gt;/&lt;slug&gt;</code></td>
    <td><code>/spielberechtigungen/xnews/sport/mein-artikel</code></td>
</tr>
</tbody>
</table>

<h3>Profil-Konfiguration</h3>
<table class="table table-bordered">
<thead>
<tr><th>Feld</th><th>Pflicht</th><th>Beschreibung</th></tr>
</thead>
<tbody>
<tr><td><strong>Domain</strong></td><td>Nein</td><td>Optional: auf eine Domain beschränken</td></tr>
<tr><td><strong>YForm Tabelle</strong></td><td>Ja</td><td>Tabellenname, z.B. <code>rex_news</code></td></tr>
<tr><td><strong>URL Trigger Segment</strong></td><td>Ja</td><td>Pfad-Segment, das die virtuelle URL einleitet, z.B. <code>news</code></td></tr>
<tr><td><strong>Slug Feld</strong></td><td>Ja</td><td>Feld in der Tabelle mit dem URL-Slug, z.B. <code>url</code> oder <code>code</code></td></tr>
<tr><td><strong>Renderer Artikel</strong></td><td>Ja</td><td>REDAXO-Artikel, der den Datensatz rendert</td></tr>
<tr><td><strong>Standard Kategorie</strong></td><td>Nein</td><td>Kategorie für Sitemap-URLs</td></tr>
<tr><td><strong>Relation Feld</strong></td><td>Nein</td><td>Feld in der Datentabelle (z.B. <code>category_id</code>)</td></tr>
<tr><td><strong>Relation Tabelle</strong></td><td>Nein</td><td>Tabelle der Relation (z.B. <code>rex_news_category</code>)</td></tr>
<tr><td><strong>Relation Slug Feld</strong></td><td>Nein</td><td>Feld für den URL-Teil (z.B. <code>name</code>), wird automatisch normalisiert</td></tr>
<tr><td><strong>Sitemap Filter</strong></td><td>Nein</td><td>SQL WHERE-Klausel, Platzhalter: <code>###NOW###</code>, <code>###CURRENT_DATE###</code></td></tr>
</tbody>
</table>

<h3>YForm Value: virtual_url_slug</h3>
<p>Für automatische Slug-Generierung kann der YForm Value-Typ <code>virtual_url_slug</code> verwendet werden. Er erzeugt automatisch einen normalisierten URL-Slug aus einem Quellfeld (z.B. Titel).</p>
';

$fragment = new rex_fragment();
$fragment->setVar('class', 'default', false);
$fragment->setVar('title', 'API-Referenz & Hilfe', false);
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');
