CREATE DATABASE IF NOT EXISTS orizon
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE orizon;

CREATE TABLE countries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE trips (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    available_seats INT UNSIGNED NOT NULL
) ENGINE=InnoDB;

CREATE TABLE country_trip (
    country_id INT UNSIGNED NOT NULL,
    trip_id INT UNSIGNED NOT NULL,

    PRIMARY KEY (country_id, trip_id),

    FOREIGN KEY (country_id)
        REFERENCES countries(id)
        ON DELETE CASCADE,

    FOREIGN KEY (trip_id)
        REFERENCES trips(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;