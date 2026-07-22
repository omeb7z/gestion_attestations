<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Enregistrement de la signature (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signature_data'])) {
    $data = $_POST['signature_data'];
    // Retirer le préfixe data:image/png;base64,
    $data = str_replace('data:image/png;base64,', '', $data);
    $data = str_replace(' ', '+', $data);
    $decoded = base64_decode($data);

    $chemin = __DIR__ . '/assets/img/signature.png';
    if (file_put_contents($chemin, $decoded)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'فشل حفظ الملف على السيرفر']);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>توقيع كاتب الضبط</title>
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
        .navbar-right a { color: white !important; text-decoration: none; }
        .navbar-right .btn-logout { background: #b33; border: none; }
        .contenu { padding: 25px; display: flex; justify-content: center; }
        .panel-formulaire { max-width: 500px; width: 100%; }
        .panel-formulaire .panel-body { padding: 30px; text-align: center; }
        #canvasSignature {
            border: 2px dashed #0ea5e9;
            border-radius: 10px;
            background: white;
            touch-action: none;
            cursor: crosshair;
            width: 100%;
            max-width: 420px;
            height: 180px;
        }
        .apercu-actuel img {
            max-width: 130px;
            max-height: 70px;
            border: 1px solid #eee;
            border-radius: 6px;
            padding: 8px;
            background: #fafafa;
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
    <div class="panel panel-default panel-formulaire">
        <div class="panel-body">
            <h2 style="color:#0ea5e9; margin-top:0;">توقيع كاتب الضبط الرئيسي</h2>
            <p class="text-muted">وقع بالماوس أو الإصبع فالمربع تحت</p>

            <div id="alertZone"></div>

            <canvas id="canvasSignature"></canvas>

            <div style="margin-top:15px;">
                <button type="button" id="btnEffacer" class="btn" style="background:#eee;">
                    <span class="glyphicon glyphicon-refresh"></span> مسح
                </button>
                <button type="button" id="btnEnregistrer" class="btn" style="background:#059669; color:white;">
                    <span class="glyphicon glyphicon-ok"></span> حفظ التوقيع
                </button>
            </div>

            <hr>
            <div class="apercu-actuel">
                <p class="text-muted" style="margin-bottom:8px;">التوقيع الحالي المستعمل فالشهادات:</p>
                <img src="assets/img/signature.png?t=<?= time() ?>" alt="لا يوجد توقيع بعد" onerror="this.style.display='none'; document.getElementById('pasDeSignature').style.display='block';">
                <p id="pasDeSignature" class="text-muted" style="display:none;">لا يوجد توقيع مسجل بعد.</p>
            </div>
        </div>
    </div>
</div>

<script>
const canvas = document.getElementById('canvasSignature');
const ctx = canvas.getContext('2d');

function redimensionnerCanvas() {
    const rect = canvas.getBoundingClientRect();
    canvas.width = rect.width;
    canvas.height = rect.height;
    ctx.lineWidth = 2.5;
    ctx.lineCap = 'round';
    ctx.strokeStyle = '#111';
}
redimensionnerCanvas();
window.addEventListener('resize', redimensionnerCanvas);

let dessine = false;
let dernierX = 0, dernierY = 0;

function getPos(e) {
    const rect = canvas.getBoundingClientRect();
    if (e.touches) {
        return { x: e.touches[0].clientX - rect.left, y: e.touches[0].clientY - rect.top };
    }
    return { x: e.clientX - rect.left, y: e.clientY - rect.top };
}

function debut(e) {
    dessine = true;
    const pos = getPos(e);
    dernierX = pos.x;
    dernierY = pos.y;
}
function dessiner(e) {
    if (!dessine) return;
    e.preventDefault();
    const pos = getPos(e);
    ctx.beginPath();
    ctx.moveTo(dernierX, dernierY);
    ctx.lineTo(pos.x, pos.y);
    ctx.stroke();
    dernierX = pos.x;
    dernierY = pos.y;
}
function fin() { dessine = false; }

canvas.addEventListener('mousedown', debut);
canvas.addEventListener('mousemove', dessiner);
canvas.addEventListener('mouseup', fin);
canvas.addEventListener('mouseleave', fin);
canvas.addEventListener('touchstart', debut);
canvas.addEventListener('touchmove', dessiner);
canvas.addEventListener('touchend', fin);

document.getElementById('btnEffacer').addEventListener('click', function () {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
});

document.getElementById('btnEnregistrer').addEventListener('click', function () {
    const dataUrl = canvas.toDataURL('image/png');
    const alertZone = document.getElementById('alertZone');

    fetch('signature_pad.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'signature_data=' + encodeURIComponent(dataUrl)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alertZone.innerHTML = '<div class="alert alert-success">تم حفظ التوقيع بنجاح ✅</div>';
            setTimeout(() => location.reload(), 1200);
        } else {
            alertZone.innerHTML = '<div class="alert alert-danger">' + (data.message || 'خطأ غير معروف') + '</div>';
        }
    })
    .catch(() => {
        alertZone.innerHTML = '<div class="alert alert-danger">تعذر الاتصال بالسيرفر.</div>';
    });
});
</script>

</body>
</html>