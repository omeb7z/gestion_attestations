<?php
require_once __DIR__ . '/config/db.php';

$nouveau_mdp = "CAFES17";
$hash = password_hash($nouveau_mdp, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE admins SET mot_de_passe = ? WHERE nom_utilisateur = 'admin'");
$stmt->execute([$hash]);

echo "Hash généré: [" . $hash . "]<br>";
echo "Mis à jour dans la base ✅<br>";

// Vérification immédiate depuis la DB
$stmt2 = $pdo->prepare("SELECT mot_de_passe FROM admins WHERE nom_utilisateur = 'admin'");
$stmt2->execute();
$row = $stmt2->fetch(PDO::FETCH_ASSOC);

echo "Test verify: " . (password_verify($nouveau_mdp, $row['mot_de_passe']) ? "OK ✅" : "ÉCHEC ❌");