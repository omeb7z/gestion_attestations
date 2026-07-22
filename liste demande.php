<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require_once __DIR__ . '/config/db.php';

// Approuver une demande -> crée l'attestation automatiquement
if (isset($_GET['approuver'])) {
    $id = intval($_GET['approuver']);
    $stmt = $pdo->prepare("SELECT * FROM demandes_attestation WHERE id = ?");
    $stmt->execute([$id]);
    $d = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($d && $d['statut'] === 'en_attente') {
        $stmt = $pdo->prepare("INSERT INTO attestations
            (type_attestation, nom_beneficiaire, prenom_beneficiaire, cin, fonction_ou_poste, date_debut, date_fin, admin_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$d['type_attestation'], $d['nom'], $d['prenom'], $d['cin'], $d['fonction_ou_poste'], $d['date_debut'], $d['date_fin'], $_SESSION['admin_id']]);

        $stmt = $pdo->prepare("UPDATE demandes_attestation SET statut = 'approuvee' WHERE id = ?");
        $stmt->execute([$id]);
    }
    header("Location: liste_demandes.php?msg=approved");
    exit();
}

// Refuser une demande
if (isset($_GET['refuser'])) {
    $id = intval($_GET['refuser']);
    $stmt = $pdo->prepare("UPDATE demandes_attestation SET statut = 'refusee' WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: liste_demandes.php?msg=refused");
    exit();
}

$stmt = $pdo->query("SELECT * FROM demandes_attestation ORDER BY 
    CASE statut WHEN 'en_attente' THEN 0 ELSE 1 END, id DESC");
$demandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

function formatDate($date) {
    if (!$date) return '-';
    return date("d/m/Y", strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>طلبات الشهادات</title>
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
        .navbar-center { flex: 2; text-align: center; font-weight: bold; font-size: 20px; color: white; }
        .navbar-right { flex: 1; text-align: left; }
        .navbar-right a { color: white !important; margin-left: 20px; text-decoration: none; }
        .navbar-right .btn-logout { background: #b33; border: none; }
        .contenu { padding: 15px; }
        .badge-statut { padding: 5px 12px; border-radius: 12px; font-size: 12px; color: white; }
        .statut-en_attente { background: #b38f00; }
        .statut-approuvee { background: #059669; }
        .statut-refusee { background: #b30000; }
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

    <h2 style="color:#0ea5e9; margin-bottom:20px;">طلبات الشهادات</h2>

    <div class="panel panel-default">
        <div class="table-responsive">
        <table class="table table-hover" style="margin-bottom:0;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>النوع</th>
                    <th>الاسم الكامل</th>
                    <th>البريد / الهاتف</th>
                    <th>الوظيفة</th>
                    <th>التاريخ</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($demandes) === 0): ?>
                    <tr><td colspan="8" class="text-center text-muted" style="padding:30px;">لا توجد طلبات حاليا.</td></tr>
                <?php else: ?>
                    <?php foreach ($demandes as $d): ?>
                        <tr>
                            <td>#<?= $d['id'] ?></td>
                            <td><?= $d['type_attestation'] === 'travail' ? 'عمل' : 'تدريب' ?></td>
                            <td><?= htmlspecialchars($d['nom'] . ' ' . $d['prenom']) ?></td>
                            <td><?= htmlspecialchars($d['email']) ?><br><small><?= htmlspecialchars($d['telephone'] ?: '-') ?></small></td>
                            <td><?= htmlspecialchars($d['fonction_ou_poste'] ?: '-') ?></td>
                            <td><?= date('d/m/Y', strtotime($d['date_demande'])) ?></td>
                            <td>
                                <span class="badge-statut statut-<?= $d['statut'] ?>">
                                    <?= ['en_attente' => 'في الانتظار', 'approuvee' => 'مقبولة', 'refusee' => 'مرفوضة'][$d['statut']] ?>
                                </span>
                            </td>
                            <td class="actions">
                                <?php if ($d['statut'] === 'en_attente'): ?>
                                    <a href="liste_demandes.php?approuver=<?= $d['id'] ?>" onclick="return confirm('قبول هذا الطلب وإنشاء الشهادة؟');" title="قبول">
                                        <span class="glyphicon glyphicon-ok" style="color:#059669;"></span>
                                    </a>
                                    <a href="liste_demandes.php?refuser=<?= $d['id'] ?>" onclick="return confirm('رفض هذا الطلب؟');" title="رفض">
                                        <span class="glyphicon glyphicon-remove" style="color:#b30000;"></span>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
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