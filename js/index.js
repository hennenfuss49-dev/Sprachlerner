// Modal-Elemente
const profilBtn = document.querySelector('#meinProfil');
const modal = document.getElementById('loginModal');
const closeBtn = document.getElementById('closeModal');

const formTitle = document.getElementById('form-title');
const formAction = document.getElementById('form-action');
const regUsername = document.getElementById('reg-username');
const emailInput = document.getElementById('email');
const passwordInput = document.getElementById('password');
const submitBtn = document.querySelector('#auth-form button');
const toggleText = document.getElementById('toggle-text');
const toggleLink = document.getElementById('toggle-link');
const authForm = document.getElementById('auth-form');

// Modal öffnen bei Klick auf "Mein Profil"
profilBtn.addEventListener('click', async (e) => {

    const response = await fetch('../php/checkLogin.php')
    const data = await response.json();
    if(data.success) window.location.href = '../html/profile.html';
    else modal.style.display = 'block';
});

// Modal schließen
closeBtn.addEventListener('click', () => {
    modal.style.display = 'none';
});

window.addEventListener('click', (e) => {
    if (e.target === modal) {
    modal.style.display = 'none';
    }
});

// Umschalten zwischen Login und Registrierung
toggleLink.addEventListener('click', (e) => {
    e.preventDefault();
    if (formAction.value === 'login') {
    formTitle.textContent = 'Registrieren';
    formAction.value = 'register';
    submitBtn.textContent = 'Registrieren';
    toggleText.textContent = 'Schon ein Konto?';
    toggleLink.textContent = 'Einloggen';
    regUsername.style.display = 'block';
    } else {
    formTitle.textContent = 'Login';
    formAction.value = 'login';
    submitBtn.textContent = 'Einloggen';
    toggleText.textContent = 'Noch kein Konto?';
    toggleLink.textContent = 'Registrieren';
    regUsername.style.display = 'none';
    }
});

// AJAX für Login/Registrierung
authForm.addEventListener('submit', function(e){
    e.preventDefault();
    const data = {
    action: formAction.value,
    email: emailInput.value,
    password: passwordInput.value,
    username: regUsername.value // nur bei Registrierung nötig
    };
    fetch('../php/auth.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: Object.keys(data).map(k=>encodeURIComponent(k)+'='+encodeURIComponent(data[k])).join('&')
    })
    .then(r => r.json())
    .then(res => {
    if(res.success){
        modal.style.display = 'none';
        document.getElementById('meinProfil').innerHTML = '<i class="fas fa-user"></i> ' + (res.username || 'Mein Profil');
        // Fortschritt laden und anzeigen, wenn eingeloggt
        document.getElementById('progressSection').style.display = 'block';
        loadProgress();
    }else{
        alert(res.message);
    }
    });
});

// Profilbild im Modal anzeigen
function showProfileModal(username, avatar) {
  document.getElementById('profile-username').innerHTML = `<strong>${username}</strong>`;
  let img = document.getElementById('profile-avatar-img');
  if (!img) {
    img = document.createElement('img');
    img.id = 'profile-avatar-img';
    img.style.width = '100px';
    img.style.height = '100px';
    img.style.borderRadius = '50%';
    img.style.objectFit = 'cover';
    img.style.margin = '1em auto';
    document.getElementById('profile-username').before(img);
  }
  img.src = avatar || '../html/audio/ProfilBild-Loewe.jpg';
}

// Beim Öffnen des Modals Profilbild und Name laden
// (Kein redeclare von profilBtn, sondern reuse)
if(window.showProfileModalInit !== true) {
  window.showProfileModalInit = true;
  profilBtn.addEventListener('click', async (e) => {
    e.preventDefault();
    const response = await fetch('php/profile_info.php');
    const data = await response.json();
    if(data.success) {
      showProfileModal(data.username, data.avatar);
      document.getElementById('profileModal').style.display = 'block';
    } else {
      window.location.href = 'html/Login.html';
    }
  });
}
// Modal schließen
const closeProfileModal = document.getElementById('closeProfileModal');
if(closeProfileModal) closeProfileModal.onclick = () => document.getElementById('profileModal').style.display = 'none';
// Logout
const logoutBtn = document.getElementById('logoutBtn');
if(logoutBtn) logoutBtn.onclick = function() {
  fetch('php/auth.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: 'action=logout'
  })
  .then(r => r.json())
  .then(() => window.location.href = 'Index.html');
};