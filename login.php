<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// חיבור למסד הנתונים
include "connect.php";
include "user.php";
// === פונקציה לשליחת קוד אימות במייל ===
function sendVerificationCode($email, $code) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'taimakizel18@gmail.com'; 
        $mail->Password = 'cgkk vmni esjv rjom'; // סיסמת אפליקציה
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('noreply@architect.com', 'Architect Panel');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = "Verification Code for Password Reset";
        $mail->Body = "
        <h2 style='color:black;'>🔐 Verification Code</h2>
        <p>Your verification code is:</p>
        <h1 style='color:#3c763d;'>$code</h1>
        <p>This code is valid for 15 minutes.</p>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// === פונקציה ליצירת קוד ===
function generateCode() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// === התחברות רגילה + חסימה ===
if (isset($_POST['submit'])) {
    $email = $_POST['email'];
    $pass = $_POST['password'];

    $query = "SELECT * FROM admin WHERE email='$email' AND password='$pass'";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['email'] = $user['email'];
        $_SESSION['is_admin'] = $user['is_admin'];
        unset($_SESSION['failed_attempts']);
        header("Location: homepage.php");
        exit;
    } else {
        if (!isset($_SESSION['failed_attempts'])) $_SESSION['failed_attempts'] = 0;
        $_SESSION['failed_attempts']++;

        if ($_SESSION['failed_attempts'] >= 3) {
            $_SESSION['blocked_email'] = $email;
            $code = generateCode();
            $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            $conn->query("UPDATE admin SET verification_code='$code', code_expiry='$expiry' WHERE email='$email'");
            sendVerificationCode($email, $code);
            $_SESSION['show_verification'] = true;
            echo "<script>alert('בוצעו 3 ניסיונות כושלים. נשלח קוד איפוס למייל שלך.');</script>";
        } else {
            $remain = 3 - $_SESSION['failed_attempts'];
            echo "<script>alert('שם משתמש או סיסמה שגויים. נותרו $remain ניסיונות.');</script>";
        }
    }
}

// === שליחת קוד חדש בלחיצה על "שכחתי סיסמה" ===
if (isset($_POST['forgot_password'])) {
    $email = $_POST['email'];
    $query = $conn->query("SELECT email FROM admin WHERE email='$email'");
    if ($query->num_rows > 0) {
        $code = generateCode();
        $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        $conn->query("UPDATE admin SET verification_code='$code', code_expiry='$expiry' WHERE email='$email'");
        sendVerificationCode($email, $code);
        $_SESSION['show_verification'] = true;
        $_SESSION['blocked_email'] = $email;
        echo "<script>alert('קוד איפוס נשלח למייל שלך.');</script>";
    } else {
        echo "<script>alert('האימייל הזה לא קיים במערכת.');</script>";
    }
}

// === אימות קוד שנשלח ===
if (isset($_POST['verify_code'])) {
    $email = $_POST['user_email'];
    $code = $_POST['verification_code'];

    $res = $conn->query("SELECT verification_code, code_expiry FROM admin WHERE email='$email'");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        if ($code == $row['verification_code'] && strtotime($row['code_expiry']) > time()) {
            $_SESSION['verified_email'] = $email;
            header("Location: reset_password.php");
            exit;
        } else {
            echo "<script>alert('קוד שגוי או שפג תוקפו. נשלח קוד חדש למייל.');</script>";
            $newCode = generateCode();
            $newExpiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            $conn->query("UPDATE admin SET verification_code='$newCode', code_expiry='$newExpiry' WHERE email='$email'");
            sendVerificationCode($email, $newCode);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<meta charset="UTF-8">
<title>Admin Login</title>
<style>
body {
    font-family: 'Segoe UI';
    background: url('b5.jpg') no-repeat center/cover;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

.container {
    background: rgba(0, 0, 0, 0.55); /* כהה יותר לטופס */
    backdrop-filter: blur(8px);
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.4);
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
    background: rgba(255,255,255,0.15);
    color: #fff;
}

input::placeholder {
    color: #ddd;
}

button {
    background: rgba(167,178,139,0.8);
    border: none;
    padding: 10px 20px;
    border-radius: 10px;
    cursor: pointer;
    color: #fff;
    font-weight: bold;
    transition: 0.3s;
}

button:hover {
    background: rgb(186,213,170);
    color:#000;
}

h2 {
    color: #a6ffb7;
    text-shadow: 0 0 8px rgba(0,0,0,0.6);
}

p {
    color: #f0f0f0;
    font-size: 16px;
}

.link { color: #fff; text-decoration: none; display: block; margin-top: 10px; }
</style>
</head>
<body>
<div class="container">
<?php if (!isset($_SESSION['show_verification'])): ?>
    <h2>🔐 התחברות מנהל</h2>
    <form method="post">
        <input type="email" name="email" placeholder="Email" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit" name="submit">התחברות</button>
        <button type="submit" name="forgot_password">שכחתי סיסמה</button>
    </form>
<?php else: ?>
    <h2>אימות קוד</h2>
    <p>הכנס את הקוד שנשלח למייל שלך</p>
    <form method="post">
        <input type="text" name="verification_code" maxlength="6" placeholder="000000" required><br>
        <input type="hidden" name="user_email" value="<?php echo $_SESSION['blocked_email']; ?>">
        <button type="submit" name="verify_code">אימות</button>
    </form>
<?php endif; ?>
</div>

</body>
</html>
