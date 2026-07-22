<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require_once __DIR__ . '/config/db.php';

$id = intval($_GET['id'] ?? 0);
$erreur = "";
$aujourdhui = date('Y-m-d');

$stmt = $pdo->prepare("SELECT * FROM attestations WHERE id = ?");
$stmt->execute([$id]);
$attestation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$attestation) {
    header("Location: liste_attestations.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type_attestation'] ?? '';
    $nom = trim($_POST['nom_beneficiaire'] ?? '');
    $prenom = trim($_POST['prenom_beneficiaire'] ?? '');
    $cin = trim(strtoupper($_POST['cin'] ?? ''));
    $fonction = trim($_POST['fonction_ou_poste'] ?? '');
    $date_debut = $_POST['date_debut'] ?? null;
    $date_fin = $_POST['date_fin'] ?? null;

    if ($nom === '' || $prenom === '' || !in_array($type, ['travail', 'stage'])) {
        $erreur = "يرجى ملء الحقول الإلزامية.";
    } elseif (!preg_match('/^[\p{L}\s\-\']+$/u', $nom) || !preg_match('/^[\p{L}\s\-\']+$/u', $prenom)) {
        $erreur = "الاسم العائلي والشخصي يجب أن يحتويا على حروف فقط.";
    } elseif ($cin !== '' && !preg_match('/^[A-Z]{2}[0-9]{4}$/', $cin)) {
        $erreur = "يجب أن تتكون البطاقة الوطنية من حرفين كبيرين متبوعين بـ 4 أرقام بالضبط (مثال: AB1234).";
    } elseif ($date_debut && $date_debut > $aujourdhui) {
        $erreur = "لا يمكن أن يتجاوز تاريخ البداية التاريخ الحالي.";
    } else {
        $stmt = $pdo->prepare("UPDATE attestations SET 
            type_attestation = ?, nom_beneficiaire = ?, prenom_beneficiaire = ?, 
            cin = ?, fonction_ou_poste = ?, date_debut = ?, date_fin = ? 
            WHERE id = ?");
        $stmt->execute([$type, $nom, $prenom, $cin, $fonction, $date_debut ?: null, $date_fin ?: null, $id]);

        header("Location: liste_attestations.php?msg=updated");
        exit();
    }
    $attestation = array_merge($attestation, $_POST);
    $attestation['cin'] = $cin;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تعديل شهادة</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/theme.css" rel="stylesheet">
    <script src="assets/js/theme.js" defer></script>
    <style>
        body { background: #f4f6f5; font-size: 14px; font-family: 'Tahoma', 'Segoe UI', sans-serif; }
        .navbar-custom {
            background: #0ea5e9;
            padding: 18px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .navbar-left { flex: 1; }
        .navbar-center {
            flex: 2;
            text-align: center;
            font-weight: bold;
            font-size: 20px;
            color: white;
        }
        .navbar-right { flex: 1; text-align: left; }
        .navbar-right a { color: white !important; margin-left: 20px; text-decoration: none; }
        .navbar-right .btn-logout { background: #b33; border: none; }
        .contenu { padding: 20px; display: flex; justify-content: center; }
        .panel-formulaire { max-width: 600px; width: 100%; }
        .panel-formulaire .panel-body { padding: 25px; }
        .form-group label { display: block; margin-bottom: 7px; font-weight: bold; color: #333; }
        .retour { display: inline-block; margin-bottom: 18px; color: #0ea5e9; text-decoration: none; }
    </style>
</head>
<body>

<div class="navbar-custom">
    <div class="navbar-left"></div>
    <div class="navbar-center">مرحبا، <?= htmlspecialchars($_SESSION['admin_nom']) ?></div>
    <div class="navbar-right">
        <a href="dashboard.php" title="الرئيسية"><span class="glyphicon glyphicon-home"></span></a>
        <a href="logout.php" class="btn btn-logout btn-sm">تسجيل الخروج</a>
    </div>
</div>

<div class="contenu">
    <div class="panel panel-default panel-formulaire">
        <div class="panel-body">
            <a class="retour" href="liste_attestations.php">
                <span class="glyphicon glyphicon-arrow-right"></span> العودة إلى القائمة
            </a>
            <h2 style="color:#0ea5e9; margin-top:10px;">تعديل الشهادة رقم #<?= $attestation['id'] ?></h2>

            <?php if ($erreur): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
            <?php endif; ?>

            <form method="POST" action="modifier_attestation.php?id=<?= $attestation['id'] ?>">
                <div class="form-group">
                    <label>نوع الشهادة *</label>
                    <select name="type_attestation" class="form-control" required>
                        <option value="travail" <?= $attestation['type_attestation'] === 'travail' ? 'selected' : '' ?>>شهادة عمل</option>
                        <option value="stage" <?= $attestation['type_attestation'] === 'stage' ? 'selected' : '' ?>>شهادة تدريب</option>
                    </select>
                </div>

                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label>الاسم العائلي *</label>
                            <input type="text" name="nom_beneficiaire" class="form-control" maxlength="25" pattern="[A-Za-zÀ-ÖØ-öø-ÿ\u0600-\u06FF\s\-']+" title="حروف فقط، بلا أرقام" value="<?= htmlspecialchars($attestation['nom_beneficiaire']) ?>" required>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label>الاسم الشخصي *</label>
                            <input type="text" name="prenom_beneficiaire" class="form-control" maxlength="25" pattern="[A-Za-zÀ-ÖØ-öø-ÿ\u0600-\u06FF\s\-']+" title="حروف فقط، بلا أرقام" value="<?= htmlspecialchars($attestation['prenom_beneficiaire']) ?>" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>البطاقة الوطنية</label>
                    <input type="text" name="cin" class="form-control" pattern="[A-Za-z]{2}[0-9]{4}" maxlength="6" title="حرفين كبيرين متبوعين بـ 4 أرقام بالضبط (مثال: AB1234)" style="text-transform:uppercase;" placeholder="مثال: AB1234" value="<?= htmlspecialchars($attestation['cin'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>الوظيفة / المنصب</label>
                    <input type="text" name="fonction_ou_poste" class="form-control" value="<?= htmlspecialchars($attestation['fonction_ou_poste'] ?? '') ?>">
                </div>

                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label>تاريخ البداية</label>
                            <input type="date" name="date_debut" class="form-control" max="<?= $aujourdhui ?>" value="<?= htmlspecialchars($attestation['date_debut'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label>تاريخ النهاية</label>
                            <input type="date" name="date_fin" class="form-control" value="<?= htmlspecialchars($attestation['date_fin'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-lg btn-block" style="background:#0ea5e9; color:white;">
                    <span class="glyphicon glyphicon-ok"></span> تحديث
                </button>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/bootstrap.min.js"></script>
</body>
</html>