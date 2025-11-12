<?php
session_start();
include "connect.php";
include "user.php";

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// אם יש לך PHPMailer בספרייה, דרוש אותו (אופציונלי, אם את משתמשת ב-mail() פשוט הסירי את הבלוק).
if (file_exists(__DIR__ . '/PHPMailer/src/Exception.php')) {
    require_once __DIR__ . '/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';
}

// CSRF token בסיסי
if (!isset($_SESSION['contact_csrf'])) {
    $_SESSION['contact_csrf'] = bin2hex(random_bytes(16));
}

$contact_msg = '';
$contact_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    // בדיקת CSRF
    $token = $_POST['contact_csrf'] ?? '';
    if (!hash_equals($_SESSION['contact_csrf'], $token)) {
        $contact_error = 'שגיאה: טוקן לא תקין.';
    } else {
        // קלטים - Trim
        $name_raw = trim($_POST['contact_name'] ?? '');
        $phone_raw = trim($_POST['contact_phone'] ?? '');
        $email_raw = trim($_POST['contact_email'] ?? '');
        $notes_raw = trim($_POST['contact_notes'] ?? '');

        // סניטיזציה ובדיקות
        $name = mb_substr($name_raw, 0, 150);
        $phone = preg_replace('/[^\d\+]/', '', $phone_raw); // ספרות ו־+
        $email = filter_var($email_raw, FILTER_VALIDATE_EMAIL) ? $email_raw : '';
        $notes = mb_substr($notes_raw, 0, 2000);

        // ולידציה בסיסית
        if ($name === '' || $phone === '' || $email === '') {
            $contact_error = 'אנא מלאי שם, טלפון ואימייל.';
        } elseif (!preg_match('/^\+?\d{7,15}$/', $phone)) {
            $contact_error = 'הטלפון צריך להכיל 7–15 ספרות (מותר + בהתחלה).';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $contact_error = 'אימייל לא תקין.';
        } else {
            // הוספה ל־DB (prepared statement) — מניחים שהטבלה contacts כבר קיימת
            if (isset($conn) && $conn instanceof mysqli) {
                $insert_sql = "INSERT INTO contacts (name, phone, email, notes) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($insert_sql);
                if ($stmt) {
                    $stmt->bind_param("ssss", $name, $phone, $email, $notes);
                    if ($stmt->execute()) {
                        $inserted = true;
                    } else {
                        $inserted = false;
                        // אפשר לוג: $conn->error
                    }
                    $stmt->close();
                } else {
                    $inserted = false;
                }
            } else {
                $inserted = false;
            }

            // שליחת מייל עם פרטי הפנייה (PHPMailer אם זמין, אחרת mail())
            $sent = false;
            $mail_error_msg = '';
            $toEmail = 'taimakizel18@gmail.com'; // החליפי אם צריך

            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                try {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'taimakizel18@gmail.com'; // עדכני
                    $mail->Password   = 'cgkk vmni esjv rjom'; // עדכני (השתמשי בסיסמת אפליקציה)
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    $mail->setFrom('noreply@architect.com', 'Architect Contact Form');
                    $mail->addAddress($toEmail);
                    $mail->addReplyTo($email, $name);

                    $mail->isHTML(true);
                    $mail->CharSet = 'UTF-8';
                    $mail->Subject = "פנייה חדשה מהאתר - {$name}";

                    $body = "<h3>פנייה חדשה</h3>";
                    $body .= "<p><strong>שם:</strong> " . htmlspecialchars($name) . "</p>";
                    $body .= "<p><strong>טלפון:</strong> " . htmlspecialchars($phone) . "</p>";
                    $body .= "<p><strong>אימייל:</strong> " . htmlspecialchars($email) . "</p>";
                    $body .= "<p><strong>הערות:</strong><br>" . nl2br(htmlspecialchars($notes)) . "</p>";
                    $body .= "<p style='font-size:0.9em;color:#666;'>נשלח בתאריך: " . date('Y-m-d H:i:s') . "</p>";

                    $mail->Body = $body;
                    $mail->AltBody = "פנייה חדשה\n\nשם: $name\nטלפון: $phone\nאימייל: $email\nהערות:\n$notes";

                    $mail->send();
                    $sent = true;
                } catch (Exception $e) {
                    $mail_error_msg = $mail->ErrorInfo ?? $e->getMessage();
                    $sent = false;
                }
            } else {
                // גיבוי: mail()
                $subject = "פנייה חדשה מהאתר - $name";
                $message_body = "פנייה חדשה\n\nשם: $name\nטלפון: $phone\nאימייל: $email\nהערות:\n$notes\n\nנשלחה בתאריך: " . date('Y-m-d H:i:s');
                $headers = "From: noreply@architect.com\r\n" .
                           "Reply-To: " . $email . "\r\n" .
                           "Content-Type: text/plain; charset=UTF-8\r\n";
                $sent = @mail($toEmail, $subject, $message_body, $headers);
                if (!$sent) $mail_error_msg = 'mail() failed';
            }

            if (($inserted ?? false) && $sent) {
                $contact_msg = 'הפנייה נשלחה והועלתה בהצלחה — נחזור אליך בהקדם!';
                // מחליפים CSRF token כדי למנוע שליחה כפולה
                $_SESSION['contact_csrf'] = bin2hex(random_bytes(16));
            } else {
                // הודעת שגיאה כללית — אפשר להציג הודעות מפורטות יותר בלוג פנימי
                $contact_error = 'אירעה שגיאה בשליחת הפנייה.';
                if (!($inserted ?? false)) $contact_error .= ' (שגיאת DB)';
                if (!$sent) $contact_error .= ' (שגיאת שליחת מייל: ' . htmlspecialchars($mail_error_msg) . ')';
            }
        } // end validation else
    } // end csrf else
} // end POST handler
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<meta charset="UTF-8">
<title>Social Media</title>
<link rel="icon" href="icon.png">
<style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .header {
            padding: 15px 30px;
            display: flex;            
            position: relative;
            justify-content: space-between; 
            color:#d6cec3;
        }

        .header h1 {
            font-size: 37px;
            color:#D6CEC3;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: bold;
            margin-right: 70px;
            margin-top: 8px;

        }

        /* ====== SIDEBAR חדש ====== */
        .open-btn {
            position: fixed;      /* ✅ קבוע על המסך */
            z-index: 2000;        /* ✅ מעל התפריט */
            font-size: 30px;
            background:none;
            border: none;
            color: #000;
            padding: 10px 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
        }

        .open-btn:hover {
            background-color:#E5EDEF;
        }

        .sidebar {
            height: 100%;
            width: 0;
            position: fixed;
            top: 0;
            right: 0; /* מימין */
            background-color: rgba(0,0,0,0.85);
            overflow-x: hidden;
            transition: 0.5s;
            padding-top: 60px;
            z-index: 999;
        }

        .nav-links2 {
            list-style: none;
            padding: 0;
            margin: 0;
            text-align: center;
            font-weight: bold;
        }

        .nav-links2 li {
            padding: 15px 0;
        }

        .nav-links2 li a {
            color: white;
            text-decoration: none;
            font-size: 22px;
            transition: 0.3s;
        }

        .nav-links2 li a:hover {
            color:#7FAAA8;
        }
        .container {
            max-width: 750px;
            margin: 50px auto;
            display: flex;
            flex-direction:space-between;
            align-items: center;
            gap: 25px;
            text-align: center;

        }
        .card {
            width: 80%;
            background:  #4d9fab14;
            border: 2px solid  #000000ff;
            border-radius: 15px;
            padding: 25px;
            transition: 0.3s;
            align-items: center;
            height: 250px;
        }
        .card:hover {
            background: rgba(178,242,187,0.1);
            transform: scale(1.05);
        }

        a.social-btn {
            display: inline-block;
            color: #000000ff;
            text-decoration: none;
            font-weight: bold;
            border: 1px solid black;
            padding: 10px 25px;
            border-radius: 8px;
            transition: 0.3s;
        }
        a.social-btn:hover {
            background: #b2f2bb;
            color: #121212;
        }
        .footer {
            margin-top: 50px;
            font-size: 14px;
            color: #aaa;
        }
        .icon{
            width: 45px;
            height: 45px;
        }
</style>
</head>
<body>
<div class="header">
    <h1>רשתות חברתיות</h1>
    <img src="images/logoRandaa.png" alt="Logo" style="height:90px;">
    <button class="open-btn" onclick="toggleNav()">☰</button>
</div>
<div id="mySidebar" class="sidebar">
        <ul class="nav-links2">
            <li><a href="homepage.php">Home</a></li>
            <li><a href="#">About Us</a></li>
            <li><a href="social_media.php">Social Media</a></li>
            <li><a href="admin.php">Admin</a></li>
        </ul>
</div>
<div class="container">

    <div class="card">
        <div><img class="icon" src="images/instagram.png"></div>
        <h2>Instagram</h2>
        <p>עקבו אחרי בעמוד האינסטגרם שלי כדי לראות פרויקטים חדשים ועדכונים.</p>
        <a href="https://www.instagram.com/randa.azam.architect?igsh=MTk0dHprdXBrenB1OQ==" target="_blank" class="social-btn">פתח אינסטגרם</a>
    </div>

    <div class="card">
        <div><img class="icon" src="images/facebook.png"></div>
        <h2>Facebook</h2>
        <p>היכנסו לדף הפייסבוק שלי והישארו מעודכנים בפרסומים והמלצות.</p>
        <a href="https://www.facebook.com/share/1CeYv9mgRJ/?mibextid=wwXIfr" target="_blank" class="social-btn">פתח פייסבוק</a>
    </div>

    <div class="card">
        <div><img class="icon" src="images/whatsapp.png"></div>
        <h2>WhatsApp</h2>
        <p> שלחו לי הודעה ישירות ב־WhatsApp רק בלחיצה אחת!</p>
        <a href="https://wa.me/972503005891" target="_blank" class="social-btn">שלח הודעה</a>
    </div>
</div>
<!-- Contact Card (שים/י במקום המתאים בתוך ה-container שלך) -->
<div class="card" style="max-width:700px; margin:20px auto;">
    <div style="text-align:center; margin-bottom:10px;">
        <h2 style="margin:0;">📩 שלח פנייה</h2>
        <p style="margin:5px 0 15px 0; color:#ddd;">מלא/י פרטים ונחזור אליך בהקדם</p>
    </div>

    <?php if(!empty($contact_msg)): ?>
        <div style="background:#173b1f; color:#bff0c7; padding:10px; border-radius:8px; margin-bottom:10px;">
            <?= htmlspecialchars($contact_msg) ?>
        </div>
    <?php endif; ?>

    <?php if(!empty($contact_error)): ?>
        <div style="background:#3b1414; color:#ffbcbc; padding:10px; border-radius:8px; margin-bottom:10px;">
            <?= htmlspecialchars($contact_error) ?>
        </div>
    <?php endif; ?>

    <form method="post" style="display:flex; flex-direction:column; gap:10px; align-items:center;">
        <input type="hidden" name="contact_csrf" value="<?php echo htmlspecialchars($_SESSION['contact_csrf']); ?>">

        <input type="text" name="contact_name" placeholder="שם מלא" required
               style="width:95%; padding:10px; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:transparent; color:black;">

        <input type="tel" name="contact_phone" placeholder="טלפון (כולל קידומת)" required
               style="width:95%; padding:10px; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:transparent; color:black;">

        <input type="email" name="contact_email" placeholder="אימייל" required
               style="width:95%; padding:10px; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:transparent; color:black;">

        <textarea name="contact_notes" rows="4" placeholder="ההודעה שלך" style="width:95%; padding:10px; border-radius:8px; border:1px solid rgba(255,255,255,0.1); background:transparent; color:black;"></textarea>

        <button type="submit" name="contact_submit" style="padding:12px 20px; border-radius:8px; border:1px solid #b2f2bb; background:transparent; color:black; cursor:pointer;">
            שלח פנייה
        </button>
    </form>
</div>

<div class="footer">© <?php echo date('Y'); ?> כל הזכויות שמורות</div>
<script>
function toggleNav() {
    let sidebar = document.getElementById("mySidebar");
    sidebar.style.width = sidebar.style.width === "250px" ? "0" : "250px";
}
</script>
</body>
</html>
