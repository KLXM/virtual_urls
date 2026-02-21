# Virtual URLs AddOn

Dieses AddOn ermöglicht es, YForm-Datensätze (z.B. News, Produkte, Mitarbeiter) als virtuelle Unterseiten in die bestehende Struktur-Hierarchie einzuhängen.

Es löst das Problem, dass man für jeden Datensatz einen eigenen REDAXO-Artikel anlegen müsste.

**Features:**
*   🚀 **Dynamisches Routing:** URLs wie `/news/mein-artikel` ohne echte Artikel.
*   🗺 **Sitemap Integration:** Automatische Aufnahme aller Datensätze in die `sitemap.xml` (via YRewrite).
*   🧭 **Smart Navigation:** Der aktive Menüpunkt bleibt erhalten (Mount Point Detection).
*   ⚡️ **Auto-Caching:** Bei Änderungen an Datensätzen wird der Cache sofort aktualisiert.
*   🐌 **Slug-Generator:** YForm-Feldtyp zum automatischen Erstellen von URL-Slugs.

## Konzept

Anstatt starre Routen zu definieren, arbeitet *Virtual URLs* mit **Profilen**:

1.  **Trigger:** Ein URL-Segment (z.B. `/news/`), das signalisiert: Hier beginnt ein virtueller Bereich.
2.  **Mounting:** Das AddOn prüft, ob der folgende URL-Teil (Slug) in einer YForm-Tabelle existiert.
3.  **Rendering:** Ist der Datensatz gefunden, wird technisch ein definierter "Renderer-Artikel" geladen (z.B. dein News-Detail-Modul), aber der URL-Pfad bleibt erhalten.

## Navigation & Active State (Hybrid Mode)

Ein besonderes Feature ist die intelligente Erkennung des Navigations-Kontextes ("Mount Point").

Das AddOn versucht, den Pfad *vor* dem virtuellen Teil auf einen echten REDAXO-Artikel aufzulösen.
Beispiel: URL ist `/unternehmen/aktuelles/news/mein-artikel`

1.  Trigger ist `news`.
2.  Das System prüft, ob für `/unternehmen/aktuelles` ein Artikel existiert.
3.  **Falls ja:** Wird dieser Artikel als aktiver Menüpunkt markiert (`article_id`). Dein Menü bleibt also aufgeklappt und aktiv!
4.  **Falls nein:** Wird der im Profil definierte "Renderer Artikel" als Fallback genutzt.

Dies ermöglicht es, virtuelle Datensätze nahtlos in die Navigationsstruktur zu integrieren, ohne physische Unterartikel anlegen zu müssen.

## Einrichtung

Unter **Virtual URLs** im Backend kannst du Profile anlegen.

### Die Felder erklärt

*   **YForm Tabelle**
    *   Der Name der Datenbank-Tabelle, in der die Daten liegen.
    *   Beispiel: `rex_news` oder `rex_product`

*   **URL Trigger Segment**
    *   Der URL-Teil, der die virtuelle URL einleitet.
    *   Beispiel `news` → reagiert auf `deine-domain.de/kategorie/news/mein-artikel`
    *   Besonderheit: Dieser Trigger kann **hinter jeder beliebigen Kategorie** stehen.

*   **Slug Feld Name**
    *   Das Feld in der YForm-Tabelle, das den URL-Namen (**bereits normalisiert**) enthält.
    *   Das AddOn vergleicht diesen Wert mit der URL. 
    *   *Tipp:* Nutze in YForm den Feldtyp `virtual_url_slug` (dieses AddOn) oder `generate_key`.
    *   Beispiel: `url` (enthält `mein-artikel`), `slug`.

### Slug-Feld in YForm einrichten

Damit die URLs automatisch beim Speichern eines Datensatzes generiert werden, bietet dieses AddOn einen speziellen Feld-Typ:

1.  Gehe in die Felder-Verwaltung deiner Tabelle (z.B. `rex_news`).
2.  Füge ein Feld vom Typ `virtual_url_slug` hinzu.
3.  **Konfiguration:**
    *   **Name:** `url` (oder `slug`)
    *   **Quell-Feld:** `title` (oder wie dein Titel-Feld heißt). Daraus wird der Slug generiert.
    *   **Sichtbarkeit:**
        *   `visible` (Standard): Redakteur kann den Slug sehen und manuell ändern.
        *   `readonly`: Slug wird angezeigt, kann aber nicht bearbeitet werden.
        *   `hidden`: Slug wird im Hintegrund generiert und nicht angezeigt.

Ab jetzt wird beim Anlegen einer News ("Mein Artikel") automatisch der Slug `mein-artikel` generiert und gespeichert. Änderungen am Titel aktualisieren den Slug nur, wenn dieser leer ist (um Link-Breaks zu vermeiden).

### Automatisches Caching

Das AddOn überwacht Änderungen an den konfigurierten YForm-Tabellen (`YFORM_DATA_ADDED`, `UPDATED`, `DELETED`).
Sobald ein Datensatz geändert wird, wird automatisch der **YRewrite Cache** und damit die Sitemap invalidiert. Neue URLs sind somit sofort erreichbar und in der `sitemap.xml` vorhanden.

*   **Renderer Artikel**
    *   Der REDAXO-Artikel, der technisch geladen wird, wenn ein Treffer gefunden wurde.
    *   Dieser Artikel sollte ein Modul enthalten, das die Daten ausgibt.
    *   Wichtig: Dies ist **nicht** der Artikel, den der User in der URL sieht, sondern nur der "Motor", der den Inhalt generiert.

*   **Standard Kategorie für Sitemap**
    *   Der "Haupt-Ort" deiner Datensätze für SEO.
    *   Nur unter dieser Kategorie (gefolgt vom Trigger und Slug) werden die URLs in der `sitemap.xml` ausgegeben.
    *   Dies verhindert Duplicate Content, da kontextuelle URLs (z.B. `/produkte/news/...`) ignoriert werden.

*   **Sitemap Filter (SQL Where)**
    *   Ein SQL-Fragment, um zu steuern, welche Datensätze in die Sitemap aufgenommen werden.
    *   Du kannst dynamische Platzhalter für Datum/Zeit nutzen.
    *   Beispiele:
        *   `status = 1`
        *   `status = 1 AND online_date <= "###NOW###"` (Kleiner/Gleich Jetzt)
        *   `online_date >= "###NOW -1 YEAR###"` (Nur News aus dem letzten Jahr)
        *   Verfügbare Platzhalter: `###NOW###`, `###CURRENT_DATE###`, `###CURRENT_TIMESTAMP###`.
        *   Unterstützt relative Angaben wie `+1 DAY`, `-2 WEEKS`, `+30 MINUTES` (gemäß PHP `strtotime`).

*   **Relation/Mount Feld (Optional)**
    *   Hier kann das Feld angegeben werden, das die Kategorie-Zugehörigkeit regelt (z.B. `category_id`).
    *   *Geplantes Feature:* Damit wird später sichergestellt, dass die News nur unter der korrekten Kategorie erreichbar ist, oder die Navigation korrekt auf "Aktiv" gesetzt wird.

## Verwendung im Modul

In deinem Modul (das im Renderer-Artikel eingebunden ist), kannst du auf die Daten zugreifen. Das Objekt ist ein vollständiges `rex_yform_manager_dataset`.

```php
// Prüfen, ob wir im virtuellen Kontext sind
$news = VirtualUrls::getCurrentData();

if ($news) {
    // Zugriff auf den gefundenen Datensatz (YOrm)
    echo '<h1>' . htmlspecialchars($news->getValue('title')) . '</h1>';
    
    // Beispiel für Relation (falls vorhanden)
    // echo $news->getRelatedDataset('category_id')->getName();
    
    echo '<div class="content">' . $news->getValue('description') . '</div>';
} else {
    echo "Kein Datensatz gefunden.";
}
```

## System-Integration

Das AddOn klinkt sich über den Extension Point `PACKAGES_INCLUDED` ein und prüft die aktuelle URL. Findet es einen validen Slug in der konfigurierten Tabelle, manipuliert es die globale `rex::$article_id`, noch bevor REDAXO die Seite rendert.
