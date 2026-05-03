<?php
$s=microtime(true);
$pdo = new PDO('mysql:host=database;dbname=cinemate_users', 'root', 'root');
echo 'Connect: ' . round(microtime(true)-$s, 4) . "s\n";
$s=microtime(true);
$stmt = $pdo->query('SELECT * FROM movie ORDER BY release_date DESC LIMIT 200');
$rows = $stmt->fetchAll();
echo 'Query: ' . round(microtime(true)-$s, 4) . "s\n";
