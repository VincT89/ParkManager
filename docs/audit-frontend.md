# Audit frontend del 28 settembre 2026

Verifica sul branch `main_doppio`, con Chrome senza finestra, profilo temporaneo e database SQLite dedicato ai test. Il database operativo e i servizi esterni non sono stati utilizzati.

## Problemi trovati e corretti

- Su smartphone i pulsanti di calendario e dettaglio prenotazione uscivano dalla testata. La pagina Analisi arrivava a 746 pixel di larghezza su uno schermo da 360 pixel. Testate e griglie ora si adattano allo spazio disponibile.
- Riducendo il menu su desktop e passando a smartphone, il menu rimaneva largo 52 pixel e senza etichette. Ora cambia correttamente modalità, si chiude con Escape e mantiene la navigazione da tastiera all’interno del pannello aperto. Il comando di chiusura compare solo nella modalità mobile.
- La finestra di importazione non riceveva il focus e non si chiudeva con Escape appena aperta. Ora usa un dialogo nativo con ripristino del focus sul pulsante di apertura.
- Il calendario poteva rimanere in caricamento dopo un errore. Ora mostra un messaggio e consente di riprovare, evita risposte obsolete quando si cambia mese e tratta nomi e descrizioni come testo. Le viste larghe scorrono nel loro contenitore.
- Un movimento non salvato lasciava comunque selezionata la spunta. Ora il controllo torna allo stato precedente in caso di errore, impedisce invii sovrapposti e spiega quando il parcheggio è cambiato in un’altra scheda.
- Nella pagina Utenti i due riquadri avevano larghezze differenti. Ora sono allineati, le celle hanno spaziature uniformi e il ruolo è affiancato al pulsante di salvataggio. Sui telefoni la tabella scorre senza allargare la pagina.
- Ripristinati gli stili mancanti nei riepiloghi delle piattaforme, collegati i nomi dei campi nei moduli di prenotazione e rimossi i riepiloghi di errore duplicati nelle pagine di amministrazione.

## Verifiche

`tests/Browser/frontend.cjs` controlla 21 pagine a 360, 768 e 1440 pixel, oltre a menu, dialoghi, viste del calendario, errori di rete simulati, testo contenente caratteri HTML, salvataggio dei movimenti e cambio parcheggio in un’altra scheda. Il filtro facoltativo `FRONTEND_AUDIT_PAGES` permette di riprovare pagine specifiche, includendo anche la larghezza di 1920 pixel. Le verifiche per Utenti confrontano le larghezze dei riquadri e controllano che i comandi rimangano nelle rispettive colonne.

`tests/Browser/management.cjs` verifica il cambio parcheggio, gli accessi del socio, la gestione degli utenti, il pagamento in struttura e la prenotazione pubblica. I due script accettano `PLAYWRIGHT_MODULE`, `BROWSER_EXECUTABLE` e `BROWSER_TEST_URL`, e bloccano le richieste verso origini diverse dal server locale di prova. Usare le fixture e l’ambiente isolato descritti in `doppia-gestione.md`.

La suite PHP ha completato 174 test con 532 asserzioni. Le risorse frontend sono state compilate con `npm run build`. Le prove delle integrazioni con fornitori reali e della pubblicazione restano separate da questo audit.
