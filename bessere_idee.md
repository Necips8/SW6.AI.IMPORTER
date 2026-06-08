# Bessere Idee: KI-gestützter Produktimport für Shopware 6

## Kernproblem
Die ursprüngliche Idee fehlt Struktur, Fehlerbehandlung und einen klaren Architektur-Entwurf.

## Verbesserter Ansatz

### Architektur
- **Console-Command** als Einstiegspunkt (`sw:product:ai-import`)
- **Async Message-Queue** (über Shopware MessageQueue) für große JSON-Listen
- **Validator-Layer**: JSON-Schema-Validierung vor Verarbeitung
- **Staged Import**: Entwurf → Validierung → Publizieren (kein direktes Schreiben in Live-Tabellen)

### KI-Integration
- **Ollama** lokal, mit Fallback auf OpenAI/Anthropic konfigurierbar
- **Structured Output** (JSON-Mode / Tool Calling), damit die KI garantiert das richtige Schema liefert
- **Prompt-Templates** pro Entität (Produkt, Hersteller, Eigenschaften, Kategorien)
- **Caching**: Bereits übersetzte/angereicherte Daten nicht erneut anfragen

### Datenfluss
1. JSON-Liste einlesen (Datei oder REST-Endpunkt)
2. Für jedes Produkt: Hersteller auflösen (Name → existierende ID oder neu anlegen)
3. Eigenschaften/Kategorien ebenso auflösen oder per KI erstellen lassen
4. Produkt als **Entwurf** anlegen (Status `draft`)
5. Nach Abschluss aller Produkte: Validierungsreport ausgeben
6. Optional: Bulk-Publizieren aller Entwürfe

### UI (Backend-Modul)
- Import-Übersicht mit Status (`pending`, `processing`, `draft`, `published`, `failed`)
- Log-Einsicht pro Produkt (welche KI-Abfrage, welche Felder gesetzt)
- Manuelles Nachbearbeiten von fehlgeschlagenen Importen

### Code-Struktur (Vorschlag)
```
src/
  Command/
    AiProductImportCommand.php
  Service/
    ProductImportService.php
    KiService.php              # Ollama + Fallback
    EntityResolver.php         # Hersteller/Kategorien auflösen
    DraftManager.php           # Entwurf-Logik
  Message/
    ImportProductMessage.php
    ImportProductHandler.php
  Struct/
    KiResult.php
    ImportResult.php
```

### Vorteile gegenüber der alten Idee
| Alte Idee | Bessere Idee |
|-----------|-------------|
| Keine Architektur | Klare Schichten (CLI → Queue → Service → KI) |
| Kein Rollback | Draft/Live-Trennung |
| Nur Ollama | Austauschbares KI-Backend |
| Kein UI | Verwaltbares Backend-Modul |
| Keine Validierung | JSON-Schema + Ergebnis-Validierung |
| Blockierend | Async via MessageQueue |
