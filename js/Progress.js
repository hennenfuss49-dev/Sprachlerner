    // --- STATE ---
    let isLoggedIn = false;
    let currentUsername = '';

    // --- ELEMENTS ---
    const progressSection = document.getElementById('progressSection');
    const unitsList = document.getElementById('units-list');
    const notLoggedIn = document.getElementById('notLoggedIn');
    const profilBtn = document.getElementById('profilBtn');
    const profileModal = document.getElementById('profileModal');
    const profileUsername = document.getElementById('profile-username');
    const closeProfileModal = document.getElementById('closeProfileModal');
    const logoutBtn = document.getElementById('logoutBtn');

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

    // --- LOGIN CHECK ---
    function checkLogin() {
      fetch('../php/auth.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'action=check'
      })
      .then(r => r.json())
      .then(res => {
        isLoggedIn = res.success;
        currentUsername = res.username || '';
        updateDisplay();
        if(isLoggedIn) loadUnits();
      });
    }

    function updateDisplay() {
      if(isLoggedIn) {
        progressSection.style.display = 'block';
        notLoggedIn.style.display = 'none';
        profilBtn.innerHTML = `<i class="fas fa-user"></i> ${currentUsername}`;
      } else {
        progressSection.style.display = 'none';
        notLoggedIn.style.display = 'block';
        profilBtn.innerHTML = `<i class="fas fa-user"></i> Mein Profil`;
      }
    }

    // --- LOAD UNITS ---
// ...existing code...
function loadUnits() {
  fetch('php/get_progress.php')
  .then(r => r.json())
  .then(data => {
    if(data.success && data.units.length) {
      unitsList.innerHTML = data.units.map(unit => `
        <li class="card" style="margin-bottom:1rem;">
          <h3><i class="fas fa-book-open"></i> ${unit.unit_name}</h3>
          <div style="margin: 0.5rem 0 1rem 0;">
            <span style="background: var(--accent-color); color: var(--secondary-color); padding: 0.3em 0.9em; border-radius: 2em; font-weight:bold;">
              Level ${unit.progress_level}
            </span>
          </div>
          <p>Letztes Üben: <strong>${unit.last_practiced ? unit.last_practiced : '-'}</strong></p>
          <div style="margin-top:1rem; height:16px; background:#eee; border-radius:8px; overflow:hidden;">
            <div style="width:${(unit.progress_percent || 0)}%; background:var(--primary-color); height:100%; border-radius:8px; transition:width 0.4s;"></div>
          </div>
          <small>Fortschritt: ${unit.progress_percent || 0}%</small>
        </li>
      `).join('');
    } else {
      unitsList.innerHTML = '<li class="card" style="text-align:center;">Noch keine Fortschritte vorhanden.</li>';
    }
  });
}
// ...existing code...

    // --- MODAL HANDLING ---
    profilBtn.addEventListener('click', (e) => {
      e.preventDefault();
      if(isLoggedIn) {
        profileUsername.textContent = "Benutzername: " + currentUsername;
        profileModal.style.display = 'block';
      } else {
        loginModal.style.display = 'block';
      }
    });
    closeProfileModal.addEventListener('click', () => profileModal.style.display = 'none');
    window.addEventListener('click', e => {
      if (e.target === profileModal) profileModal.style.display = 'none';
      if (e.target === loginModal) loginModal.style.display = 'none';
    });

    // Logout
    logoutBtn.addEventListener('click', () => {
      fetch('../php/auth.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'action=logout'
      })
      .then(r => r.json())
      .then(res => {
        if(res.success){
          isLoggedIn = false;
          currentUsername = '';
          updateDisplay();
          profileModal.style.display = 'none';
        }
      });
    });

    // Login Modal Handling
    if (loginOpenBtn) {
      loginOpenBtn.addEventListener('click', () => {
        loginModal.style.display = 'block';
      });
    }
    closeLoginModal.addEventListener('click', () => loginModal.style.display = 'none');

    // Umschalt Login/Register
    toggleLink.addEventListener('click', (e) => {
      e.preventDefault();
      if (formAction.value === 'login') {
        formTitle.textContent = 'Registrieren';
        formAction.value = 'register';
        toggleText.textContent = 'Schon ein Konto?';
        toggleLink.textContent = 'Einloggen';
      } else {
        formTitle.textContent = 'Login';
        formAction.value = 'login';
        toggleText.textContent = 'Noch kein Konto?';
        toggleLink.textContent = 'Registrieren';
      }
    });

    // Auth-Formular
    authForm.addEventListener('submit', function(e){
      e.preventDefault();
      const data = {
        action: formAction.value,
        email: emailInput.value,
        password: passwordInput.value
      };
      fetch('../php/auth.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: Object.keys(data).map(k=>encodeURIComponent(k)+'='+encodeURIComponent(data[k])).join('&')
      })
      .then(r => r.json())
      .then(res => {
        if(res.success){
          loginModal.style.display = 'none';
          isLoggedIn = true;
          currentUsername = res.username;
          updateDisplay();
          loadUnits();
        }else{
          alert(res.message);
        }
      });
    });

    // Initial
    window.addEventListener('DOMContentLoaded', checkLogin);