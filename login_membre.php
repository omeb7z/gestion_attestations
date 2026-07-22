<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/config/db.php';

if (isset($_SESSION['membre_id'])) {
    header("Location: demande_attestation.php");
    exit();
}

$erreur = "";

if (empty($_SESSION['csrf_token_membre'])) {
    $_SESSION['csrf_token_membre'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['tentatives_membre'])) {
    $_SESSION['tentatives_membre'] = 0;
    $_SESSION['dernier_echec_membre'] = 0;
}
$max_tentatives = 5;
$duree_blocage = 120;
$temps_ecoule = time() - $_SESSION['dernier_echec_membre'];
$bloque = ($_SESSION['tentatives_membre'] >= $max_tentatives && $temps_ecoule < $duree_blocage);

if ($bloque) {
    $temps_restant = $duree_blocage - $temps_ecoule;
    $erreur = "عدد كبير من المحاولات الفاشلة. أعد المحاولة بعد " . ceil($temps_restant / 60) . " دقيقة.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$bloque) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token_membre']) {
        die("طلب غير صالح.");
    }

    $nom_utilisateur = trim($_POST['nom_utilisateur'] ?? '');
    $mot_de_passe = trim($_POST['mot_de_passe'] ?? '');

    if ($nom_utilisateur === '' || $mot_de_passe === '') {
        $erreur = "يرجى ملء جميع الحقول.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM membres WHERE nom_utilisateur = ?");
        $stmt->execute([$nom_utilisateur]);
        $membre = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($membre && password_verify($mot_de_passe, $membre['mot_de_passe'])) {
            $_SESSION['tentatives_membre'] = 0;
            $_SESSION['membre_id'] = $membre['id'];
            $_SESSION['membre_nom'] = $membre['nom_complet'];
            $_SESSION['membre_email'] = $membre['email'];
            header("Location: demande_attestation.php");
            exit();
        } else {
            $_SESSION['tentatives_membre']++;
            $_SESSION['dernier_echec_membre'] = time();
            $erreur = "اسم المستخدم أو كلمة المرور غير صحيحة.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تسجيل الدخول - عضو</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/theme.css" rel="stylesheet">
    <script src="assets/js/theme.js" defer></script>
    <style>
        body {
            background: linear-gradient(135deg, #0ea5e9, #38bdf8);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Tahoma', 'Segoe UI', sans-serif;
            padding: 20px;
        }
        .panel-formulaire { max-width: 420px; width: 100%; }
        .panel-formulaire .panel-body { padding: 35px; }
        .logo-haut { text-align: center; margin-bottom: 15px; }
        .logo-haut img { width: 75px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: bold; color: #333; }
        .lien-bas { text-align: center; margin-top: 15px; }
        .lien-bas a { color: #0ea5e9; text-decoration: none; }
    </style>
</head>
<body>

<div class="panel panel-default panel-formulaire">
    <div class="panel-body">
        <div class="logo-haut">
            <img src="assets/img/logo-maroc.png" alt="المملكة المغربية">
        </div>
        <h2 class="text-center" style="color:#0ea5e9;">تسجيل الدخول</h2>
        <p class="text-center text-muted" style="margin-bottom:20px;">فضاء الأعضاء</p>

        <?php if ($erreur): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <form method="POST" action="login_membre.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token_membre']) ?>">
            <div class="form-group">
                <label>اسم المستخدم</label>
                <input type="text" name="nom_utilisateur" class="form-control" required <?= $bloque ? 'disabled' : '' ?>>
            </div>
            <div class="form-group">
                <label>كلمة المرور</label>
                <input type="password" name="mot_de_passe" class="form-control" required <?= $bloque ? 'disabled' : '' ?>>
            </div>
            <button type="submit" class="btn btn-lg btn-block" style="background:#0ea5e9; color:white;" <?= $bloque ? 'disabled' : '' ?>>
                تسجيل الدخول
            </button>
        </form>

        <div class="lien-bas">
            ما عندكش حساب؟ <a href="inscription_membre.php">إنشاء حساب جديد</a>
        </div>
    </div>
</div>

</body>
</html>