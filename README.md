# AI Assistant – Shopware 6 Plugin

KI-gestützter Produktimport für Shopware 6. Erzeugt automatisch komplette Produktdatensätze (Name, Beschreibung, Hersteller, Preise, Eigenschaften, Kategorien) aus einer einfachen JSON-Produktliste via Ollama (lokal).

## Features

- **Single Import** – Ein Produkt per Admin-UI oder API anlegen
- **Batch Import** – Mehrere Produkte auf einmal per JSON-Datei (CLI) oder JSON-Array (API/UI)
- **Entwurfs-Modus** – Produkte standardmässig als inaktiven Entwurf anlegen, später prüfen und publizieren
- **Entity Auto-Create** – Hersteller, Kategorien und Eigenschaftsgruppen werden automatisch angelegt
- **Console Command** – `sw:product:ai-import` für Batch-Importe und CI/CD
- **Admin UI** – Import-Formular, Batch-Import, Entwurfsverwaltung
- **Async Processing** – Message-Queue-Support für grosse Importe

## Voraussetzungen

- Shopware 6.5+
- PHP 8.1+
- [Ollama](https://ollama.ai) mit einem Modell

## Installation

```bash
# 1. Plugin ins Projekt kopieren
cp -r aiAssistant custom/plugins/

# 2. Plugin installieren und aktivieren
bin/console plugin:install --activate SwagAiAssistant

# 3. Cache leeren
bin/console cache:clear
```

## Konfiguration

Im Admin unter _Einstellungen → System → KI Assistent_:

| Feld | Beschreibung |
|------|-------------|
| AI Provider | KI-Anbieter (derzeit nur Ollama) |
| Ollama API URL | Basis-URL der Ollama-Instanz (Default: `http://localhost:11434`) |
| Ollama Model | Modellname, z.B. `phi3:mini`, `llama3`, `mistral` |
| Produkte als Entwurf anlegen | Wenn aktiv, werden Produkte als inaktive Entwürfe gespeichert |
| Standard-Lagerbestand | Fallback-Wert falls die KI keinen Lagerbestand liefert |

### Docker-Umgebung (Dockware, Docker Desktop)

Läuft Shopware in einem Docker-Container, muss die **Ollama API URL** auf den Host zeigen:

| Umgebung | URL |
|-----------|-----|
| Docker Desktop (Windows/Mac) | `http://host.docker.internal:11434` |
| Linux Docker | `http://172.17.0.1:11434` |
| Host-Network (Dockware) | `http://localhost:11434` |

### Modell-Empfehlungen

| Modell | RAM | Geschwindigkeit | Hinweis |
|--------|-----|----------------|---------|
| `phi3:mini` | ~2.5 GiB | Langsam | Läuft auf fast jedem System |
| `qwen2.5:7b` | ~4.5 GiB | Mittel | Ausreichend RAM nötig |
| `llama3` | ~4.0 GiB | Mittel | Gute Qualität |
| `mistral:7b` | ~4.5 GiB | Mittel | Vergleichbar mit llama3 |
| `qwen2.5:0.5b` | ~352MB | Schnell | ✅ eines der schnellsten brauchbaren Modelle überhaupt |


**Timeout:** Der HTTP-Client hat ein Timeout von 300s. Bei kleinen Modellen auf CPU kann eine Anfrage bis zu 3 Minuten dauern.

**GPU-Tipp:** Ollama nutzt standardmässig die CPU. Mit NVIDIA GPU:
```bash
OLLAMA_NUM_GPU=1 ollama serve
```
Verkürzt die Generierung auf 10–20s pro Produkt.

## Verwendung

### Admin UI

Im Admin unter _Katalog → KI Assistent_:

1. **Single Import** – Produktnamen eingeben, optional "sofort veröffentlichen"
2. **Batch Import** – JSON mit mehreren Produkten einfügen
3. **Entwürfe** – Alle Entwürfe anzeigen, einzeln oder gesammelt publizieren/löschen

### Console

```bash
# Einzelne JSON-Datei importieren (als Entwurf)
bin/console sw:product:ai-import produkte.json

# Direkt veröffentlichen (kein Entwurf)
bin/console sw:product:ai-import produkte.json --publish

# Nur JSON validieren (ohne Import)
bin/console sw:product:ai-import produkte.json --dry-run

# Alle offenen Entwürfe veröffentlichen
bin/console sw:product:ai-import --publish-drafts
```

### JSON-Format

Die JSON-Datei erwartet ein Array von Objekten mit `produktname` oder `name`:

```json
[
  { "produktname": "Apple iPad Pro M4" },
  { "produktname": "Samsung Galaxy S25 Ultra" },
  { "produktname": "Sony WH-1000XM6" }
]
```

### API

```bash
# Einzelimport
curl -X POST /api/_action/ai-assistant/import \
  -H "Authorization: Bearer <token>" \
  -d '{"name": "iPad Pro M4"}'

# Batch-Import
curl -X POST /api/_action/ai-assistant/import-batch \
  -H "Authorization: Bearer <token>" \
  -d '{"products": ["iPad Pro M4", "MacBook Air M4"]}'

# Entwürfe abrufen
curl -X GET /api/_action/ai-assistant/drafts \
  -H "Authorization: Bearer <token>"

# Alle Entwürfe publizieren
curl -X POST /api/_action/ai-assistant/publish-drafts \
  -H "Authorization: Bearer <token>"
```

## Architektur

```
Command/                          # Console-Command (Batch-Import)
  AiProductImportCommand.php
Controller/
  AiImportController.php          # REST-API (Import, Batch, Drafts)
Exception/
  ImportValidationException.php
Message/
  ImportProductHandler.php        # Async-Message-Handler
  ImportProductMessage.php        # Async-Message
Service/
  KiService.php                   # KI-Abstraktion (Ollama + vorbereitet für Fallback)
  OllamaClient.php                # Ollama-API-Client (300s Timeout)
  ProductCreator.php              # Produkt-Entity erstellen (Properties, Kategorien, SEO)
  ProductImportService.php        # Import-Orchestrierung
  EntityResolver.php              # Hersteller/Kategorien/Eigenschaften auflösen oder anlegen
  DraftManager.php                # Entwurf-Logik (publizieren, löschen, abfragen)
Struct/
  KiResult.php                    # Typisierter Zugriff auf KI-Antwort
  ImportResult.php                # Import-Ergebnis (draft/published/failed/skipped)
Resources/config/
  config.xml                      # Plugin-Konfiguration
  routes.xml                      # Routing
  services.xml                    # DI-Container
Resources/app/administration/     # Admin UI (Vue.js)
```

## Ablauf

```
JSON-Produktliste
  → ProductImportService
    → KiService → OllamaClient (KI-Abfrage mit Structured Output, 300s Timeout)
    → EntityResolver (Hersteller/Kategorien/Properties auflösen oder anlegen)
    → ProductCreator (Produkt als Entwurf oder aktiv anlegen)
    → DraftManager (Entwurf-Markierung)
  → ImportResult (Ergebnis mit Status/Warnungen)
```

## Performance

| Modell | CPU-only | Mit GPU |
|--------|----------|---------|
| `phi3:mini` | ~180-210s | ~10-15s |
| `qwen2.5:7b` | n.v. (zu wenig RAM) | ~15-25s |

## Entwicklung

```bash
# Plugin bauen (Admin UI)
npm run build
# oder für Shopware 6 Administration:
./bin/build-storefront.sh
```
