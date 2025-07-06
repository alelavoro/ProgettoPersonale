<?php
session_start();
require_once "config.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $password_confirm = $_POST["password_confirm"];

    if (!$username || !$email || !$password || !$password_confirm) {
        $message = "Compila tutti i campi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Email non valida.";
    } elseif ($password !== $password_confirm) {
        $message = "Le password non coincidono.";
    } else {
        // Controlla se esiste già username o email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $message = "Username o email già usati.";
        } else {
            // Hash password e inserisci
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            if ($stmt->execute([$username, $email, $hash])) {
                $_SESSION['user'] = $username;
                header("Location: dashboard.php");
                exit;
            } else {
                $message = "Errore durante la registrazione.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Registrazione</title>
<style>
  body { font-family: Arial, sans-serif; background: #f0f2f5; padding: 2rem; }
  form { background: white; max-width: 400px; margin: auto; padding: 2rem; border-radius: 8px; box-shadow: 0 0 10px #aaa;}
  input, button { width: 100%; padding: 0.8rem; margin: 0.5rem 0; font-size: 1rem; border-radius: 5px; border: 1px solid #ccc;}
  button { background: #3b82f6; color: white; border: none; cursor: pointer; }
  button:hover { background: #2563eb; }
  .error { color: red; margin-bottom: 1rem; }
  a { display: block; text-align: center; margin-top: 1rem; color: #3b82f6; text-decoration: none;}
</style>
</head>
<body>
  <h2 style="text-align:center;">Registrazione</h2>
  <?php if ($message): ?>
    <div class="error"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>
  <form method="post" action="">
    <input type="text" name="username" placeholder="Username" required autofocus />
    <input type="email" name="email" placeholder="Email" required />
    <input type="password" name="password" placeholder="Password" required />
    <input type="password" name="password_confirm" placeholder="Conferma Password" required />
    <button type="submit">Registrati</button>
  </form>
  <a href="login.php">Hai già un account? Accedi</a>
</body>
</html>