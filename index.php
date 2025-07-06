<?php
session_start();

function db_connect() {
    $conn = new mysqli('localhost', 'root', '', 'sito_personale');
    if ($conn->connect_error) {
        die('Errore connessione DB: ' . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

$register_error = '';
$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = db_connect();

    if (isset($_POST['register'])) {
        $username = $conn->real_escape_string(trim($_POST['reg_username']));
        $email = $conn->real_escape_string(trim($_POST['reg_email']));
        $password = $_POST['reg_password'];
        $password_confirm = $_POST['reg_password_confirm'];

        if ($password !== $password_confirm) {
            $register_error = 'Le password non coincidono.';
        } elseif (strlen($username) < 3 || strlen($password) < 6) {
            $register_error = 'Username minimo 3 caratteri, password minimo 6.';
        } else {
            $check = $conn->query("SELECT id FROM users WHERE username='$username' OR email='$email' LIMIT 1");
            if ($check && $check->num_rows > 0) {
                $register_error = 'Username o email già utilizzati.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->bind_param('sss', $username, $email, $hash);
                if ($stmt->execute()) {
                    $_SESSION['username'] = $username;
                    $_SESSION['user_id'] = $stmt->insert_id;
                    $_SESSION['email'] = $email;
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                } else {
                    $register_error = 'Errore nella registrazione, riprova.';
                }
                $stmt->close();
            }
        }
    }

    if (isset($_POST['login'])) {
        $username = $conn->real_escape_string(trim($_POST['login_username']));
        $password = $_POST['login_password'];

        $stmt = $conn->prepare("SELECT id, username, email, password FROM users WHERE username=? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows === 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                $_SESSION['username'] = $row['username'];
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['email'] = $row['email'];
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $login_error = 'Password errata.';
            }
        } else {
            $login_error = 'Utente non trovato.';
        }
        $stmt->close();
    }

    $conn->close();
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>alelavoro - Portfolio & Profilo</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

<style>
:root {
  --accent: #5c6ac4;
  --accent-light: #7e8ff8;
  --bg-dark: #121827;
  --bg-light: #fafafa;
  --text-light: #e0e6f5;
  --text-dark: #2c2f4a;
  --card-bg-dark: #1f2937;
  --card-bg-light: #fff;
  --btn-bg-dark: #4f46e5;
  --btn-bg-light: #6366f1;
  --btn-hover-dark: #4338ca;
  --btn-hover-light: #4f46e5;
  --transition: 0.35s ease;
  --font-family: 'Poppins', sans-serif;
}

* {
  margin: 0; padding: 0; box-sizing: border-box; scroll-behavior: smooth;
}
body {
  font-family: var(--font-family);
  background: var(--bg-dark);
  color: var(--text-light);
  min-height: 100vh;
  padding-bottom: 80px;
  transition: background-color var(--transition), color var(--transition);
}

body.light-theme {
  background: var(--bg-light);
  color: var(--text-dark);
}

/* Header */
header {
  background: var(--card-bg-dark);
  padding: 1.6rem 2.5rem;
  position: sticky;
  top: 0;
  z-index: 1000;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 4px 12px rgb(0 0 0 / 0.5);
  transition: background-color var(--transition);
}

body.light-theme header {
  background: var(--card-bg-light);
  box-shadow: 0 4px 10px rgb(0 0 0 / 0.1);
}

header h1 {
  font-size: 1.9rem;
  font-weight: 700;
  color: var(--accent);
  letter-spacing: 2px;
  user-select: none;
  cursor: default;
}

nav a {
  margin-left: 2rem;
  text-decoration: none;
  font-weight: 600;
  font-size: 1rem;
  color: var(--text-light);
  position: relative;
  padding-bottom: 4px;
  transition: color var(--transition);
  user-select: none;
}
body.light-theme nav a {
  color: var(--text-dark);
}
nav a:hover,
nav a:focus-visible {
  color: var(--accent);
  outline-offset: 3px;
}
nav a::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  height: 2px;
  width: 0;
  background: var(--accent);
  transition: width var(--transition);
}
nav a:hover::after,
nav a:focus-visible::after {
  width: 100%;
}
nav a.logout {
  color: #ef4444;
}
nav a.logout:hover,
nav a.logout:focus-visible {
  color: #dc2626;
}

/* Theme toggle button */
#themeToggle {
  background: transparent;
  border: none;
  color: var(--accent);
  font-size: 1.7rem;
  cursor: pointer;
  margin-left: 1.3rem;
  transition: color var(--transition);
  user-select: none;
}
#themeToggle:hover,
#themeToggle:focus-visible {
  color: var(--accent-light);
  outline-offset: 3px;
}

/* Main content */
main {
  max-width: 900px;
  margin: 5rem auto 3rem;
  padding: 0 2rem;
}

/* Hero section */
.hero {
  text-align: center;
  margin-bottom: 3rem;
  animation: fadeUp 1s ease forwards;
}
.hero h1 {
  font-size: 3rem;
  color: var(--accent);
  font-weight: 800;
  letter-spacing: 3px;
  margin-bottom: 1rem;
  text-shadow: 0 0 6px var(--accent);
  user-select: none;
}
.hero p {
  font-size: 1.25rem;
  font-weight: 500;
  color: var(--text-light);
  max-width: 550px;
  margin: 0 auto;
  user-select: none;
}
body.light-theme .hero p {
  color: var(--text-dark);
}

/* Certifications */
.certificati {
  margin-bottom: 4rem;
  border-top: 3px solid var(--accent);
  padding-top: 2rem;
  user-select: none;
}
.certificati h2 {
  font-size: 2.7rem;
  font-weight: 700;
  color: var(--accent);
  margin-bottom: 1.8rem;
}
.certificati ul {
  list-style: none;
  padding-left: 0;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.2rem 2rem;
}
.certificati li {
  display: flex;
  align-items: center;
  font-weight: 600;
  font-size: 1.15rem;
  color: var(--text-light);
}
body.light-theme .certificati li {
  color: var(--text-dark);
}
.certificati i {
  color: var(--accent);
  font-size: 1.5rem;
  margin-right: 12px;
  min-width: 24px;
}

/* Services */
#servizi {
  margin-bottom: 4rem;
  border-top: 3px solid var(--accent);
  padding-top: 2rem;
  user-select: none;
}
#servizi h2 {
  font-size: 2.7rem;
  font-weight: 700;
  color: var(--accent);
  margin-bottom: 2rem;
  text-align: center;
}
.services-list {
  display: flex;
  gap: 1.8rem;
  justify-content: center;
  flex-wrap: wrap;
}
.service {
  background: var(--card-bg-dark);
  padding: 1.8rem 2rem;
  border-radius: 14px;
  box-shadow: 0 8px 25px rgb(0 0 0 / 0.35);
  max-width: 320px;
  flex: 1 1 280px;
  color: var(--text-light);
  transition: background-color var(--transition), color var(--transition);
  cursor: default;
}
body.light-theme .service {
  background: var(--card-bg-light);
  color: var(--text-dark);
}
.service h3 {
  margin-bottom: 1rem;
  color: var(--accent);
  font-weight: 700;
}
.service p {
  font-weight: 500;
  font-size: 1.05rem;
  line-height: 1.4;
}

/* Projects */
#progetti {
  margin-bottom: 4rem;
  border-top: 3px solid var(--accent);
  padding-top: 2rem;
  user-select: none;
}
#progetti h2 {
  font-size: 2.7rem;
  font-weight: 700;
  color: var(--accent);
  margin-bottom: 2rem;
  text-align: center;
}

.carousel {
  display: flex;
  overflow-x: auto;
  gap: 1.5rem;
  padding-bottom: 12px;
  scroll-padding-left: 2rem;
  scrollbar-width: thin;
  scrollbar-color: var(--accent) transparent;
}
.carousel::-webkit-scrollbar {
  height: 8px;
}
.carousel::-webkit-scrollbar-thumb {
  background-color: var(--accent);
  border-radius: 12px;
}

.project-card {
  background: var(--card-bg-dark);
  border-radius: 16px;
  box-shadow: 0 12px 25px rgb(0 0 0 / 0.4);
  width: 320px;
  flex-shrink: 0;
  overflow: hidden;
  color: var(--text-light);
  display: flex;
  flex-direction: column;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  cursor: default;
}
.project-card:hover,
.project-card:focus-within {
  transform: translateY(-8px);
  box-shadow: 0 20px 35px rgb(0 0 0 / 0.6);
  outline: none;
}
body.light-theme .project-card {
  background: var(--card-bg-light);
  color: var(--text-dark);
}
.project-card img {
  width: 100%;
  height: 180px;
  object-fit: cover;
}
.project-card-content {
  padding: 1.4rem 1.8rem 2rem;
  flex-grow: 1;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}
.project-card-content h3 {
  color: var(--accent);
  margin-bottom: 0.7rem;
  font-weight: 700;
}
.project-card-content p {
  font-weight: 500;
  flex-grow: 1;
  font-size: 1rem;
  margin-bottom: 1.1rem;
}
.project-card-content a {
  background: var(--btn-bg-dark);
  color: var(--bg-dark);
  text-decoration: none;
  padding: 0.55rem 1.4rem;
  font-weight: 600;
  border-radius: 32px;
  width: fit-content;
  transition: background-color var(--transition);
  user-select: none;
}
.project-card-content a:hover,
.project-card-content a:focus-visible {
  background: var(--btn-hover-dark);
  outline-offset: 3px;
}
body.light-theme .project-card-content a {
  background: var(--btn-bg-light);
  color: var(--bg-light);
}
body.light-theme .project-card-content a:hover,
body.light-theme .project-card-content a:focus-visible {
  background: var(--btn-hover-light);
  outline-offset: 3px;
}

/* Contacts */
#contatti {
  margin-bottom: 5rem;
  border-top: 3px solid var(--accent);
  padding-top: 2rem;
  text-align: center;
  user-select: none;
}
#contatti h2 {
  font-size: 2.7rem;
  font-weight: 700;
  color: var(--accent);
  margin-bottom: 1.6rem;
}
#contatti p,
#contatti a {
  font-weight: 600;
  font-size: 1.15rem;
  color: var(--text-light);
}
body.light-theme #contatti p,
body.light-theme #contatti a {
  color: var(--text-dark);
}
#contatti a {
  text-decoration: none;
  margin: 0 0.5rem;
  user-select: text;
}
#contatti a:hover,
#contatti a:focus-visible {
  text-decoration: underline;
  outline-offset: 3px;
}
#contatti i {
  margin-right: 6px;
  color: var(--accent);
}

/* Footer */
footer {
  position: fixed;
  bottom: 0;
  left: 0; right: 0;
  background: var(--card-bg-dark);
  color: var(--text-light);
  text-align: center;
  font-weight: 500;
  font-size: 0.9rem;
  padding: 0.7rem 1rem;
  user-select: none;
  box-shadow: 0 -2px 12px rgb(0 0 0 / 0.7);
  transition: background-color var(--transition), color var(--transition);
  z-index: 999;
}
body.light-theme footer {
  background: var(--card-bg-light);
  color: var(--text-dark);
  box-shadow: 0 -2px 12px rgb(0 0 0 / 0.1);
}

/* Modal Overlay */
.modal-bg {
  position: fixed;
  inset: 0;
  background-color: rgba(18, 24, 39, 0.85);
  backdrop-filter: blur(6px);
  display: none;
  justify-content: center;
  align-items: center;
  z-index: 1500;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.4s ease;
}
body.light-theme .modal-bg {
  background-color: rgba(250, 250, 250, 0.85);
}
.modal-bg.active {
  display: flex;
  opacity: 1;
  pointer-events: auto;
}

/* Modal */
.modal {
  background: var(--card-bg-dark);
  padding: 2.5rem 3rem;
  border-radius: 20px;
  max-width: 420px;
  width: 90%;
  box-shadow: 0 25px 55px rgb(0 0 0 / 0.7);
  color: var(--text-light);
  position: relative;
  transform: translateY(40px);
  opacity: 0;
  transition: transform 0.4s ease, opacity 0.4s ease;
  outline-offset: 4px;
}
body.light-theme .modal {
  background: var(--card-bg-light);
  color: var(--text-dark);
}
.modal-bg.active .modal {
  transform: translateY(0);
  opacity: 1;
}

/* Inputs */
.modal label {
  display: block;
  font-weight: 600;
  margin-bottom: 0.3rem;
  color: var(--accent);
  user-select: none;
}
.modal input[type="text"],
.modal input[type="email"],
.modal input[type="password"] {
  width: 100%;
  padding: 0.65rem 1rem;
  font-size: 1.05rem;
  border-radius: 10px;
  border: 2px solid transparent;
  background: var(--card-bg-light);
  color: var(--text-dark);
  transition: border-color var(--transition), box-shadow var(--transition);
  margin-bottom: 1.25rem;
  font-family: var(--font-family);
  outline-offset: 3px;
}
body:not(.light-theme) .modal input[type="text"],
body:not(.light-theme) .modal input[type="email"],
body:not(.light-theme) .modal input[type="password"] {
  background: var(--card-bg-dark);
  color: var(--text-light);
}
.modal input[type="text"]:focus,
.modal input[type="email"]:focus,
.modal input[type="password"]:focus {
  outline: none;
  box-shadow: 0 0 12px var(--accent);
  border-color: var(--accent);
}

/* Submit Button */
.modal button[type="submit"] {
  background: var(--btn-bg-dark);
  color: var(--bg-dark);
  border: none;
  padding: 0.85rem 1.6rem;
  border-radius: 30px;
  font-weight: 700;
  font-size: 1.1rem;
  cursor: pointer;
  width: 100%;
  user-select: none;
  transition: background-color var(--transition);
}
.modal button[type="submit"]:hover,
.modal button[type="submit"]:focus-visible {
  background: var(--btn-hover-dark);
  outline-offset: 3px;
}
body.light-theme .modal button[type="submit"] {
  background: var(--btn-bg-light);
  color: var(--bg-light);
}
body.light-theme .modal button[type="submit"]:hover,
body.light-theme .modal button[type="submit"]:focus-visible {
  background: var(--btn-hover-light);
}

/* Close Button */
.close-btn {
  position: absolute;
  top: 18px;
  right: 20px;
  background: transparent;
  border: none;
  font-size: 2.4rem;
  line-height: 1;
  color: var(--accent);
  cursor: pointer;
  user-select: none;
  transition: color var(--transition);
}
.close-btn:hover,
.close-btn:focus-visible {
  color: var(--accent-light);
  outline-offset: 3px;
}

/* Error messages */
.error {
  background: #ef4444;
  color: white;
  padding: 0.45rem 0.9rem;
  border-radius: 8px;
  font-size: 0.95rem;
  margin-bottom: 1rem;
  user-select: none;
}

/* Profile Info inside modal */
.profile-info {
  font-size: 1rem;
  line-height: 1.4;
  margin-top: 0.6rem;
  user-select: text;
}
.profile-info h4 {
  font-weight: 700;
  color: var(--accent);
  margin-bottom: 0.6rem;
}
.profile-info p {
  margin-bottom: 0.7rem;
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 500;
}
.profile-info i {
  color: var(--accent);
  font-size: 1.25rem;
}

/* Animations */
@keyframes fadeUp {
  0% {
    opacity: 0;
    transform: translateY(30px);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive */
@media (max-width: 520px) {
  header {
    padding: 1rem 1.5rem;
  }
  nav a {
    margin-left: 1rem;
    font-size: 0.9rem;
  }
  main {
    margin: 3rem 1rem 3rem;
    padding: 0;
  }
  .hero h1 {
    font-size: 2.3rem;
  }
  .certificati ul {
    grid-template-columns: 1fr;
    gap: 1rem 0;
  }
  .services-list {
    flex-direction: column;
    gap: 1.2rem;
  }
  .service {
    max-width: 100%;
  }
  .carousel {
    overflow-x: scroll;
    scroll-padding-left: 1rem;
  }
  .project-card {
    width: 280px;
  }
}
</style>
</head>
<body>

<header>
  <h1 aria-label="Logo alelavoro">AleLavoro</h1>
  <nav role="navigation" aria-label="Menu principale">
    <a href="#chi-sono" tabindex="0">Chi sono</a>
    <a href="#certificazioni" tabindex="0">Certificazioni</a>
    <a href="#servizi" tabindex="0">Servizi</a>
    <a href="#progetti" tabindex="0">Progetti</a>
    <a href="#contatti" tabindex="0">Contatti</a>

    <?php if(isset($_SESSION['username'])): ?>
      <a href="#" id="openProfile" aria-haspopup="dialog" aria-controls="profileModal" aria-expanded="false" tabindex="0" aria-label="Apri profilo utente"><?=htmlspecialchars($_SESSION['username'])?></a>
      <a href="?logout" class="logout" tabindex="0" aria-label="Logout">Logout</a>
    <?php else: ?>
      <a href="#" id="openLogin" aria-haspopup="dialog" aria-controls="loginModal" aria-expanded="false" tabindex="0">Login</a>
      <a href="#" id="openRegister" aria-haspopup="dialog" aria-controls="registerModal" aria-expanded="false" tabindex="0">Registrati</a>
    <?php endif; ?>

    <button id="themeToggle" aria-label="Cambia tema: attualmente scuro" aria-pressed="false" title="Cambia tema" tabindex="0">
      <i id="themeIcon" class="fa-solid fa-moon"></i>
    </button>
  </nav>
</header>

<main>
<section class="hero" id="chi-sono" tabindex="-1" aria-label="Chi sono">
  <h1>Ciao, sono AleLavoro</h1>
  <p>Diplomato in informatica con passioni per la programmazione, sviluppo web e soluzioni tecnologiche moderne. Amo creare progetti dinamici e di impatto.</p>
</section>

<section class="certificati" id="certificazioni" tabindex="-1" aria-label="Certificazioni">
  <h2>Certificazioni</h2>
  <ul>
    <li><i class="fa-solid fa-certificate" aria-hidden="true"></i> <strong>ICDL Base</strong> – Competenze informatiche fondamentali</li>
    <li><i class="fa-solid fa-certificate" aria-hidden="true"></i> <strong>ICDL Standard</strong> – Competenze avanzate informatiche</li>
    <li><i class="fa-solid fa-network-wired" aria-hidden="true"></i> <strong>Cisco CCNA</strong> – Reti e infrastrutture di rete</li>
  </ul>
</section>

<section id="servizi" tabindex="-1" aria-label="Servizi offerti">
  <h2>Servizi</h2>
  <div class="services-list" role="list">
    <div class="service" role="listitem" tabindex="0">
      <h3>Sviluppo Web</h3>
      <p>Creazione siti web dinamici e responsive con PHP, MySQL, JavaScript e CSS.</p>
    </div>
    <div class="service" role="listitem" tabindex="0">
      <h3>Networking</h3>
      <p>Configurazione reti locali, gestione infrastrutture Cisco, sicurezza di rete e troubleshooting.</p>
    </div>
    <div class="service" role="listitem" tabindex="0">
      <h3>Consulenza IT</h3>
      <p>Supporto tecnologico personalizzato per aziende e privati, analisi e ottimizzazione sistemi.</p>
    </div>
  </div>
</section>

<section id="progetti" tabindex="-1" aria-label="Progetti recenti">
  <h2>Progetti</h2>
  <div class="carousel" role="list" tabindex="0" aria-label="Lista progetti recenti">
    
    <!-- Progetto Portfolio -->
    <article class="project-card" role="listitem" tabindex="0">
      <img src="immagini_prodotti/Portfolio1.png" alt="Screenshot progetto gestione portfolio" />
      <div class="project-card-content">
        <h3>Gestione Portfolio</h3>
        <p>Sito dinamico per mostrare progetti personali con autenticazione e tema dark/light.</p>
        <a href="https://it.fiverr.com/alelavoro0201?public_mode=true" target="_blank" rel="noopener noreferrer">Vedi su Fiverr</a>
      </div>
    </article>

    <!-- Progetto Ristorante -->
    <article class="project-card" role="listitem" tabindex="0">
      <img src="immagini_prodotti/SitoMenùristorante.png" alt="Screenshot sito ristorante moderno" />
      <div class="project-card-content">
        <h3>Template Ristorante</h3>
        <p>Design elegante per ristoranti, include menù animato, prenotazioni e layout responsive.</p>
        <a href="https://it.fiverr.com/alelavoro0201?public_mode=true" target="_blank" rel="noopener noreferrer">Vedi su Fiverr</a>
      </div>
    </article>

  </div>
</section>

<section id="contatti" tabindex="-1" aria-label="Contatti">
  <h2>Contatti</h2>
  <p>Puoi contattarmi via email o sui miei profili social:</p>
  <p><a href="mailto:alelavoro0201@gmail.com" aria-label="Email di Alessio Polimeni">alelavoro0201@gmail.com</a></p>
  <p>
    <a href="https://github.com/alelavoro" target="_blank" rel="noopener noreferrer" aria-label="Profilo GitHub Alessio Polimeni">
      <i class="fa-brands fa-github"></i> GitHub
    </a> | 
    <a href="https://it.fiverr.com/alelavoro0201?public_mode=true" target="_blank" rel="noopener noreferrer" aria-label="Profilo Fiverr Alessio Polimeni">
      <i class="fa-brands fa-fiverr"></i> Fiverr
    </a>
  </p>
</section>
</main>

<footer>
  &copy; 2025 AleLavoro. Tutti i diritti riservati.
</footer>

<!-- MODALI -->

<!-- Login Modal -->
<div class="modal-bg" id="loginModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="loginTitle" tabindex="-1">
  <div class="modal" role="document" tabindex="0">
    <button class="close-btn" aria-label="Chiudi login">&times;</button>
    <h3 id="loginTitle">Accedi al tuo account</h3>
    <?php if($login_error): ?>
      <p class="error" role="alert" tabindex="0"><?=htmlspecialchars($login_error)?></p>
    <?php endif; ?>
    <form method="POST" novalidate>
      <label for="login_username">Username</label>
      <input id="login_username" type="text" name="login_username" required autocomplete="username" />
      <label for="login_password">Password</label>
      <input id="login_password" type="password" name="login_password" required autocomplete="current-password" />
      <button type="submit" name="login">Login</button>
    </form>
  </div>
</div>

<!-- Register Modal -->
<div class="modal-bg" id="registerModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="registerTitle" tabindex="-1">
  <div class="modal" role="document" tabindex="0">
    <button class="close-btn" aria-label="Chiudi registrazione">&times;</button>
    <h3 id="registerTitle">Crea un nuovo account</h3>
    <?php if($register_error): ?>
      <p class="error" role="alert" tabindex="0"><?=htmlspecialchars($register_error)?></p>
    <?php endif; ?>
    <form method="POST" novalidate>
      <label for="reg_username">Username</label>
      <input id="reg_username" type="text" name="reg_username" required autocomplete="username" />
      <label for="reg_email">Email</label>
      <input id="reg_email" type="email" name="reg_email" required autocomplete="email" />
      <label for="reg_password">Password</label>
      <input id="reg_password" type="password" name="reg_password" required autocomplete="new-password" />
      <label for="reg_password_confirm">Conferma Password</label>
      <input id="reg_password_confirm" type="password" name="reg_password_confirm" required autocomplete="new-password" />
      <button type="submit" name="register">Registrati</button>
    </form>
  </div>
</div>

<!-- Profile Modal -->
<?php if(isset($_SESSION['username'])): ?>
<div class="modal-bg" id="profileModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="profileTitle" tabindex="-1">
  <div class="modal" role="document" tabindex="0">
    <button class="close-btn" aria-label="Chiudi profilo">&times;</button>
    <h3 id="profileTitle">Profilo utente</h3>
    <div class="profile-info">
      <h4><?=htmlspecialchars($_SESSION['username'])?></h4>
      <p><i class="fa-solid fa-envelope" aria-hidden="true"></i> <?=htmlspecialchars($_SESSION['email'] ?? 'email non disponibile')?></p>
      <p>ID utente: <?=htmlspecialchars($_SESSION['user_id'])?></p>
      <hr style="border-color: var(--accent); margin: 1rem 0;">
      <p>Benvenuto nel tuo profilo personale.</p>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
(() => {
  const themeToggleBtn = document.getElementById('themeToggle');
  const themeIcon = document.getElementById('themeIcon');
  const bodyEl = document.body;

  // Set initial theme from localStorage
  const savedTheme = localStorage.getItem('theme');
  if(savedTheme === 'light') {
    bodyEl.classList.add('light-theme');
    themeIcon.classList.replace('fa-moon', 'fa-sun');
    themeToggleBtn.setAttribute('aria-pressed', 'true');
    themeToggleBtn.setAttribute('aria-label', 'Cambia tema: attualmente chiaro');
  } else {
    themeIcon.classList.replace('fa-sun', 'fa-moon');
    themeToggleBtn.setAttribute('aria-pressed', 'false');
    themeToggleBtn.setAttribute('aria-label', 'Cambia tema: attualmente scuro');
  }

  themeToggleBtn.addEventListener('click', () => {
    const isLight = bodyEl.classList.toggle('light-theme');
    if (isLight) {
      themeIcon.classList.replace('fa-moon', 'fa-sun');
      themeToggleBtn.setAttribute('aria-pressed', 'true');
      themeToggleBtn.setAttribute('aria-label', 'Cambia tema: attualmente chiaro');
      localStorage.setItem('theme', 'light');
    } else {
      themeIcon.classList.replace('fa-sun', 'fa-moon');
      themeToggleBtn.setAttribute('aria-pressed', 'false');
      themeToggleBtn.setAttribute('aria-label', 'Cambia tema: attualmente scuro');
      localStorage.setItem('theme', 'dark');
    }
  });

  // Modal open/close helpers
  function openModal(modal) {
    if (!modal) return;
    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
    // Focus first input or close button
    const focusEl = modal.querySelector('input, button.close-btn, form button[type="submit"], a');
    if (focusEl) focusEl.focus();
    document.body.style.overflow = 'hidden'; // blocca scroll body
  }
  function closeModal(modal) {
    if (!modal) return;
    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = ''; // sblocca scroll body
  }

  // Open modal buttons
  document.getElementById('openLogin')?.addEventListener('click', e => {
    e.preventDefault();
    openModal(document.getElementById('loginModal'));
  });
  document.getElementById('openRegister')?.addEventListener('click', e => {
    e.preventDefault();
    openModal(document.getElementById('registerModal'));
  });
  document.getElementById('openProfile')?.addEventListener('click', e => {
    e.preventDefault();
    openModal(document.getElementById('profileModal'));
  });

  // Close modal buttons
  document.querySelectorAll('.modal .close-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      closeModal(btn.closest('.modal-bg'));
    });
  });

  // Close modal on background click
  document.querySelectorAll('.modal-bg').forEach(bg => {
    bg.addEventListener('click', e => {
      if(e.target === bg) closeModal(bg);
    });
  });

  // Close modal on Escape key
  document.addEventListener('keydown', e => {
    if(e.key === 'Escape') {
      document.querySelectorAll('.modal-bg.active').forEach(modal => closeModal(modal));
    }
  });
})();
</script>

</body>
</html>