<?php
session_start();
if (!isset($_SESSION['membre_id'])) {
    header("Location: login_membre.php");
    exit();
}
require_once __DIR__ . '/config/db.php';

$erreur = "";
$succes = false;
$aujourdhui = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type_attestation'] ?? '';
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $cin = trim(strtoupper($_POST['cin'] ?? ''));
    $telephone = trim($_POST['telephone'] ?? '');
    $email = $_SESSION['membre_email'];
    $fonction = trim($_POST['fonction_ou_poste'] ?? '');
    $date_debut = $_POST['date_debut'] ?? null;
    $date_fin = $_POST['date_fin'] ?? null;

    if ($nom === '' || $prenom === '' || !in_array($type, ['travail', 'stage'])) {
        $erreur = "يرجى ملء جميع الحقول الإلزامية.";
    } elseif (!preg_match('/^[\p{L}\s\-\']+$/u', $nom) || !preg_match('/^[\p{L}\s\-\']+$/u', $prenom)) {
        $erreur = "الاسم العائلي والشخصي يجب أن يحتويا على حروف فقط.";
    } elseif ($cin !== '' && !preg_match('/^[A-Z]{2}[0-9]{4}$/', $cin)) {
        $erreur = "يجب أن تتكون البطاقة الوطنية من حرفين كبيرين متبوعين بـ 4 أرقام (مثال: AB1234).";
    } elseif ($date_debut && $date_debut > $aujourdhui) {
        $erreur = "لا يمكن أن يتجاوز تاريخ البداية التاريخ الحالي.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO demandes_attestation
            (type_attestation, nom, prenom, cin, telephone, email, fonction_ou_poste, date_debut, date_fin, membre_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$type, $nom, $prenom, $cin, $telephone, $email, $fonction, $date_debut ?: null, $date_fin ?: null, $_SESSION['membre_id']]);
        $succes = true;
    }
}

// Historique des demandes de ce membre
$stmt = $pdo->prepare("SELECT * FROM demandes_attestation WHERE membre_id = ? ORDER BY id DESC");
$stmt->execute([$_SESSION['membre_id']]);
$mesDemandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>طلب شهادة - محكمة الاستئناف بفاس</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/theme.css" rel="stylesheet">
    <script src="assets/js/theme.js" defer></script>
    <style>
        body { background: #f4f6f5; font-family: 'Tahoma', 'Segoe UI', sans-serif; font-size: 14px; }
        .navbar-custom {
            background: #0ea5e9;
            padding: 18px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .navbar-left { flex: 1; }
        .navbar-center { flex: 2; text-align: center; font-weight: bold; font-size: 18px; color: white; }
        .navbar-right { flex: 1; text-align: left; }
        .navbar-right a { color: white !important; text-decoration: none; }
        .navbar-right .btn-logout { background: #b33; border: none; }
        .contenu { padding: 25px; max-width: 700px; margin: 0 auto; }
        .form-group label { display: block; margin-bottom: 7px; font-weight: bold; color: #333; }
        .badge-statut { padding: 5px 12px; border-radius: 12px; font-size: 12px; color: white; }
        .statut-en_attente { background: #b38f00; }
        .statut-approuvee { background: #059669; }
        .statut-refusee { background: #b30000; }
    </style>
</head>
<body>

<div class="navbar-custom">
    <div class="navbar-left"></div>
    <div class="navbar-center">مرحبا، <?= htmlspecialchars($_SESSION['membre_nom']) ?></div>
    <div class="navbar-right">
        <a href="logout_membre.php" class="btn btn-logout btn-sm">تسجيل الخروج</a>
    </div>
</div>

<div class="contenu">

    <div class="panel panel-default">
        <div class="panel-body" style="padding:25px;">
            <h2 style="color:#0ea5e9; margin-top:0;">طلب شهادة عمل أو تدريب</h2>

            <?php if ($succes): ?>
                <div class="alert alert-success" style="text-align:center; padding:20px;">
                    تم استلام طلبكم بنجاح. سيتم التواصل معكم بعد مراجعة الطلب من طرف الإدارة.
                </div>
            <?php endif; ?>
            <?php if ($erreur): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
            <?php endif; ?>

            <form method="POST" action="demande_attestation.php">
                <div class="form-group">
                    <label>نوع الشهادة المطلوبة *</label>
                    <select name="type_attestation" class="form-control" required>
                        <option value="">-- اختر --</option>
                        <option value="travail">شهادة عمل</option>
                        <option value="stage">شهادة تدريب</option>
                    </select>
                </div>

                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label>الاسم العائلي *</label>
                            <input type="text" name="nom" class="form-control" maxlength="50" pattern="[A-Za-zÀ-ÖØ-öø-ÿ\u0600-\u06FF\s\-']+" title="حروف فقط" required>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label>الاسم الشخصي *</label>
                            <input type="text" name="prenom" class="form-control" maxlength="50" pattern="[A-Za-zÀ-ÖØ-öø-ÿ\u0600-\u06FF\s\-']+" title="حروف فقط" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>البطاقة الوطنية</label>
                    <input type="text" name="cin" class="form-control" maxlength="6" pattern="[A-Za-z]{2}[0-9]{4}" style="text-transform:uppercase;" placeholder="مثال: AB1234">
                </div>

                <div class="form-group">
                    <label>الهاتف</label>
                    <input type="tel" name="telephone" class="form-control" maxlength="20" placeholder="06XXXXXXXX">
                </div>

                <div class="form-group">
                    <label>الوظيفة / المنصب</label>
                    <input type="text" name="fonction_ou_poste" class="form-control" maxlength="150">
                </div>

                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label>تاريخ البداية</label>
                            <input type="date" name="date_debut" class="form-control" max="<?= $aujourdhui ?>">
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label>تاريخ النهاية</label>
                            <input type="date" name="date_fin" class="form-control">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-lg btn-block" style="background:#0ea5e9; color:white;">
                    <span class="glyphicon glyphicon-send"></span> إرسال الطلب
                </button>
            </form>
        </div>
    </div>

    <h3 style="color:#0ea5e9; margin-top:30px;">طلباتي السابقة</h3>
    <div class="panel panel-default">
        <div class="table-responsive">
        <table class="table table-hover" style="margin-bottom:0;">
            <thead>
                <tr>
                    <th>النوع</th>
                    <th>الاسم</th>
                    <th>التاريخ</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($mesDemandes) === 0): ?>
                    <tr><td colspan="4" class="text-center text-muted" style="padding:20px;">لا توجد طلبات سابقة.</td></tr>
                <?php else: ?>
                    <?php foreach ($mesDemandes as $d): ?>
                        <tr>
                            <td><?= $d['type_attestation'] === 'travail' ? 'عمل' : 'تدريب' ?></td>
                            <td><?= htmlspecialchars($d['nom'] . ' ' . $d['prenom']) ?></td>
                            <td><?= date('d/m/Y', strtotime($d['date_demande'])) ?></td>
                            <td>
                                <span class="badge-statut statut-<?= $d['statut'] ?>">
                                    <?= ['en_attente' => 'في الانتظار', 'approuvee' => 'مقبولة', 'refusee' => 'مرفوضة'][$d['statut']] ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

</div>

</body>
</html>