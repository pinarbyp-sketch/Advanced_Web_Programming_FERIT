<?php
// XML dosyasının adını belirtiyoruz
$xmlFile = 'LV2.xml';

// Dosyanın klasörde olup olmadığını kontrol ediyoruz
if (file_exists($xmlFile)) {
    // PHP'nin yerleşik SimpleXML kütüphanesi ile dosyayı okuyoruz
    $xml = simplexml_load_file($xmlFile);
    
    // Kartların yan yana düzgün durması için CSS Flexbox kullanıyoruz
    echo "<div style='display: flex; flex-wrap: wrap; gap: 20px; font-family: Arial, sans-serif; padding: 20px;'>";
    
    // XML içindeki her bir <record> etiketi için döngü başlatıyoruz
    foreach ($xml->record as $person) {
        $ime = $person->ime;
        $prezime = $person->prezime;
        $email = $person->email;
        $slika = $person->slika;
        $zivotopis = $person->zivotopis;
        
        // Her bir kişi için HTML profil kartı oluşturuyoruz
        echo "<div style='border: 1px solid #ddd; padding: 15px; width: 320px; border-radius: 10px; box-shadow: 2px 2px 10px rgba(0,0,0,0.05); background: #f9f9f9;'>";
        // Profil resmi (float ile sola yaslıyoruz)
        echo "<img src='{$slika}' alt='Profile' style='float: left; width: 60px; height: 60px; border-radius: 50%; margin-right: 15px;'>";
        // İsim ve Soyisim
        echo "<h3 style='margin: 0 0 5px 0; color: #333;'>{$ime} {$prezime}</h3>";
        // E-posta
        echo "<p style='margin: 0 0 10px 0; font-size: 14px;'>📧 <a href='mailto:{$email}' style='color: #0066cc; text-decoration: none;'>{$email}</a></p>";
        // Özgeçmiş (Resume)
        echo "<p style='margin: 0; font-size: 13px; color: #555; line-height: 1.4; clear: both; padding-top: 10px; border-top: 1px solid #eee;'><strong>Resume:</strong> {$zivotopis}</p>";
        echo "</div>";
    }
    
    echo "</div>";
} else {
    // Dosya bulunamazsa hata mesajı veriyoruz
    echo "<h2 style='color: red;'>Hata: LV2.xml dosyası bulunamadı! Lütfen dosyanın ferit_tez klasöründe olduğundan emin olun.</h2>";
}
?>