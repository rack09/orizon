-- Orizon API - Dati di esempio
-- Codifica: UTF-8.
-- Eseguire prima migrations.sql e poi questo file, su tabelle vuote.
-- Caricamento facoltativo, da eseguire una sola volta per ogni nuova installazione.
-- Gli identificativi espliciti rendono riproducibili gli esempi della documentazione.

USE orizon;
SET NAMES utf8mb4;

START TRANSACTION;

INSERT INTO countries (id, name) VALUES
    (1, 'Italia'),
    (2, 'Francia'),
    (3, 'Spagna'),
    (4, 'Giappone'),
    (5, 'Perù'),
    (6, 'Colombia');

INSERT INTO trips (id, available_seats) VALUES
    (1, 5),
    (2, 0),
    (3, 8),
    (4, 2),
    (5, 12),
    (6, 5);

INSERT INTO country_trip (country_id, trip_id) VALUES
    (1, 1),
    (3, 1),
    (2, 2),
    (1, 3),
    (2, 3),
    (4, 4),
    (5, 5),
    (6, 5),
    (2, 6),
    (3, 6);

COMMIT;

-- Esempi di richieste GET dopo il caricamento:
-- /trips                                      -> viaggi 1, 2, 3, 4, 5, 6
-- /trips?available_seats=5                    -> viaggi 1, 6
-- /trips?available_seats=0                    -> viaggio 2
-- /trips?country_ids=2                        -> viaggi 2, 3, 6
-- /trips?country_ids=5,6                      -> viaggio 5
-- /trips?country_ids=1&available_seats=5      -> viaggio 1
-- /trips?country_ids=4&available_seats=5      -> nessun risultato: []
