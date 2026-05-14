🌾 Azienda Agricola

-------------------------------------------------------------------

📚 Descrizione del progetto

Azienda Agricola è un’applicazione web sviluppata per simulare la gestione digitale di un’azienda agricola e della vendita dei suoi prodotti. L’obiettivo principale del progetto è quello di automatizzare tutte le operazioni principali che normalmente verrebbero svolte manualmente, come la gestione dei prodotti, delle quantità disponibili in magazzino, degli ordini e degli utenti registrati.
Il sistema è stato progettato per offrire una piattaforma semplice da utilizzare sia dal lato cliente sia dal lato amministratore. Attraverso il sito è possibile visualizzare il catalogo dei prodotti agricoli, effettuare acquisti, controllare gli ordini e gestire il magazzino in modo rapido e organizzato.
L’applicazione rappresenta quindi un esempio pratico di gestionale web completo, sviluppato utilizzando tecnologie moderne come PHP, MySQL e Docker.

-------------------------------------------------------------------

⚙️ Come funziona il progetto

All’interno del sistema esistono principalmente due tipologie di utenti: il cliente e l’amministratore.
Il cliente può registrarsi ed effettuare il login per accedere alle funzionalità principali del sito. Una volta entrato nella piattaforma può consultare il catalogo dei prodotti agricoli disponibili, aggiungere articoli al carrello e completare un ordine. Inoltre ha la possibilità di visualizzare lo storico degli acquisti effettuati e controllare i dettagli dei propri ordini.
L’amministratore invece dispone di una visione completa del sistema. Tramite la dashboard amministrativa può controllare le giacenze del magazzino, monitorare gli ordini ricevuti e gestire i prodotti presenti nel catalogo. Ogni acquisto effettuato da un cliente aggiorna automaticamente le quantità disponibili nel database.
Nel progetto è stato integrato anche un sistema di autenticazione a due fattori (2FA), che permette di aumentare la sicurezza degli accessi.

-------------------------------------------------------------------

🏗 Struttura tecnica

Il progetto è stato sviluppato utilizzando PHP 8 per la gestione della logica lato server e MySQL per l’organizzazione dei dati all’interno del database. L’interfaccia grafica è stata realizzata tramite HTML e CSS, mentre Apache viene utilizzato come web server.
Per semplificare l’installazione e l’esecuzione del progetto è stato utilizzato Docker insieme a Docker Compose. Questo permette di avviare automaticamente tutti i servizi necessari senza dover configurare manualmente ogni componente.
All’interno del sistema è presente anche phpMyAdmin, utile per gestire il database tramite interfaccia grafica, e il servizio 2FAuth per l’autenticazione a due fattori.

-------------------------------------------------------------------

💻 Requisiti per eseguire il progetto

Per eseguire correttamente il progetto è consigliato utilizzare Docker Desktop insieme a Docker Compose. È inoltre necessario avere un browser moderno per poter accedere all’applicazione.
In alternativa è possibile utilizzare ambienti come XAMPP o Laragon installando manualmente PHP 8 e MySQL.

-------------------------------------------------------------------

🚀 Come avviare il progetto

Per prima cosa bisogna estrarre la cartella del progetto scaricato. Successivamente occorre aprire il terminale direttamente all’interno della directory principale del progetto e assicurarsi che Docker Desktop sia attivo.
Una volta fatto questo è sufficiente eseguire il comando:
docker compose up --build

Questo comando creerà automaticamente tutti i container necessari, compresi Apache, PHP, MySQL, phpMyAdmin e il servizio dedicato all’autenticazione 2FA.
Dopo alcuni secondi il progetto sarà pronto all’utilizzo. Aprendo il browser sarà possibile accedere all’applicazione digitando:
http://localhost:8080

-------------------------------------------------------------------

🛢 Configurazione del database

Per configurare il database bisogna aprire phpMyAdmin dal browser visitando l’indirizzo:
http://localhost:8081

Le credenziali di accesso sono:
Utente: root
Password: rootpassword

Dopo aver effettuato l’accesso bisogna creare un database chiamato:
myapp_db

Successivamente si importa il file SQL presente nel progetto chiamato:
tabelle.sql

Questo file creerà automaticamente tutte le tabelle necessarie per il corretto funzionamento dell’applicazione.

-------------------------------------------------------------------

🔐 Sistema di autenticazione

Il progetto include un sistema di autenticazione a due fattori per aumentare la sicurezza degli utenti. Il servizio può essere raggiunto tramite il browser all’indirizzo:
http://localhost:8082

Grazie a questo sistema è possibile simulare una gestione più sicura degli accessi.

-------------------------------------------------------------------

🧪 Come testare il sistema

Una volta avviato il progetto è possibile registrare un nuovo utente oppure utilizzare eventuali account già presenti nel database.
Dal lato cliente si può accedere al catalogo, aggiungere prodotti al carrello e completare un ordine per verificare che il sistema aggiorni correttamente il magazzino.
Dal lato amministratore invece è possibile controllare le giacenze, verificare gli ordini registrati e monitorare le vendite effettuate all’interno del sistema.

-------------------------------------------------------------------

📂 Struttura del progetto

All’interno della cartella principale sono presenti diversi file e directory fondamentali per il funzionamento del sistema.
La cartella src contiene tutte le pagine PHP dell’applicazione, comprese le sezioni dedicate al login, al catalogo, al carrello e alla dashboard amministrativa.
Nel progetto sono inoltre presenti il file docker-compose.yaml per la configurazione dei container Docker e il file tabelle.sql utilizzato per creare automaticamente il database.

-------------------------------------------------------------------

🔒 Sicurezza

La sicurezza del sistema viene gestita tramite sessioni PHP, controllo dei ruoli utente e protezione delle pagine riservate.
Le password vengono salvate in forma criptata e il sistema 2FA aggiunge un ulteriore livello di sicurezza durante l’autenticazione.

-------------------------------------------------------------------

🧠 Concetti informatici utilizzati

Durante lo sviluppo del progetto sono stati utilizzati diversi concetti studiati nel percorso di informatica, come la programmazione web lato server, l’utilizzo dei database relazionali, le query SQL, la gestione utenti e il modello client-server.
Il progetto utilizza inoltre operazioni CRUD per la gestione dei dati e introduce anche l’utilizzo della containerizzazione tramite Docker.

-------------------------------------------------------------------

📄 Documentazione

All’interno del progetto sono presenti i file necessari per la configurazione del database e dell’ambiente Docker. Eventuali diagrammi UML o diagrammi E-R possono essere aggiunti nella cartella docs per descrivere meglio la struttura logica del sistema.

-------------------------------------------------------------------

🎓 Conclusione

Azienda Agricola rappresenta un esempio completo di applicazione web gestionale sviluppata con tecnologie moderne.
Il progetto dimostra come sia possibile digitalizzare e automatizzare la gestione di un’attività agricola attraverso strumenti studiati durante il percorso scolastico di informatica.
L’applicazione integra:
gestione database
autenticazione utenti
gestione ordini
controllo magazzino
sicurezza degli accessi
utilizzo di Docker per semplificare l’esecuzione
Il sistema può essere ulteriormente ampliato aggiungendo nuove funzionalità come pagamenti online, report statistici e gestione avanzata delle spedizioni.

