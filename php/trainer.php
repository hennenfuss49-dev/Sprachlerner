<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "sprachlerner";

// Get unit_id from URL parameter
$unit_id = isset($_GET['unit_id']) ? intval($_GET['unit_id']) : 1;

try {
    // Create database connection
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get unit information
    $stmt = $pdo->prepare("SELECT unit_name, description FROM Units WHERE unit_id = ?");
    $stmt->execute([$unit_id]);
    $unit = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$unit) {
        throw new Exception("Unit not found");
    }

    // Initialize response structure
    $response = [
        "title" => $unit['unit_name'],
        "description" => $unit['description'],
        "exercises" => []
    ];

    // First, get all German words in this unit
    $stmt = $pdo->prepare("
        SELECT w.word_id, w.word, w.audio_path
        FROM UnitWords uw
        JOIN Words w ON uw.word_id = w.word_id
        JOIN Languages l ON w.language_id = l.language_id
        WHERE uw.unit_id = ?
        AND l.language_code = 'de'
    ");
    $stmt->execute([$unit_id]);
    $german_words = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // For each German word, get all its English translations
    foreach ($german_words as $german_word) {
        // Get all English translations for this German word
        $stmt = $pdo->prepare("
            SELECT DISTINCT w2.word_id, w2.word, w2.audio_path
            FROM Words w1
            JOIN WordTranslations wt ON (w1.word_id = wt.word_id_1 OR w1.word_id = wt.word_id_2)
            JOIN Words w2 ON (
                CASE 
                    WHEN w1.word_id = wt.word_id_1 THEN w2.word_id = wt.word_id_2
                    ELSE w2.word_id = wt.word_id_1
                END
            )
            JOIN Languages l2 ON w2.language_id = l2.language_id
            WHERE w1.word_id = ? 
            AND l2.language_code = 'en'
        ");
        $stmt->execute([$german_word['word_id']]);
        $english_translations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($english_translations)) {
            // Generate vocabulary exercise (German)
            $image_path = str_replace('.mp3', '.jpg', $german_word['audio_path']);

            // Get distractor words (German)
            $stmt = $pdo->prepare("
                SELECT DISTINCT w.word
                FROM Words w
                JOIN Languages l ON w.language_id = l.language_id
                WHERE l.language_code = 'de'
                AND w.word_id != ?
                ORDER BY RAND()
                LIMIT 3
            ");
            $stmt->execute([$german_word['word_id']]);
            $distractors = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $options = array_merge([$german_word['word']], $distractors);
            shuffle($options);
            $correct_index = array_search($german_word['word'], $options);

            $response['exercises'][] = [
                "type" => "vocabulary",
                "question" => "Was ist das? (Deutsch)",
                "image" => $image_path,
                "options" => $options,
                "correct" => $correct_index,
                "correct_options" => [$german_word['word']]
            ];

            // Translation exercise (English to German)
            // Use first English translation as the question
            $english_word = $english_translations[0]['word'];

            // Get all possible German translations as correct answers
            $stmt = $pdo->prepare("
                SELECT DISTINCT w2.word
                FROM Words w1
                JOIN WordTranslations wt ON (w1.word_id = wt.word_id_1 OR w1.word_id = wt.word_id_2)
                JOIN Words w2 ON (
                    CASE 
                        WHEN w1.word_id = wt.word_id_1 THEN w2.word_id = wt.word_id_2
                        ELSE w2.word_id = wt.word_id_1
                    END
                )
                JOIN Languages l1 ON w1.language_id = l1.language_id
                JOIN Languages l2 ON w2.language_id = l2.language_id
                WHERE w1.word_id = ?
                AND l1.language_code = 'en'
                AND l2.language_code = 'de'
            ");
            $stmt->execute([$english_translations[0]['word_id']]);
            $correct_german_translations = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Get distractor words (German)
            $stmt = $pdo->prepare("
                SELECT DISTINCT w.word
                FROM Words w
                JOIN Languages l ON w.language_id = l.language_id
                WHERE l.language_code = 'de'
                AND w.word NOT IN (" . str_repeat('?,', count($correct_german_translations) - 1) . "?)
                ORDER BY RAND()
                LIMIT 3
            ");
            $stmt->execute($correct_german_translations);
            $distractors = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $options = array_merge($correct_german_translations, $distractors);
            shuffle($options);

            $response['exercises'][] = [
                "type" => "translation",
                "question" => "Übersetze ins Deutsche:",
                "text" => $english_word,
                "answer" => $correct_german_translations,
                "options" => array_values(array_unique($options))
            ];
        }
    }

    // Get sentences for this unit
    $stmt = $pdo->prepare("
        SELECT s1.sentence_id as id1, s1.sentence as sentence1, s1.audio_path as audio1,
               s2.sentence_id as id2, s2.sentence as sentence2
        FROM Sentences s1
        JOIN UnitSentences us1 ON s1.sentence_id = us1.sentence_id
        LEFT JOIN Sentences s2 ON s2.sentence_id != s1.sentence_id 
        JOIN UnitSentences us2 ON s2.sentence_id = us2.sentence_id AND us2.unit_id = us1.unit_id
        WHERE us1.unit_id = ?
    ");
    $stmt->execute([$unit_id]);
    $sentences = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Helper function to detect language
    function detectLanguage($text) {
        if (preg_match('/[äöüÄÖÜß]|ist|das|Der|Die|Das/', $text)) {
            return 'de';
        }
        return 'en';
    }

    // Generate listening exercises
    foreach ($sentences as $sentence) {
        $lang1 = detectLanguage($sentence['sentence1']);
        if ($lang1 === 'de' && !empty($sentence['sentence2'])) {
            $response['exercises'][] = [
                "type" => "listening",
                "question" => "Was hörst du?",
                "audio" => $sentence['audio1'],
                "options" => [$sentence['sentence2'], $sentence['sentence1']],
                "correct" => 1
            ];
        }
    }

    // Generate matching exercises
    $matching_pairs = [];
    $used_german_words = []; // Track used German words to avoid synonyms
    $used_english_words = []; // Track used English words to avoid synonyms
    $count = 0;

    foreach ($german_words as $german_word) {
        if ($count >= 4) break;

        // Skip if we've already used this German word or its synonym
        $stmt = $pdo->prepare("
            WITH GermanSynonyms AS (
                SELECT DISTINCT w2.word
                FROM Words w1
                JOIN WordTranslations wt ON (w1.word_id = wt.word_id_1 OR w1.word_id = wt.word_id_2)
                JOIN Words w2 ON (
                    CASE 
                        WHEN w1.word_id = wt.word_id_1 THEN w2.word_id = wt.word_id_2
                        ELSE w2.word_id = wt.word_id_1
                    END
                )
                JOIN Languages l2 ON w2.language_id = l2.language_id
                WHERE w1.word_id = ?
                AND l2.language_code = 'de'
            )
            SELECT word FROM GermanSynonyms
        ");
        $stmt->execute([$german_word['word_id']]);
        $german_synonyms = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Check if this word or any of its synonyms have been used
        $skip = false;
        foreach ($german_synonyms as $synonym) {
            if (in_array($synonym, $used_german_words)) {
                $skip = true;
                break;
            }
        }
        if ($skip) continue;

        // Get English translations for this German word
        $stmt = $pdo->prepare("
            WITH EnglishTranslations AS (
                SELECT DISTINCT w2.word
                FROM Words w1
                JOIN WordTranslations wt ON (w1.word_id = wt.word_id_1 OR w1.word_id = wt.word_id_2)
                JOIN Words w2 ON (
                    CASE 
                        WHEN w1.word_id = wt.word_id_1 THEN w2.word_id = wt.word_id_2
                        ELSE w2.word_id = wt.word_id_1
                    END
                )
                JOIN Languages l2 ON w2.language_id = l2.language_id
                WHERE w1.word_id = ?
                AND l2.language_code = 'en'
            )
            SELECT word FROM EnglishTranslations
        ");
        $stmt->execute([$german_word['word_id']]);
        $english_translations = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Skip if all English translations have been used
        $available_english = array_filter($english_translations, function($word) use ($used_english_words) {
            return !in_array($word, $used_english_words);
        });
        if (empty($available_english)) continue;

        // Select one English translation randomly
        $english_word = $available_english[array_rand($available_english)];

        $matching_pairs[] = [
            "left" => $english_word,
            "right" => $german_word['word']
        ];

        // Add this word and its synonyms to the used words arrays
        $used_german_words = array_merge($used_german_words, $german_synonyms);
        $used_english_words[] = $english_word;

        $count++;
    }

    if (!empty($matching_pairs)) {
        $response['exercises'][] = [
            "type" => "matching",
            "question" => "Verbinde die Paare:",
            "pairs" => $matching_pairs
        ];
    }

    // Set content type and output JSON
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "error" => "Database error",
        "message" => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(404);
    echo json_encode([
        "error" => "Error",
        "message" => $e->getMessage()
    ]);
}
?>