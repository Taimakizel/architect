<?php
include "connect.php";
include "user.php";
$query = "SELECT * FROM products ORDER BY id DESC";
$result = $conn->query($query);
session_start();
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>קטלוג פרויקטים - אדריכלית</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            font-family: Times New Roman;
            margin: 0;
            padding: 0;
            color: #333;
            min-height: 100vh;
            background-color: #E5EDEF;
        }

        /* אזור רקע עליון */
        .top-section {
            height: 65vh;
            background: url('images/Backgroundd.jpeg') no-repeat center center;
            background-size: cover;
            position: relative;
        }

        /* שכבת מעבר רכה מלמעלה למטה */
        .top-section::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 200px;
            background: linear-gradient(to bottom, rgba(229,237,239,0), #E5EDEF 90%);
        }

        /* ===== HEADER על גבי התמונה ===== */
        .header {
            padding: 15px 30px;
            display: flex;            
            position: relative;
            justify-content: space-between; 
        }

        .header h1 {
            font-size: 37px;
            color: #000000ff;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: bold;
            margin-right: 70px;
            margin-top: 8px;

        }

        .header h1 span {
            color:rgba(14, 91, 91, 1);
        }

        .btn {
            font-size: 18px;
            background: none;
            border: none;
            cursor: pointer;
            transition: 0.3s ease;
            color: white;
            font-weight: bold;
            padding: 8px 12px;
            border-radius: 6px;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: rgba(167, 178, 139, 0.7);
        }

        /* ====== SIDEBAR חדש ====== */
        .open-btn {
            position: fixed;      /* ✅ קבוע על המסך */
            z-index: 3;        /* ✅ מעל התפריט */
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
        .sidebar a {
             text-align: left;
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

        /* ===== אזור המוצרים ===== */
        .content-wrapper {
            display: flex;
            padding: 50px;
            max-width: 1600px;
            margin: 0 auto;
            gap: 30px;
            background-color: #E5EDEF;
        }

        .main {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }

        .card {
            background: #d6cec3aa;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.58);
        }

        .card img {
            width: 100%;
            height: 240px;
            object-fit: cover;
        }

        .card-content {
            padding: 15px;
        }

        .card h3 {
            font-size: 28px;
            color: #000;
            margin-top: 0;
            margin-bottom: 10px;
            position: relative;
            padding-bottom: 8px;
        }

        .card h3:after {
            content: '';
            position: absolute;
            width: 120px;
            height: 2px;
            background-color: #4a7270ff;
            bottom: 0;
            left: 0;
            margin-left: 100px;
        }

        .card-info {
            display: flex;
            flex-direction: column;
            text-align: right;
            gap: 5px;
            margin-top: 10px;
        }

        .info-item {
            font-size: 22px;
            color: #000;
            white-space: pre-line;
            word-break: break-word;
        }

        .select {
            background-color: #39625cbe;
            border: none;
            padding: 12px 20px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s;
            width: 50%;
            margin: 10px auto;
            display: block;
            color: white;
        }

        .select:hover {
            background-color: #d2e2e0be;
            color: black;
            transform: translateY(-2px);
        }
        .logo{
            height:90px;
        }
    </style>
</head>
<body>

<div class="top-section">
    <div class="header">
        <button class="open-btn" onclick="toggleNav()">☰</button>
        <h1>קטלוג פרויקטים <span>אדריכלית</span></h1>
        <img src="images/logoRandaa.png" alt="Logo" class="logo">
    </div>
</div>
    <!-- תפריט צד -->
    <div id="mySidebar" class="sidebar">
        <ul class="nav-links2">
            <li><a href="#">About Us</a></li>
            <li><a href="social_media.php">Social Media</a></li>
            <li><a href="admin.php">Admin</a></li>
        </ul>
    </div>
<div class="content-wrapper">
    <div class="main">
        <?php
            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo "<div class='card'>";
                    if (!empty($row['image'])) {
                        echo "<img src='" . htmlspecialchars($row['image']) . "' alt='Project Image'>";
                    } else {
                        echo "<img src='images/default.jpg' alt='No Image Available'>";
                    }

                    echo "<div class='card-content'>";
                    $h3 = htmlspecialchars($row['name']);
                    $h3_dir = preg_match('/[א-ת]/u', $h3) ? 'rtl' : 'ltr';
                    echo "<h3 dir='$h3_dir'>" . $h3 . "</h3>";
                    echo "<div class='card-info'>";

                    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1) {
                        echo "<div class='info-item'><strong>קוד מוצר:</strong> " . htmlspecialchars($row['id']) . "</div><br>";
                    }

                    echo "<div class='info-item'><strong>קטגוריה:</strong> " . htmlspecialchars($row['category']) . "</div><br>";
                    echo "<div class='info-item'><strong>תיאור:</strong>" . nl2br(htmlspecialchars($row['description'])) . "</div>";
                    echo "</div><br>";
                    echo "</div>";

                    echo "<form method='post' action='selectedProject.php' style='width: 100%;'>";
                    echo "<input type='hidden' name='id' value='" . $row['id'] . "'>";
                    echo "<button type='submit' class='select'>צפייה</button>";
                    echo "</form>";
                    echo "</div>";
                }
            } else {
                echo "<div class='empty-state'><h3>לא נמצאו פרויקטים</h3></div>";
            }
            $conn->close();
        ?>
    </div>
</div>

<script>
function toggleNav() {
    let sidebar = document.getElementById("mySidebar");
    sidebar.style.width = sidebar.style.width === "250px" ? "0" : "250px";
}
</script>

</body>
</html>
