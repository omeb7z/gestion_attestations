<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require_once __DIR__ . '/config/db.php';
 
$recherche = trim($_GET['q'] ?? '');
 
if ($recherche !== '') {
    $stmt = $pdo->prepare("SELECT * FROM attestations WHERE nom_beneficiaire LIKE ? OR prenom_beneficiaire LIKE ? OR cin LIKE ? ORDER BY id DESC");
    $like = "%$recherche%";
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $pdo->query("SELECT * FROM attestations ORDER BY id DESC");
}
$attestations = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
function formatDate($date) {
    if (!$date) return '-';
    return date("d/m/Y", strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تصدير قائمة الشهادات</title>
    <style>
        body {
            font-family: 'Tahoma', 'Segoe UI', sans-serif;
            padding: 30px;
            color: #111;
        }
        .entete {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #0ea5e9;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .entete img { width: 60px; height: auto; }
        .entete-texte { text-align: center; }
        .entete-texte h3 { margin: 3px 0; color: #0ea5e9; }
        .entete-texte p { margin: 0; color: #555; font-size: 13px; }
        .infos-export {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: #666;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px 10px;
            text-align: right;
        }
        th { background: #0ea5e9; color: white; }
        tr:nth-child(even) { background: #f7f9fa; }
        .badge {
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 11px;
            color: white;
        }
        .badge-travail { background: #0ea5e9; }
        .badge-stage { background: #b38f00; }
        .btn-imprimer { text-align: center; margin-bottom: 25px; }
        .btn-imprimer button {
            padding: 12px 24px; background: #0ea5e9; color: white;
            border: none; border-radius: 6px; cursor: pointer; font-size: 15px;
        }
        .pied {
            margin-top: 20px;
            font-size: 12px;
            color: #888;
            text-align: left;
        }
        @media print {
            .btn-imprimer { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
 
<div class="btn-imprimer">
    <button onclick="window.print()">🖨 تصدير كـ PDF / طباعة</button>
</div>
 
<div class="entete">
    <img src="assets/img/logo-maroc.png" alt="المملكة المغربية">
    <div class="entete-texte">
        <h3>محكمة الاستئناف بفاس</h3>
        <p>قائمة شهادات العمل والتدريب</p>
    </div>
    <div style="width:60px;"></div>
</div>
 
<div class="infos-export">
    <span>تاريخ التصدير: <?= date('d/m/Y') ?></span>
    <span>عدد الشهادات: <?= count($attestations) ?></span>
</div>
 
<table>
    <thead>
        <tr>
            <th>الرقم</th>
            <th>النوع</th>
            <th>الاسم الكامل</th>
            <th>البطاقة الوطنية</th>
            <th>الوظيفة / المنصب</th>
            <th>تاريخ التسليم</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($attestations) === 0): ?>
            <tr><td colspan="6" style="text-align:center; padding:20px;">لم يتم العثور على أي شهادة.</td></tr>
        <?php else: ?>
            <?php foreach ($attestations as $a): ?>
                <tr>
                    <td>#<?= $a['id'] ?></td>
                    <td>
                        <span class="badge <?= $a['type_attestation'] === 'travail' ? 'badge-travail' : 'badge-stage' ?>">
                            <?= $a['type_attestation'] === 'travail' ? 'عمل' : 'تدريب' ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($a['nom_beneficiaire'] . ' ' . $a['prenom_beneficiaire']) ?></td>
                    <td><?= htmlspecialchars($a['cin'] ?: '-') ?></td>
                    <td><?= htmlspecialchars($a['fonction_ou_poste'] ?: '-') ?></td>
                    <td><?= formatDate($a['date_delivrance']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
 
<div class="pied">
    وثيقة مولدة تلقائيا من نظام تسيير الشهادات - محكمة الاستئناف بفاس
</div>
 
</body>
</html>