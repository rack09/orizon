# Orizon - API REST per paesi e viaggi

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![JSON](https://img.shields.io/badge/REST_API-JSON-16324F?style=for-the-badge)

**Progetto PHP e MySQL di Melvin Rosales - Start2Impact**

Un backend per Orizon, un'agenzia di viaggi orientata al turismo responsabile. L'API consente di gestire paesi e viaggi e di cercare le offerte in base alle destinazioni e al numero di posti disponibili.

**[Apri la demo live](https://melvinrosales.com/orizon-api)** · **[Repository GitHub](https://github.com/rack09/orizon)**

## Indice

- [Demo live](#demo-live)
- [Funzionalità](#funzionalità)
- [Tecnologie e scelte tecniche](#tecnologie-e-scelte-tecniche)
- [Installazione locale](#installazione-locale)
- [Endpoint](#endpoint)
- [Filtri dei viaggi](#filtri-dei-viaggi)
- [Prove da PowerShell](#prove-da-powershell)
- [Risposte ed errori](#risposte-ed-errori)
- [Modello dei dati](#modello-dei-dati)
- [Struttura del progetto](#struttura-del-progetto)
- [Convenzioni linguistiche](#convenzioni-linguistiche)

## Demo live

La versione online permette di **consultare i dati in modalità di sola lettura** tramite richieste `GET`: elenchi dei paesi e dei viaggi, filtri per paesi, filtri per posti disponibili e combinazioni dei due criteri. Le risposte sono in JSON.

| Consulta online | Richiesta |
| --- | --- |
| [Stato dell'API](https://melvinrosales.com/orizon-api/) | `GET /` |
| [Elenco dei paesi](https://melvinrosales.com/orizon-api/countries) | `GET /countries` |
| [Elenco dei viaggi](https://melvinrosales.com/orizon-api/trips) | `GET /trips` |
| [Viaggi con 5 posti](https://melvinrosales.com/orizon-api/trips?available_seats=5) | `GET /trips?available_seats=5` |
| [Viaggi in Francia](https://melvinrosales.com/orizon-api/trips?country_ids=2) | `GET /trips?country_ids=2` |
| [Filtro combinato: Italia e 5 posti](https://melvinrosales.com/orizon-api/trips?country_ids=1&available_seats=5) | `GET /trips?country_ids=1&available_seats=5` |

Le operazioni complete di creazione, aggiornamento ed eliminazione descritte di seguito si provano eseguendo il progetto in locale.

## Funzionalità

- Creazione, aggiornamento, eliminazione ed elenco dei paesi.
- Creazione, aggiornamento, eliminazione ed elenco dei viaggi.
- Associazione di più paesi allo stesso viaggio.
- Ricerca dei viaggi per paesi e numero esatto di posti disponibili.
- Validazione dei dati e risposte con codici HTTP appropriati.
- Ricostruzione del database con `migrations.sql`.
- Caricamento facoltativo di dati di esempio con `seed.sql`.

Il progetto è un'API backend: per provarlo si possono usare un browser per le consultazioni, PowerShell oppure un client HTTP.

## Tecnologie e scelte tecniche

| Componente | Impiego e motivazione |
| --- | --- |
| PHP senza framework | Gestione esplicita di routing, metodi HTTP, validazione e risposte |
| MySQL con InnoDB | Persistenza relazionale, chiavi esterne e transazioni |
| PDO | Query parametrizzate e gestione delle eccezioni del database |
| JSON | Un formato uniforme per i dati scambiati con il client |
| Apache e `.htaccess` | URL delle risorse instradate verso `index.php` |

La relazione molti a molti evita di duplicare i dati dei paesi quando una destinazione compare in più viaggi. Il salvataggio di un viaggio e delle sue associazioni avviene in una transazione: se un passaggio fallisce, le modifiche vengono annullate.

## Installazione locale

### 1. Preparare l'ambiente

Avviare Apache e MySQL da WAMP. Verificare che PHP abbia le estensioni `pdo_mysql` e `mbstring` attive e che Apache permetta le regole `.htaccess` con `mod_rewrite`.

Da PowerShell:

```powershell
cd C:\wamp64\www
git clone https://github.com/rack09/orizon.git orizon-api
cd orizon-api
```

In alternativa, scaricare il repository come ZIP ed estrarne il contenuto in `C:\wamp64\www\orizon-api`.

### 2. Verificare la connessione al database

Aprire `config/database.php` e adattare host, porta, nome del database, utente e password alla propria installazione.

Per una configurazione WAMP locale, i valori di esempio sono:

| Parametro | Valore di esempio |
| --- | --- |
| Host | `127.0.0.1` |
| Porta | `3306` |
| Database | `orizon` |
| Utente | `root` |
| Password | Stringa vuota |
| Charset della connessione | `utf8mb4` |

### 3. Creare la struttura

Importare `migrations.sql` da phpMyAdmin su una nuova installazione. Il file crea il database `orizon` e le tabelle `countries`, `trips` e `country_trip`.

### 4. Inserire i dati di esempio, se desiderato

Importare `seed.sql` **dopo `migrations.sql`, con le tabelle ancora vuote**. Il caricamento è facoltativo e va eseguito una sola volta per ogni nuova installazione.

Il seed inserisce **6 paesi, 6 viaggi e 10 associazioni**. Gli ID espliciti permettono di riprodurre gli esempi riportati in questa documentazione. Su un database già popolato, mantenere i dati presenti oppure preparare una nuova installazione per le prove.

### 5. Verificare l'avvio

Aprire `http://localhost/orizon-api/` oppure eseguire:

```powershell
Invoke-RestMethod -Uri "http://localhost/orizon-api/" -Method Get
```

Risposta attesa:

```json
{"message":"API Orizon funzionante"}
```

## Endpoint

URL di base per eseguire tutte le operazioni in locale:

```text
http://localhost/orizon-api
```

Le richieste `POST` e `PUT` richiedono un oggetto JSON e l'header `Content-Type: application/json`.

| Metodo | Percorso | Operazione | Risposta di successo |
| --- | --- | --- | --- |
| GET | `/` | Verifica del funzionamento dell'API | `200` e messaggio JSON |
| GET | `/countries` | Elenco dei paesi | `200` e array JSON |
| POST | `/countries` | Creazione di un paese | `201` e paese creato |
| PUT | `/countries/{id}` | Aggiornamento di un paese | `200` e paese aggiornato |
| DELETE | `/countries/{id}` | Eliminazione di un paese | `204`, senza corpo |
| GET | `/trips` | Elenco dei viaggi, con filtri facoltativi | `200` e array JSON |
| POST | `/trips` | Creazione di un viaggio | `201` e viaggio creato |
| PUT | `/trips/{id}` | Aggiornamento di un viaggio | `200` e viaggio aggiornato |
| DELETE | `/trips/{id}` | Eliminazione di un viaggio | `204`, senza corpo |

La lettura delle risorse avviene tramite gli endpoint di elenco. I percorsi con `{id}` supportano `PUT` e `DELETE`.

### Corpo JSON per i paesi

```json
{"name":"Germania"}
```

Il nome deve essere una stringa da 1 a 100 caratteri dopo la rimozione degli spazi iniziali e finali. I nomi sono univoci nel database.

### Corpo JSON per i viaggi

```json
{
  "available_seats": 5,
  "country_ids": [1, 3]
}
```

- `available_seats`: intero JSON compreso tra `0` e `4294967295`.
- `country_ids`: array non vuoto di ID interi positivi, compresi tra `1` e `4294967295`, riferiti a paesi esistenti. Gli ID ripetuti vengono accorpati.
- Con `PUT` occorre inviare entrambi i campi. I paesi ricevuti sostituiscono le associazioni precedenti del viaggio.

Esempio di risposta per un viaggio:

```json
{
  "id": 1,
  "available_seats": 5,
  "countries": [
    {"id": 1, "name": "Italia"},
    {"id": 3, "name": "Spagna"}
  ]
}
```

## Filtri dei viaggi

| Parametro | Esempio | Significato |
| --- | --- | --- |
| `available_seats` | `available_seats=5` | Numero di posti **esattamente uguale** a 5 |
| `country_ids` | `country_ids=1,3` | Viaggi associati ad **almeno uno** dei paesi indicati |

I due parametri si possono combinare: devono essere soddisfatte entrambe le condizioni. Ogni viaggio restituito include tutti i suoi paesi, anche quelli diversi dal paese usato come filtro.

Risultati attesi dopo aver caricato il seed, prima di modificare i dati:

| Richiesta GET | ID dei viaggi restituiti |
| --- | --- |
| `/trips` | `1, 2, 3, 4, 5, 6` |
| `/trips?available_seats=5` | `1, 6` |
| `/trips?available_seats=0` | `2` |
| `/trips?country_ids=2` | `2, 3, 6` |
| `/trips?country_ids=1,3` | `1, 3, 6` |
| `/trips?country_ids=5,6` | `5` |
| `/trips?country_ids=1&available_seats=5` | `1` |
| `/trips?country_ids=4&available_seats=5` | Nessuno: `[]` |

## Prove da PowerShell

### Consultare la demo online

```powershell
$demoUrl = "https://melvinrosales.com/orizon-api"

Invoke-RestMethod -Uri "$demoUrl/countries" -Method Get
Invoke-RestMethod -Uri "$demoUrl/trips" -Method Get
Invoke-RestMethod -Uri "$demoUrl/trips?country_ids=1&available_seats=5" -Method Get
```

### Provare il CRUD completo in locale

Gli esempi seguenti richiedono i paesi del seed. Creano un nuovo viaggio, lo aggiornano e infine eliminano il viaggio appena creato.

```powershell
$baseUrl = "http://localhost/orizon-api"

# Elenco e ricerca
Invoke-RestMethod -Uri "$baseUrl/countries" -Method Get
Invoke-RestMethod -Uri "$baseUrl/trips?country_ids=1,3&available_seats=5" -Method Get

# Creazione
$body = @{ available_seats = 4; country_ids = @(1, 2) } | ConvertTo-Json
$trip = Invoke-RestMethod -Uri "$baseUrl/trips" -Method Post -ContentType "application/json; charset=utf-8" -Body $body
$trip | ConvertTo-Json -Depth 5

# Aggiornamento del viaggio appena creato
$body = @{ available_seats = 2; country_ids = @(1, 3) } | ConvertTo-Json
Invoke-RestMethod -Uri "$baseUrl/trips/$($trip.id)" -Method Put -ContentType "application/json; charset=utf-8" -Body $body

# Eliminazione del viaggio appena creato: risposta 204, senza corpo
Invoke-WebRequest -Uri "$baseUrl/trips/$($trip.id)" -Method Delete -UseBasicParsing |
    Select-Object StatusCode, Content
```

## Risposte ed errori

| Codice HTTP | Utilizzo |
| --- | --- |
| `200 OK` | Lettura o aggiornamento riusciti; un elenco vuoto è `[]` |
| `201 Created` | Risorsa creata |
| `204 No Content` | Risorsa eliminata, senza corpo di risposta |
| `400 Bad Request` | Corpo non valido: è richiesto un oggetto JSON |
| `404 Not Found` | Percorso inesistente o risorsa da aggiornare/eliminare non trovata |
| `405 Method Not Allowed` | Metodo non supportato per il percorso; header `Allow` presente |
| `409 Conflict` | Nome di paese già presente |
| `422 Unprocessable Content` | Dati o filtri non validi |
| `500 Internal Server Error` | Errore del database; dettagli tecnici registrati nel log del server |

Esempio di errore applicativo:

```json
{"error":"Paese non trovato"}
```

## Modello dei dati

| Tabella | Campi | Vincoli principali |
| --- | --- | --- |
| `countries` | `id`, `name` | Chiave primaria; nome univoco, massimo 100 caratteri |
| `trips` | `id`, `available_seats` | Chiave primaria; posti come intero senza segno |
| `country_trip` | `country_id`, `trip_id` | Chiave primaria composta; chiavi esterne verso paesi e viaggi |

Un paese può comparire in più viaggi e un viaggio può coinvolgere più paesi. La tabella `country_trip` rappresenta questa relazione molti a molti. Le chiavi esterne con `ON DELETE CASCADE` eliminano le associazioni quando viene eliminato un paese o un viaggio.

## Struttura del progetto

| File | Responsabilità |
| --- | --- |
| `index.php` | Routing HTTP, richieste dei paesi e gestione delle risposte |
| `src/trips.php` | Lettura, salvataggio ed eliminazione dei viaggi |
| `config/database.php` | Configurazione della connessione PDO |
| `.htaccess` | Riscrittura delle richieste verso `index.php` |
| `migrations.sql` | Creazione del database e delle tabelle |
| `seed.sql` | Inserimento facoltativo dei dati di esempio |
| `README.md` | Installazione, endpoint, esempi e collegamenti al progetto |

## Convenzioni linguistiche

Rotte, campi JSON, parametri e identificatori del codice sono in inglese per mantenere una nomenclatura uniforme e comprensibile anche in contesti internazionali. Messaggi descrittivi e documentazione sono in italiano, in linea con il contesto formativo del progetto.

---

**Melvin Rosales** · Progetto PHP e MySQL · Start2Impact

[Demo live](https://melvinrosales.com/orizon-api) · [GitHub](https://github.com/rack09/orizon)
