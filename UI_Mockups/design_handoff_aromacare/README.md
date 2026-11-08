# Handoff: AromaCare – Naturkosmetik-Bestands- & Wissens-App (PWA)

## Overview
AromaCare verwaltet selbstgemachte Naturkosmetik-Zutaten: ätherische Öle (u. a. doTERRA), Basisöle, Wachse und Additive. Funktionen: Bestandsliste mit Suche/Filter, Barcode-Scan (Handy-Kamera) oder manuelle Erfassung, typspezifische Detailansichten (Wirkung, Anwendung, Rezeptideen bzw. Komedogenität, Einziehgeschwindigkeit, Anwendungsgebiete) und Rezept-Finder.
Mobile-first (PWA), zusätzlich Desktop-Layout. Sprache der UI: Deutsch.

**Ziel-Stack:** Laravel 12, Livewire 3, Alpine.js, Tailwind CSS v4.

## About the Design Files
Die Dateien in `design_reference/` sind **Design-Referenzen in HTML** (Prototypen für Aussehen und Verhalten) – **kein Produktionscode zum Kopieren**. Aufgabe: die Designs im Ziel-Stack (Blade-Komponenten + Livewire + Alpine + Tailwind v4) nachbauen und dessen Konventionen verwenden.
- `AromaCare Mockups.dc.html` – Board mit allen Screens (1a–1j). Öffnen im Browser (benötigt `support.js` im selben Ordner und Internet für Google Fonts).
- `BestandScreen.dc.html` – Bestandsliste (Light + Dark über Prop `dark`).
- `BottomNav.dc.html` – Bottom-Navigation (Prop `active` 0–3, `dark`).
- Die Inline-Styles in den HTML-Dateien sind Mock-Technik; im Projekt bitte Tailwind-Klassen und die Tokens unten verwenden. Fertige Tailwind-/Blade-Snippets stehen in Option **1j** und `screenshots/10-tailwind-code.png`.

## Fidelity
**High-fidelity.** Finale Farben, Typografie, Abstände, Zustände. Pixelgenau mit Tailwind nachbauen. Alle Produktdaten (Namen, Mengen, Wirkungen, Komedogenitätswerte) sind **Beispieldaten** und fachlich zu prüfen.

## Screenshots (`screenshots/`)
01 Design System · 02 Bestand (hell) · 03 Scanner · 04 Detail Ätherisches Öl · 05 Detail Basisöl · 06 Erfassen Ätherisches Öl · 07 Erfassen Basisöl · 08 Bestand (dunkel) · 09 Desktop · 10 Tailwind-Code

## Design Tokens

### Farben (Tailwind-v4 `@theme`)
| Token | Hex | Verwendung |
|---|---|---|
| mist-50 | #FAFBFF | Karten, Oberflächen |
| mist-100 | #F1F4FB | App-Hintergrund |
| mist-300 | #D6DCF0 | Linien, Ränder |
| (Input-Rand) | #BCC5E3 | Formularfelder |
| (Segment-Bg) | #E1E6F5 | Segmented Control |
| sky-100 | #DFF1FA | Tag-/Feldset-Fläche (Psyche, Äth. Öl) |
| lilac-300 | #C1C8E4 | Komedogenität 0–1, Tacho hell |
| sky-300 | #84CEEB | Scanner-Button, Highlights, Komedogenität 2–3, Balken „Mittel“ |
| azure-400 | #5AB9EA | Tacho Mitte |
| cobalt-500 | #5680E9 | Balken „Voll“ |
| cobalt-700 | #3558C2 | Icons, Tacho dunkel |
| navy-900 | #1B2447 | Text, Primärbutton |
| (Text sekundär) | #4E5876 | Muted (6,4:1 auf mist-100) |
| violet-400 | #A98BE0 | Komedogenität 4–5 |
| violet-500 | #8860D0 | Balken „Fast leer“, Akzent |
| violet-700 | #5B3AA6 | Akzent-Text / Abschnitts-Labels |
| night-900 | #11162B | Dark: Hintergrund |
| night-800 | #1A2140 | Dark: Karten/Nav |
| chalk | #E6E9F7 | Dark: Text |
| cobalt-200 | #A9C0F5 | Dark: Akzent/aktive Nav |

Status-Badges (Hintergrund / Text / Balken):
- **Voll:** #DCE5FB / #1E3A8A / #5680E9
- **Mittel:** #D6EEF9 / #14507A / #84CEEB
- **Fast leer:** #DCCFF3 / #3F1F85 / #8860D0
- Dark: Voll rgba(86,128,233,.22)/#A9C0F5; Mittel rgba(132,206,235,.18)/#A8DEF3; Fast leer rgba(136,96,208,.28)/#CDB7F5.

Wirkungs-Tags: **Psyche** bg #DFF1FA / text #1E3A8A · **Körper** bg #E6E0F6 / text #4B2E8F. Dark: bg Akzent @15–20 % Alpha, Text aufgehellt.
Kategorie-Kacheln (bg/fg): Äth. Öl #DFF1FA/#3558C2 · Basisöl #E6E0F6/#4B2E8F · Wachs #DDE6FB/#1E3A8A · Additiv #E3E6F2/#4E5876. Dark: fg-Farbe + 15 % Alpha als Hintergrund.

**Kontrast:** Navy auf mist-100 13,6:1 · Muted 6,4:1 · violet-700 7,3:1 · Navy auf sky-300 8,6:1 · chalk auf night-900 14,6:1. Status/Skalen nie nur über Farbe: immer Text/Zahl.

### Typografie
- Display (`--font-display`): **Newsreader** 500 (Google Fonts) – Titel, Ölnamen, Kachel-Initialen; botanische Namen Newsreader *italic*.
- Sans (`--font-sans`): **DM Sans** 400/500/600/700 – UI & Body.
- Skala: Seitentitel 36 (mobil) / 42 (Desktop), Detail-Titel 34, Formular-Titel 22, Body/Listenname 16/600, Meta 13, Chips/Tags 14, Badges 12/700, Abschnitts-Label 12/700 uppercase, letter-spacing .08em, Farbe violet-700.

### Form & Raum
- 8-px-Raster. Seitenrand mobil 20 px. Tap-Ziele ≥ 44 px (Chips 36–38 px, Inputs 42–46 px, Hauptbutton 54 px).
- Radien: Karten 20 (`--radius-card: 1.25rem`), Inputs 12, Buttons 14–16, Chips/Badges voll rund (Badges 12), Kacheln 12–16, Bottom-Sheet 28 oben.
- Schatten sehr weich: `0 1px 2px rgb(27 36 71/.06), 0 8px 24px rgb(27 36 71/.06)`.
- Dark Mode: Klassenstrategie `@custom-variant dark (&:where(.dark, .dark *))`.

## Screens / Views

### 1. Bestandsliste / Dashboard (mobil, 390 px) – `02`, `08`
**Zweck:** Überblick, Suche, Filter, Niedrigbestand erkennen.
**Layout (vertikal):** Statusbar-Platz 44 → Header (Begrüßung „Guten Abend, Anna“ 14/muted, Titel „Bestand“ Display 36, Avatar-Kreis 44 navy mit Initiale rechts) → Suchfeld (Höhe 48, Radius 14, mist-50, Rand mist-300, Lupe, Platzhalter „Öl, Wirkung oder Barcode suchen“, rechts Filter-Button 32×32) → Kategorie-Chips (horizontal scrollbar, Höhe 38, Gap 8): „Alle 48“ (aktiv: navy-Fläche, heller Text), „Ätherische Öle 27“, „Basisöle 12“, „Wachse 4“, „Additive 5“ → Warnband (Höhe 44, Radius 12, Badge-Farben „Fast leer“): „3 Produkte fast leer“ + „Liste ›“ → Liste → Bottom-Nav (84 px).
**Listenzeile:** Padding 11/0, Gap 14, Trennlinie mist-300. Links Kachel 48×48 (Radius 14, Kategoriefarbe, 2-Buchstaben-Kürzel in Newsreader 20/600). Mitte: Name 16/600, Meta 13/muted (z. B. „doTERRA · 15 ml“), Füllbalken Höhe 4 (Track mist-300, Füllung nach Status). Rechts: Status-Badge (12/700, Padding 4×10) + Menge 13/muted („12 ml“).
**Beispiel:** Lavendel 80 % Voll · Pfefferminze 27 % Mittel · Teebaum 13 % Fast leer · Jojobaöl 85 % Voll · Süßmandelöl 40 % Mittel · Bienenwachs 62 % Voll.
**Status-Schwellen (Vorschlag):** ≥ 50 % Voll, 20–49 % Mittel, < 20 % Fast leer – konfigurierbar pro Produkt.

### 2. Bottom-Navigation (PWA)
Sticky, nur < md. Höhe 84, mist-50 (Dark night-800), obere Linie 1 px. 4 Tabs: **Bestand**, **Scanner** (zentraler Rundbutton 54 px sky-300, Ring 4 px in Navi-Farbe, 26 px nach oben versetzt, Navy-Icon, Schatten violetter Ton), **Rezept-Finder**, **Profil**. Aktiver Tab: Pille 52×30 (hell #DCE5FB, dunkel rgba(169,192,245,.16)), Label 12/700 navy; inaktiv 12/500 muted. Items 88 px breit, Label `whitespace-nowrap`. `pb-[env(safe-area-inset-bottom)]` für iOS-PWA.

### 3. Barcode-Scanner (Vollbild-Modal) – `03`
Ohne Bottom-Nav. Hintergrund Kamerabild; Abdunklung `rgba(8,12,28,.68)` außerhalb des Suchers. Sucher 280×170 zentriert, Radius 18, vier Eckklammern 38 px, 4 px sky-300; Scanlinie 2 px sky-300 mit Glow, animiert vertikal (Vorschlag 2 s ease-in-out, Endlos). Topbar: Schließen (44 px Kreis, rgba(255,255,255,.16)), Titel „Barcode scannen“ 17/600, Taschenlampe. Darunter Hinweis „Barcode im Rahmen ausrichten“ + „EAN-13 · EAN-8 · QR-Code“. Modus-Umschalter „Scannen | Manuell“ (aktiv sky-300/navy).
**Bottom-Sheet (Treffer):** Höhe ~278, mist-50, Radius 28/28/0/0, Handle 40×4. Badge „✓ Produkt erkannt“; Produktzeile (Kachel 56, Name 18/600, „15 ml · EAN …“, Hinweis „Bereits im Bestand: 1 Flasche“ in violet-700); Primärbutton „Menge erhöhen · +15 ml“ (54 px navy); Sekundär „Als neues Produkt erfassen“ (50 px, 1,5 px Rand navy).
**Technik:** `BarcodeDetector` (formats ean_13, ean_8, qr_code) mit ZXing-Fallback; `getUserMedia({video:{facingMode:'environment'}})`; HTTPS erforderlich; Berechtigungs-/Fehlerzustand und Taschenlampe via `track.applyConstraints({advanced:[{torch:true}]})` wo unterstützt.

### 4. Detail · Ätherisches Öl – `04`
Topbar: Zurück (44 px Kreis), Label „Ätherisches Öl“ (violet-700 uppercase), Bearbeiten ✎. Kopfkarte: Foto-Slot 96×116 (Radius 18, Platzhalter gestreift), Name Display 34, botanischer Name Newsreader italic 16 cobalt-700, Badges (Marke navy-gefüllt, Status), „Bestand 12 / 15 ml“ + Balken 6 px.
Abschnitte: **Wirkung** (Zeilen „Psyche“ / „Körper“ mit Tag-Gruppen, Tag Padding 5×12, Radius 15, 14 px) · **Anwendung** (Toggle-Chips Höhe 36: aktiv navy mit „✓“, inaktiv 1,5 px Rand; rechts „Hautverdünnung 1–2 %“) · **Beschwerden & Rezeptideen** (Akkordeon in mist-50-Karte, Radius 18; Kopf Höhe 48–50 mit Titel + „n Rezepte“ + ▴/▾; geöffnet: Rezeptkarte mit Titel „Schlaf-Roll-on · 10 ml“, Zutaten „6 Tr. Lavendel + 10 ml Jojobaöl“, Badges „≈ 1 % Verdünnung“ und „✓ Alles vorrätig“ = Abgleich mit Bestand).
Akkordeon: Alpine `x-data="{open:0}"`, `x-collapse`, `aria-expanded`.

### 5. Detail · Basisöl – `05`
Header analog (Name „Jojobaöl“, „Simmondsia chinensis“, Badge „Bio · kaltgepresst“).
- **Komedogenität (Karte, Radius 20, Padding 16):** Label + „2 / 5 · gering“ (Zahl Display 22). 5 Segmente (flex-1, Höhe 36, voll rund) mit Ziffer 1–5; gefüllt: Wert ≤ 1 lilac-300, ≤ 3 sky-300, ≥ 4 violet-400, Text navy 14/700; leer: mist-100 mit Rand #BCC5E3. Fußzeile „0 = verstopft keine Poren · 5 = stark“. Wortskala: 0 nicht komedogen, 1 sehr gering, 2 gering, 3 mäßig, 4 hoch, 5 sehr hoch. A11y: `role="img"` + `aria-label`.
- **Einziehgeschwindigkeit:** Halbkreis-Tacho 128×70 (conic-gradient lilac-300 → azure-400 → cobalt-700, Innenkreis 92 px in Kartenfarbe, Nadel navy, Nabe 14 px). Winkel: langsam −60°, mittel 0°, schnell +60°. Rechts Label (Display 26) + „Langsam · Mittel · Schnell“.
- **Anwendungsgebiete:** Chips Gesicht/Massage/Haare/Körper (Höhe 38).

### 6. Erfassen / Bearbeiten – `06`, `07`
Topbar: ×, Titel „Neues Produkt“ (Display 22), „Scannen“-Pille (sky-300, navy, 13/700). Segmented Control (Padding 4, Radius 14, Segmente 40 px): Äth. Öl | Basisöl | Wachs | Additiv. Basisfelder (Label 12/600 muted, Input 42 px, Radius 12, Rand #BCC5E3): Name*, Marke, Menge / Einheit (Select) / Haltbar bis. Fixe Aktionsleiste unten (84 px) mit „Speichern“ (54 px navy, Radius 16).
**Dynamisches Feldset** (Radius 18, Padding 12–14, Titel 12/700 uppercase mit Punkt):
- *Äth. Öl* (Fläche sky-100, Text #1E3A8A): Botanischer Name (kursiv); Wirkung psychisch/körperlich als Tag-Eingabe (Tags mit ×, „+ Tag“ gestrichelt); Anwendungsarten als Toggle-Chips (Verdampfen, Hautpflege, Badezusatz, Inhalieren …).
- *Basisöl* (Fläche #E6E0F6, Text #4B2E8F): Komedogenität 0–5 als Stepper (6 Kreise 42 px, aktiv navy), Einziehgeschwindigkeit Segmented (Langsam | Mittel | Schnell), Anwendungsgebiete Mehrfach-Chips.
- Wachs/Additiv: nur Basisfelder (Feldset später definieren).
„Scannen“ füllt Name, Marke und Barcode vor (Lookup – Datenquelle offen).
Umsetzung: `<x-segmented wire:model.live="category">` und `@if($category==='essential_oil') … @elseif($category==='carrier_oil') …`.

### 7. Desktop – `09`
Ab ≈ 1280 px, 3 Spalten: **Sidebar 236** (navy-900, Logo „AromaCare“ Display 28 mit Tropfen-Icon sky-300, Nav: Bestand [aktiv rgba(169,192,245,.18)], Scanner, Rezept-Finder, Profil; unten Button „+ Neues Produkt“ sky-300 und Dark-Mode-Schalter) · **Hauptbereich** flex-1, Padding 32 (Titel 42, Suchfeld 360×46, Chips, Tabelle in mist-50-Karte Radius 20, Spalten Kachel 56 / Produkt 1.6fr / Kategorie 1fr / Bestand 1.3fr mit Balken / Status 110; Kopfzeile 12/700 uppercase muted; ausgewählte Zeile Hintergrund mist-100) · **Detailpanel 420** (mist-50, linke Linie, Padding 32/28: Kopf mit „✎ Bearbeiten“, Kurzfassung der Detailansicht inkl. Akkordeon). Tablet: Panel als Drawer. Scanner am Desktop: Webcam oder Foto-Upload.

## Interactions & Behavior
- Kategorie-Chip filtert Liste (Livewire `wire:model.live`, URL-Query-Parameter); Suche mit Debounce 300 ms über Name, Marke, Wirkungs-Tags, Barcode.
- Tippen auf Listenzeile → Detail (mobil Vollseite mit Zurück, Desktop Panel).
- Scanner: Treffer im Bestand → „Menge erhöhen“ / sonst „Neu erfassen“ (Formular vorbefüllt); „Manuell“ öffnet Formular.
- Akkordeon: ein Eintrag gleichzeitig offen; Chevron rotiert 180°, Übergang 150–200 ms ease.
- Toggle-Chips: Mehrfachauswahl, `aria-pressed`.
- Formular: Pflichtfeld Name; Menge numerisch > 0; Komedogenität 0–5 ganzzahlig; Haltbarkeit Format MM/JJJJ. Fehlerzustand: Rand violet-500 + Hilfetext darunter (nicht nur Farbe).
- Zustände zu ergänzen (nicht im Mock): Leere Liste, Laden (Skeleton), Kamera-Berechtigung verweigert, Scan nicht erkannt, Offline-Hinweis (PWA).
- Focus-Ringe: 2 px cobalt-500 mit Offset, Hover Chips leicht abgedunkelt.

## State Management
- `category` (essential_oil | carrier_oil | wax | additive), `search`, `statusFilter`, `selectedProductId`.
- Produktmodell (Vorschlag): `name, brand, barcode, category, quantity, unit, expires_at`; ätherisch: `latin_name, effects_mental[], effects_physical[], usage_types[], dilution_hint, remedies[]` (Titel + Rezept); Basisöl: `comedogenic_rating (0–5), absorption (slow|medium|fast), use_areas[], latin_name, certification`.
- Scanner: `status` (idle | scanning | found | notFound | denied), `lastCode`, `torch`.
- Statusstufe wird aus `quantity / initial_quantity` abgeleitet.
- Persistenz: Eloquent-Modelle; PWA mit Service Worker (Offline-Lesezugriff auf Bestand).

## Assets
- Keine Bilder/Icon-Dateien: Icons sind einfache CSS-Formen/Unicode (✓ × ‹ ▾ ✎) – im Projekt durch Icon-Set ersetzen (z. B. Heroicons/Lucide).
- Produktfotos: Platzhalter (gestreift) – echte Fotos über Upload.
- Schriften: Google Fonts Newsreader + DM Sans.

## Tailwind-v4-Snippets
Vollständig in Option **1j** (`design_reference/AromaCare Mockups.dc.html`, Funktion `renderVals → codes`) und `screenshots/10-tailwind-code.png`: Theme-Tokens, `comedo-scale`, `speed-gauge`, `oil-card` (Alpine-Akkordeon), Bottom-Nav + Livewire-Feldumschaltung. Die Snippets nutzen bereits die gültigen Token-Namen (mist, sky, lilac, azure, cobalt, navy, violet, night).

## Files
- `design_reference/AromaCare Mockups.dc.html` – Gesamtboard (1a Design System, 1b Bestand, 1c Scanner, 1d Detail Äth. Öl, 1e Detail Basisöl [Tweaks: Komedogenität, Einzug], 1f/1g Erfassen, 1h Dark, 1i Desktop, 1j Code)
- `design_reference/BestandScreen.dc.html`, `BottomNav.dc.html`, `support.js`
- `screenshots/*.png`
