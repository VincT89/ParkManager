# Integrazioni Piattaforme

Questo modulo documenta le convenzioni, le regole di mapping e i flussi per le sincronizzazioni da piattaforme OTA (es. Parkos, MyParking, ecc.).

## Convenzioni Comuni

### 1. Shape minima del payload normalizzato
Ogni adapter è responsabile di mappare il JSON grezzo fornito dal provider in un oggetto `NormalizedReservation`.
Campi obbligatori:
- `external_id` (string)
- `external_product_ref` (string)
- `customer_name` (string)
- `starts_at` (Carbon)
- `ends_at` (Carbon)
- `spots` (int, >= 1)
- `raw_data` (array)

### 2. Comportamento in Dry-Run
Il processo in `--dry-run` carica i payload dall'API o dalle fixture (se `fixture_mode` è attivo).
Invece di chiamare `importFromExternal()`, l'azione interroga il database verificando l'esistenza di `(parking_listing_id, external_id)`:
- Se esiste, incrementa le statistiche di `updated`.
- Se non esiste, incrementa le statistiche di `created`.

### 3. Errori e Logging
Gli errori di shape vengono bloccati dentro l'Adapter e riportati nel `SyncLog`.
Gli errori di mapping (business errors, come prodotto non trovato) vengono bloccati nel `PlatformProductResolver` (interno all'adapter base) e loggati.
I log sono consultabili in UI sotto `/sync-logs`.
