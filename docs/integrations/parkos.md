# Parkos

## Stato
- fixture mode: attivo
- real API: non implementata

## Credenziali richieste
- API key
- base URL

## Endpoint prenotazioni
- da definire

## Campi richiesti per import
- `id` -> `external_id`
- `product_code` -> `external_product_ref`
- `period.starts_at` -> `starts_at`
- `period.ends_at` -> `ends_at`
- `customer.name` -> `customer_name`

## Campi opzionali
- `customer.email`
- `customer.phone`
- `vehicle.license_plate`
- `price.amount`
- `price.currency`
- `status`
- `notes`

## Mapping prodotto
- external_ref atteso: `product_code` dell'API Parkos.

## Mapping Status (Esterno -> Interno)
- `confirmed` -> `confirmed` (Default se assente)
- `cancelled` -> `cancelled` (Da implementare quando arriveranno API reali)
- `pending` -> `pending` (Da implementare quando arriveranno API reali)

## Note
- Usare fixture `reservations_success.json` per test base
- Usare fixture `reservations_unmapped_product.json` per test di business failure/skip
- Usare fixture `reservations_bad_shape.json` per test su anomalie strutturali
