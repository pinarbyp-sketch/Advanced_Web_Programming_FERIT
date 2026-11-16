<?php
// 1. VERİTABANI BAĞLANTISI (PDO KULLANARAK)
$host = '127.0.0.1';
$db   = 'thesis';
$user = 'root'; // XAMPP'ta varsayılan kullanıcı adı root'tur
$pass = '';     // XAMPP'ta varsayılan şifre boştur

try {
    // Veritabanına bağlanıyoruz
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

// 2. ÖDEVDE İSTENEN ARAYÜZ (INTERFACE) VE SINIF (CLASS)
interface iRadio {
    public function create($data);
    public function save();
    public function read();
}

class GraduateThesis implements iRadio {
    public $work_name;
    public $work_text;
    public $work_link;
    public $identification_number;
    
    private $pdo; // Veritabanı işlemleri için bağlantıyı tutacak

    // Sınıf başlatıldığında veritabanı bağlantısını içine alıyoruz
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Gelen dizideki (array) verileri sınıfın değişkenlerine atıyoruz
    public function create($data) {
        $this->work_name = $data['work_name'] ?? '';
        $this->work_text = $data['work_text'] ?? '';
        $this->work_link = $data['work_link'] ?? '';
        $this->identification_number = $data['identification_number'] ?? '';
    }

    // Sınıftaki verileri MySQL veritabanına kaydediyoruz
    public function save() {
        // SQL Injection saldırılarını önlemek için prepare (hazırlıklı) sorgu kullanıyoruz
        $sql = "INSERT INTO graduate_theses (work_name, work_text, work_link, identification_number) 
                VALUES (:work_name, :work_text, :work_link, :identification_number)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'work_name' => $this->work_name,
            'work_text' => $this->work_text,
            'work_link' => $this->work_link,
            'identification_number' => $this->identification_number
        ]);
    }

    // Veritabanındaki tüm tezleri okuyup geri döndürüyoruz
    public function read() {
        $stmt = $this->pdo->query("SELECT * FROM graduate_theses");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// 3. WEB SCRAPING (CURL İLE BAŞKA SİTEDEN VERİ ÇEKME)
// Ödevde 2. sayfadan 6. sayfaya kadar ($number goes from 2 to 6) döngü kurulması isteniyor
for ($number = 2; $number <= 6; $number++) {
    $url = "https://stup.ferit.hr/zavrsni-radovi/page/$number/";
    
    // cURL başlatılıyor ve hedef URL'ye gidiliyor
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // HTTPS sertifika hatalarını yok say
    $htmlString = curl_exec($ch);
    curl_close($ch);

    if (empty($htmlString)) continue; // Sayfa boş dönerse bir sonrakine geç

    // 4. HTML KODUNU PARÇALAMA (DOMDocument KULLANARAK)
    $dom = new DOMDocument();
    libxml_use_internal_errors(true); // HTML hatalarını ekrana basmasını engelliyoruz
    $dom->loadHTML($htmlString);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    
    // Sitedeki her bir tez bloğunu (article) buluyoruz
    $articles = $xpath->query('//article');

    foreach ($articles as $article) {
        $data = [];
        
        // Başlık ve linki bulma (genellikle başlık h2 veya h3 içindeki <a> etiketindedir)
        $titleNode = $xpath->query('.//h2/a | .//h3/a | .//header/h2/a', $article)->item(0);
        if ($titleNode) {
            $data['work_name'] = trim($titleNode->textContent);
            $data['work_link'] = $titleNode->getAttribute('href');
        }

        // Tezin özet metnini bulma (genellikle entry-content veya benzeri bir div içindeki p etiketindedir)
        $textNode = $xpath->query('.//div[contains(@class, "entry-content")]/p', $article)->item(0);
        if ($textNode) {
            $data['work_text'] = trim($textNode->textContent);
        }

        // Görsel src'sinden kırmızı rakamları (OIB/Firma Numarası) bulma
        $imgNode = $xpath->query('.//img', $article)->item(0);
        if ($imgNode) {
            $imgSrc = $imgNode->getAttribute('src');
            // Regex ile dosya ismindeki sayıları ayıklıyoruz (örn: 08393361027.png)
            if (preg_match('/\/(\d+)\.(png|jpg|jpeg)/i', $imgSrc, $matches)) {
                $data['identification_number'] = $matches[1];
            } else {
                $data['identification_number'] = 'Bulunamadı';
            }
        }

        // Eğer başlık veya link boşsa bu kaydı atla
        if (empty($data['work_name']) || empty($data['work_link'])) {
            continue;
        }

        // Nesneyi oluştur, verileri yükle ve veritabanına kaydet
        $thesis = new GraduateThesis($pdo);
        $thesis->create($data);
        $thesis->save();
    }
}

// 5. TEST VE SONUÇ EKRANI
$testThesis = new GraduateThesis($pdo);
$savedTheses = $testThesis->read();

echo "<h1>İşlem Başarılı!</h1>";
echo "<p>Veritabanına toplam <strong>" . count($savedTheses) . "</strong> bitirme projesi başarıyla kaydedildi.</p>";
echo "<h3>Kaydedilen Verilerin Listesi:</h3>";
echo "<pre>";
print_r($savedTheses);
echo "</pre>";
?>