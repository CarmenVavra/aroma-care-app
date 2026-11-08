@php
    $legal = config('legal');
    $isAt = $legal['country_code'] === 'AT';
    $app = config('app.name');
    $sessionMinutes = config('session.lifetime');
    $resetMinutes = config('auth.passwords.users.expire');
@endphp

<x-layouts.legal title="Datenschutzerklärung">
    <p class="text-sm text-muted dark:text-chalk/70">Stand: {{ $legal['privacy_updated_at'] }}</p>

    <p>
        Der Schutz deiner Daten ist mir wichtig. Diese Erklärung beschreibt, welche personenbezogenen Daten
        bei der Nutzung von {{ $app }} verarbeitet werden, zu welchem Zweck und auf welcher Rechtsgrundlage
        nach der Datenschutz-Grundverordnung (DSGVO).
    </p>

    <h2>1. Verantwortliche Stelle</h2>
    <address class="mt-3 not-italic">
        {{ $legal['name'] }}<br>
        {{ $legal['postal_code'] }} {{ $legal['city'] }}, {{ $legal['country'] }}<br>
        E-Mail: <a href="mailto:{{ $legal['email'] }}">{{ $legal['email'] }}</a>
    </address>
    <p>Ein Datenschutzbeauftragter ist nicht bestellt, da hierzu keine gesetzliche Pflicht besteht.</p>

    <h2>2. Grundsätze</h2>
    <ul>
        <li>Es gibt <strong>keine Werbung, kein Tracking und keine Analyse-Tools</strong>.</li>
        <li>Schriftarten und alle anderen Dateien werden vom eigenen Server geladen. Es werden keine Daten an
            Google oder andere Drittanbieter übertragen.</li>
        <li>Deine Daten werden nicht verkauft und nur dann weitergegeben, wenn dies in dieser Erklärung genannt
            ist oder eine gesetzliche Pflicht besteht.</li>
        <li>Die Verbindung ist per TLS (HTTPS) verschlüsselt.</li>
    </ul>

    <h2>3. Hosting und Server-Logfiles</h2>
    <p>
        Die Anwendung wird bei {{ $legal['hoster']['name'] }}, {{ $legal['hoster']['address'] }}, betrieben.
        Mit dem Hoster besteht ein Vertrag zur Auftragsverarbeitung nach Art. 28 DSGVO.
    </p>
    <p>
        Beim Aufruf der Anwendung verarbeitet der Webserver technisch notwendige Daten: IP-Adresse, Datum und
        Uhrzeit, aufgerufene Adresse, übertragene Datenmenge, Statuscode, Browser und Betriebssystem
        (User-Agent) sowie ggf. die zuvor besuchte Seite (Referrer). Diese Daten dienen der Auslieferung der
        Anwendung, der Stabilität und der Abwehr von Angriffen. Rechtsgrundlage ist Art. 6 Abs. 1 lit. f DSGVO
        (berechtigtes Interesse am sicheren Betrieb). Die Logfiles werden nach kurzer Zeit automatisch
        gelöscht; die genaue Frist richtet sich nach der Konfiguration des Hosters.
    </p>

    <h2>4. Nutzerkonto</h2>
    <p>Für die Nutzung ist ein Konto erforderlich. Dabei werden verarbeitet:</p>
    <ul>
        <li>Name (bzw. ein frei wählbarer Anzeigename) und E-Mail-Adresse,</li>
        <li>dein Passwort – ausschließlich als sicherer Hash, niemals im Klartext,</li>
        <li>Zeitpunkte der Erstellung und letzten Änderung des Kontos.</li>
    </ul>
    <p>
        Zweck ist die Bereitstellung der Anwendung und deines persönlichen Bestands. Rechtsgrundlage ist
        Art. 6 Abs. 1 lit. b DSGVO (Nutzungsverhältnis). Die Daten werden gespeichert, bis du dein Konto
        löschst. Du kannst dein Konto jederzeit unter „Profil → Konto löschen“ selbst löschen; dabei werden
        auch alle deine Produkte endgültig entfernt.
    </p>

    <h3>Schutz vor Missbrauch beim Anmelden</h3>
    <p>
        Um das Erraten von Passwörtern zu verhindern, werden fehlgeschlagene Anmeldeversuche kurzzeitig
        (Kombination aus E-Mail-Adresse und IP-Adresse) gezählt. Diese Zähler verfallen nach wenigen Minuten.
        Rechtsgrundlage ist Art. 6 Abs. 1 lit. f DSGVO (Sicherheit der Konten).
    </p>

    <h3>Passwort vergessen</h3>
    <p>
        Wenn du ein neues Passwort anforderst, wird ein einmaliger Link an deine E-Mail-Adresse gesendet. Das
        zugehörige Token wird nur verschlüsselt (gehasht) gespeichert und ist {{ $resetMinutes }} Minuten
        gültig. Der Versand erfolgt über den E-Mail-Server des Hosters. Rechtsgrundlage ist Art. 6 Abs. 1
        lit. b DSGVO.
    </p>

    <h2>5. Deine Bestandsdaten</h2>
    <p>
        Die von dir erfassten Produkte (z. B. Name, Marke, Barcode, Menge, Haltbarkeit, Wirkungen und
        Anwendungen) werden deinem Konto zugeordnet gespeichert und sind nur für dich sichtbar. Rechtsgrundlage
        ist Art. 6 Abs. 1 lit. b DSGVO. Gelöschte Produkte werden zunächst als gelöscht markiert und mit dem
        Löschen deines Kontos endgültig entfernt.
    </p>

    <h2>6. Kamera und Barcode-Scanner</h2>
    <p>
        Der Barcode-Scanner nutzt die Kamera deines Geräts erst, nachdem du den Zugriff im Browser erlaubt
        hast. Das Kamerabild wird <strong>ausschließlich lokal in deinem Browser</strong> ausgewertet und
        weder gespeichert noch an den Server übertragen. Übermittelt wird nur die erkannte Ziffern- bzw.
        Zeichenfolge des Barcodes, um das passende Produkt zu finden. Dasselbe gilt für hochgeladene Fotos:
        Sie werden im Browser ausgewertet und nicht hochgeladen. Du kannst die Kamera-Berechtigung jederzeit in
        den Einstellungen deines Browsers widerrufen.
    </p>

    <h2>7. Cookies und lokaler Speicher</h2>
    <p>Es werden ausschließlich technisch notwendige Cookies verwendet:</p>
    <ul>
        <li><strong>Sitzungs-Cookie</strong> – hält dich angemeldet. Die Sitzung wird auf dem Server
            gespeichert (inkl. IP-Adresse und Browser-Kennung zur Absicherung) und läuft nach
            {{ $sessionMinutes }} Minuten Inaktivität ab.</li>
        <li><strong>XSRF-TOKEN</strong> – schützt Formulare vor Fremdzugriffen (CSRF-Schutz).</li>
        <li><strong>„Angemeldet bleiben“</strong> – nur wenn du diese Option wählst; wird beim Abmelden
            gelöscht.</li>
    </ul>
    <p>
        Zusätzlich wird im lokalen Speicher deines Browsers (localStorage) gespeichert, ob du den Dark Mode
        nutzt. Diese Information verlässt dein Gerät nicht.
    </p>
    <p>
        Da diese Speicherungen für den Betrieb unbedingt erforderlich sind bzw. auf deinen Wunsch erfolgen, ist
        keine Einwilligung nötig
        ({{ $isAt ? '§ 165 Abs. 3 TKG 2021' : '§ 25 Abs. 2 Nr. 2 TDDDG' }}). Es gibt deshalb kein Cookie-Banner.
    </p>

    <h2>8. Kontakt per E-Mail</h2>
    <p>
        Wenn du per E-Mail Kontakt aufnimmst, werden deine Angaben zur Bearbeitung der Anfrage verarbeitet
        (Art. 6 Abs. 1 lit. b bzw. lit. f DSGVO) und gelöscht, sobald sie nicht mehr benötigt werden.
    </p>

    <h2>9. Deine Rechte</h2>
    <p>Du hast gegenüber der verantwortlichen Stelle folgende Rechte:</p>
    <ul>
        <li>Auskunft über deine gespeicherten Daten (Art. 15 DSGVO),</li>
        <li>Berichtigung unrichtiger Daten (Art. 16 DSGVO) – Name und E-Mail kannst du im Profil selbst ändern,</li>
        <li>Löschung (Art. 17 DSGVO) – direkt über „Konto löschen“ im Profil,</li>
        <li>Einschränkung der Verarbeitung (Art. 18 DSGVO),</li>
        <li>Datenübertragbarkeit (Art. 20 DSGVO),</li>
        <li>Widerspruch gegen Verarbeitungen auf Grundlage berechtigter Interessen (Art. 21 DSGVO).</li>
    </ul>
    <p>Eine formlose E-Mail an <a href="mailto:{{ $legal['email'] }}">{{ $legal['email'] }}</a> genügt.</p>
    <p>
        Außerdem hast du das Recht, dich bei einer Datenschutz-Aufsichtsbehörde zu beschweren (Art. 77 DSGVO).
        @if ($isAt)
            In Österreich ist das die Österreichische Datenschutzbehörde, Barichgasse 40–42, 1030 Wien
            (<a href="https://www.dsb.gv.at" rel="noopener" target="_blank">www.dsb.gv.at</a>).
        @else
            Zuständig ist insbesondere die Aufsichtsbehörde des Bundeslands, in dem du wohnst oder in dem der
            Verantwortliche ansässig ist.
        @endif
    </p>

    <h2>10. Automatisierte Entscheidungen</h2>
    <p>Eine automatisierte Entscheidungsfindung einschließlich Profiling (Art. 22 DSGVO) findet nicht statt.</p>

    <h2>11. Änderungen</h2>
    <p>
        Diese Datenschutzerklärung wird angepasst, wenn sich die Anwendung oder die Rechtslage ändert. Es gilt
        die jeweils hier veröffentlichte Fassung.
    </p>
</x-layouts.legal>
