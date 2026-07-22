<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require_once __DIR__ . '/config/db.php';
 
$erreur = "";
$aujourdhui = date('Y-m-d');

// Pré-remplissage depuis un stagiaire existant
$prefill = ['nom' => '', 'prenom' => '', 'specialite' => '', 'type' => ''];
$stagiaireId = intval($_GET['stagiaire_id'] ?? 0);
if ($stagiaireId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM stagiaires WHERE id = ?");
    $stmt->execute([$stagiaireId]);
    $stag = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($stag) {
        $prefill['nom'] = $stag['nom'];
        $prefill['prenom'] = $stag['prenom'];
        $prefill['specialite'] = $stag['specialite'];
        $prefill['type'] = 'stage';
    }
}
 
// Liste des stagiaires pour le sélecteur rapide (modal)
$tousStagiaires = $pdo->query("SELECT id, nom, prenom, specialite, cne FROM stagiaires ORDER BY nom, prenom")->fetchAll(PDO::FETCH_ASSOC);

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
        $stmt = $pdo->prepare("INSERT INTO attestations 
            (type_attestation, nom_beneficiaire, prenom_beneficiaire, cin, fonction_ou_poste, date_debut, date_fin, admin_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$type, $nom, $prenom, $cin, $fonction, $date_debut ?: null, $date_fin ?: null, $_SESSION['admin_id']]);
 
        header("Location: liste_attestations.php?msg=added");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إضافة شهادة</title>
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
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-top:10px;">
                <h2 style="color:#0ea5e9; margin:0;">شهادة جديدة</h2>
                <button type="button" class="btn" style="background:#059669; color:white;" data-toggle="modal" data-target="#modalStagiaires">
                    <span class="glyphicon glyphicon-user"></span> اختر من المتدربين
                </button>
            </div>
 
            <?php if ($erreur): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
            <?php endif; ?>
 
            <?php if ($stagiaireId > 0 && $prefill['nom']): ?>
                <div class="alert alert-info" style="background:#e0f2fe; border-color:#0ea5e9; color:#075985;">
                    <span class="glyphicon glyphicon-info-sign"></span>
                    تم تعبئة البيانات تلقائيا من المتدرب المختار.
                </div>
            <?php endif; ?>

            <form method="POST" action="ajouter_attestation.php">
                <div class="form-group">
                    <label>نوع الشهادة *</label>
                    <select name="type_attestation" class="form-control" required>
                        <option value="">-- اختر --</option>
                        <option value="travail" <?= $prefill['type'] === 'travail' ? 'selected' : '' ?>>شهادة عمل</option>
                        <option value="stage" <?= $prefill['type'] === 'stage' ? 'selected' : '' ?>>شهادة تدريب</option>
                    </select>
                </div>
 
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label>الاسم العائلي *</label>
                            <input type="text" name="nom_beneficiaire" class="form-control" maxlength="25" pattern="[A-Za-zÀ-ÖØ-öø-ÿ\u0600-\u06FF\s\-']+" title="حروف فقط، بلا أرقام" value="<?= htmlspecialchars($prefill['nom']) ?>" required>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label>الاسم الشخصي *</label>
                            <input type="text" name="prenom_beneficiaire" class="form-control" maxlength="25" pattern="[A-Za-zÀ-ÖØ-öø-ÿ\u0600-\u06FF\s\-']+" title="حروف فقط، بلا أرقام" value="<?= htmlspecialchars($prefill['prenom']) ?>" required>
                        </div>
                    </div>
                </div>
 
                <div class="form-group">
                    <label>البطاقة الوطنية</label>
                    <input type="text" name="cin" class="form-control" pattern="[A-Za-z]{2}[0-9]{4}" maxlength="6" title="حرفين كبيرين متبوعين بـ 4 أرقام بالضبط (مثال: AB1234)" style="text-transform:uppercase;" placeholder="مثال: AB1234">
                </div>
 
                <div class="form-group">
                    <label>الوظيفة / المنصب</label>
                    <input type="text" name="fonction_ou_poste" class="form-control" value="<?= htmlspecialchars($prefill['specialite']) ?>">
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
                    <span class="glyphicon glyphicon-ok"></span> تسجيل
                </button>
            </form>
        </div>
    </div>
</div>
 
<!-- Modal: اختيار متدرب -->
<div class="modal fade" id="modalStagiaires" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document" style="max-width:520px;">
        <div class="modal-content" style="border-radius:14px; overflow:hidden;">
            <div class="modal-header" style="background:#0ea5e9; color:white;">
                <button type="button" class="close" data-dismiss="modal" style="color:white; opacity:1;">&times;</button>
                <h4 class="modal-title">اختر متدربا</h4>
            </div>
            <div class="modal-body" style="max-height:70vh; overflow-y:auto; padding:15px;">
                <input type="text" id="rechercheStagiaireModal" class="form-control" placeholder="بحث بالاسم..." style="margin-bottom:15px;">
                <div id="listeStagiairesModal">
                    <?php if (count($tousStagiaires) === 0): ?>
                        <p class="text-muted text-center">لا يوجد متدربون مسجلون بعد.</p>
                    <?php else: ?>
                        <?php foreach ($tousStagiaires as $st): ?>
                            <div class="item-stagiaire-modal"
                                 data-nom="<?= htmlspecialchars($st['nom']) ?>"
                                 data-prenom="<?= htmlspecialchars($st['prenom']) ?>"
                                 data-specialite="<?= htmlspecialchars($st['specialite'] ?? '') ?>"
                                 style="padding:12px 14px; border:1px solid #eee; border-radius:10px; margin-bottom:8px; cursor:pointer; transition:background 0.2s;">
                                <strong><?= htmlspecialchars($st['nom'] . ' ' . $st['prenom']) ?></strong>
                                <div style="font-size:12px; color:#888;">
                                    <?= htmlspecialchars($st['specialite'] ?: '-') ?>
                                    <?= $st['cne'] ? ' • CNE: ' . htmlspecialchars($st['cne']) : '' ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var recherche = document.getElementById('rechercheStagiaireModal');
    var items = document.querySelectorAll('.item-stagiaire-modal');

    items.forEach(function (item) {
        item.addEventListener('mouseenter', function () { item.style.background = '#f0f9ff'; });
        item.addEventListener('mouseleave', function () { item.style.background = 'white'; });
        item.addEventListener('click', function () {
            document.querySelector('[name="nom_beneficiaire"]').value = item.dataset.nom;
            document.querySelector('[name="prenom_beneficiaire"]').value = item.dataset.prenom;
            document.querySelector('[name="fonction_ou_poste"]').value = item.dataset.specialite;
            document.querySelector('[name="type_attestation"]').value = 'stage';
            $('#modalStagiaires').modal('hide');
            showToast('تم تعبئة بيانات المتدرب', 'success');
        });
    });

    if (recherche) {
        recherche.addEventListener('input', function () {
            var val = recherche.value.trim().toLowerCase();
            items.forEach(function (item) {
                var texte = (item.dataset.nom + ' ' + item.dataset.prenom).toLowerCase();
                item.style.display = texte.includes(val) ? '' : 'none';
            });
        });
    }
});
</script>
</body>
</html>