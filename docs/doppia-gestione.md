# Gestione di due parcheggi

Il branch main_doppio introduce un database unico con dati separati logicamente. Non duplica le tabelle e non crea prefissi per parcheggio.

## Accessi e separazione

Una gestione rappresenta il confine degli accessi e delle configurazioni commerciali. Può contenere uno o più parcheggi; nell'interfaccia si lavora sempre su un solo parcheggio alla volta.

- Il cliente può appartenere a entrambe le gestioni con lo stesso account e cambiare parcheggio dal selettore in alto.
- Il socio deve avere un account personale con ruolo Amministratore nella sola nuova gestione. Può collaborare su prenotazioni, pagamenti in struttura, configurazione e utenti della propria gestione.
- Il ruolo Operatore conserva i permessi operativi esistenti senza amministrazione di parcheggi, piattaforme e utenti.
- L'elenco degli utenti mostra solo i membri della gestione corrente. Nome, email e password identificano un account unico; i ruoli sono assegnati separatamente per gestione.
- Non è possibile rimuovere o declassare l'ultimo amministratore della gestione.
- La registrazione pubblica degli operatori è disabilitata. Gli account si abilitano dalla pagina Utenti.

Le tabelle managements e management_user definiscono gestioni, appartenenze e ruoli. Parcheggi e piattaforme hanno management_id; prenotazioni, prodotti, disponibilità, allocazioni, pagamenti, associazioni prodotto e log sono limitati attraverso il parcheggio e le sue relazioni. Il vecchio users.role serve solo alla migrazione dei ruoli iniziali.

Dashboard, calendario, analisi, esportazioni, avvisi e sincronizzazioni manuali seguono il parcheggio attivo. Un ID appartenente a un altro parcheggio non permette di aggirare il controllo. Le modifiche inviate da una scheda aperta prima del cambio parcheggio vengono rifiutate con stato 409: occorre ricaricare la pagina.

Il registro attività conserva autore, operazione, elemento e nomi dei campi modificati. Non è uno storico completo dei valori precedenti e non include retroattivamente le operazioni anteriori alla migrazione.

## Migrazione e attivazione

La migrazione 2026_09_17_000001_create_management_boundaries associa tutti i dati e gli utenti esistenti alla gestione iniziale, mantenendo gli identificativi, le password e le configurazioni API preesistenti. Il collegamento pubblico /booking viene assegnato al primo parcheggio attivo esistente; ogni parcheggio riceve inoltre un link dedicato.

La migrazione è stata provata su database SQLite di test. Il database operativo non è stato migrato durante questo intervento. Prima della pubblicazione, eseguire un backup completo e verificare l'aggiornamento su una copia con lo stesso motore del database di destinazione. Durante l'aggiornamento sospendere accessi e worker, distribuire il codice e gli asset, eseguire php artisan migrate --force e riavviare i worker con il nuovo codice. Conservare APP_KEY: serve anche per decifrare le credenziali memorizzate.

Dopo la migrazione, l'amministratore esistente può aprire Parcheggi, creare il nuovo parcheggio lasciando selezionato Crea una gestione con accessi separati e inserire i nomi e la capienza reali. Soltanto chi lo crea riceve automaticamente l'accesso. Configurare poi categorie, capacità e tariffe, e aggiungere il socio da Utenti con ruolo Amministratore. Non creare il secondo parcheggio nella gestione iniziale: condividerebbe gli accessi della prima.

Nessun secondo parcheggio operativo, account del socio o prezzo è stato inserito automaticamente. I dati necessari devono essere quelli forniti dal cliente.

Il rollback automatico viene rifiutato quando esistono più gestioni, per evitare di annullare l'isolamento. Un eventuale ripristino deve riguardare codice e database coerenti fra loro. Il seeder dimostrativo non deve essere usato sul database operativo.

## Piattaforme e pagamenti

Le piattaforme possono avere lo stesso codice in gestioni diverse. In Piattaforme si creano il canale e il collegamento al parcheggio; nella modifica del canale si impostano le credenziali e l'identificativo esterno del parcheggio.

Con credenziali distinte, i segreti sono cifrati nel database e i token hanno cache separate. Le password salvate non vengono mostrate né riproposte dopo un errore di compilazione. Se manca una configurazione, la nuova gestione non usa automaticamente le credenziali del parcheggio originale.

Se il fornitore usa un unico account, un amministratore abilitato anche alla gestione iniziale può autorizzare la connessione esistente e impostare l'identificativo corretto del nuovo parcheggio. Il socio può gestire il proprio parcheggio ma non modificare l'identità di una connessione condivisa. Prima di sincronizzare va verificato l'identificativo assegnato dal fornitore. Le integrazioni reali non sono state chiamate durante i test.

I pagamenti in struttura sono operativi. I pulsanti per i pagamenti online conservano la disabilitazione già presente nel progetto. Il codice Stripe e PayPal ora risolve la configurazione per gestione; le nuove gestioni partono senza account di incasso. L'eventuale attivazione online richiede una configurazione tecnica dedicata in managements.payment_settings, cifrata tramite il modello Management, e una verifica dei flussi con il fornitore. Non è stata aggiunta una pagina per configurare gli account di incasso.

I webhook della gestione iniziale mantengono /webhooks/stripe e /webhooks/paypal. Per una nuova gestione sono disponibili /webhooks/{public_key}/stripe e /webhooks/{public_key}/paypal; firma o verifica server e ricerca del pagamento usano esclusivamente quella gestione. Una notifica della gestione iniziale non può confermare un pagamento della seconda.

## Prenotazioni pubbliche

La pagina di modifica del parcheggio mostra il link pubblico dedicato /booking?parking={public_booking_key}. Disponibilità, categorie, tariffa e prenotazione sono limitate a quel parcheggio. I codici delle nuove prenotazioni web contengono 32 caratteri casuali dopo WEB-; i codici esistenti restano validi.

La nuova gestione mostra il proprio nome e non eredita le dichiarazioni commerciali, il logo o il documento contrattuale del parcheggio iniziale. Per la pubblicazione del nuovo servizio restano da fornire e integrare gli eventuali contenuti commerciali e documenti propri. Il percorso di prenotazione e i collegamenti per ripeterla mantengono il parcheggio selezionato.

## Verifiche e manutenzione

Sono presenti test per migrazione dei dati, accessi diretti e modifiche fra gestioni, ruoli, revoca accessi, ultimo amministratore, schede obsolete, esportazioni, prenotazione pubblica, credenziali separate, sincronizzazioni e notifiche di pagamento. La suite PHP si esegue con vendor/bin/pest in ambiente testing su SQLite in memoria; la compilazione delle risorse con npm run build.

La verifica nel browser usa tests/Browser/prepare.php e tests/Browser/management.cjs. Il preparatore accetta soltanto APP_ENV=testing, DB_CONNECTION=sqlite e il database dedicato storage/framework/testing/management-browser.sqlite. Non cancella dati e rifiuta di rigenerare gli utenti se il database è già preparato. Durante il test il server deve usare quel database, posta disabilitata tramite MAIL_MAILER=array, coda database e pagamenti online disattivati. Lo script browser usa un profilo temporaneo, blocca gli indirizzi esterni e accetta PLAYWRIGHT_MODULE, BROWSER_EXECUTABLE e BROWSER_TEST_URL per adattarsi all'ambiente. Gli screenshot finiscono in storage/framework/testing/browser-qa, escluso da Git.

Per nuove funzionalità interne, mantenere le rotte nel gruppo parking.context e il campo parkingContext nei moduli di modifica. I job che lavorano sui dati di un parcheggio devono usare ParkingContext::run e ripristinare il contesto al termine. Le query SQL dirette e le operazioni tecniche fuori dal contesto applicativo richiedono filtri espliciti: la separazione qui è applicativa, non un sistema di utenti database separati.
