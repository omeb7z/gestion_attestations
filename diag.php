<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
echo "Étape 1 OK<br>";

session_start();
echo "Étape 2 OK (session)<br>";

require_once __DIR__ . '/config/db.php';
echo "Étape 3 OK (db.php inclus)<br>";

echo "Connexion DB: OK<br>";