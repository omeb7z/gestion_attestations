<?php
session_start();
 
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
 
require_once __DIR__ . '/config/db.php';
 
$erreur = "";
 
if (!isset($_SESSION['tentatives'])) {
    $_SESSION['tentatives'] = 0;
    $_SESSION['dernier_echec'] = 0;
}
 
$max_tentatives = 5;
$duree_blocage = 120;
 
$temps_ecoule = time() - $_SESSION['dernier_echec'];
$bloque = ($_SESSION['tentatives'] >= $max_tentatives && $temps_ecoule < $duree_blocage);
 
if ($bloque) {
    $temps_restant = $duree_blocage - $temps_ecoule;
    $erreur = "عدد كبير من المحاولات الفاشلة. أعد المحاولة بعد " . ceil($temps_restant / 60) . " دقيقة.";
}
 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$bloque) {
 
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("طلب غير صالح (رمز الحماية مفقود أو غير صحيح).");
    }
 
    $nom_utilisateur = trim($_POST['nom_utilisateur'] ?? '');
    $mot_de_passe = trim($_POST['mot_de_passe'] ?? '');
 
    if ($nom_utilisateur === '' || $mot_de_passe === '') {
        $erreur = "يرجى ملء جميع الحقول.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE nom_utilisateur = ?");
        $stmt->execute([$nom_utilisateur]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
 
        if ($admin && password_verify($mot_de_passe, $admin['mot_de_passe'])) {
            $_SESSION['tentatives'] = 0;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_nom'] = $admin['nom_complet'];
            header("Location: dashboard.php");
            exit();
        } else {
            $_SESSION['tentatives']++;
            $_SESSION['dernier_echec'] = time();
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
    <title>تسجيل الدخول - محكمة الاستئناف بفاس</title>
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
        }
        .login-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .logo-maroc {
            width: 90px;
            height: auto;
            background: transparent;
            filter: drop-shadow(0 4px 10px rgba(0,0,0,0.2));
        }
        .login-panel {
            max-width: 420px;
            width: 100%;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            border: none;
        }
        .login-panel .panel-body { padding: 40px; }
        .btn-vert {
            background: #0ea5e9;
            color: white;
            width: 100%;
            padding: 14px;
        }
        .btn-vert:hover { background: #38bdf8; color: white; }
    </style>
</head>
<body>
 
    <div class="login-wrapper">
 
        <div class="panel panel-default login-panel">
            <div class="panel-body">
                <div class="text-center" style="margin-bottom:15px;">
                    <img src="assets/img/logo-maroc.png" alt="المملكة المغربية" class="logo-maroc">
                </div>
                <h2 class="text-center" style="color:#0ea5e9; font-weight:bold; margin-bottom:5px;">محكمة الاستئناف بفاس</h2>
                <p class="text-center text-muted" style="margin-bottom:25px;">تسيير شهادات العمل والتدريب</p>
 
                <?php if ($erreur): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
                <?php endif; ?>
 
                <form method="POST" action="login.php">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
 
                    <div class="form-group">
                        <label>اسم المستخدم</label>
                        <input type="text" name="nom_utilisateur" class="form-control input-lg" required <?= $bloque ? 'disabled' : '' ?>>
                    </div>
 
                    <div class="form-group">
                        <label>كلمة المرور</label>
                        <input type="password" name="mot_de_passe" class="form-control input-lg" required <?= $bloque ? 'disabled' : '' ?>>
                    </div>
 
                    <button type="submit" class="btn btn-lg btn-vert" <?= $bloque ? 'disabled' : '' ?>>
                        تسجيل الدخول
                    </button>
                </form>
            </div>
        </div>
 
    </div>
 
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>