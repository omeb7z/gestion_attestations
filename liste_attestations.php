<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require_once __DIR__ . '/config/db.php';
 
if (isset($_GET['supprimer'])) {
    $id = intval($_GET['supprimer']);
    $stmt = $pdo->prepare("DELETE FROM attestations WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: liste_attestations.php?msg=deleted");
    exit();
}
 
$recherche = trim($_GET['q'] ?? '');
 
if ($recherche !== '') {
    $stmt = $pdo->prepare("SELECT * FROM attestations WHERE nom_beneficiaire LIKE ? OR prenom_beneficiaire LIKE ? OR cin LIKE ? ORDER BY id DESC");
    $like = "%$recherche%";
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $pdo->query("SELECT * FROM attestations ORDER BY id DESC");
}
$attestations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>قائمة الشهادات</title>
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
        .badge-type { padding: 5px 12px; border-radius: 12px; font-size: 12px; color: white; }
        .badge-travail { background: #0ea5e9; }
        .badge-stage { background: #b38f00; }
        table th { background: #0ea5e9; color: white; }
        .actions a { margin-left: 10px; }
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
        <h2 style="color:#0ea5e9; margin:0;">قائمة الشهادات</h2>
        <div>
            <a href="export_liste.php<?= $recherche ? '?q='.urlencode($recherche) : '' ?>" target="_blank" class="btn btn-lg" style="background:#059669; color:white; margin-left:10px;">
                <span class="glyphicon glyphicon-download-alt"></span> تصدير PDF
            </a>
            <a href="ajouter_attestation.php" class="btn btn-lg" style="background:#0ea5e9; color:white;">
                <span class="glyphicon glyphicon-plus"></span> شهادة جديدة
            </a>
        </div>
    </div>
 
    <form method="GET" action="liste_attestations.php" class="form-inline" style="margin-bottom:20px;">
        <div class="form-group" style="width:300px; max-width:100%;">
            <input type="text" name="q" class="form-control" placeholder="بحث (الاسم، البطاقة الوطنية...)" value="<?= htmlspecialchars($recherche) ?>" style="width:100%;">
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
                    <th>الرقم</th>
                    <th>النوع</th>
                    <th>الاسم الكامل</th>
                    <th>البطاقة الوطنية</th>
                    <th>الوظيفة / المنصب</th>
                    <th>تاريخ التسليم</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($attestations) === 0): ?>
                    <tr><td colspan="7" class="text-center text-muted" style="padding:30px;">لم يتم العثور على أي شهادة.</td></tr>
                <?php else: ?>
                    <?php foreach ($attestations as $a): ?>
                        <tr>
                            <td>#<?= $a['id'] ?></td>
                            <td>
                                <span class="badge-type <?= $a['type_attestation'] === 'travail' ? 'badge-travail' : 'badge-stage' ?>">
                                    <?= $a['type_attestation'] === 'travail' ? 'عمل' : 'تدريب' ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($a['nom_beneficiaire'] . ' ' . $a['prenom_beneficiaire']) ?></td>
                            <td><?= htmlspecialchars($a['cin'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($a['fonction_ou_poste'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($a['date_delivrance']) ?></td>
                            <td class="actions">
                                <a href="modifier_attestation.php?id=<?= $a['id'] ?>" title="تعديل">
                                    <span class="glyphicon glyphicon-pencil" style="color:#0ea5e9;"></span>
                                </a>
                                <a href="imprimer_attestation.php?id=<?= $a['id'] ?>" target="_blank" title="طباعة">
                                    <span class="glyphicon glyphicon-print" style="color:#555;"></span>
                                </a>
                                <a href="envoyer_email.php?id=<?= $a['id'] ?>" title="إرسال بالبريد">
                                    <span class="glyphicon glyphicon-envelope" style="color:#059669;"></span>
                                </a>
                                <a href="liste_attestations.php?supprimer=<?= $a['id'] ?>" onclick="return confirm('هل تريد حذف هذه الشهادة؟');" title="حذف">
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