<?php
session_start();
include "connect.php";

// ווידוא שהגעת מדף האימות
if (!isset($_SESSION['verified_email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['verified_email'];

// שליפת פרטי המשתמש לצורך תצוגה
$query = $conn->prepare("SELECT email FROM admin WHERE email = ?");
$query->bind_param("s", $email);
$query->execute();
$result = $query->get_result();
if ($result->num_rows == 0) {
    header("Location: login.php");
    exit();
}
$user = $result->fetch_assoc();

// טיפול בהחלפת סיסמה
if (isset($_POST['reset_password'])) {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($newPassword !== $confirmPassword) {
        $error = "הסיסמאות אינן תואמות.";
    } elseif (strlen($newPassword) < 6) {
        $error = "על הסיסמה להכיל לפחות 6 תווים.";
    } else {
        // עדכון בסיסמה וביטול קוד האימות
        $update = $conn->prepare("UPDATE admin SET password = ?, verification_code = NULL, code_expiry = NULL WHERE email = ?");
        $update->bind_param("ss", $newPassword, $email);

        if ($update->execute()) {
            unset($_SESSION['verified_email']);
            unset($_SESSION['show_verification']);
            $success = true;
        } else {
            $error = "שגיאה בעדכון הסיסמה. נסה שוב.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<meta charset="UTF-8">
<title>איפוס סיסמה - Admin Panel</title>
<style>
body {
    font-family: 'Segoe UI', Tahoma;
    background: url('b5.jpg') no-repeat center/cover;
    margin: 0;
    padding: 0;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
}
.container {
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.3);
    color: #fff;
    text-align: center;
    width: 400px;
}
input {
    width: 90%;
    padding: 12px;
    margin: 10px;
    border: none;
    border-radius: 8px;
    background: rgba(25,63,92,0.7);
    color: #fff;
}
input::placeholder { color: #ccc; }
button {
    background: rgba(167,178,139,0.7);
    border: none;
    padding: 10px 20px;
    border-radius: 10px;
    cursor: pointer;
    color: #fff;
    font-weight: bold;
    transition: 0.3s;
}
button:hover { background: rgb(186,213,170); color:#000; }
h2 { color: #fff; }
.success {
    color: #b5e2b5;
    font-size: 18px;
    margin-bottom: 15px;
}
.error {
    color: #ff7b7b;
    font-size: 16px;
    margin-bottom: 10px;
}
</style>
</head>
<body>
<div class="container">
<?php if (!isset($success)): ?>
    <h2>🔑 איפוס סיסמה</h2>
    <p>הזן סיסמה חדשה עבור החשבון<br><strong><?php echo htmlspecialchars($user['email']); ?></strong></p>
    <?php if (isset($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="post">
        <input type="password" name="new_password" placeholder="סיסמה חדשה (מינימום 6 תווים)" required minlength="6"><br>
        <input type="password" name="confirm_password" placeholder="אשר סיסמה חדשה" required><br>
        <button type="submit" name="reset_password">עדכן סיסמה</button>
    </form>
<?php else: ?>
    <h2>✅ הסיסמה שונתה בהצלחה!</h2>
    <p class="success">באפשרותך להתחבר כעת עם הסיסמה החדשה.</p>
    <a href="login.php"><button>חזרה להתחברות</button></a>
<?php endif; ?>
</div>
</body>
</html>
