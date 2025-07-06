<?php
session_start();
require_once "config.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    if (!$username || !$password) {
        $message = "Inserisci username e password.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = $user['username'];
            header("Location: dashboard.php");
            exit;
        } else {
            $message = "Username o password errati.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Login</title>
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
  <h2 style="text-align:center;">Login</h2>
  <?php if ($message): ?>
    <div class="error"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>
  <form method="post" action="">
    <input type="text" name="username" placeholder="Username" required autofocus />
    <input type="password" name="password" placeholder="Password" required />
    <button type="submit">Accedi</button>
  </form>
  <a href="register.php">Non hai un account? Registrati</a>
</body>
</html>