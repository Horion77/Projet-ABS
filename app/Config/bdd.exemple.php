<?php

$host     = '127.0.0.1';
$dbname   = 'abs_db';
$user     = 'root';
$pass     = 'root';
$charset  = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$dsn = "mysql:host={$host};charset={$charset};dbname={$dbname}";

$pdo = new PDO($dsn, $user, $pass, $options);
