<?php
session_start();

// ביטול כל המשתנים של session
$_SESSION = [];

// הריסת ה-session
session_destroy();

// מניעת שמירת עמודים בזיכרון המטמון (כדי למנוע חזרה אחורה)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// הפניה לדף ההתחברות
header("Location: homepage.php");
exit();
?>
