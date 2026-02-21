# Virtual URLs AddOn

Dieses AddOn ermöglicht es, YForm-Datensätze (z.B. News, Produkte, Mitarbeiter) als virtuelle Unterseiten in die bestehende Struktur-Hierarchie einzuhängen — ohne für jeden Datensatz einen eigenen REDAXO-Artikel anlegen zu müssen.

## Features

- 🚀 **Dynamisches Routing:** URLs wie `/news/mein-artikel` ohne echte Artikel
- 🗺️ **Sitemap Integration:** Automatische Aufnahme in die `sitemap.xml` (via YRewrite)
- 🧭 **Smart Navigation:** Der aktive Menüpunkt bleibt erhalten (Mount Point Detection)
- ⚡ **Auto-Caching:** Bei Änderungen an Datensätzen wird der Cache sofort aktualisiert
- 🐌 **Slug-Generator:** YForm-Feldtyp + Bulk-Generator für bestehende Datensätze
- 🔗 **Relation-URLs:** Optionale Kategorie-Segmente in der URL (`/news/sport/mein-artikel`)
- 🌐 **Mehrsprachigkeit:** Pro Sprache eigene Profile mit unterschiedlichen Triggern und Slug-Feldern
- 🏢 **Multi-Domain:** Profile können auf einzelne Domains beschränkt werden
- 🧪 **URL-Tester:** Backend-Tool zum Testen und Debuggen von URLs
- 📖 **Helper-Klasse:** API zum Erzeugen von URLs und Links in Modulen/Templates

## Konzept

*Virtual URLs* arbeitet mit **Profilen**:

1. **Trigger:** Ein URL-Segment (z.B. `news`), das signalisiert: Hier beginnt ein virtueller Bereich
2. **Matching:** Das AddOn prüft, ob der folgende Slug in der konfigurierten YForm-Tabelle existiert
3. **Rendering:** Ist der Datensatz gefunden, wird der definierte „Renderer-Artikel" geladen, aber der URL-Pfad bleibt erhalten

### URL-Schemas

| Typ | Schema | Beispiel |
|---|---|---|
| Ohne Relation | `/<pfad>/<trigger>/<slug>` | `/spielberechtigungen/xnews/mein-artikel` |
| Mit Relation | `/<pfad>/<trigger>/<relation-slug>/<slug>` | `/spielberechtigungen/xnews/sport/mein-artikel` |

## Einrichtung

### 1. Profil anlegen

Unter **Virtual URLs → Profile** ein neues Profil erstellen:

| Feld | Pflicht | Beschreibung |
|---|---|---|
| **Sprache** | Nein | Sprache für dieses Profil. „Alle Sprachen" = sprachunabhängig |
| **Domain** | Nein | Auf eine Domain beschränken. „Alle Domains" = überall aktiv |
| **YForm Tabelle** | Ja | Name der Datentabelle, z.B. `rex_news` |
| **URL Trigger Segment** | Ja | Segment, das die virtuelle URL einleitet, z.B. `news` |
| **Slug Feld Name** | Ja | Feld mit dem normalisierten URL-Slug, z.B. `url` oder `code` |
| **Renderer Artikel** | Ja | REDAXO-Artikel, der den Datensatz rendert |
| **Standard Kategorie** | Nein | Basis-Kategorie für Sitemap-URLs |
| **Relation Feld** | Nein | Feld in der Datentabelle (z.B. `category_id`) |
| **Relation Tabelle** | Nein | Tabelle der Relation (z.B. `rex_news_category`) |
| **Relation Slug Feld** | Nein | Feld für den URL-Teil (z.B. `name`), wird automatisch normalisiert |
| **Sitemap Filter** | Nein | SQL WHERE-Klausel mit optionalen Platzhaltern |

### 2. Slug-Feld einrichten

#### Option A: YForm-Feldtyp `virtual_url_slug`

1. In der YForm-Feldverwaltung ein Feld vom Typ `virtual_url_slug` anlegen
2. **Name:** `url` (oder `slug`)
3. **Quell-Feld:** `title` (oder das Feld, aus dem der Slug erzeugt wird)
4. **Sichtbarkeit:** `visible` / `readonly` / `hidden`

Der Slug wird beim Anlegen automatisch aus dem Quellfeld generiert. Bestehende Slugs werden beim Bearbeiten nicht überschrieben.

#### Option B: Slug-Generator für bestehende Daten

Unter **Virtual URLs → Slug-Generator**:

1. YForm-Tabelle wählen
2. Quellfeld wählen (z.B. `title`)
3. Zielfeld wählen (z.B. `url`)
4. Modus: „Nur leere Felder füllen" oder „Alle überschreiben"
5. Vorschau prüfen und generieren

Duplikate werden automatisch mit Suffix (`-1`, `-2`, …) versehen.

### 3. Mehrsprachigkeit

Für mehrsprachige Seiten pro Sprache ein eigenes Profil anlegen:

| Sprache | Trigger | Slug-Feld | Renderer |
|---|---|---|---|
| Deutsch | `nachrichten` | `slug_de` | Artikel 10 (DE) |
| Englisch | `news` | `slug_en` | Artikel 10 (EN) |

Das Routing filtert automatisch nach der aktuellen Sprache. Der Helper nutzt immer das sprachspezifische Profil und fällt auf „Alle Sprachen" zurück.

### 4. Relation-URLs

Für hierarchische URLs (z.B. `/news/sport/mein-artikel`):

1. In der Datentabelle braucht es ein Relation-Feld (z.B. `category_id`)
2. Die Relationstabelle (z.B. `rex_news_category`) muss ein Feld haben, das als URL-Segment dient (z.B. `name`)
3. Im Profil alle drei Relation-Felder ausfüllen

Die Relation wird automatisch normalisiert: „Sport & Fitness" → `sport-fitness`.

## Verwendung im Modul

### Datensatz im Renderer-Artikel abrufen

```php
$data = VirtualUrls::getCurrentData();
$profile = VirtualUrls::getCurrentProfile();

if ($data) {
    echo '<h1>' . rex_escape($data->getValue('title')) . '</h1>';
    echo '<div>' . $data->getValue('text') . '</div>';
} else {
    echo 'Kein Datensatz gefunden.';
}
```

### URLs und Links erzeugen

```php
// URL für einen Datensatz
$url = VirtualUrlsHelper::getUrl('rex_news', 42);
// → "/news/mein-artikel" oder "/news/sport/mein-artikel"

// URL für eine bestimmte Sprache
$url = VirtualUrlsHelper::getUrl('rex_news', 42, 2); // clang=2

// URL aus bestehendem Dataset
$dataset = rex_yform_manager_dataset::get(42, 'rex_news');
$url = VirtualUrlsHelper::getUrlByDataset($dataset);

// HTML-Link erzeugen
$link = VirtualUrlsHelper::getLink('rex_news', 42, 'Zum Artikel');
// → <a href="/news/mein-artikel">Zum Artikel</a>

// Link mit CSS-Klassen
$link = VirtualUrlsHelper::getLink('rex_news', 42, 'Mehr', ['class' => 'btn btn-primary']);

// Alle URLs einer Tabelle (z.B. für Übersichtsseiten)
$urls = VirtualUrlsHelper::getUrlList('rex_news', 'status = 1', 'date DESC');
foreach ($urls as $item) {
    echo '<li><a href="' . $item['url'] . '">' . $item['dataset']->getValue('title') . '</a></li>';
    // $item['id'], $item['url'], $item['slug'], $item['dataset']
}
```

### URL programmatisch testen

```php
$result = VirtualUrlsHelper::testUrl('/news/sport/mein-artikel', 'wdfv.de');

if ($result['resolved']) {
    echo 'Datensatz ID: ' . $result['dataset']->getId();
    echo 'Artikel: ' . $result['article_id'];
} else {
    echo 'Fehler: ' . $result['message'];
}
```

## Backend-Seiten

| Tab | Beschreibung |
|---|---|
| **Profile** | Profilverwaltung (Erstellen, Bearbeiten, Löschen) |
| **URLs & Tester** | Übersicht aller generierten URLs + interaktiver URL-Tester |
| **Slug-Generator** | Bulk-Generierung von Slugs für bestehende YForm-Tabellen |
| **Hilfe** | API-Referenz mit Code-Beispielen |

## Navigation & Active State

Das AddOn erkennt intelligent den Navigations-Kontext:

URL: `/unternehmen/aktuelles/news/mein-artikel`

1. Trigger ist `news`
2. Das System prüft, ob `/unternehmen/aktuelles` einem echten Artikel entspricht
3. **Falls ja:** Dieser Artikel wird als aktiver Menüpunkt markiert → Menü bleibt aufgeklappt
4. **Falls nein:** Der im Profil definierte Renderer-Artikel wird verwendet

## Sitemap

Datensätze werden automatisch in die `sitemap.xml` aufgenommen wenn:

- Eine **Standard Kategorie** im Profil definiert ist
- Der optionale **Sitemap Filter** den Datensatz einschließt

### Platzhalter im Sitemap-Filter

| Platzhalter | Beschreibung |
|---|---|
| `###NOW###` | Aktuelles Datum + Uhrzeit (`Y-m-d H:i:s`) |
| `###CURRENT_DATE###` | Aktuelles Datum (`Y-m-d`) |
| `###CURRENT_TIMESTAMP###` | Unix Timestamp |

Relative Angaben: `###NOW -1 YEAR###`, `###NOW +30 MINUTES###`, `###CURRENT_DATE -2 WEEKS###` (gemäß PHP `strtotime`).

**Beispiele:**
```
status = 1
status = 1 AND online_date <= "###NOW###"
online_date >= "###NOW -1 YEAR###"
```

## Caching

Das AddOn überwacht `YFORM_DATA_ADDED`, `YFORM_DATA_UPDATED` und `YFORM_DATA_DELETED`. Bei Änderungen an konfigurierten Tabellen wird der YRewrite-Cache automatisch invalidiert.

## System-Integration

- Extension Point `YREWRITE_PREPARE` für URL-Auflösung
- Extension Point `YREWRITE_DOMAIN_SITEMAP` für Sitemap-Einträge
- Benötigt: YRewrite ≥ 2.0, YForm ≥ 4.0, REDAXO ≥ 5.10

## API-Referenz

### `VirtualUrls` (Routing)

| Methode | Beschreibung |
|---|---|
| `getCurrentData(): ?rex_yform_manager_dataset` | Aktueller Datensatz im Renderer |
| `getCurrentProfile(): ?array` | Aktuelles Profil im Renderer |

### `VirtualUrlsHelper` (URL-Erzeugung)

| Methode | Beschreibung |
|---|---|
| `getUrl(string $table, int $id, int $clang = -1): ?string` | URL für einen Datensatz |
| `getUrlByDataset(rex_yform_manager_dataset $d, int $clang = -1): ?string` | URL aus Dataset |
| `getLink(string $table, int $id, string $label, array $attrs, int $clang): string` | HTML-Link |
| `getUrlList(string $table, string $where, string $order, int $clang): array` | Alle URLs einer Tabelle |
| `testUrl(string $url, ?string $domain): array` | URL testen |
| `getProfileByTable(string $table, int $clang = -1): ?array` | Profil für Tabelle+Sprache |
| `getAllProfiles(): array` | Alle Profile |
| `clearCache(): void` | Profil-Cache leeren |

## Autor

**Friends Of REDAXO**

* http://www.redaxo.org
* https://github.com/FriendsOfREDAXO

## Credits

**Projektleitung**

[Thomas Skerbis](https://github.com/skerbis)

## Lizenz

MIT License – siehe [LICENSE](LICENSE)
