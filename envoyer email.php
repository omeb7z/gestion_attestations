<?php
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
    if (!$date) return '............';
    return date("d/m/Y", strtotime($date));
}

$succes = "";
$erreur = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "البريد الإلكتروني غير صحيح.";
    } else {
        $nomComplet = $a['prenom_beneficiaire'] . ' ' . strtoupper($a['nom_beneficiaire']);
        $typeTexte = $a['type_attestation'] === 'travail' ? 'عمل' : 'تدريب';

        if ($a['type_attestation'] === 'travail') {
            $corps = "أشهد أنا الموقع أدناه، كاتب الضبط الرئيسي لمحكمة الاستئناف بفاس، أن السيد(ة) {$nomComplet}، "
                . (!empty($a['cin']) ? "الحامل(ة) لبطاقة التعريف الوطنية رقم {$a['cin']}، " : "")
                . "يعمل (تعمل) بمؤسستنا بصفة " . ($a['fonction_ou_poste'] ?: '............') . " منذ " . formatDate($a['date_debut']) . ".";
        } else {
            $corps = "أشهد أنا الموقع أدناه، كاتب الضبط الرئيسي لمحكمة الاستئناف بفاس، أن السيد(ة) {$nomComplet}، "
                . (!empty($a['cin']) ? "الحامل(ة) لبطاقة التعريف الوطنية رقم {$a['cin']}، " : "")
                . "قام (قامت) بتدريب بمحكمة الاستئناف بفاس من " . formatDate($a['date_debut']) . " إلى " . formatDate($a['date_fin'])
                . "، بمصلحة " . ($a['fonction_ou_poste'] ?: '............') . ".";
        }

        $sujet = "شهادة {$typeTexte} - محكمة الاستئناف بفاس";

        $htmlMessage = "
        <html dir='rtl' lang='ar'>
        <body style='font-family: Tahoma, Arial, sans-serif; background:#f4f6f5; padding:20px;'>
            <div style='max-width:600px; margin:0 auto; background:white; border:3px solid #b8860b; border-radius:10px; padding:30px;'>
                <h2 style='text-align:center; color:#0ea5e9;'>محكمة الاستئناف بفاس</h2>
                <h3 style='text-align:center; color:#1a1a4d;'>شهادة {$typeTexte}</h3>
                <hr>
                <p style='line-height:2; text-align:justify;'>{$corps}</p>
                <p>وقد سلمت له (لها) هذه الشهادة بناء على طلبه (طلبها) لتُستعمل عند الاقتضاء.</p>
                <p style='margin-top:40px;'>حرر بفاس في " . formatDate($a['date_delivrance'] ?? date('Y-m-d')) . "</p>
                <div style='text-align:left; margin-top:20px;'>
                    <img src='https://cafes17.infinityfree.io/gestion_attestations/assets/img/signature.png' alt='التوقيع' style='max-width:130px; max-height:70px;'><br>
                    <strong>كاتب الضبط الرئيسي</strong>
                </div>
            </div>
        </body>
        </html>";

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Cour d'Appel de Fès <no-reply@cafes17.infinityfree.io>" . "\r\n";

        if (mail($email, "=?UTF-8?B?" . base64_encode($sujet) . "?=", $htmlMessage, $headers)) {
            $succes = "تم إرسال الشهادة بنجاح إلى: " . htmlspecialchars($email);
        } else {
            $erreur = "تعذر إرسال البريد. تحقق من إعدادات السيرفر أو حاول لاحقا.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إرسال الشهادة بالبريد</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/theme.css" rel="stylesheet">
    <script src="assets/js/theme.js" defer></script>
    <style>
        body { background: #f4f6f5; font-size: 14px; font-family: 'Tahoma', 'Segoe UI', sans-serif; }
        .contenu { padding: 30px; display: flex; justify-content: center; }
        .panel-formulaire { max-width: 500px; width: 100%; }
        .panel-formulaire .panel-body { padding: 30px; }
        .retour { display: inline-block; margin-bottom: 18px; color: #0ea5e9; text-decoration: none; }
    </style>
</head>
<body>

<div class="contenu">
    <div class="panel panel-default panel-formulaire">
        <div class="panel-body">
            <a class="retour" href="liste_attestations.php">
                <span class="glyphicon glyphicon-arrow-right"></span> العودة إلى القائمة
            </a>
            <h2 style="color:#0ea5e9; margin-top:10px;">إرسال الشهادة #<?= $a['id'] ?> بالبريد</h2>
            <p class="text-muted">المستفيد: <strong><?= htmlspecialchars($a['prenom_beneficiaire'] . ' ' . $a['nom_beneficiaire']) ?></strong></p>

            <?php if ($succes): ?>
                <div class="alert alert-success"><?= $succes ?></div>
            <?php endif; ?>
            <?php if ($erreur): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
            <?php endif; ?>

            <form method="POST" action="envoyer_email.php?id=<?= $a['id'] ?>">
                <div class="form-group">
                    <label>البريد الإلكتروني للمستفيد</label>
                    <input type="email" name="email" class="form-control" placeholder="exemple@gmail.com" required>
                </div>
                <button type="submit" class="btn btn-lg btn-block" style="background:#0ea5e9; color:white;">
                    <span class="glyphicon glyphicon-send"></span> إرسال
                </button>
            </form>
        </div>
    </div>
</div>

</body>
</html>