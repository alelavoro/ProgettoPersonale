<?php
// config.php
$host = "localhost";
$dbname = "sito_personale";
$user = "root"; // modifica se necessario
$pass = "";     // modifica se necessario

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Errore connessione DB: " . $e->getMessage());
}
?>