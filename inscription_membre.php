<?php
session_start();
require_once __DIR__ . '/config/db.php';

$erreur = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_utilisateur = trim($_POST['nom_utilisateur'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $confirmation = $_POST['confirmation'] ?? '';
    $nom_complet = trim($_POST['nom_complet'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($nom_utilisateur === '' || $mot_de_passe === '' || $nom_complet === '' || $email === '') {
        $erreur = "يرجى ملء جميع الحقول.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "البريد الإلكتروني غير صحيح.";
    } elseif (strlen($mot_de_passe) < 6) {
        $erreur = "كلمة المرور يجب أن تحتوي على 6 أحرف على الأقل.";
    } elseif ($mot_de_passe !== $confirmation) {
        $erreur = "كلمتا المرور غير متطابقتين.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM membres WHERE nom_utilisateur = ?");
        $stmt->execute([$nom_utilisateur]);
        if ($stmt->fetch()) {
            $erreur = "اسم المستخدم هذا مستعمل بالفعل.";
        } else {
            $hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO membres (nom_utilisateur, mot_de_passe, nom_complet, email) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nom_utilisateur, $hash, $nom_complet, $email]);

            $stmt = $pdo->prepare("SELECT * FROM membres WHERE nom_utilisateur = ?");
            $stmt->execute([$nom_utilisateur]);
            $membre = $stmt->fetch(PDO::FETCH_ASSOC);

            $_SESSION['membre_id'] = $membre['id'];
            $_SESSION['membre_nom'] = $membre['nom_complet'];
            $_SESSION['membre_email'] = $membre['email'];

            header("Location: demande_attestation.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إنشاء حساب - محكمة الاستئناف بفاس</title>
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
        .panel-formulaire { max-width: 450px; width: 100%; }
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
        <h2 class="text-center" style="color:#0ea5e9;">إنشاء حساب عضو</h2>
        <p class="text-center text-muted" style="margin-bottom:20px;">لطلب شهادة عمل أو تدريب</p>

        <?php if ($erreur): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <form method="POST" action="inscription_membre.php">
            <div class="form-group">
                <label>الاسم الكامل</label>
                <input type="text" name="nom_complet" class="form-control" maxlength="100" required>
            </div>
            <div class="form-group">
                <label>البريد الإلكتروني</label>
                <input type="email" name="email" class="form-control" maxlength="100" required>
            </div>
            <div class="form-group">
                <label>اسم المستخدم</label>
                <input type="text" name="nom_utilisateur" class="form-control" maxlength="50" required>
            </div>
            <div class="form-group">
                <label>كلمة المرور</label>
                <input type="password" name="mot_de_passe" class="form-control" required>
            </div>
            <div class="form-group">
                <label>تأكيد كلمة المرور</label>
                <input type="password" name="confirmation" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-lg btn-block" style="background:#0ea5e9; color:white;">
                إنشاء الحساب
            </button>
        </form>

        <div class="lien-bas">
            عندك حساب بالفعل؟ <a href="login_membre.php">تسجيل الدخول</a>
        </div>
    </div>
</div>

</body>
</html>