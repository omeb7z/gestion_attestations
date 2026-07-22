<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require_once __DIR__ . '/config/db.php';

// Total
$total = $pdo->query("SELECT COUNT(*) FROM attestations")->fetchColumn();

// Par type
$stmt = $pdo->query("SELECT type_attestation, COUNT(*) as nb FROM attestations GROUP BY type_attestation");
$parType = ['travail' => 0, 'stage' => 0];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $parType[$row['type_attestation']] = (int)$row['nb'];
}

// Ce mois-ci
$ceMois = $pdo->query("SELECT COUNT(*) FROM attestations WHERE MONTH(date_delivrance) = MONTH(CURDATE()) AND YEAR(date_delivrance) = YEAR(CURDATE())")->fetchColumn();

// Demandes en attente
$demandesEnAttente = $pdo->query("SELECT COUNT(*) FROM demandes_attestation WHERE statut = 'en_attente'")->fetchColumn();

// 14 derniers jours (pour le graphique en barres)
$stmt = $pdo->query("
    SELECT date_delivrance as jour, COUNT(*) as nb
    FROM attestations
    WHERE date_delivrance >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
    GROUP BY jour
    ORDER BY jour ASC
");
$parJour = $stmt->fetchAll(PDO::FETCH_ASSOC);

$jourLabels = [];
$jourData = [];
foreach ($parJour as $j) {
    $jourLabels[] = date('d/m', strtotime($j['jour']));
    $jourData[] = (int)$j['nb'];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>لوحة القيادة - محكمة الاستئناف بفاس</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/theme.css" rel="stylesheet">
    <script src="assets/js/theme.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>
    <style>
        body { background: #f4f6f5; font-size: 15px; font-family: 'Tahoma', 'Segoe UI', sans-serif; }
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
        .contenu { padding: 30px; }

        .cartes-nav { display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 30px; }
        .carte-nav {
            flex: 1;
            min-width: 250px;
            border-radius: 14px;
            padding: 35px 25px;
            text-align: center;
            text-decoration: none;
            color: white;
            box-shadow: 0 6px 18px rgba(0,0,0,0.15);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .carte-nav:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 26px rgba(0,0,0,0.22);
            color: white;
            text-decoration: none;
        }
        .carte-nav .glyphicon { font-size: 36px; display: block; margin-bottom: 12px; }
        .carte-nav .titre { font-size: 20px; font-weight: bold; margin-bottom: 5px; }
        .carte-nav .sous-titre { font-size: 13px; opacity: 0.9; }
        .carte-nav-bleu { background: linear-gradient(135deg, #0ea5e9, #38bdf8); }
        .carte-nav-vert { background: linear-gradient(135deg, #059669, #10b981); }
        .carte-nav-orange { background: linear-gradient(135deg, #d97706, #f59e0b); }
        .carte-nav-violet { background: linear-gradient(135deg, #7c3aed, #a855f7); }

        .cartes { display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 30px; }
        .carte {
            flex: 1;
            min-width: 200px;
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            text-align: center;
        }
        .carte .chiffre { font-size: 34px; font-weight: bold; color: #0ea5e9; }
        .carte .label { color: #666; margin-top: 5px; font-size: 14px; }
        .carte.travail .chiffre { color: #0ea5e9; }
        .carte.stage .chiffre { color: #b38f00; }
        .carte.mois .chiffre { color: #059669; }

        .graphiques { display: flex; gap: 20px; flex-wrap: wrap; }
        .bloc-graphique {
            flex: 1;
            min-width: 300px;
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }
        .bloc-graphique h4 { color: #333; margin-top: 0; margin-bottom: 20px; font-size: 16px; }

        .lien-liste {
            display: inline-block;
            margin-top: 25px;
            color: #0ea5e9;
            font-weight: bold;
            font-size: 16px;
            text-decoration: none;
        }
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

    <h2 style="color:#0ea5e9; margin-bottom:25px;">لوحة القيادة</h2>

    <div class="cartes-nav">
        <a href="liste_attestations.php" class="carte-nav carte-nav-bleu">
            <span class="glyphicon glyphicon-file"></span>
            <div class="titre">الشهادات</div>
            <div class="sous-titre">إدارة شهادات العمل والتدريب</div>
        </a>
        <a href="liste_stagiaires.php" class="carte-nav carte-nav-vert">
            <span class="glyphicon glyphicon-user"></span>
            <div class="titre">المتدربين</div>
            <div class="sous-titre">إدارة لائحة المتدربين</div>
        </a>
        <a href="liste_demandes.php" class="carte-nav carte-nav-orange" style="position:relative;">
            <?php if ($demandesEnAttente > 0): ?>
                <span style="position:absolute; top:-8px; left:-8px; background:#b30000; color:white; border-radius:50%; width:28px; height:28px; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:bold; box-shadow:0 2px 6px rgba(0,0,0,0.3);"><?= $demandesEnAttente ?></span>
            <?php endif; ?>
            <span class="glyphicon glyphicon-inbox"></span>
            <div class="titre">طلبات الشهادات</div>
            <div class="sous-titre">مراجعة طلبات الأعضاء</div>
        </a>
        <a href="signature_pad.php" class="carte-nav carte-nav-violet">
            <span class="glyphicon glyphicon-pencil"></span>
            <div class="titre">التوقيع الإلكتروني</div>
            <div class="sous-titre">توقيع كاتب الضبط</div>
        </a>
    </div>

    <div class="cartes">
        <div class="carte">
            <div class="chiffre"><?= $total ?></div>
            <div class="label">إجمالي الشهادات</div>
        </div>
        <div class="carte travail">
            <div class="chiffre"><?= $parType['travail'] ?></div>
            <div class="label">شهادات العمل</div>
        </div>
        <div class="carte stage">
            <div class="chiffre"><?= $parType['stage'] ?></div>
            <div class="label">شهادات التدريب</div>
        </div>
        <div class="carte mois">
            <div class="chiffre"><?= $ceMois ?></div>
            <div class="label">هذا الشهر</div>
        </div>
    </div>

    <div class="graphiques">
        <div class="bloc-graphique">
            <h4>توزيع الشهادات حسب النوع</h4>
            <canvas id="chartType" height="220"></canvas>
        </div>
        <div class="bloc-graphique">
            <h4>الشهادات خلال 14 يوما الأخيرة</h4>
            <canvas id="chartJour" height="220"></canvas>
        </div>
    </div>

    <div class="text-center">
        <a href="liste_attestations.php" class="lien-liste">
            <span class="glyphicon glyphicon-list"></span> عرض جميع الشهادات
        </a>
    </div>

</div>

<script src="assets/js/bootstrap.min.js"></script>
<script>
new Chart(document.getElementById('chartType'), {
    type: 'doughnut',
    data: {
        labels: ['عمل', 'تدريب'],
        datasets: [{
            data: [<?= $parType['travail'] ?>, <?= $parType['stage'] ?>],
            backgroundColor: ['#0ea5e9', '#b38f00']
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom', rtl: true } }
    }
});

new Chart(document.getElementById('chartJour'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($jourLabels, JSON_UNESCAPED_UNICODE) ?>,
        datasets: [{
            label: 'عدد الشهادات',
            data: <?= json_encode($jourData) ?>,
            backgroundColor: '#0ea5e9'
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});
</script>
</body>
</html>