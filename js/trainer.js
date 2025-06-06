window.onload = function() {
    // Initialize the trainer
    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);
    const unitId = urlParams.get('unit_id'); // Example unit ID
    fetchUnit(unitId)
        .then(unit => loadUnit(unit))
        .catch(error => console.error('Error loading unit:', error));
}

// Globale Variablen
let currentUnit = null;
let currentExerciseIndex = 0;
let userAnswers = [];
let score = 0;
let totalQuestions = 0;
let correctAnswers = 0;

// DOM-Elemente
const trainerContent = document.getElementById('trainer-content');
const progressElement = document.getElementById('progress');
const scoreElement = document.getElementById('score');
const prevBtn = document.getElementById('prev-btn');
const nextBtn = document.getElementById('next-btn');
const completionScreen = document.getElementById('completion-screen');
const finalScoreElement = document.getElementById('final-score');


// Funktion zum Aktualisieren des Fortschritts
function updateProgress() {
    progressElement.textContent = `Frage ${currentExerciseIndex + 1} von ${totalQuestions}`;
    scoreElement.textContent = `Punkte: ${correctAnswers}/${totalQuestions}`;
}

// Funktion zum Anzeigen einer Übung
function displayExercise(index) {
    const exercise = currentUnit.exercises[index];
    trainerContent.innerHTML = '';

    const exerciseDiv = document.createElement('div');
    exerciseDiv.className = 'exercise';

    // Frage anzeigen
    const questionText = document.createElement('p');
    questionText.className = 'question-text';
    questionText.textContent = exercise.question;
    exerciseDiv.appendChild(questionText);

    // Medieninhalt anzeigen (Bild oder Audio)
    if (exercise.image) {
        const img = document.createElement('img');
        img.className = 'question-image';
        img.src = exercise.image;
        img.alt = 'Bild zur Frage';
        exerciseDiv.appendChild(img);
    } else if (exercise.audio) {
        const audio = document.createElement('audio');
        audio.className = 'question-audio';
        audio.controls = true;
        audio.src = exercise.audio;
        exerciseDiv.appendChild(audio);
    }

    // Übungstyp-spezifische Anzeige
    switch (exercise.type) {
        case 'vocabulary':
            displayVocabularyExercise(exerciseDiv, exercise);
            break;
        case 'listening':
            displayListeningExercise(exerciseDiv, exercise);
            break;
        case 'translation':
            displayTranslationExercise(exerciseDiv, exercise);
            break;
        case 'matching':
            displayMatchingExercise(exerciseDiv, exercise);
            break;
    }

    trainerContent.appendChild(exerciseDiv);
    updateButtonStates();
}

// Funktion zum Anzeigen einer Vokabelübung
function displayVocabularyExercise(container, exercise) {
    const optionsContainer = document.createElement('div');
    optionsContainer.className = 'options-container';

    exercise.options.forEach((option, i) => {
        const button = document.createElement('button');
        button.className = 'option-button';
        button.textContent = option;
        button.dataset.index = i;
        button.addEventListener('click', () => {
            const result = checkVocabularyExercise(exercise, i);
            showFeedback(result);
            disableOptions(optionsContainer);
        });
        optionsContainer.appendChild(button);
    });

    container.appendChild(optionsContainer);
}

// Funktion zum Anzeigen einer Hörübung
function displayListeningExercise(container, exercise) {
    const optionsContainer = document.createElement('div');
    optionsContainer.className = 'options-container';

    exercise.options.forEach((option, i) => {
        const button = document.createElement('button');
        button.className = 'option-button';
        button.textContent = option;
        button.dataset.index = i;
        button.addEventListener('click', () => {
            const result = {
                correct: i === exercise.correct,
                feedback: i === exercise.correct ?
                    "Richtig!" : `Falsch. Die richtige Antwort ist "${exercise.options[exercise.correct]}".`
            };
            showFeedback(result);
            disableOptions(optionsContainer);
        });
        optionsContainer.appendChild(button);
    });

    container.appendChild(optionsContainer);
}

function displayTranslationExercise(container, exercise) {
    const inputContainer = document.createElement('div');
    inputContainer.innerHTML = `
        <p>${exercise.text}</p>
        <input type="text" id="translation-input" class="input-answer" placeholder="Ihre Antwort">
        <button class="btn" id="submit-translation">Antwort überprüfen</button>
    `;

    speakText(exercise.text, 'de-DE');

    inputContainer.querySelector('#submit-translation').addEventListener('click', () => {
        const userAnswer = inputContainer.querySelector('#translation-input').value;
        if (userAnswer.trim() !== '') {
            const result = checkTranslationExercise(exercise, userAnswer);
            showFeedback(result);
            inputContainer.querySelector('#translation-input').disabled = true;
            inputContainer.querySelector('#submit-translation').disabled = true;
        } else {
            alert('Bitte geben Sie eine Antwort ein.');
        }
    });

    container.appendChild(inputContainer);
}


// Funktion zum Anzeigen einer Matching-Übung
function displayMatchingExercise(container, exercise) {
    const pairsContainer = document.createElement('div');
    pairsContainer.className = 'pairs-container';

    // Linke Seite (Fremdsprache)
    const leftColumn = document.createElement('div');
    leftColumn.className = 'column';

    // Rechte Seite (Deutsche Übersetzungen)
    const rightColumn = document.createElement('div');
    rightColumn.className = 'column';

    // Paare mischen
    const shuffledPairs = [...exercise.pairs].sort(() => Math.random() - 0.5);

    // Linke Elemente hinzufügen
    shuffledPairs.forEach((pair, i) => {
        const leftItem = document.createElement('div');
        leftItem.className = 'pair-item';
        leftItem.textContent = pair.left;
        leftItem.dataset.index = i;
        leftItem.dataset.matched = 'false';
        leftItem.addEventListener('click', () => {
            speakText(leftItem.textContent, "")
            if (leftItem.classList.contains('selected')) {
                leftItem.classList.remove('selected');
            } else {
                document.querySelectorAll('.pair-item.selected').forEach(el => {
                    el.classList.remove('selected');
                });
                leftItem.classList.add('selected');
            }
        });
        leftColumn.appendChild(leftItem);
    });

    // Rechte Elemente hinzufügen
    shuffledPairs.forEach((pair, i) => {
        const rightItem = document.createElement('div');
        rightItem.className = 'pair-item';
        rightItem.textContent = pair.right;
        rightItem.dataset.index = i;
        rightItem.dataset.matched = 'false';
        rightItem.addEventListener('click', () => {
            speakText(rightItem.textContent, "")
            const selectedLeft = document.querySelector('.pair-item.selected');
            if (selectedLeft) {
                const leftIndex = parseInt(selectedLeft.dataset.index);
                const rightIndex = parseInt(rightItem.dataset.index);

                if (leftIndex === rightIndex) {
                    selectedLeft.classList.add('matched');
                    rightItem.classList.add('matched');
                    selectedLeft.dataset.matched = 'true';
                    rightItem.dataset.matched = 'true';
                    selectedLeft.classList.remove('selected');

                    // Überprüfen, ob alle Paare verbunden sind
                    if (document.querySelectorAll('.pair-item:not(.matched)').length === 0) {
                        showFeedback({
                            correct: true,
                            feedback: "Alle Paare sind richtig verbunden!"
                        });
                    }
                } else {
                    selectedLeft.classList.add('incorrect');
                    rightItem.classList.add('incorrect');
                    setTimeout(() => {
                        selectedLeft.classList.remove('incorrect');
                        rightItem.classList.remove('incorrect');
                        selectedLeft.classList.remove('selected');
                    }, 1000);
                }
            }
        });
        rightColumn.appendChild(rightItem);
    });

    pairsContainer.appendChild(leftColumn);
    pairsContainer.appendChild(rightColumn);
    container.appendChild(pairsContainer);
}

// Funktion zum Deaktivieren der Optionen
function disableOptions(container) {
    const buttons = container.querySelectorAll('.option-button');
    buttons.forEach(button => {
        button.classList.add('disabled');
    });
}

function showFeedback(result) {
    const feedbackDiv = document.createElement('div');
    feedbackDiv.className = `feedback ${result.correct ? 'correct' : 'incorrect'}`;

    if (result.correct) {
        feedbackDiv.textContent = result.feedback;
        correctAnswers++;
    } else {
        feedbackDiv.innerHTML = `
            <p>${result.feedback}</p>
            ${currentUnit.exercises[currentExerciseIndex].type === 'translation' ?
            `<p style="margin-top: 0.5rem; font-size: 0.9rem;">Mögliche Antworten: ${currentUnit.exercises[currentExerciseIndex].answer.join(", ")}</p>` : ''}
        `;
    }

    trainerContent.appendChild(feedbackDiv);
    updateProgress();
    updateButtonStates();
}


// Funktion zum Aktualisieren der Button-Zustände
function updateButtonStates() {
    prevBtn.disabled = currentExerciseIndex === 0;
    nextBtn.disabled = false;

    if (currentExerciseIndex === totalQuestions - 1) {
        nextBtn.textContent = 'Fertig';
        nextBtn.addEventListener('click', () => {
            window.location.href = `../html/Progress.html`;
        })
    } else {
        nextBtn.textContent = 'Weiter';
    }
}

// Funktion für die "Zurück"-Schaltfläche
function previousQuestion() {
    if (currentExerciseIndex > 0) {
        currentExerciseIndex--;
        displayExercise(currentExerciseIndex);
    }
}

// Funktion für die "Weiter"-Schaltfläche
function nextQuestion() {
    if (currentExerciseIndex < totalQuestions - 1) {
        currentExerciseIndex++;
        displayExercise(currentExerciseIndex);
    } else {
        // Alle Übungen abgeschlossen
        showCompletionScreen();
    }
}

// Funktion zum Anzeigen des Abschlussbildschirms
function showCompletionScreen() {
    trainerContent.style.display = 'none';
    document.querySelector('.controls').style.display = 'none';
    completionScreen.style.display = 'block';

    const percentage = Math.round((correctAnswers / totalQuestions) * 100);
    finalScoreElement.innerHTML = `
        <p>Sie haben ${correctAnswers} von ${totalQuestions} Fragen richtig beantwortet (${percentage}%).</p>
        <p>${getFeedbackMessage(percentage)}</p>
    `;
}

// Funktion zum Generieren einer Feedback-Nachricht basierend auf der Punktzahl
function getFeedbackMessage(percentage) {
    if (percentage >= 90) {
        return "Ausgezeichnet! Sie haben diese Einheit perfekt gemeistert!";
    } else if (percentage >= 70) {
        return "Gut gemacht! Sie haben diese Einheit gut gemeistert.";
    } else if (percentage >= 50) {
        return "Nicht schlecht! Üben Sie weiter, um sich zu verbessern.";
    } else {
        return "Üben Sie weiter, um Ihre Fähigkeiten zu verbessern.";
    }
}

// Funktion zum Neustarten des Trainers
function restartTrainer() {
    //reload page
    location.reload();
}

// Initialisierung
document.addEventListener('DOMContentLoaded', () => {
    // Beispiel: Unit mit ID 1 laden
    loadUnit(1);
});

// Überprüfungsfunktionen (wie zuvor definiert)
function checkVocabularyExercise(exercise, userAnswerIndex) {
    const correctIndex = exercise.correct;
    const correctOptions = exercise.correct_options || [exercise.options[correctIndex]];
    const userAnswer = exercise.options[userAnswerIndex];

    if (userAnswer === exercise.options[correctIndex]) {
        return {
            correct: true,
            feedback: "Richtig!"
        };
    }

    if (correctOptions.includes(userAnswer)) {
        return {
            correct: true,
            feedback: `Richtig! "${userAnswer}" ist ein Synonym für "${exercise.options[correctIndex]}".`
        };
    }

    return {
        correct: false,
        feedback: `Falsch. Die richtige Antwort ist "${exercise.options[correctIndex]}".`
    };
}

function checkTranslationExercise(exercise, userAnswer) {
    const correctAnswers = exercise.answer;
    const normalizedUserAnswer = userAnswer.trim().toLowerCase();

    // Überprüfen, ob die Benutzerantwort mit einer der korrekten Antworten übereinstimmt
    for (const answer of correctAnswers) {
        if (normalizedUserAnswer === answer.trim().toLowerCase()) {
            return {
                correct: true,
                feedback: "Richtig!"
            };
        }
    }

    // Wenn keine Übereinstimmung gefunden wurde
    return {
        correct: false,
        feedback: `Falsch. Mögliche Antworten sind: ${correctAnswers.join(", ")}`
    };
}

    // Example unit structure - this would be passed to loadUnit()
 async function fetchUnit(unitId) {
     try {
         const response = await fetch(`../php/trainer.php?unit_id=${unitId}`);

         if (!response.ok) {
             throw new Error(`HTTP error! status: ${response.status}`);
         }

         const unit = await response.json();
         console.log('Unit data:', unit);
         return unit;
     } catch (error) {
         console.error('Error fetching unit:', error);
         throw error;
     }
 }

    // Load a unit
 function loadUnit(unit) {
    currentUnit = unit;
     currentExerciseIndex = 0;
     userAnswers = [];
     score = 0;
     correctAnswers = 0;
     totalQuestions = currentUnit.exercises.length;

     updateProgress();
     displayExercise(currentExerciseIndex);
}

function speakText(text, lang) {
    let msg = new SpeechSynthesisUtterance();
    msg.text = text;
    speechSynthesis.speak(msg);
}