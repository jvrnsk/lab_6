<?php
$user = 'u82950';
$pass = '4218692';
$dbname = 'u82950';
try {
    $db = new PDO(
        "mysql:host=localhost;dbname=$dbname;charset=utf8",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );
}
catch(PDOException $e) {
    die('Ошибка БД: ' . $e->getMessage());
}