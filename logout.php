<?php
// logout.php
session_start();
session_unset();   // 清空所有 Session 變數
session_destroy(); // 銷毀 Session 檔案

// 登出後強制導回登入頁面
header("Location: login.php");
exit;