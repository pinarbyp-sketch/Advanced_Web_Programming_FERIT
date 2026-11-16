<?php
// Şifreleme Ayarları (Mcrypt kaldırıldığı için OpenSSL AES-256 kullanıyoruz)
$encryption_key = 'ferit_secret_key_2026';
$cipher_algo = 'aes-256-cbc';
$iv = '1234567890123456'; // AES için 16 karakterli başlangıç vektörü

$upload_dir = 'uploads/';

// 1. DOSYA YÜKLEME VE ŞİFRELEME
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['fileToUpload'])) {
    $file = $_FILES['fileToUpload'];
    $allowed_ext = ['pdf', 'jpeg', 'jpg', 'png'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // Sadece izin verilen uzantıları kabul et
    if (in_array($file_ext, $allowed_ext)) {
        // Dosyanın ham (orijinal) içeriğini oku
        $file_data = file_get_contents($file['tmp_name']);
        
        // İçeriği OpenSSL ile şifrele
        $encrypted_data = openssl_encrypt($file_data, $cipher_algo, $encryption_key, 0, $iv);
        
        // Dosyanın sonuna .enc ekleyip sunucudaki uploads/ klasörüne kaydet
        $encrypted_file_path = $upload_dir . basename($file['name']) . '.enc';
        file_put_contents($encrypted_file_path, $encrypted_data);
        
        echo "<div style='color: green; padding: 10px; border: 1px solid green; margin-bottom: 20px;'>Dosya başarıyla yüklendi ve şifrelendi!</div>";
    } else {
        echo "<div style='color: red; padding: 10px; border: 1px solid red; margin-bottom: 20px;'>Hata: Sadece PDF, JPEG ve PNG dosyalarına izin verilir.</div>";
    }
}

// 2. ŞİFRELİ DOSYAYI İNDİRME VE ŞİFREYİ ÇÖZME
if (isset($_GET['download'])) {
    $filename = basename($_GET['download']);
    $filepath = $upload_dir . $filename;
    
    if (file_exists($filepath)) {
        // Şifrelenmiş içeriği oku
        $encrypted_data = file_get_contents($filepath);
        
        // Şifreyi çözüp orijinal içeriği elde et
        $decrypted_data = openssl_decrypt($encrypted_data, $cipher_algo, $encryption_key, 0, $iv);
        
        // Orijinal dosya adını bul (sondaki .enc kısmını at)
        $original_filename = str_replace('.enc', '', $filename);
        
        // Tarayıcıya bunun bir dosya indirme işlemi olduğunu söyle
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $original_filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($decrypted_data));
        
        // Çözülmüş veriyi tarayıcıya yolla ve işlemi sonlandır
        echo $decrypted_data;
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Secure File Upload</title>
    <style>body { font-family: Arial, sans-serif; padding: 30px; }</style>
</head>
<body>

    <h2>1. Güvenli Dosya Yükleme Formu</h2>
    <form action="task3.php" method="post" enctype="multipart/form-data" style="background: #f4f4f4; border:1px solid #ccc; padding:20px; width:400px; border-radius: 5px;">
        <label>Bir PDF veya Görsel (jpeg, png) seçin:</label><br><br>
        <input type="file" name="fileToUpload" required><br><br>
        <input type="submit" value="Sunucuya Şifreleyerek Yükle" style="padding: 8px 15px; background: #28a745; color: white; border: none; cursor: pointer;">
    </form>

    <br><hr><br>

    <h2>2. Sunucudaki Şifrelenmiş Dosyalar</h2>
    <ul>
        <?php
        // uploads/ klasöründeki dosyaları tara
        $files = array_diff(scandir($upload_dir), array('.', '..'));
        if(count($files) > 0) {
            foreach ($files as $f) {
                echo "<li style='margin-bottom: 10px;'>
                        <b>{$f}</b> 
                        <a href='task3.php?download={$f}' style='margin-left: 15px; padding: 5px 10px; background: #007bff; color: white; text-decoration: none; border-radius: 3px;'>
                          Şifreyi Çöz ve İndir
                        </a>
                      </li>";
            }
        } else {
            echo "<p>Henüz yüklenmiş bir dosya bulunmuyor.</p>";
        }
        ?>
    </ul>

</body>
</html>