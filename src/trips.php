<?php

function getTrips(PDO $pdo, array $filters = []): array
{
    $conditions = [];
    $parameters = [];

    if (array_key_exists('available_seats', $filters)) {
        $value = $filters['available_seats'];

        $availableSeats = is_string($value)
            ? filter_var($value, FILTER_VALIDATE_INT, [
                'options' => [
                    'min_range' => 0,
                    'max_range' => 4294967295
                ]
            ])
            : false;

        if ($availableSeats === false) {
            throw new InvalidArgumentException(
                'Il filtro available_seats deve essere un intero non negativo valido'
            );
        }

        $conditions[] = 't.available_seats = ?';
        $parameters[] = $availableSeats;
    }

    if (array_key_exists('country_ids', $filters)) {
        $value = $filters['country_ids'];

        if (!is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException(
                'Il filtro country_ids non è valido'
            );
        }

        $countryIds = [];

        foreach (explode(',', $value) as $countryId) {
            $id = filter_var(
                trim($countryId),
                FILTER_VALIDATE_INT,
                [
                    'options' => [
                        'min_range' => 1,
                        'max_range' => 4294967295
                    ]
                ]
            );

            if ($id === false) {
                throw new InvalidArgumentException(
                    'Il filtro country_ids deve contenere identificativi positivi separati da virgole'
                );
            }

            $countryIds[] = $id;
        }

        $countryIds = array_values(array_unique($countryIds));

        $placeholders = implode(
            ', ',
            array_fill(0, count($countryIds), '?')
        );

        $conditions[] = "EXISTS (
            SELECT 1
            FROM country_trip AS filter_ct
            WHERE filter_ct.trip_id = t.id
              AND filter_ct.country_id IN ($placeholders)
        )";

        $parameters = array_merge($parameters, $countryIds);
    }

    $sql = 'SELECT
                t.id AS trip_id,
                t.available_seats,
                c.id AS country_id,
                c.name AS country_name
            FROM trips AS t
            LEFT JOIN country_trip AS ct ON ct.trip_id = t.id
            LEFT JOIN countries AS c ON c.id = ct.country_id';

    if ($conditions !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    $sql .= ' ORDER BY t.id, c.id';

    $statement = $pdo->prepare($sql);
    $statement->execute($parameters);

    $trips = [];

    foreach ($statement->fetchAll() as $row) {
        $tripId = (int) $row['trip_id'];

        if (!isset($trips[$tripId])) {
            $trips[$tripId] = [
                'id' => $tripId,
                'available_seats' => (int) $row['available_seats'],
                'countries' => []
            ];
        }

        if ($row['country_id'] !== null) {
            $trips[$tripId]['countries'][] = [
                'id' => (int) $row['country_id'],
                'name' => $row['country_name']
            ];
        }
    }

    return array_values($trips);
}

function saveTrip(PDO $pdo, stdClass $data, ?int $tripId = null): array
{
    $availableSeats = $data->available_seats ?? null;
    $countryIds = $data->country_ids ?? null;

    if (
        !is_int($availableSeats)
        || $availableSeats < 0
        || $availableSeats > 4294967295
    ) {
        throw new InvalidArgumentException(
            'Il numero di posti disponibili non è valido'
        );
    }

    if (!is_array($countryIds) || $countryIds === []) {
        throw new InvalidArgumentException(
            'Il viaggio deve includere almeno un paese'
        );
    }

    foreach ($countryIds as $countryId) {
        if (
            !is_int($countryId)
            || $countryId < 1
            || $countryId > 4294967295
        ) {
            throw new InvalidArgumentException(
                'Gli identificativi dei paesi devono essere interi positivi validi'
            );
        }
    }

    $countryIds = array_values(array_unique($countryIds));

    $pdo->beginTransaction();

    try {
        $placeholders = implode(
            ', ',
            array_fill(0, count($countryIds), '?')
        );

        $statement = $pdo->prepare(
    "SELECT id, name
     FROM countries
     WHERE id IN ($placeholders)
     ORDER BY id
     FOR UPDATE"
);

$statement->execute($countryIds);

$countries = $statement->fetchAll();

if (count($countries) !== count($countryIds)) {
    throw new InvalidArgumentException(
        'Uno o più paesi indicati non esistono'
    );
}

        if ($tripId === null) {
    $statement = $pdo->prepare(
        'INSERT INTO trips (available_seats)
         VALUES (:available_seats)'
    );

    $statement->execute([
        'available_seats' => $availableSeats
    ]);

    $tripId = (int) $pdo->lastInsertId();

} else {
    $statement = $pdo->prepare(
        'SELECT id FROM trips WHERE id = :id FOR UPDATE'
    );

    $statement->execute([
        'id' => $tripId
    ]);

    if ($statement->fetch() === false) {
        throw new OutOfBoundsException(
            'Viaggio non trovato'
        );
    }

    $statement = $pdo->prepare(
        'UPDATE trips
         SET available_seats = :available_seats
         WHERE id = :id'
    );

    $statement->execute([
        'available_seats' => $availableSeats,
        'id' => $tripId
    ]);

    $statement = $pdo->prepare(
        'DELETE FROM country_trip WHERE trip_id = :trip_id'
    );

    $statement->execute([
        'trip_id' => $tripId
    ]);
}

        $statement = $pdo->prepare(
            'INSERT INTO country_trip (trip_id, country_id)
             VALUES (:trip_id, :country_id)'
        );

        foreach ($countryIds as $countryId) {
            $statement->execute([
                'trip_id' => $tripId,
                'country_id' => $countryId
            ]);
        }

        $pdo->commit();

        return [
            'id' => $tripId,
            'available_seats' => $availableSeats,
            'countries' => $countries
        ];

    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $exception;
    }
}

function deleteTrip(PDO $pdo, int $tripId): bool
{
    $statement = $pdo->prepare(
        'DELETE FROM trips WHERE id = :id'
    );

    $statement->execute([
        'id' => $tripId
    ]);

    return $statement->rowCount() > 0;
}