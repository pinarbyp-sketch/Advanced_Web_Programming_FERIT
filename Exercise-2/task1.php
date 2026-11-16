<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$dbName = 'thesis'; // İstenilen veritabanı adı buraya yazılır
$host = '127.0.0.1';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

$backupText = "";

// 2. VERİTABANINDAKİ TABLOLARI BULMA
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

// 3. HER TABLO İÇİN VERİLERİ OKUYUP SQL SORGUSU OLUŞTURMA
foreach ($tables as $table) {
    $rows = $pdo->query("SELECT * FROM $table")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($rows)) continue;

    foreach ($rows as $row) {
        // Sütun isimlerini ve değerlerini ayırmak için
        $columns = array_keys($row);
        $values = array_values($row);

        $colString = implode(', ', $columns);
        
        // Değerlerin içindeki olası tırnak işaretlerini kaçış karakteriyle (addslashes) temizleme
        $valString = implode("', '", array_map('addslashes', $values));

        // Ödevde istenen spesifik formata uygun INSERT cümlesini oluşturma
        $backupText .= "INSERT INTO $table ($colString) \nVALUES ('$valString');\n\n";
    }
}

// 4. METİN DOSYASINA (TXT) KAYDETME
$txtFileName = $dbName . "_backup.txt";
file_put_contents($txtFileName, $backupText);

// 5. ZIP DOSYASINA SIKIŞTIRMA
$zipFileName = $dbName . "_backup.zip";
$zip = new ZipArchive();

if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    $zip->addFile($txtFileName, $txtFileName);
    $zip->close();
    
    echo "<h1>Yedekleme Başarılı!</h1>";
    echo "<p>Veritabanı <b>$txtFileName</b> dosyasına yazıldı ve <b>$zipFileName</b> olarak sıkıştırıldı.</p>";
    echo "<p><a href='$zipFileName' style='padding: 10px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;'>ZIP Dosyasını İndir</a></p>";
} else {
    echo "<h1>Hata!</h1>";
    echo "<p>ZIP dosyası oluşturulamadı. Sunucunun ZipArchive eklentisi aktif olmayabilir.</p>";
}
?>