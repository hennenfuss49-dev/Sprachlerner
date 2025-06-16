window.addEventListener('DOMContentLoaded', function() {
    const progressSection = document.getElementById('progressSection');
    const unitsList = document.getElementById('units-list');
    const notLoggedIn = document.getElementById('notLoggedIn');
    const profilBtn = document.getElementById('meinProfil');
    const loginModal = document.getElementById('loginModal');
    const closeLoginModal = document.getElementById('closeLoginModal');
    const loginOpenBtn = document.getElementById('loginOpenBtn');
    const authForm = document.getElementById('auth-form');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const formAction = document.getElementById('form-action');
    const formTitle = document.getElementById('form-title');
    const toggleText = document.getElementById('toggle-text');
    const toggleLink = document.getElementById('toggle-link');
    const usernameInput = document.getElementById('username');
    const togglePassword = document.getElementById('togglePassword');

    let currentUsername = '';

    // Check Login
    function checkLogin() {
        fetch('../php/auth.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: 'action=check'
        })
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                currentUsername = res.username;
                progressSection.style.display = 'block';
                notLoggedIn.style.display = 'none';
                loadUnits();
            } else {
                progressSection.style.display = 'none';
                notLoggedIn.style.display = 'block';
            }
        });
    }

function loadUnits() {
    fetch('../php/get_progress.php')
    .then(r => r.json())
    .then(data => {
        if(data.success && data.units.length) {
            unitsList.innerHTML = data.units.map((unit, idx) => `
                <li class="card unit-item" data-unit-id="${unit.unit_id}" style="margin-bottom:1rem; cursor:pointer;">
                    <h3><i class="fas fa-book-open"></i> ${unit.unit_name}</h3>
                    <div>${unit.description || ''}</div>
                    <div style="margin-top:0.5em;">
                        <strong>Level:</strong> ${unit.progress_level ?? 0}
                        <br>
                        <strong>Letztes Üben:</strong> ${unit.last_practiced ? unit.last_practiced : '-'}
                    </div>
                </li>
            `).join('');
            // Klick-Event für ALLE Units
            const allUnits = unitsList.querySelectorAll('.unit-item');
            allUnits.forEach(unitElem => {
                unitElem.addEventListener('click', function() {
                    const unitId = this.getAttribute('data-unit-id');
                    window.location.href = `../html/Trainer.html?unit_id=${unitId}`;
                });
            });
        } else {
            unitsList.innerHTML = '<li class="card" style="text-align:center;">Noch keine Fortschritte vorhanden.</li>';
        }
    });
}

// Modal öffnen bei Klick auf "Mein Profil"
    profilBtn.addEventListener('click', async (e) => {

        const response = await fetch('../php/checkLogin.php')
        const data = await response.json();
        if(data.success) window.location.href = '../html/Profile.html';
        else modal.style.display = 'block';
    });
    if (loginOpenBtn) {
        loginOpenBtn.addEventListener('click', () => loginModal.style.display = 'block');
    }
    closeLoginModal.addEventListener('click', () => loginModal.style.display = 'none');
    window.addEventListener('click', e => {
        if (e.target === loginModal) loginModal.style.display = 'none';
    });

    // Umschalten Login/Register
    toggleLink.addEventListener('click', (e) => {
        e.preventDefault();
        if (formAction.value === 'login') {
            formTitle.textContent = 'Registrieren';
            formAction.value = 'register';
            toggleText.textContent = 'Schon ein Konto?';
            toggleLink.textContent = 'Einloggen';
            usernameInput.style.display = 'block';
            usernameInput.required = true;
            usernameInput.value = '';
            authForm.querySelector('button').textContent = 'Registrieren';
        } else {
            formTitle.textContent = 'Login';
            formAction.value = 'login';
            toggleText.textContent = 'Noch kein Konto?';
            toggleLink.textContent = 'Registrieren';
            usernameInput.style.display = 'none';
            usernameInput.required = false;
            authForm.querySelector('button').textContent = 'Einloggen';
        }
    });

    // Passwort anzeigen/ausblenden
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function () {
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
            this.className = type === 'password'
                ? 'fas fa-eye'
                : 'fas fa-eye-slash';
        });
    }

    // Auth-Formular
    authForm.addEventListener('submit', function(e){
        e.preventDefault();
        const data = {
            action: formAction.value,
            email: emailInput.value,
            password: passwordInput.value
        };
        if(formAction.value === 'register') {
            data.username = usernameInput.value;
        }
        fetch('../php/auth.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: Object.keys(data).map(k=>encodeURIComponent(k)+'='+encodeURIComponent(data[k])).join('&')
        })
        .then(r => r.json())
        .then(res => {
            if(res.success){
                loginModal.style.display = 'none';
                progressSection.style.display = 'block';
                notLoggedIn.style.display = 'none';
                currentUsername = res.username || '';
                loadUnits();
            }else{
                alert(res.message);
            }
        });
    });

    checkLogin();
});