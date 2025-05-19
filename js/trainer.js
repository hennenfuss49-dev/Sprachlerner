window.onload = function() {
    // Initialize the trainer
    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);
    const unitId = urlParams.get('unit_id'); // Example unit ID
    fetchUnit(unitId)
        .then(unit => loadUnit(unit))
        .catch(error => console.error('Error loading unit:', error));
}

// Global variables
let currentUnit = null;
let currentQuestionIndex = 0;
let score = 0;
let answered = false;
let userAnswers = [];
let selectedPairs = [];

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
    currentQuestionIndex = 0;
    score = 0;
    userAnswers = [];
    answered = false;
    console.info('Loaded unit:', unit);
    updateProgress();
    updateScore();
    displayQuestion();
    document.getElementById('completion-screen').style.display = 'none';
    document.getElementById('trainer-content').style.display = 'block';
}

    // Display current question
 function displayQuestion() {
    const exercise = currentUnit.exercises[currentQuestionIndex];
    const content = document.getElementById('trainer-content');
    answered = false;
    selectedPairs = [];

    updateNavigationButtons();

    switch(exercise.type) {
    case 'vocabulary':
    content.innerHTML = createVocabularyQuestion(exercise);
    break;
    case 'listening':
    content.innerHTML = createListeningQuestion(exercise);
    break;
    case 'translation':
    content.innerHTML = createTranslationQuestion(exercise);
    break;
    case 'matching':
    content.innerHTML = createMatchingQuestion(exercise);
    break;
    }
}

    // Create vocabulary question
 function createVocabularyQuestion(exercise) {
    return `
                <div class="question-card">
                    <h3>${exercise.question}</h3>
                    ${exercise.image ? `<img src="${exercise.image}" alt="Vocabulary Image" class="question-image">` : ''}
                    <div class="options-container">
                        ${exercise.options.map((option, index) =>
    `<button class="option-button" onclick="selectOption(${index})">${option}</button>`
    ).join('')}
                    </div>
                    <div id="feedback"></div>
                </div>
            `;
}

    // Create listening question
function createListeningQuestion(exercise) {
return `
            <div class="question-card">
                <h3>${exercise.question}</h3>
                ${exercise.audio ? `
                    <div class="question-audio">
                        <audio controls>
                            <source src="${exercise.audio}" type="audio/mpeg">
                            Your browser does not support the audio element.
                        </audio>
                    </div>
                ` : ''}
                <div class="options-container">
                    ${exercise.options.map((option, index) =>
`<button class="option-button" onclick="selectOption(${index})">${option}</button>`
).join('')}
                </div>
                <div id="feedback"></div>
            </div>
        `;
}

// Create translation question
function createTranslationQuestion(exercise) {
return `
            <div class="question-card">
                <h3>${exercise.question}</h3>
                <div class="question-text">${exercise.text}</div>
                <input type="text" id="translation-input" class="input-answer" placeholder="Deine Antwort...">
                <button class="btn" onclick="checkTranslation()">Antwort prüfen</button>
                <div id="feedback"></div>
            </div>
        `;
}

// Create matching question
function createMatchingQuestion(exercise) {
const shuffledLeft = [...exercise.pairs].sort(() => Math.random() - 0.5);
const shuffledRight = [...exercise.pairs].sort(() => Math.random() - 0.5);

return `
            <div class="question-card">
                <h3>${exercise.question}</h3>
                <div class="pairs-container">
                    <div>
                        ${shuffledLeft.map((pair, index) =>
`<div class="pair-item" data-type="left" data-value="${pair.left}" onclick="selectPair(this)">${pair.left}</div>`
).join('')}
                    </div>
                    <div>
                        ${shuffledRight.map((pair, index) =>
`<div class="pair-item" data-type="right" data-value="${pair.right}" onclick="selectPair(this)">${pair.right}</div>`
).join('')}
                    </div>
                </div>
                <div id="feedback"></div>
            </div>
        `;
}

// Handle option selection (vocabulary, listening)
function selectOption(selectedIndex) {
if (answered) return;

const exercise = currentUnit.exercises[currentQuestionIndex];
const options = document.querySelectorAll('.option-button');

options.forEach((option, index) => {
option.classList.add('disabled');
if (index === exercise.correct) {
option.classList.add('correct');
} else if (index === selectedIndex && index !== exercise.correct) {
option.classList.add('incorrect');
}
});

const isCorrect = selectedIndex === exercise.correct;
userAnswers[currentQuestionIndex] = { selected: selectedIndex, correct: isCorrect };

if (isCorrect) {
score++;
showFeedback(true, 'Richtig! 🎉');
} else {
showFeedback(false, `Falsch. Die richtige Antwort ist: ${exercise.options[exercise.correct]}`);
}

answered = true;
updateScore();
updateNavigationButtons();
}

// Check translation answer
function checkTranslation() {
if (answered) return;

const exercise = currentUnit.exercises[currentQuestionIndex];
const userAnswer = document.getElementById('translation-input').value.trim().toLowerCase();
const correctAnswer = exercise.answer.toLowerCase();

const isCorrect = userAnswer === correctAnswer;
userAnswers[currentQuestionIndex] = { answer: userAnswer, correct: isCorrect };

if (isCorrect) {
score++;
showFeedback(true, 'Richtig! 🎉');
} else {
showFeedback(false, `Falsch. Die richtige Antwort ist: ${exercise.answer}`);
}

answered = true;
updateScore();
updateNavigationButtons();
document.getElementById('translation-input').disabled = true;
}

// Handle pair selection (matching)
function selectPair(element) {
if (answered) return;

if (element.classList.contains('matched')) return;

if (selectedPairs.length === 2) {
// Reset previous selection
selectedPairs.forEach(item => {
if (!item.classList.contains('matched')) {
item.classList.remove('selected', 'incorrect');
}
});
selectedPairs = [];
}

element.classList.add('selected');
selectedPairs.push(element);

if (selectedPairs.length === 2) {
checkPairMatch();
}
}

// Check if selected pairs match
function checkPairMatch() {
const exercise = currentUnit.exercises[currentQuestionIndex];
const [first, second] = selectedPairs;

const leftValue = first.dataset.type === 'left' ? first.dataset.value : second.dataset.value;
const rightValue = first.dataset.type === 'right' ? first.dataset.value : second.dataset.value;

const isMatch = exercise.pairs.some(pair =>
pair.left === leftValue && pair.right === rightValue
);

if (isMatch) {
selectedPairs.forEach(item => {
item.classList.remove('selected');
item.classList.add('matched');
});

// Check if all pairs are matched
const allMatched = document.querySelectorAll('.pair-item.matched').length === exercise.pairs.length * 2;
if (allMatched) {
score++;
userAnswers[currentQuestionIndex] = { correct: true };
showFeedback(true, 'Alle Paare richtig verbunden! 🎉');
answered = true;
updateScore();
updateNavigationButtons();
}
} else {
selectedPairs.forEach(item => {
item.classList.remove('selected');
item.classList.add('incorrect');
setTimeout(() => {
item.classList.remove('incorrect');
}, 1000);
});
}

selectedPairs = [];
}

// Show feedback
function showFeedback(isCorrect, message) {
const feedback = document.getElementById('feedback');
feedback.className = `feedback ${isCorrect ? 'correct' : 'incorrect'}`;
feedback.textContent = message;
}

// Navigation functions
function nextQuestion() {
if (currentQuestionIndex < currentUnit.exercises.length - 1) {
currentQuestionIndex++;
updateProgress();
displayQuestion();
} else {
showCompletionScreen();
}
}

function previousQuestion() {
    if (currentQuestionIndex > 0) {
    currentQuestionIndex--;
    updateProgress();
    displayQuestion();
}
}

    // Update navigation buttons
 function updateNavigationButtons() {
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');

    prevBtn.disabled = currentQuestionIndex === 0;
    nextBtn.disabled = !answered;

    if (currentQuestionIndex === currentUnit.exercises.length - 1) {
    nextBtn.textContent = 'Beenden';
} else {
    nextBtn.textContent = 'Weiter';
}
}

    // Update progress display
 function updateProgress() {
    const progress = document.getElementById('progress');
    progress.textContent = `Frage ${currentQuestionIndex + 1} von ${currentUnit.exercises.length}`;
}

    // Update score display
 function updateScore() {
    const scoreElement = document.getElementById('score');
    scoreElement.textContent = `Punkte: ${score}/${currentUnit.exercises.length}`;
}

    // Show completion screen
 function showCompletionScreen() {
    document.getElementById('trainer-content').style.display = 'none';
    document.querySelector('.controls').style.display = 'none';

    const completionScreen = document.getElementById('completion-screen');
    const finalScore = document.getElementById('final-score');

    const percentage = Math.round((score / currentUnit.exercises.length) * 100);
    finalScore.textContent = `Du hast ${score} von ${currentUnit.exercises.length} Fragen richtig beantwortet (${percentage}%)`;

    completionScreen.style.display = 'block';
}

    // Restart trainer
 function restartTrainer() {
    document.querySelector('.controls').style.display = 'flex';
    loadUnit(currentUnit);
}