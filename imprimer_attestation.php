<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require_once __DIR__ . '/config/db.php';

$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM attestations WHERE id = ?");
$stmt->execute([$id]);
$a = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$a) {
    die("الشهادة غير موجودة.");
}

function formatDate($date) {
    if (!$date) return '';
    return date("d/m/Y", strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>شهادة رقم #<?= $a['id'] ?></title>
    <style>
        body {
            font-family: 'Traditional Arabic', 'Times New Roman', 'Tahoma', serif;
            background: #e9ecef;
            margin: 0;
            padding: 30px;
        }
        .feuille {
            max-width: 780px;
            margin: 0 auto;
            background: #fff;
            border: 6px solid #b8860b;
            padding: 6px;
        }
        .cadre-interieur {
            border: 1px solid #b8860b;
            padding: 45px 55px;
            min-height: 900px;
        }
        .entete {
            text-align: center;
            margin-bottom: 15px;
        }
        .entete img { width: 80px; height: auto; margin-bottom: 12px; }
        .entete h3 {
            margin: 3px 0;
            font-size: 17px;
            font-weight: bold;
        }
        .separateur {
            border: none;
            border-top: 1px solid #999;
            margin: 20px 0 45px;
        }
        .titre {
            text-align: center;
            font-weight: bold;
            font-size: 24px;
            letter-spacing: 1px;
            margin-bottom: 45px;
            color: #1a1a4d;
        }
        .contenu-texte {
            text-align: justify;
            font-size: 18px;
            line-height: 2.3;
        }
        .pied-page {
            display: flex;
            justify-content: space-between;
            margin-top: 90px;
            font-size: 16px;
        }
        .signature { text-align: left; font-style: italic; font-weight: bold; }
        .btn-imprimer { text-align: center; margin-bottom: 20px; }
        .btn-imprimer button {
            padding: 12px 22px; background: #0ea5e9; color: white;
            border: none; border-radius: 6px; cursor: pointer; font-size: 15px;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .btn-imprimer { display: none; }
            .feuille { border-width: 5px; max-width: 100%; }
        }
    </style>
</head>
<body>

<div class="btn-imprimer">
    <button onclick="window.print()">🖨 طباعة</button>
</div>

<div class="feuille">
    <div class="cadre-interieur">

        <div class="entete">
            <img src="assets/img/logo-maroc.png" alt="المملكة المغربية">
            <h3>المملكة المغربية</h3>
            <h3>محكمة الاستئناف بفاس</h3>
        </div>

        <hr class="separateur">

        <div class="titre">
            شهادة <?= $a['type_attestation'] === 'travail' ? 'عمل' : 'تدريب' ?>
        </div>

        <div class="contenu-texte">
            <?php if ($a['type_attestation'] === 'travail'): ?>
                <p>
                    أشهد أنا الموقع أدناه، كاتب الضبط الرئيسي لمحكمة الاستئناف بفاس، أن السيد(ة)
                    <strong><?= htmlspecialchars($a['prenom_beneficiaire'] . ' ' . strtoupper($a['nom_beneficiaire'])) ?></strong>،
                    <?php if (!empty($a['cin'])): ?>
                        الحامل(ة) لبطاقة التعريف الوطنية رقم <strong><?= htmlspecialchars($a['cin']) ?></strong>،
                    <?php endif; ?>
                    يعمل (تعمل) بمؤسستنا بصفة
                    <strong><?= htmlspecialchars($a['fonction_ou_poste'] ?: '............') ?></strong>
                    منذ <strong><?= formatDate($a['date_debut']) ?: '............' ?></strong>.
                </p>
                <p>وقد سلمت له (لها) هذه الشهادة بناء على طلبه (طلبها) لتُستعمل عند الاقتضاء.</p>
            <?php else: ?>
                <p>
                    أشهد أنا الموقع أدناه، كاتب الضبط الرئيسي لمحكمة الاستئناف بفاس، أن السيد(ة)
                    <strong><?= htmlspecialchars($a['prenom_beneficiaire'] . ' ' . strtoupper($a['nom_beneficiaire'])) ?></strong>،
                    <?php if (!empty($a['cin'])): ?>
                        الحامل(ة) لبطاقة التعريف الوطنية رقم <strong><?= htmlspecialchars($a['cin']) ?></strong>،
                    <?php endif; ?>
                    قام (قامت) بتدريب بمحكمة الاستئناف بفاس من
                    <strong><?= formatDate($a['date_debut']) ?: '............' ?></strong>
                    إلى <strong><?= formatDate($a['date_fin']) ?: '............' ?></strong>،
                    بمصلحة <strong><?= htmlspecialchars($a['fonction_ou_poste'] ?: '............') ?></strong>.
                </p>
                <p>وقد سلمت له (لها) هذه الشهادة بناء على طلبه (طلبها) لتُستعمل عند الاقتضاء.</p>
            <?php endif; ?>
        </div>

        <div class="pied-page">
            <div>حرر بفاس في <?= formatDate($a['date_delivrance'] ?? date('Y-m-d')) ?></div>
            <div class="signature">
                <img src="assets/img/signature.png" alt="التوقيع" style="max-width:130px; max-height:70px; display:block; margin:0 0 8px auto;">
                كاتب الضبط الرئيسي
            </div>
        </div>

    </div>
</div>

</body>
</html>