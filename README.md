# Parking Management System (Laravel)

Sistema gestionale multi-parcheggio sviluppato in Laravel, progettato per supportare sia operatività interne (staff/admin) sia prenotazioni pubbliche con assegnazione automatica del parcheggio.

---

## Caratteristiche principali

- Supporto multi-parcheggio
- Booking pubblico senza selezione parcheggio
- Gestione prodotti per parcheggio
- Sistema avanzato di disponibilità (prenotazioni + blocchi + allocazioni)
- Architettura scalabile per integrazioni API (Parkos, MyParking, ecc.)
- Gestione ruoli: admin / staff
- Archiviazione logica tramite `is_active`
- Gestione concorrenza e prevenzione overbooking
- Test suite completa

---

## Architettura

Il sistema è costruito attorno a servizi applicativi:

- `ReservationService` → creazione e gestione prenotazioni (entry point unico)
- `AvailabilityService` → calcolo disponibilità
- `ParkingAssignmentService` → assegnazione parcheggio per booking pubblico

### Regola fondamentale

> [!IMPORTANT]
> Nessuna prenotazione viene creata fuori da `ReservationService`.

---

## Multi-parcheggio

- Il sistema supporta più parcheggi attivi
- Dashboard e analytics sono aggregati
- Le operazioni admin lavorano su contesto parcheggio esplicito
- Il booking pubblico assegna automaticamente il parcheggio disponibile

---

## Prodotti parcheggio

- Ogni parcheggio ha i propri prodotti
- I prodotti sono identificabili tramite `code`
- I prodotti inattivi (`is_active = false`) non sono prenotabili

---

## Capacity Mode

Ogni parcheggio ha una modalità di gestione capacità:

### Shared

- Tutti i prodotti condividono lo stesso pool fisico
- Disponibilità calcolata come:
  ```text
  min(product_available, parking_global_available)
  ```

### Per Product

- Ogni prodotto ha capacità indipendente
- Allocazioni e blocchi globali **non consentiti**
- La disponibilità è calcolata **solo sul prodotto**

---

## Disponibilità

La disponibilità considera:

- Prenotazioni attive
- Prenotazioni pending non scadute
- Blocchi disponibilità
- Allocazioni capacità

### Regola di overlap

Le date si sovrappongono quando:
```sql
starts_at < ends_at AND ends_at > starts_at
```

---

## Gestione Pending

- Le prenotazioni pubbliche nascono come `pending`
- Viene assegnato `expires_at` (es. +30 minuti)
- Le pending bloccano disponibilità solo se **non scadute**
- Un job schedulato cancella automaticamente le pending scadute

---

## Concorrenza e Prevenzione Overbooking

### Lock deterministico

In `ReservationService` i lock sono acquisiti sempre nello stesso ordine (Top-Down):
```text
Parking → ParkingProduct
```

### Comportamento

- **Shared** → lock su Parking + ParkingProduct
- **Per Product** → lock solo su ParkingProduct

### Flusso di transazione

1. Lock (`lockForUpdate()`)
2. Re-check availability (lock logico in lettura)
3. Create reservation
4. Commit

---

## Booking pubblico

Endpoint: `/booking`

### Caratteristiche

- L'utente non sceglie il parcheggio
- Il sistema assegna automaticamente il primo disponibile
- Validazione completa lato backend
- Controllo disponibilità:
  - Informativo via AJAX
  - Autoritativo in `store()`

### External ID

Formato delle prenotazioni da sito web:
```text
WEB-XXXXXXX
```

---

## Piattaforme e Integrazioni

Supporto predisposto per:

- Parkos
- MyParking
- Vologio
- ParkingMyCar

### Architettura Integrazioni

- `PlatformAdapterInterface`
- `AbstractPlatformAdapter`
- `AdapterRegistry`
- `SyncListingJob`
- `SyncLog`

### Mapping Prodotti

I prodotti esterni sono mappati su prodotti interni tramite:
```text
PlatformProductMapping
```

---

## Fail-Fast Policy

La piattaforma `website`:

- Deve esistere via seeder
- Non viene mai creata a runtime
- Usa `firstOrFail()` nel codice per fail-fast istantaneo ed evitare inconsistenze silenziose

---

## Archiviazione logica (is_active)

Il sistema **non usa** i `SoftDeletes` nativi di Laravel per questioni di storicizzazione ed estrazione dati.
Usa invece lo stato:

```sql
is_active = false
```

Metodo scope standardizzato: `->active()`

Applicato a:

- `parkings`
- `parking_products`
- `availability_blocks`
- `parking_capacity_allocations`
- `platform_product_mappings`
- `reservations`

---

## Testing

Copertura test:

- Availability (incluse allocazioni e pending)
- Booking pubblico
- Concorrenza (Phantom Reads)
- Capacity mode
- Permessi admin/staff
- Prodotti inattivi
- Fail-fast piattaforma

### Esecuzione

```bash
php artisan test
```

---

## Seeders

Seeders **idempotenti** (`updateOrCreate`):

- `PlatformSeeder` → include `website`
- `ParkingSeeder` → multi-parking (1 condiviso attivo, 1 per-product)
- `ParkingProductsSeeder`
- `ReservationsDemoSeeder` (ambiente dev/test)

### Reset completo del database

```bash
php artisan migrate:fresh --seed
```

---

## Ruoli e Permessi

### Admin

- Gestione totale parcheggi
- Gestione prodotti
- Gestione piattaforme
- Mapping piattaforme

### Staff

- Operatività quotidiana
- Gestione prenotazioni base
- Consultazione calendario

Protezione autorizzativa gestita tramite Gates/Policies:
- `can:manage-parkings`
- `can:manage-platforms`

---

## Frontend

- Stack: Blade + Tailwind CSS
- Layout separato:
  - Pubblico (`layouts.public`)
  - Admin (`layouts.app`)
- Nessun brand hardcoded (Variabili CSS e Configurazione applicativa)
