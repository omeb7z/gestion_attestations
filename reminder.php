<?php
// الاتصال بقاعدة البيانات
$conn = new mysqli("localhost", "root", "", "projet");

// جلب التواريخ من قاعدة البيانات
$sql = "SELECT id, nom, date_fin FROM certificats";
$result = $conn->query($sql);

$aujourdhui = date("Y-m-d");

while($row = $result->fetch_assoc()) {
    $date_fin = $row['date_fin'];
    $nom = $row['nom'];

    // حساب الفرق بين اليوم وتاريخ النهاية
    $diff = (strtotime($date_fin) - strtotime($aujourdhui)) / (60*60*24);

    // إذا باقي أقل من 7 أيام
    if($diff <= 7 && $diff >= 0) {
        echo "🔔 تذكير: الشهادة ديال $nom غادي تسالي فـ $date_fin<br>";
    }
}
?>
