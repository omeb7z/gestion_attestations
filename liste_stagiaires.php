<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require_once __DIR__ . '/config/db.php';

if (isset($_GET['supprimer'])) {
    $id = intval($_GET['supprimer']);
    $stmt = $pdo->prepare("DELETE FROM stagiaires WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: liste_stagiaires.php?msg=deleted");
    exit();
}

$recherche = trim($_GET['q'] ?? '');

if ($recherche !== '') {
    $stmt = $pdo->prepare("SELECT * FROM stagiaires WHERE nom LIKE ? OR prenom LIKE ? OR cne LIKE ? ORDER BY id DESC");
    $like = "%$recherche%";
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $pdo->query("SELECT * FROM stagiaires ORDER BY id DESC");
}
$stagiaires = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>قائمة المتدربين - Liste des stagiaires</title>
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
        .contenu { padding: 15px; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
        table th { background: #0ea5e9; color: white; }
        .actions a { margin-left: 10px; }
        th small, td small { display: block; font-weight: normal; color: #cde9f9; font-size: 11px; }
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

    <div class="top-bar">
        <h2 style="color:#0ea5e9; margin:0;">قائمة المتدربين <small style="font-size:14px; color:#888;">/ Liste des stagiaires</small></h2>
        <a href="ajouter_stagiaire.php" class="btn btn-lg" style="background:#0ea5e9; color:white;">
            <span class="glyphicon glyphicon-plus"></span> إضافة متدرب
        </a>
    </div>

    <form method="GET" action="liste_stagiaires.php" class="form-inline" style="margin-bottom:20px;">
        <div class="form-group" style="width:300px; max-width:100%;">
            <input type="text" name="q" class="form-control" placeholder="بحث (الاسم، CNE...)" value="<?= htmlspecialchars($recherche) ?>" style="width:100%;">
        </div>
        <button type="submit" class="btn" style="background:#38bdf8; color:white; margin-right:10px;">
            <span class="glyphicon glyphicon-search"></span> بحث
        </button>
    </form>

    <div class="panel panel-default">
        <div class="table-responsive">
        <table class="table table-hover" style="margin-bottom:0;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>الاسم الكامل<small>Nom complet</small></th>
                    <th>CNE</th>
                    <th>الهاتف<small>Téléphone</small></th>
                    <th>البريد الإلكتروني<small>Email</small></th>
                    <th>المؤسسة<small>Établissement</small></th>
                    <th>التخصص<small>Spécialité</small></th>
                    <th>المدة<small>Durée</small></th>
                    <th>الإجراءات<small>Actions</small></th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($stagiaires) === 0): ?>
                    <tr><td colspan="9" class="text-center text-muted" style="padding:30px;">لم يتم العثور على أي متدرب. / Aucun stagiaire trouvé.</td></tr>
                <?php else: ?>
                    <?php foreach ($stagiaires as $s): ?>
                        <tr>
                            <td>#<?= $s['id'] ?></td>
                            <td><?= htmlspecialchars($s['nom'] . ' ' . $s['prenom']) ?></td>
                            <td><?= htmlspecialchars($s['cne'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($s['telephone'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($s['email'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($s['etablissement'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($s['specialite'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($s['duree_stage'] ?: '-') ?></td>
                            <td class="actions">
                                <a href="ajouter_attestation.php?stagiaire_id=<?= $s['id'] ?>" title="إنشاء شهادة">
                                    <span class="glyphicon glyphicon-file" style="color:#059669;"></span>
                                </a>
                                <a href="modifier_stagiaire.php?id=<?= $s['id'] ?>" title="تعديل">
                                    <span class="glyphicon glyphicon-pencil" style="color:#0ea5e9;"></span>
                                </a>
                                <a href="liste_stagiaires.php?supprimer=<?= $s['id'] ?>" onclick="return confirm('هل تريد حذف هذا المتدرب؟');" title="حذف">
                                    <span class="glyphicon glyphicon-trash" style="color:#b30000;"></span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

</div>

<script src="assets/js/bootstrap.min.js"></script>
</body>
</html>