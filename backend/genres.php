<?php
$d = json_decode(file_get_contents('http://localhost:8000/api/movies'), true);
$g = [];
foreach($d as $m) $g = array_merge($g, $m['genres'] ?? []);
print_r(array_unique($g));
