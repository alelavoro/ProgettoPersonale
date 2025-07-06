<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Dashboard</title>
<style>
  body { font-family: Arial, sans-serif; padding: 2rem; background: #f0f2f5; }
  .container { max-width: 600px; margin: auto; background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 0 10px #aaa; }
  a { text-decoration: none; color: #3b82f6; font-weight: 600; }
  a:hover { text-decoration: underline; }
</style>
</head>
<body>
  <div class="container">
    <h1>Ciao, <?= htmlspecialchars($_SESSION['user']) ?>!</h1>
    <p>Benvenuto nell'area riservata.</p>
    <p><a href="logout.php">Esci</a></p>
  </div>
</body>
</html>