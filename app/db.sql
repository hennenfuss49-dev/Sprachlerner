-- Datenbank erstellen
CREATE DATABASE IF NOT EXISTS sprachlerner;
USE sprachlerner;

-- Tabellen erstellen
CREATE TABLE IF NOT EXISTS Languages
(
    language_id   INT AUTO_INCREMENT PRIMARY KEY,
    language_code VARCHAR(10) NOT NULL UNIQUE,
    language_name VARCHAR(50) NOT NULL
    );

CREATE TABLE IF NOT EXISTS Users (
                                     user_id INT AUTO_INCREMENT PRIMARY KEY,
                                     username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

CREATE TABLE IF NOT EXISTS Words (
                                     word_id INT AUTO_INCREMENT PRIMARY KEY,
                                     language_id INT NOT NULL,
                                     word VARCHAR(100) NOT NULL,
    audio_path VARCHAR(255),
    FOREIGN KEY (language_id) REFERENCES Languages(language_id)
    );

CREATE TABLE IF NOT EXISTS WordTranslations (
                                                translation_id INT AUTO_INCREMENT PRIMARY KEY,
                                                word_id_1 INT NOT NULL,
                                                word_id_2 INT NOT NULL,
                                                FOREIGN KEY (word_id_1) REFERENCES Words(word_id),
    FOREIGN KEY (word_id_2) REFERENCES Words(word_id),
    UNIQUE (word_id_1, word_id_2)
    );

CREATE TABLE IF NOT EXISTS Units (
                                     unit_id INT AUTO_INCREMENT PRIMARY KEY,
                                     unit_name VARCHAR(100) NOT NULL,
    description TEXT
    );

CREATE TABLE IF NOT EXISTS UnitWords (
                                         unit_id INT NOT NULL,
                                         word_id INT NOT NULL,
                                         PRIMARY KEY (unit_id, word_id),
    FOREIGN KEY (unit_id) REFERENCES Units(unit_id),
    FOREIGN KEY (word_id) REFERENCES Words(word_id)
    );

CREATE TABLE IF NOT EXISTS Sentences (
                                         sentence_id INT AUTO_INCREMENT PRIMARY KEY,
                                         sentence TEXT NOT NULL,
                                         audio_path VARCHAR(255)
    );

CREATE TABLE IF NOT EXISTS UnitSentences (
                                             unit_id INT NOT NULL,
                                             sentence_id INT NOT NULL,
                                             PRIMARY KEY (unit_id, sentence_id),
    FOREIGN KEY (unit_id) REFERENCES Units(unit_id),
    FOREIGN KEY (sentence_id) REFERENCES Sentences(sentence_id)
    );

CREATE TABLE IF NOT EXISTS UserUnitProgress (
                                                user_id INT NOT NULL,
                                                unit_id INT NOT NULL,
                                                progress_level INT DEFAULT 0,
                                                last_practiced TIMESTAMP,
                                                PRIMARY KEY (user_id, unit_id),
    FOREIGN KEY (user_id) REFERENCES Users(user_id),
    FOREIGN KEY (unit_id) REFERENCES Units(unit_id)
    );

CREATE TABLE IF NOT EXISTS UserWordProgress (
                                                user_id INT NOT NULL,
                                                word_id INT NOT NULL,
                                                progress_level INT DEFAULT 0,
                                                last_practiced TIMESTAMP,
                                                PRIMARY KEY (user_id, word_id),
    FOREIGN KEY (user_id) REFERENCES Users(user_id),
    FOREIGN KEY (word_id) REFERENCES Words(word_id)
    );

-- Testdaten einfügen
INSERT INTO Languages (language_code, language_name) VALUES
                                                         ('de', 'Deutsch'),
                                                         ('en', 'Englisch');

INSERT INTO Users (username, email, password_hash) VALUES
                                                       ('user1', 'user1@example.com', 'hashed_password1'),
                                                       ('user2', 'user2@example.com', 'hashed_password2'),
                                                       ('user3', 'user3@example.com', 'hashed_password3'),
                                                       ('user4', 'user4@example.com', 'hashed_password4'),
                                                       ('user5', 'user5@example.com', 'hashed_password5');

INSERT INTO Words (language_id, word, audio_path) VALUES
                                                      (1, 'Haus', 'audio/haus.mp3'),
                                                      (1, 'Gebäude', 'audio/gebaeude.mp3'),
                                                      (2, 'house', 'audio/house.mp3'),
                                                      (2, 'building', 'audio/building.mp3'),
                                                      (1, 'Auto', 'audio/auto.mp3'),
                                                      (1, 'Wagen', 'audio/wagen.mp3'),
                                                      (2, 'car', 'audio/car.mp3'),
                                                      (2, 'vehicle', 'audio/vehicle.mp3'),
                                                      (1, 'Tisch', 'audio/tisch.mp3'),
                                                      (1, 'Schreibtisch', 'audio/schreibtisch.mp3'),
                                                      (2, 'table', 'audio/table.mp3'),
                                                      (2, 'desk', 'audio/desk.mp3'),
                                                      (1, 'Stuhl', 'audio/stuhl.mp3'),
                                                      (1, 'Sessel', 'audio/sessel.mp3'),
                                                      (2, 'chair', 'audio/chair.mp3'),
                                                      (2, 'armchair', 'audio/armchair.mp3'),
                                                      (1, 'Essen', 'audio/essen.mp3'),
                                                      (1, 'Mahlzeit', 'audio/mahlzeit.mp3'),
                                                      (2, 'food', 'audio/food.mp3'),
                                                      (2, 'meal', 'audio/meal.mp3');

INSERT INTO WordTranslations (word_id_1, word_id_2) VALUES
                                                        (1, 3),
                                                        (1, 4),
                                                        (2, 3),
                                                        (2, 4),
                                                        (5, 7),
                                                        (5, 8),
                                                        (6, 7),
                                                        (6, 8),
                                                        (9, 11),
                                                        (9, 12),
                                                        (10, 11),
                                                        (10, 12),
                                                        (13, 15),
                                                        (13, 16),
                                                        (14, 15),
                                                        (14, 16),
                                                        (17, 19),
                                                        (17, 20),
                                                        (18, 19),
                                                        (18, 20);

INSERT INTO Units (unit_name, description) VALUES
                                               ('Zuhause', 'Wörter und Sätze rund ums Zuhause'),
                                               ('Fortbewegung', 'Wörter und Sätze rund um die Fortbewegung'),
                                               ('Möbel', 'Wörter und Sätze rund um Möbel'),
                                               ('Essen und Trinken', 'Wörter und Sätze rund ums Essen und Trinken'),
                                               ('Einkaufen', 'Wörter und Sätze rund ums Einkaufen'),
                                               ('Arbeit', 'Wörter und Sätze rund um die Arbeit'),
                                               ('Freizeit', 'Wörter und Sätze rund um die Freizeit'),
                                               ('Reisen', 'Wörter und Sätze rund um das Reisen');

INSERT INTO UnitWords (unit_id, word_id) VALUES
                                             (1, 1),
                                             (1, 2),
                                             (1, 3),
                                             (1, 4),
                                             (2, 5),
                                             (2, 6),
                                             (2, 7),
                                             (2, 8),
                                             (3, 9),
                                             (3, 10),
                                             (3, 11),
                                             (3, 12),
                                             (3, 13),
                                             (3, 14),
                                             (3, 15),
                                             (3, 16),
                                             (4, 17),
                                             (4, 18),
                                             (4, 19),
                                             (4, 20);

INSERT INTO Sentences (sentence, audio_path) VALUES
                                                 ('Das Haus ist groß.', 'audio/das_haus_ist_gross.mp3'),
                                                 ('The house is big.', 'audio/the_house_is_big.mp3'),
                                                 ('Ich fahre mit dem Auto.', 'audio/ich_fahre_mit_dem_auto.mp3'),
                                                 ('I drive a car.', 'audio/i_drive_a_car.mp3'),
                                                 ('Der Tisch ist aus Holz.', 'audio/der_tisch_ist_aus_holz.mp3'),
                                                 ('The table is made of wood.', 'audio/the_table_is_made_of_wood.mp3'),
                                                 ('Ich esse gerne Pizza.', 'audio/ich_esse_gerne_pizza.mp3'),
                                                 ('I like to eat pizza.', 'audio/i_like_to_eat_pizza.mp3');

INSERT INTO UnitSentences (unit_id, sentence_id) VALUES
                                                     (1, 1),
                                                     (1, 2),
                                                     (2, 3),
                                                     (2, 4),
                                                     (3, 5),
                                                     (3, 6),
                                                     (4, 7),
                                                     (4, 8);

INSERT INTO UserUnitProgress (user_id, unit_id, progress_level, last_practiced) VALUES
                                                                                    (1, 1, 2, '2023-10-01 10:00:00'),
                                                                                    (1, 2, 1, '2023-10-02 11:00:00'),
                                                                                    (2, 1, 0, NULL),
                                                                                    (2, 2, 3, '2023-10-03 12:00:00'),
                                                                                    (3, 3, 1, '2023-10-04 13:00:00'),
                                                                                    (3, 4, 2, '2023-10-05 14:00:00'),
                                                                                    (4, 3, 0, NULL),
                                                                                    (4, 4, 3, '2023-10-06 15:00:00'),
                                                                                    (5, 1, 1, '2023-10-07 16:00:00'),
                                                                                    (5, 2, 2, '2023-10-08 17:00:00');

INSERT INTO UserWordProgress (user_id, word_id, progress_level, last_practiced) VALUES
                                                                                    (1, 1, 2, '2023-10-01 10:00:00'),
                                                                                    (1, 3, 1, '2023-10-01 10:10:00'),
                                                                                    (1, 5, 0, NULL),
                                                                                    (2, 1, 3, '2023-10-03 12:00:00'),
                                                                                    (2, 7, 2, '2023-10-03 12:10:00'),
                                                                                    (3, 9, 1, '2023-10-04 13:00:00'),
                                                                                    (3, 11, 2, '2023-10-04 13:10:00'),
                                                                                    (3, 17, 0, NULL),
                                                                                    (4, 9, 3, '2023-10-06 15:00:00'),
                                                                                    (4, 19, 2, '2023-10-06 15:10:00'),
                                                                                    (5, 1, 1, '2023-10-07 16:00:00'),
                                                                                    (5, 7, 2, '2023-10-08 17:00:00');
