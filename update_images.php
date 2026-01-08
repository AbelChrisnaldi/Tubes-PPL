<?php
require_once 'config.php';

$updates = [
    'Nasi Goreng Spesial' => 'nasi_goreng_spesial.png',
    'Ayam Goreng' => 'ayam_goreng.png',
    'Es Teh Manis' => 'es_teh_manis.png',
    'Puding Coklat' => 'puding_coklat.png'
];

try {
    foreach ($updates as $name => $image) {
        $stmt = $conn->prepare("UPDATE menu SET image = ? WHERE name = ?");
        $stmt->execute([$image, $name]);
        echo "Updated $name with $image<br>";
    }
    echo "Done updating images!";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
