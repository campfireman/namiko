# Version 1.5.4
- Bugfix interner Warenkorb

# Version 1.5.3
- Anpassung der Anzeige von Vorbestellungswertes (Lagerware wird nun beruecksichtigt)
- Bugfix Vorbestellungen
- Bugfix loeschen aus dem internen Lagerwarenkorb

# Version 1.5.2
- Logging von Preisaenderungen
- Anzeige von letzter Preisaenderung

# Version 1.5.1
- Leere Vorbestellungen werden nicht mehr angezeigt
- Anpassung der Regel fuer die Empfehlung von Bestellungen
- Anzeige der Organisation auf Rechnungen
- Loeschen von Empfehlungen

# Version 1.5
- Einfuehrung von Lagerware als Flag
- Bugfix von Bestellempfehlungen
- Bestellempfehlungen basiered auf Lagerware j/n
- update von Einheitengroesse aktualisiert bestellungen automatisch

# Version 1.4.2
- EmailCenter Update
- SEPA timeframe update

# Version 1.4.1
- Anzeige von Vorbestellungen, Summiert nach vollen Gebinden

# Version 1.4
- PDF Erstellung fuer Bestellungen
- TCPDF Update
- Bugfixes

# Version 1.3.8
- Bugfix not clickable content due to jquery version, major upgrade to 3.4.1
- style

# Version 1.3.7
- Fehlender nettopreis

# Version 1.3.6
- Aktualisierung der Vorbestellungspreise nach Preisaenderung
- Highlighting von Tabellenzeilen
- Abholung von Artikeln kann Inventar nicht in den negativen Bereich bringen
- Inventar step size = 1

# Version 1.3.5
- Ueberarbeitung der Katalogverwaltung: einfuehrung von Filtern

# Version 1.3.4
- Bugfixes
- Automatische Versionsanzeige

# Version 1.3.3
- Lizenz

# Version 1.3.2
- Automaitscher Abfrage aktueller Version von API

# Version 1.3.1
- Neue Email Benachrichtigung bei Umwandlung von Vorbestellungen
- Neuer Button unter meine Bestellungen
- Verbesserte Ueberpruefung der Einkaufwagens auf etwaige Konflikte mit dem Lagerbestand
- Loeschen von Gesamtbestellungen im Adminbereich
- Fix von Bug in Email an Vorbesteller (Einheiten fehlten)

# Version 1.3
- Unter "Meine Bestellungen" kann man jetzt Artikel einzeln als bestellt markieren:
	ACHTUNG: Diese Aenderung hat eine Anpassung des Datebankschemas erfordert, um die Datenbankintegritaet zu wahren, musste ich einige anpassungen vornehmen, falls etwas nicht stimmen sollte mit "als abgeholt markiert" sagt bescheid ich habe ein backup der Datenbank
	Auch wurden diverse datenbank abfragen geaendert entsprechend des neuen schemas auch andere bereiche des Systems funktionieren moeglicherweise nicht richtig
- diverse kleine Anpassungen im adminbereich: speichernbutton, scrollable table headers, bestellempfehlungen nun ab einem defizit echt kleiner 0, autofill maske fuer inventar