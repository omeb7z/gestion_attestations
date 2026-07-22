<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require_once __DIR__ . '/config/db.php';

$id = intval($_GET['id'] ?? 0);
$erreur = "";

$stmt = $pdo->prepare("SELECT * FROM stagiaires WHERE id = ?");
$stmt->execute([$id]);
$stagiaire = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$stagiaire) {
    header("Location: liste_stagiaires.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $cne = trim(strtoupper($_POST['cne'] ?? ''));
    $lieu = trim($_POST['lieu_residence'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $etablissement = trim($_POST['etablissement'] ?? '');
    $specialite = trim($_POST['specialite'] ?? '');
    $duree = trim($_POST['duree_stage'] ?? '');

    if ($nom === '' || $prenom === '') {
        $erreur = "يرجى ملء الاسم العائلي والشخصي. / Merci de remplir le nom et prénom.";
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "البريد الإلكتروني غير صحيح. / Adresse email invalide.";
    } elseif ($cne !== '' && !preg_match('/^[A-Z]{2}[0-9]{6}$/', $cne)) {
        $erreur = "يجب أن يتكون CNE من حرفين كبيرين متبوعين بـ 6 أرقام (مثال: QV123413).";
    } else {
        if ($cne !== '') {
            $stmt = $pdo->prepare("SELECT id FROM stagiaires WHERE cne = ? AND id != ?");
            $stmt->execute([$cne, $id]);
            if ($stmt->fetch()) {
                $erreur = "رمز CNE هذا مسجل بالفعل لمتدرب آخر. / Ce CNE est déjà utilisé.";
            }
        }
    }

    if (!$erreur) {
        $stmt = $pdo->prepare("UPDATE stagiaires SET
            nom = ?, prenom = ?, cne = ?, lieu_residence = ?, telephone = ?, email = ?, etablissement = ?, specialite = ?, duree_stage = ?
            WHERE id = ?");
        $stmt->execute([$nom, $prenom, $cne ?: null, $lieu, $telephone, $email, $etablissement, $specialite, $duree, $id]);

        header("Location: liste_stagiaires.php?msg=updated");
        exit();
    }
    $stagiaire = array_merge($stagiaire, $_POST);
    $stagiaire['cne'] = $cne;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تعديل متدرب - Modifier un stagiaire</title>
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
        .panel-formulaire { max-width: 650px; width: 100%; }
        .panel-formulaire .panel-body { padding: 25px; }
        .form-group label { display: block; margin-bottom: 7px; font-weight: bold; color: #333; }
        .form-group label .fr { font-weight: normal; color: #888; font-size: 12px; }
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
            <a class="retour" href="liste_stagiaires.php">
                <span class="glyphicon glyphicon-arrow-right"></span> العودة إلى القائمة / Retour à la liste
            </a>
            <h2 style="color:#0ea5e9; margin-top:10px;">تعديل متدرب #<?= $stagiaire['id'] ?> <span style="font-size:14px; color:#888;">/ Modifier</span></h2>

            <?php if ($erreur): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
            <?php endif; ?>

            <form method="POST" action="modifier_stagiaire.php?id=<?= $stagiaire['id'] ?>">
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label>الاسم العائلي * <span class="fr">/ Nom</span></label>
                            <input type="text" name="nom" class="form-control" maxlength="50" value="<?= htmlspecialchars($stagiaire['nom']) ?>" required>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label>الاسم الشخصي * <span class="fr">/ Prénom</span></label>
                            <input type="text" name="prenom" class="form-control" maxlength="50" value="<?= htmlspecialchars($stagiaire['prenom']) ?>" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>رمز التسجيل الوطني <span class="fr">/ CNE</span></label>
                    <input type="text" name="cne" class="form-control" maxlength="8" pattern="[A-Za-z]{2}[0-9]{6}" title="حرفين كبيرين متبوعين بـ 6 أرقام (مثال: QV123413)" style="text-transform:uppercase;" placeholder="مثال: QV123413" value="<?= htmlspecialchars($stagiaire['cne'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>مكان السكن <span class="fr">/ Lieu de résidence</span></label>
                    <input type="text" name="lieu_residence" class="form-control" maxlength="150" value="<?= htmlspecialchars($stagiaire['lieu_residence'] ?? '') ?>">
                </div>

                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label>الهاتف <span class="fr">/ Téléphone</span></label>
                            <input type="tel" name="telephone" class="form-control" maxlength="20" value="<?= htmlspecialchars($stagiaire['telephone'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label>البريد الإلكتروني <span class="fr">/ Email</span></label>
                            <input type="email" name="email" class="form-control" maxlength="100" value="<?= htmlspecialchars($stagiaire['email'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>مؤسسة التكوين <span class="fr">/ Établissement de formation</span></label>
                    <input type="text" name="etablissement" class="form-control" maxlength="150" value="<?= htmlspecialchars($stagiaire['etablissement'] ?? '') ?>">
                </div>

                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label>التخصص <span class="fr">/ Spécialité</span></label>
                            <input type="text" name="specialite" class="form-control" maxlength="100" value="<?= htmlspecialchars($stagiaire['specialite'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label>مدة التداريب <span class="fr">/ Durée du stage</span></label>
                            <input type="text" name="duree_stage" class="form-control" maxlength="50" value="<?= htmlspecialchars($stagiaire['duree_stage'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-lg btn-block" style="background:#0ea5e9; color:white;">
                    <span class="glyphicon glyphicon-ok"></span> تحديث / Mettre à jour
                </button>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/bootstrap.min.js"></script>
</body>
</html>