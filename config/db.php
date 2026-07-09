<?php
// config/db.php

// 資料庫設定變數
$host     = '127.0.0.1';
$db       = 'library_db';
$user     = 'root';
$pass     = '123456789'; // ⚠️ 請改成你登入 phpMyAdmin 時用的密碼！如果是空的就留 ''
$charset  = 'utf8mb4';

// 建立資料庫連線 DSN
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// 連線錯誤控制設定
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // 開啟錯誤異常拋出
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // 用關聯陣列撈取資料
    PDO::ATTR_EMULATE_PREPARES   => false,                  // 禁用模擬預處理，強化資安
];

try {
    // 建立 PDO 連線物件
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // 若連線失敗，中斷程式並顯示錯誤訊息
    die("資料庫連線失敗，錯誤訊息: " . $e->getMessage());
}
?>