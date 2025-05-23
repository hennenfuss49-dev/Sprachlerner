<?php
// Datenbankverbindungskonfiguration
$dbConfig = [
    'host' => 'localhost',
    'user' => 'root',
    'password' => 'root',
    'database' => 'sprachlerner'
];


// Funktion zum Abrufen einer Unit aus der Datenbank
function getUnit($unitId) {
    global $dbConfig;

    try {
        // Datenbankverbindung herstellen
        $conn = new PDO(
            "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']}",
            $dbConfig['user'],
            $dbConfig['password']
        );
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Abrufen der Unit-Informationen
        $stmt = $conn->prepare(
            'SELECT unit_id, unit_name, description FROM Units WHERE unit_id = ?'
        );
        $stmt->execute([$unitId]);
        $unit = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$unit) {
            throw new Exception('Unit not found');
        }

        // Abrufen der Wörter für die Unit
        $stmt = $conn->prepare(
            'SELECT w.word_id, w.word, w.audio_path, l.language_name, l.language_id
             FROM UnitWords uw
             JOIN Words w ON uw.word_id = w.word_id
             JOIN Languages l ON w.language_id = l.language_id
             WHERE uw.unit_id = ?'
        );
        $stmt->execute([$unitId]);
        $wordRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Abrufen der Sätze für die Unit
        $stmt = $conn->prepare(
            'SELECT s.sentence, s.audio_path
             FROM UnitSentences us
             JOIN Sentences s ON us.sentence_id = s.sentence_id
             WHERE us.unit_id = ?'
        );
        $stmt->execute([$unitId]);
        $sentenceRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Abrufen aller Wörter aus anderen Units (für falsche Optionen)
        $stmt = $conn->prepare(
            'SELECT w.word_id, w.word, l.language_id
             FROM Words w
             JOIN Languages l ON w.language_id = l.language_id
             WHERE w.word_id IN (
                 SELECT word_id FROM UnitWords
                 WHERE unit_id != ?
             )'
        );
        $stmt->execute([$unitId]);
        $otherWords = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Abrufen der Übersetzungen und Synonyme für die Wörter in der Unit
        $stmt = $conn->prepare(
            'SELECT wt.word_id_1, wt.word_id_2, w1.word AS word1, w2.word AS word2
             FROM WordTranslations wt
             JOIN Words w1 ON wt.word_id_1 = w1.word_id
             JOIN Words w2 ON wt.word_id_2 = w2.word_id
             WHERE wt.word_id_1 IN (
                 SELECT word_id FROM UnitWords WHERE unit_id = ?
             ) OR wt.word_id_2 IN (
                 SELECT word_id FROM UnitWords WHERE unit_id = ?
             )'
        );
        $stmt->execute([$unitId, $unitId]);
        $translationRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Synonyme gruppieren
        $synonyms = [];
        foreach ($translationRows as $translation) {
            $wordId1 = $translation['word_id_1'];
            $wordId2 = $translation['word_id_2'];

            if (!isset($synonyms[$wordId1])) {
                $synonyms[$wordId1] = [];
            }
            if (!isset($synonyms[$wordId2])) {
                $synonyms[$wordId2] = [];
            }

            $synonyms[$wordId1][] = $wordId2;
            $synonyms[$wordId2][] = $wordId1;
        }

        // Erstellen der Unit im gewünschten Format
        $exampleUnit = [
            'title' => $unit['unit_name'],
            'description' => $unit['description'],
            'exercises' => []
        ];

        // Vokabelübungen hinzufügen
        foreach ($wordRows as $word) {
            $wordId = $word['word_id'];
            $languageId = $word['language_id'];
            $correctOptions = [$word['word']];

            // Synonyme hinzufügen, falls vorhanden
            if (isset($synonyms[$wordId])) {
                foreach ($synonyms[$wordId] as $synonymId) {
                    foreach ($wordRows as $w) {
                        if ($w['word_id'] == $synonymId) {
                            $correctOptions[] = $w['word'];
                            break;
                        }
                    }
                }
            }

            // Zufällig eine korrekte Option auswählen (entweder das Wort selbst oder ein Synonym)
            $correctOption = $correctOptions[array_rand($correctOptions)];
            $correctIndex = 0; // Wird später gesetzt

            // Optionen erstellen (eine korrekte Option und drei falsche Optionen)
            $options = [$correctOption];

            // Falsche Optionen aus anderen Units hinzufügen (gleiche Sprache)
            $otherOptions = array_filter($otherWords, function($w) use ($wordId, $synonyms, $languageId) {
                return $w['language_id'] == $languageId &&
                    $w['word_id'] != $wordId &&
                    (!isset($synonyms[$wordId]) || !in_array($w['word_id'], $synonyms[$wordId]));
            });

            shuffle($otherOptions);
            $otherOptions = array_slice($otherOptions, 0, 3);

            foreach ($otherOptions as $otherWord) {
                $options[] = $otherWord['word'];
            }

            // Falls nicht genug falsche Optionen aus anderen Units, füge Wörter aus derselben Unit hinzu
            if (count($options) < 4) {
                $sameUnitOptions = array_filter($wordRows, function($w) use ($wordId, $synonyms) {
                    return $w['word_id'] != $wordId &&
                        (!isset($synonyms[$wordId]) || !in_array($w['word_id'], $synonyms[$wordId]));
                });

                shuffle($sameUnitOptions);
                $sameUnitOptions = array_slice($sameUnitOptions, 0, 4 - count($options));

                foreach ($sameUnitOptions as $sameUnitWord) {
                    if (!in_array($sameUnitWord['word'], $options)) {
                        $options[] = $sameUnitWord['word'];
                    }
                }
            }

            shuffle($options);
            $correctIndex = array_search($correctOption, $options);

            $exampleUnit['exercises'][] = [
                'type' => "vocabulary",
                'question' => "Was ist das? ({$word['language_name']})",
                'image' => $word['audio_path'] ? str_replace('.mp3', '.jpg', $word['audio_path']) : "/api/placeholder/300/200",
                'options' => $options,
                'correct' => $correctIndex,
                'correct_options' => $correctOptions // Alle korrekten Optionen (für Feedback)
            ];
        }

        // Hörübungen hinzufügen
        foreach ($sentenceRows as $sentence) {
            $exampleUnit['exercises'][] = [
                'type' => "listening",
                'question' => "Was hörst du?",
                'audio' => $sentence['audio_path'] ?: "/api/placeholder.mp3",
                'options' => array_merge([$sentence['sentence']], getRandomSentences($sentenceRows, $sentence['sentence'], 3)),
                'correct' => 0
            ];
        }

        // ... (vorheriger Code bleibt gleich bis zur Übersetzungsübung)

        // Übersetzungsübungen hinzufügen
        foreach ($translationRows as $translation) {
            // Bestimmen, welche Sprache die Quelle und welche das Ziel ist
            $isGerman1 = isGermanWord($translation['word1']);
            $isGerman2 = isGermanWord($translation['word2']);

            // Nur Übersetzungen zwischen verschiedenen Sprachen
            if ($isGerman1 != $isGerman2) {
                $fromWord = $isGerman1 ? $translation['word2'] : $translation['word1']; // Fremdsprache
                $toWord = $isGerman1 ? $translation['word1'] : $translation['word2']; // Deutsch
                $toWordId = $isGerman1 ? $translation['word_id_1'] : $translation['word_id_2'];

                // Alle akzeptablen Antworten sammeln (Hauptwort + Synonyme in der ZIELSPRACHE)
                $correctOptions = [$toWord];

                // Synonyme in der ZIELSPRACHE hinzufügen
                if (isset($synonyms[$toWordId])) {
                    foreach ($synonyms[$toWordId] as $synonymId) {
                        foreach ($wordRows as $w) {
                            if ($w['word_id'] == $synonymId && isGermanWord($w['word']) == $isGerman1) {
                                $correctOptions[] = $w['word'];
                                break;
                            }
                        }
                    }
                }

                // Falsche Optionen für Übersetzungsübungen (nur in der ZIELSPRACHE)
                $otherOptions = array_filter($otherWords, function($w) use ($toWordId, $synonyms, $isGerman1) {
                    return isGermanWord($w['word']) == $isGerman1 &&
                        $w['word_id'] != $toWordId &&
                        (!isset($synonyms[$toWordId]) || !in_array($w['word_id'], $synonyms[$toWordId]));
                });

                shuffle($otherOptions);
                $otherOptions = array_slice($otherOptions, 0, 3);

                $exampleUnit['exercises'][] = [
                    'type' => "translation",
                    'question' => $isGerman1 ?
                        "Übersetze ins Deutsche:" : "Übersetze ins Englisch:",
                    'text' => $fromWord,
                    'answer' => $correctOptions, // Nur Übersetzungen in die ZIELSPRACHE
                    'options' => array_merge($correctOptions, array_column($otherOptions, 'word'))
                ];
            }
        }

// ... (Rest des Codes bleibt gleich)


        // Matching-Übungen hinzufügen
        if (!empty($translationRows)) {
            $pairs = array_slice($translationRows, 0, 3);
            $pairs = array_map(function($t) {
                return [
                    'left' => isGermanWord($t['word1']) ? $t['word2'] : $t['word1'],
                    'right' => isGermanWord($t['word1']) ? $t['word1'] : $t['word2']
                ];
            }, $pairs);

            // Falsche Optionen für Matching-Übungen hinzufügen
            $leftOptions = array_column($pairs, 'left');
            $rightOptions = array_column($pairs, 'right');

            $otherLeftOptions = array_filter($otherWords, function($w) use ($leftOptions) {
                return !in_array($w['word'], $leftOptions) && !isGermanWord($w['word']);
            });

            $otherRightOptions = array_filter($otherWords, function($w) use ($rightOptions) {
                return !in_array($w['word'], $rightOptions) && isGermanWord($w['word']);
            });

            shuffle($otherLeftOptions);
            shuffle($otherRightOptions);

            $otherLeftOptions = array_slice($otherLeftOptions, 0, 1);
            $otherRightOptions = array_slice($otherRightOptions, 0, 1);

            if (!empty($otherLeftOptions) && !empty($otherRightOptions)) {
                $pairs[] = [
                    'left' => $otherLeftOptions[0]['word'],
                    'right' => $otherRightOptions[0]['word']
                ];
            }

            shuffle($pairs);

            $exampleUnit['exercises'][] = [
                'type' => "matching",
                'question' => "Verbinde die Paare:",
                'pairs' => $pairs
            ];
        }

        return $exampleUnit;

    } catch (Exception $e) {
        error_log('Error fetching unit: ' . $e->getMessage());
        throw $e;
    }
}

// Hilfsfunktion zum Überprüfen, ob ein Wort Deutsch ist
// Verbesserte Funktion zum Überprüfen der Sprache eines Wortes
function isGermanWord($word) {
    // Überprüfen, ob das Wort mit einem deutschen Artikel beginnt
    if (preg_match('/^(der|die|das)\s/i', $word)) {
        return true;
    }

    // Überprüfen, ob das Wort Umlaute enthält (typisch für Deutsch)
    if (preg_match('/[äöüß]/i', $word)) {
        return true;
    }

    // Standardfall: Deutsche Wörter beginnen oft mit Kleinbuchstaben
    return preg_match('/^[a-zäöüß]/', $word) === 1;
}


// Hilfsfunktion zum Zufälligen Auswählen von Sätzen
function getRandomSentences($sentences, $excludeSentence, $count) {
    $filteredSentences = array_filter($sentences, function($s) use ($excludeSentence) {
        return $s['sentence'] !== $excludeSentence;
    });
    shuffle($filteredSentences);
    return array_slice(array_column($filteredSentences, 'sentence'), 0, $count);
}
// Beispielaufruf
try {
    $unitId = intval($_GET['unit_id']);
    $unit = getUnit($unitId);
    header('Content-Type: application/json');
    echo json_encode($unit, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
