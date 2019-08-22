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