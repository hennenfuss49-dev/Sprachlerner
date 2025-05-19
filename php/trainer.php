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
    // Überprüfen, ob die Unit-ID gültig ist
    if (!is_numeric($unitId) || $unitId <= 0) {
        throw new Exception('Invalid unit ID');
    }
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
            'SELECT w.word, w.audio_path, l.language_name
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

        // Abrufen der Übersetzungen für die Wörter in der Unit
        $stmt = $conn->prepare(
            'SELECT w1.word AS word1, w2.word AS word2
             FROM WordTranslations wt
             JOIN Words w1 ON wt.word_id_1 = w1.word_id
             JOIN Words w2 ON wt.word_id_2 = w2.word_id
             WHERE w1.word_id IN (
                 SELECT word_id FROM UnitWords WHERE unit_id = ?
             ) OR w2.word_id IN (
                 SELECT word_id FROM UnitWords WHERE unit_id = ?
             )'
        );
        $stmt->execute([$unitId, $unitId]);
        $translationRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Erstellen der Unit im gewünschten Format
        $exampleUnit = [
            'title' => $unit['unit_name'],
            'description' => $unit['description'],
            'exercises' => []
        ];

        // Vokabelübungen hinzufügen
        foreach ($wordRows as $word) {
            $exampleUnit['exercises'][] = [
                'type' => "vocabulary",
                'question' => "Was ist das? ({$word['language_name']})",
                'image' => $word['audio_path'] ? str_replace('.mp3', '.jpg', $word['audio_path']) : "/api/placeholder/300/200",
                'options' => array_merge([$word['word']], getRandomWords($wordRows, $word['word'], 3)),
                'correct' => 0
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

        // Übersetzungsübungen hinzufügen
        foreach ($translationRows as $translation) {
            $exampleUnit['exercises'][] = [
                'type' => "translation",
                'question' => "Übersetze ins Deutsche:",
                'text' => $translation['word2'],
                'answer' => $translation['word1']
            ];
        }

        // Matching-Übungen hinzufügen
        if (!empty($translationRows)) {
            $pairs = array_slice($translationRows, 0, 3);
            $pairs = array_map(function($t) {
                return [
                    'left' => $t['word2'],
                    'right' => $t['word1']
                ];
            }, $pairs);

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

// Hilfsfunktion zum Zufälligen Auswählen von Wörtern
function getRandomWords($words, $excludeWord, $count) {
    $filteredWords = array_filter($words, function($word) use ($excludeWord) {
        return $word['word'] !== $excludeWord;
    });
    shuffle($filteredWords);
    return array_slice(array_column($filteredWords, 'word'), 0, $count);
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
