<?php
require_once 'config.php';
$stmt = $conn->query("SELECT name FROM menu");
while($row = $stmt->fetch()) {
    echo $row['name'] . "\n";
}
?>
