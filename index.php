<?php

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

$requestPath = parse_url(
    $_SERVER['REQUEST_URI'],
    PHP_URL_PATH
);

$basePath = str_replace(
    '\\',
    '/',
    dirname($_SERVER['SCRIPT_NAME'])
);

$basePath = rtrim($basePath, '/');

$route = '/' . trim(
    substr($requestPath, strlen($basePath)),
    '/'
);



$countryId = null;
$tripId = null;

if (preg_match('#^/countries/([1-9][0-9]*)$#', $route, $matches)) {
    $countryId = (int) $matches[1];
}

if (preg_match('#^/trips/([1-9][0-9]*)$#', $route, $matches)) {
    $tripId = (int) $matches[1];
}

if (
    !in_array($route, ['/', '/countries', '/trips'], true)
    && $countryId === null
    && $tripId === null
) {
    http_response_code(404);

    echo json_encode([
        'error' => 'Risorsa non trovata'
    ]);

    exit;
}

$allowedMethods = ['GET'];

if (in_array($route, ['/countries', '/trips'], true)) {
    $allowedMethods = ['GET', 'POST'];
} elseif ($countryId !== null) {
    $allowedMethods = ['PUT', 'DELETE'];
} elseif ($tripId !== null) {
    $allowedMethods = ['PUT', 'DELETE'];
}

if (!in_array($method, $allowedMethods, true)) {
    http_response_code(405);

    header('Allow: ' . implode(', ', $allowedMethods));

    echo json_encode([
        'error' => 'Metodo non consentito'
    ]);

    exit;
}

try {
    if ($route === '/') {
        echo json_encode([
            'message' => 'API Orizon funzionante'
        ]);

        exit;
    }

    $pdo = require __DIR__ . '/config/database.php';

if ($route === '/trips' || $tripId !== null) {
    require __DIR__ . '/src/trips.php';

    if ($method === 'DELETE') {
    if (!deleteTrip($pdo, $tripId)) {
        http_response_code(404);

        echo json_encode([
            'error' => 'Viaggio non trovato'
        ]);

        exit;
    }

    http_response_code(204);

    exit;
}

    if ($method === 'POST' || $method === 'PUT') {
        $data = json_decode(file_get_contents('php://input'));

        if (json_last_error() !== JSON_ERROR_NONE || !is_object($data)) {
            http_response_code(400);

            echo json_encode([
                'error' => 'Il corpo della richiesta deve essere un oggetto JSON valido'
            ]);

            exit;
        }

        try {
            $trip = saveTrip($pdo, $data, $tripId);

        } catch (InvalidArgumentException | OutOfBoundsException $exception) {
            $status = $exception instanceof OutOfBoundsException
                ? 404
                : 422;

            http_response_code($status);

            echo json_encode([
                'error' => $exception->getMessage()
            ], JSON_UNESCAPED_UNICODE);

            exit;
        }

        http_response_code($method === 'POST' ? 201 : 200);

        echo json_encode($trip, JSON_UNESCAPED_UNICODE);

        exit;
    }

    try {
    $trips = getTrips($pdo, $_GET);

} catch (InvalidArgumentException $exception) {
    http_response_code(422);

    echo json_encode([
        'error' => $exception->getMessage()
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

    echo json_encode($trips, JSON_UNESCAPED_UNICODE);

    exit;
}

    if ($method === 'DELETE') {
    $statement = $pdo->prepare(
        'DELETE FROM countries WHERE id = :id'
    );

    $statement->execute([
        'id' => $countryId
    ]);

    if ($statement->rowCount() === 0) {
        http_response_code(404);

        echo json_encode([
            'error' => 'Paese non trovato'
        ]);

        exit;
    }

    http_response_code(204);

    exit;
}

    if ($method === 'POST' || $method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'));

    if (json_last_error() !== JSON_ERROR_NONE || !is_object($data)) {
        http_response_code(400);

        echo json_encode([
            'error' => 'Il corpo della richiesta deve essere un oggetto JSON valido'
        ]);

        exit;
    }

    $name = $data->name ?? null;

    if (
        !is_string($name)
        || trim($name) === ''
        || mb_strlen(trim($name), 'UTF-8') > 100
    ) {
        http_response_code(422);

        echo json_encode([
            'error' => 'Il nome deve contenere da 1 a 100 caratteri'
        ]);

        exit;
    }

    $name = trim($name);

    if ($method === 'PUT') {
    $statement = $pdo->prepare(
        'SELECT id FROM countries WHERE id = :id'
    );

    $statement->execute([
        'id' => $countryId
    ]);

    if ($statement->fetch() === false) {
        http_response_code(404);

        echo json_encode([
            'error' => 'Paese non trovato'
        ]);

        exit;
    }

    $statement = $pdo->prepare(
        'UPDATE countries SET name = :name WHERE id = :id'
    );

    $statement->execute([
        'name' => $name,
        'id' => $countryId
    ]);

    http_response_code(200);

    echo json_encode([
        'id' => $countryId,
        'name' => $name
    ], JSON_UNESCAPED_UNICODE);

    exit;
    }

    $statement = $pdo->prepare(
        'INSERT INTO countries (name) VALUES (:name)'
    );

    $statement->execute([
        'name' => $name
    ]);

    http_response_code(201);

    echo json_encode([
        'id' => (int) $pdo->lastInsertId(),
        'name' => $name
    ], JSON_UNESCAPED_UNICODE);

    exit;
    }

    $statement = $pdo->query(
        'SELECT id, name FROM countries ORDER BY id'
    );

    $countries = $statement->fetchAll();

    echo json_encode($countries, JSON_UNESCAPED_UNICODE);

} catch (PDOException $exception) {
    if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
        http_response_code(409);

        echo json_encode([
            'error' => 'Esiste già un paese con questo nome'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        error_log($exception->getMessage());

        http_response_code(500);

        echo json_encode([
            'error' => 'Errore interno del server'
        ]);
    }
}