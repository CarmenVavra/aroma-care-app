@php
    $legal = config('legal');
    $isAt = $legal['country_code'] === 'AT';
@endphp

<x-layouts.legal title="Impressum">
    <h2>{{ $isAt ? 'Informationen gemäß § 5 ECG und Offenlegung gemäß § 25 MedienG' : 'Angaben gemäß § 5 DDG' }}</h2>
    <address class="mt-3 not-italic">
        {{ $legal['name'] }}<br>
        {{ $legal['postal_code'] }} {{ $legal['city'] }}<br>
        {{ $legal['country'] }}
    </address>

    <h3>Kontakt</h3>
    <p>E-Mail: <a href="mailto:{{ $legal['email'] }}">{{ $legal['email'] }}</a></p>

    @if (filled($legal['vat_id']))
        <h3>Umsatzsteuer-Identifikationsnummer</h3>
        <p>{{ $legal['vat_id'] }}</p>
    @endif

    @if ($isAt)
        <h3>Medieninhaber</h3>
        <p>{{ $legal['name'] }}, {{ $legal['postal_code'] }} {{ $legal['city'] }}</p>

        <h3>Grundlegende Richtung</h3>
        <p>
            Private, nicht-kommerzielle Web-Anwendung zur Verwaltung selbst genutzter Naturkosmetik-Zutaten
            (ätherische Öle, Basisöle, Wachse, Additive) und zugehöriger Informationen.
        </p>
    @else
        <h3>Verantwortlich für den Inhalt nach § 18 Abs. 2 MStV</h3>
        <p>{{ $legal['content_responsible'] }}, {{ $legal['postal_code'] }} {{ $legal['city'] }}</p>
    @endif

    <h2>Hinweis</h2>
    <p>
        {{ config('app.name') }} ist ein privates, nicht-kommerzielles Projekt. Es werden keine Waren oder
        Dienstleistungen verkauft und keine Werbung geschaltet.
    </p>

    <h2>Haftung für Inhalte</h2>
    <p>
        Die Inhalte dieser Anwendung wurden mit Sorgfalt erstellt. Angaben zu Wirkungen, Anwendungen,
        Verdünnungen oder Komedogenität sind allgemeine Informationen und ersetzen keine ärztliche,
        pharmazeutische oder sonstige fachliche Beratung. Für die Richtigkeit, Vollständigkeit und Aktualität
        der Inhalte wird keine Gewähr übernommen. Für Daten, die Nutzerinnen und Nutzer selbst eingeben, sind
        diese selbst verantwortlich.
    </p>

    <h2>Haftung für Links</h2>
    <p>
        Diese Anwendung kann Links zu externen Websites enthalten, auf deren Inhalte kein Einfluss besteht.
        Für diese fremden Inhalte ist stets der jeweilige Anbieter verantwortlich. Bei Bekanntwerden von
        Rechtsverletzungen werden entsprechende Links umgehend entfernt.
    </p>

    <h2>Urheberrecht</h2>
    <p>
        Die durch den Betreiber erstellten Inhalte und Werke unterliegen dem Urheberrecht. Eine Verwendung
        außerhalb der Grenzen des Urheberrechts bedarf der vorherigen Zustimmung.
    </p>
</x-layouts.legal>
