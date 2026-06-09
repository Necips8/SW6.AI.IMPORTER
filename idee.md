Ein Plugin für Shopware 6, das auf Basis einer Produktliste im JSON-Format automatisch alle erforderlichen Datenbanktabellen befüllt.

Beispiel:

```json
{ "produktname": "iPad 1" }
```

Die integrierte KI erkennt anhand des Produktnamens relevante Informationen wie Produktbeschreibung, Attribute, Hersteller, technische Daten sowie aktuelle Marktpreise. Auf dieser Grundlage generiert sie strukturierte Datensätze und füllt damit automatisch die entsprechenden Bereiche im Shop, darunter Attribute, Herstellerinformationen und Produktbeschreibungen.

Als KI-Engine wird zunächst Ollama eingesetzt.
