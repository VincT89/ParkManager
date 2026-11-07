# ParkManager

Gestionale per parcheggi sviluppato con Laravel, Blade, Tailwind CSS e Alpine.js. Usa un unico database con dati e accessi separati per gestione; ogni utente lavora su un parcheggio alla volta.

Documentazione di riferimento:

- [Doppia gestione: accessi, migrazione e configurazione](docs/doppia-gestione.md)
- [Audit frontend del 28 settembre 2026](docs/audit-frontend.md)

## Gestione di più parcheggi

Una **gestione** rappresenta il confine degli accessi e delle configurazioni. Può contenere uno o più parcheggi. L'account di una persona è unico, mentre appartenenza e ruolo vengono assegnati per ciascuna gestione.

Il database e le tabelle rimangono gli stessi: non vengono creati database o prefissi diversi per ogni parcheggio. Dashboard, prenotazioni, movimenti, disponibilità, pagamenti, calendario, analisi ed esportazioni seguono il parcheggio selezionato.

### Accessi e cambio parcheggio

Esempio con A e B appartenenti a **due gestioni separate**:

| Account | Accesso assegnato | Parcheggi visibili | Selettore in alto |
| --- | --- | --- | --- |
| Cliente che amministra entrambe le gestioni | Amministratore in A e in B | A e B | Presente |
| Socio del secondo parcheggio | Amministratore solo in B | Solo B | Assente |
| Operatore del primo parcheggio | Operatore solo in A | Solo A | Assente |

Chi ha più parcheggi accessibili li seleziona dal campo **Parcheggio attivo** e conferma con **Cambia**. Chi ha accesso a un solo parcheggio vede soltanto il suo nome.

Il ruolo Amministratore vale nella gestione assegnata. Non concede accesso alle altre gestioni, neppure tramite URL o identificativi inseriti direttamente. Anche l'accesso del cliente a entrambe le gestioni deve risultare dalle sue appartenenze: non si estende automaticamente agli altri utenti di A.

Se il parcheggio viene cambiato in un'altra scheda del browser, le modifiche inviate dalla vecchia scheda vengono rifiutate con risposta `409`. Occorre ricaricare la pagina prima di salvare.

### Creare il secondo parcheggio e abilitare il socio

1. Da **Parcheggi**, aprire **Nuovo parcheggio**.
2. Lasciare selezionato **Crea una gestione con accessi separati** e inserire nome, gestione e capienza reali. Chi crea il parcheggio riceve automaticamente il ruolo Amministratore nella nuova gestione.
3. Configurare categorie, posti e tariffe del nuovo parcheggio.
4. Con B selezionato, aprire **Utenti** e aggiungere il socio con ruolo **Amministratore**. Per un nuovo account sono richieste password e conferma; la password deve avere almeno 12 caratteri.
5. Configurare le piattaforme e il relativo codice del parcheggio, quindi verificare il collegamento con ciascun fornitore.

Creare B nella gestione di A ne condividerebbe gli utenti. Per mantenere il socio separato dai dati di A, usare due gestioni distinte e assegnarlo soltanto a B.

Per aggiungere a una gestione un account già esistente, usare la stessa email e lasciare vuote le password: nome e password dell'account rimangono invariati. L'operazione deve essere eseguita da un amministratore che gestisce già l'accesso di quell'account. Il ruolo può essere diverso nelle due gestioni.

### Ruoli e registro attività

- **Amministratore** (`admin`): operatività, configurazione dei parcheggi, categorie e tariffe, piattaforme, associazioni delle categorie, utenti e registro attività della gestione.
- **Operatore** (`staff`): prenotazioni e operazioni quotidiane, calendario e consultazione dei dati consentiti del parcheggio attivo.

La registrazione pubblica degli utenti del gestionale è disabilitata. Gli accessi si assegnano da **Utenti**; la rimozione riguarda la sola gestione corrente. Non è possibile rimuovere o declassare l'ultimo amministratore di una gestione.

Il menu di amministrazione contiene **Piattaforme, Parcheggi, Utenti, Registro attività**. Il registro conserva autore, operazione, elemento e nomi dei campi modificati; non ricostruisce i valori precedenti né le operazioni anteriori alla migrazione.

## Funzioni operative

- Creazione e modifica delle prenotazioni, ricerca, filtri ed esportazione Excel.
- Registrazione di ingressi e uscite e incassi in struttura.
- Calendario giornaliero, settimanale e mensile; elenchi delle entrate e delle uscite.
- Dashboard, analisi, avvisi e blocchi di disponibilità del parcheggio attivo.
- Categorie di sosta con codice, capienza, tariffa e stato di attivazione.
- Posti riservati ad altri usi e controllo della disponibilità prima del salvataggio.
- Prenotazioni pubbliche tramite un collegamento dedicato al parcheggio.
- Interfaccia adattata a desktop e smartphone, con scorrimento interno delle tabelle più larghe.

Le correzioni manuali a nominativo, email, telefono, targa, riferimento volo, numero di persone e note hanno precedenza sugli aggiornamenti successivi delle piattaforme. Il gestionale protegge soltanto i campi effettivamente modificati, anche quando vengono svuotati. Gli altri dati continuano a essere aggiornati dalla piattaforma, comprese le cancellazioni della prenotazione.

La modifica dei dati anagrafici può essere salvata anche quando la disponibilità è insufficiente. Cambiamenti a categoria, date o posti di una prenotazione attiva e riattivazioni richiedono la verifica della disponibilità. Questa correzione richiede la migrazione `2026_11_07_000001_add_manually_edited_fields_to_reservations`. Le correzioni già sovrascritte prima dell'aggiornamento devono essere reinserite e salvate.

### Filtro delle prenotazioni valet

In **Prenotazioni**, selezionare **Solo valet** per vedere le prenotazioni riconosciute come valet. Il filtro si combina con canale, stato, ricerca e date; resta attivo nella paginazione e nell'esportazione Excel. Disattivandolo tornano visibili anche le prenotazioni ordinarie e quelle con servizio non identificato, sempre nel parcheggio selezionato.

Il modulo pubblico del sito e l'inserimento manuale permettono di scegliere **Parcheggio standard** oppure **Car valet**, con Parcheggio standard preselezionato. La scelta compare nel riepilogo pubblico e nel dettaglio della prenotazione ed è modificabile dal gestionale. Indica soltanto il servizio: non cambia prezzi, posti occupati o regole di disponibilità. In modifica, **Non specificato** conserva lo stato delle vecchie prenotazioni prive di classificazione. Per le prenotazioni importate, una successiva sincronizzazione aggiorna il servizio secondo i dati della piattaforma.

Il servizio è salvato in `reservations.is_valet`: `true` indica valet, `false` un servizio ordinario riconosciuto, `null` un valore mancante o non riconosciuto. Parkos documenta `parking_type` con valori `shuttle` o `valet`. La [documentazione ParkingMyCar](https://documenter.getpostman.com/view/15764785/TzRX9ReF) usa `carvalet: "Parcheggio + Car Valet"` per il valet e `carvalet: "Parcheggio"` per il servizio ordinario; sono riconosciute anche le etichette esplicite `Valet` e `Car Valet`. Per Roosh il riconoscimento associa `service_id` al catalogo delle sedi. Non ricava il servizio da note, nomi dei clienti o categorie coperto/scoperto. Le modifiche agli altri campi conservano la classificazione; una successiva risposta API non riconoscibile la riporta a `null`.

La migrazione `2026_10_26_000001_add_is_valet_to_reservations` recupera i dati riconoscibili dai JSON Parkos/ParkingMyCar già salvati, senza chiamare API. Per riclassificare lo storico ancora sconosciuto di un singolo parcheggio, incluso Roosh, sostituire `ID_PARCHEGGIO` con l'ID reale e verificare prima la simulazione:

```bash
php artisan reservations:backfill-valet ID_PARCHEGGIO --with-catalog --dry-run
php artisan reservations:backfill-valet ID_PARCHEGGIO --with-catalog
```

Il comando non modifica prezzi, date, note o classificazioni già note. `--with-catalog` consulta il catalogo Roosh usando le credenziali della gestione selezionata; omettendolo vengono letti soltanto i JSON salvati.

Quando Roosh usa una sede esterna distinta per il valet, associare esplicitamente il relativo **codice servizio** a una categoria del parcheggio in **Piattaforme**. L'importazione include soltanto i servizi valet associati a quel parcheggio, anche negli import per periodo di sosta. La sola presenza nel catalogo non concede accesso ad altre sedi. Un servizio disabilitato nel catalogo può identificare prenotazioni storiche: il gestionale non lo attiva sulla piattaforma.

Le ricerche Roosh per codice servizio vengono eseguite separatamente, mantenendo la paginazione e rimuovendo i duplicati: nell'API verificata, una richiesta con più codici separati da virgole restituiva un elenco vuoto anche in presenza di prenotazioni. Il recupero per periodo di sosta include lo storico; la sincronizzazione live riguarda invece la finestra delle ultime modifiche.

## Capienza e disponibilità

| Modalità nell'interfaccia | Valore interno | Comportamento |
| --- | --- | --- |
| Posti condivisi tra le categorie | `shared` | Le categorie utilizzano la capienza fisica totale del parcheggio, rispettando anche i propri limiti. La disponibilità è il minimo fra disponibilità della categoria e disponibilità globale. |
| Posti separati per categoria | `per_product` | Ogni categoria ha disponibilità indipendente. Blocchi e allocazioni globali non sono consentiti; le allocazioni devono riferirsi alla categoria. |

La disponibilità considera prenotazioni confermate o modificate, prenotazioni in attesa non scadute e le riserve di capacità applicabili. Le categorie inattive non sono prenotabili.

Due intervalli si sovrappongono quando:

```text
inizio_esistente < fine_richiesta e fine_esistente > inizio_richiesta
```

`ReservationService` verifica nuovamente la disponibilità dentro una transazione. In modalità `shared` acquisisce il lock sul parcheggio e poi sulla categoria; in modalità `per_product` sulla categoria.

La disattivazione delle anagrafiche, dei blocchi e delle allocazioni usa `is_active`. Le prenotazioni hanno invece un proprio `status`: non usano `is_active` per determinarne lo stato.

## Prenotazioni pubbliche e pagamenti

Il collegamento dedicato è `/booking?parking={public_booking_key}` ed è disponibile nella configurazione del parcheggio. `/booking` continua a servire il parcheggio pubblico predefinito. Categorie, disponibilità, tariffa e prenotazione sono limitate al parcheggio individuato dal collegamento.

Le prenotazioni da sito web:

- ricevono un codice `WEB-` seguito da 32 caratteri casuali;
- nascono in stato `pending`, con scadenza impostata a 15 minuti dal flusso pubblico;
- occupano disponibilità finché sono in attesa e non scadute;
- vengono confermate attraverso il flusso di conferma della prenotazione.

Il servizio generale usa una scadenza di 30 minuti solo quando una prenotazione `pending` viene creata senza una scadenza esplicita. Il comando pianificato annulla le prenotazioni in attesa scadute.

Il canale `website` e il suo collegamento vengono predisposti quando si crea un parcheggio dal gestionale. La prenotazione pubblica richiede che il canale e il collegamento attivo esistano già; non li crea durante l'invio del cliente.

I **pagamenti in struttura** sono disponibili. I pulsanti dei **pagamenti online** restano disabilitati: il codice Stripe e PayPal gestisce configurazioni e webhook separati, ma l'attivazione richiede configurazione e prove dedicate. Non è presente una pagina per impostare gli account di incasso online.

La nuova gestione mostra il proprio nome e non eredita automaticamente logo, contenuti commerciali o documenti contrattuali della gestione iniziale. Per pubblicarne il servizio occorrono i contenuti reali del nuovo parcheggio.

## Piattaforme e sincronizzazioni

Sono presenti integrazioni per **Parkos, ParkingMyCar e Vologio**. I prodotti esterni vengono associati alle categorie interne tramite `PlatformProductMapping`.

Le piattaforme sono configurate per gestione e possono usare lo stesso codice in gestioni differenti. Le credenziali dedicate sono cifrate e i token hanno cache separate. Una gestione priva di credenziali non usa automaticamente quelle di un'altra.

Per riutilizzare una connessione già esistente occorre un amministratore abilitato anche alla gestione iniziale. Va verificato con il fornitore che quell'account comprenda il nuovo parcheggio e va impostato il relativo identificativo esterno. Il socio può amministrare B senza modificare l'identità di una connessione condivisa.

Dalla pagina **Piattaforme** si possono aggiornare le prenotazioni o importare un periodo. Le attività automatiche sono definite in [routes/console.php](routes/console.php):

| Attività | Frequenza prevista |
| --- | --- |
| Sincronizzazione delle connessioni attive (`sync:automatic`) | Ogni 5 minuti |
| Annullamento delle prenotazioni in attesa scadute | Ogni minuto |
| Elaborazione della coda database fino al suo svuotamento | Ogni minuto |

Queste attività richiedono che lo scheduler Laravel sia in esecuzione. Le connessioni con fornitori reali vanno configurate e verificate nel proprio ambiente.

## Architettura

| Componente | Responsabilità |
| --- | --- |
| `Management`, `management_user` | Gestione, appartenenze e ruoli degli utenti |
| `ParkingContext`, `ParkingScope` | Contesto corrente e limitazione delle query |
| `ResolveParkingContext` | Accesso al parcheggio e controllo delle modifiche da schede obsolete |
| `ResolvePublicBookingContext` | Individuazione del parcheggio del flusso pubblico |
| `ReservationService` | Creazione e aggiornamento delle prenotazioni con verifica della disponibilità |
| `AvailabilityService`, `ParkingAssignmentService` | Disponibilità e assegnazione nel contesto del collegamento pubblico |
| `AdapterRegistry`, `PlatformConnectionFactory`, `SyncListingJob` | Integrazioni e sincronizzazioni per gestione |
| `PaymentConfiguration`, `PaymentConfirmationService` | Configurazione e conferma dei pagamenti |
| `ActivityRecorder` | Registrazione delle attività |

Per nuove operazioni interne, mantenere le rotte nel gruppo `parking.context`, includere `@parkingContext` nei moduli di modifica e usare `ParkingContext::run` nei job che elaborano un parcheggio. Le query SQL dirette richiedono filtri espliciti: l'isolamento è applicativo.

## Requisiti e avvio locale

- PHP 8.3 compatibile con `composer.json`, Composer e le estensioni richieste dalle dipendenze.
- Laravel 13, installato tramite Composer.
- Node.js compatibile con Vite 8: `^20.19.0` oppure `>=22.12.0`, e npm.
- Database configurato in `.env`; la suite automatica usa SQLite in memoria.

Per una **nuova installazione locale**, installare le dipendenze:

```sh
composer install
npm ci
```

Copiare `.env.example` in `.env` se il file non esiste e configurare URL, database, posta e integrazioni. Poi:

```sh
php artisan key:generate
php artisan migrate
npm run build
```

Generare `APP_KEY` soltanto per una nuova installazione. In un ambiente esistente va conservata, perché serve anche a decifrare le credenziali salvate.

Avviare l'ambiente di sviluppo:

```sh
composer run dev
```

Lo script avvia server Laravel, Vite, elaborazione della coda e lettura dei log. Per eseguire anche le attività pianificate in locale, usare un terminale separato:

```sh
php artisan schedule:work
```

### Dati dimostrativi

Su un database locale dedicato e sacrificabile è disponibile `php artisan db:seed`. **`DatabaseSeeder` svuota e ricrea i dati dimostrativi**: non è un comando di aggiornamento dei dati esistenti. Rifiuta ambienti diversi da `local`/`testing` e database con più gestioni, ma questi controlli non sostituiscono la scelta di un database di prova.

Gli account dimostrativi sono definiti in [UserSeeder](database/seeders/UserSeeder.php). Per le prove dei due parcheggi usare le fixture dedicate in `tests/Browser/prepare.php`.

## Aggiornamento di un ambiente esistente

La migrazione `2026_09_17_000001_create_management_boundaries` assegna dati e utenti esistenti alla gestione iniziale. Non crea automaticamente il secondo parcheggio operativo o l'account del socio.

Prima della pubblicazione, eseguire un backup e verificare la migrazione su una copia con lo stesso motore del database di destinazione. Distribuire codice e risorse compilate, applicare `php artisan migrate --force` durante la manutenzione e riavviare i worker con il codice aggiornato, conservando `APP_KEY`.

La procedura e le limitazioni del rollback sono descritte in [Doppia gestione](docs/doppia-gestione.md). L'attivazione comprende anche configurazione dei parcheggi, accessi, categorie, tariffe e collegamenti reali alle piattaforme.

## Verifiche

La suite PHP copre disponibilità, capacità, prenotazioni pubbliche, pagamenti, migrazione, ruoli, isolamento delle gestioni e sincronizzazioni.

Esecuzione in PowerShell con configurazione di test e database in memoria:

```powershell
$env:APP_ENV = 'testing'
$env:APP_CONFIG_CACHE = 'bootstrap/cache/testing_config.php'
$env:DB_CONNECTION = 'sqlite'
$env:DB_DATABASE = ':memory:'
$env:DB_URL = ''
php vendor/bin/pest --compact
```

Usare un terminale dedicato ai test: queste variabili rimangono impostate nella sessione. La compilazione delle risorse si verifica con:

```sh
npm run build
```

Le prove browser usano Chrome/Chromium con Playwright e un profilo temporaneo. Richiedono il server locale collegato al database dedicato `storage/framework/testing/management-browser.sqlite`, preparato con `tests/Browser/prepare.php`; il preparatore rifiuta database diversi o fixture già popolate. Usare le impostazioni di isolamento descritte in [Doppia gestione](docs/doppia-gestione.md).

Configurare `PLAYWRIGHT_MODULE` con il modulo Playwright disponibile nell'ambiente, `BROWSER_EXECUTABLE` con il browser di test e, se diverso da `http://127.0.0.1:8765`, `BROWSER_TEST_URL`. Dopo aver preparato e avviato l'ambiente isolato:

```sh
node tests/Browser/management.cjs
node tests/Browser/frontend.cjs
```

- `management.cjs`: cambio parcheggio, accessi del socio, utenti, pagamento in struttura e prenotazione pubblica.
- `frontend.cjs`: 21 pagine a 360, 768 e 1440 pixel, menu, dialoghi, calendario, errori di salvataggio e cambio parcheggio in un'altra scheda. `FRONTEND_AUDIT_PAGES` consente di limitare le pagine e aggiunge la verifica a 1920 pixel.

Nella verifica del **28 settembre 2026** sono passati **174 test PHP con 532 asserzioni**, la compilazione e le prove browser descritte nell'[audit frontend](docs/audit-frontend.md). Questi risultati riguardano l'ambiente di prova; le integrazioni con i fornitori reali richiedono una verifica dedicata.
