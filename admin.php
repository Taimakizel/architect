<?php
include "connect.php";
include "user.php";

session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$message = ''; 

// --- אימות גישה ---
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: index.php");
    exit();
}

$user = new User('', '', $_SESSION['email'], '', '');

// --- הוספת מוצר ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product_submit'])) {
    $name = $_POST['name'] ?? '';
    $category = $_POST['category'] ?? '';
    $description = $_POST['description'] ?? '';
    $image = '';

    if (!empty($_FILES['image']['name'])) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $new_name = uniqid('prod_') . '.' . $ext;
        $dest = $upload_dir . $new_name;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) $image = $dest;
    }

    $stmt = $conn->prepare("INSERT INTO products (name, category, image, description) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $category, $image, $description);
    $message = $stmt->execute() ? "✅ המוצר '$name' נוסף בהצלחה!" : "❌ שגיאה בהוספה: " . $stmt->error;
    $stmt->close();
}

// --- עדכון מוצר ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product_submit'])) {
    $id = $_POST['id'] ?? 0;
    $name = $_POST['name'] ?? '';
    $category = $_POST['category'] ?? '';
    $description = $_POST['description'] ?? '';
    $image = $_POST['existing_image'] ?? '';

    if (!empty($_FILES['image']['name'])) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $new_name = uniqid('prod_') . '.' . $ext;
        $dest = $upload_dir . $new_name;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) $image = $dest;
    }

    $stmt = $conn->prepare("UPDATE products SET name=?, category=?, image=?, description=? WHERE id=?");
    $stmt->bind_param("ssssi", $name, $category, $image, $description, $id);
    $message = $stmt->execute() ? "✏️ המוצר עודכן בהצלחה!" : "❌ שגיאה בעדכון: " . $stmt->error;
    $stmt->close();
}

// --- מחיקת מוצר ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product_submit'])) {
    $id = $_POST['id'] ?? 0;
    $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
    $stmt->bind_param("i", $id);
    $message = $stmt->execute() ? "🗑️ המוצר נמחק בהצלחה!" : "❌ שגיאה במחיקה: " . $stmt->error;
    $stmt->close();
}

// --- העלאת תמונות נוספות ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_images_submit'])) {
    $ProNum = $_POST['ProNum'] ?? 0;
    if (!empty($_FILES['image_file']['name'][0])) {
        $upload_dir = 'uploads_extra/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        foreach ($_FILES['image_file']['tmp_name'] as $key => $tmp) {
            $ext = strtolower(pathinfo($_FILES['image_file']['name'][$key], PATHINFO_EXTENSION));
            $new_name = uniqid('img_') . '.' . $ext;
            $dest = $upload_dir . $new_name;
            if (move_uploaded_file($tmp, $dest)) {
                $stmt = $conn->prepare("INSERT INTO images (ProNum, image) VALUES (?, ?)");
                $stmt->bind_param("is", $ProNum, $dest);
                $stmt->execute();
                $stmt->close();
            }
        }
        $message = "✅ התמונות הועלו בהצלחה למוצר $ProNum!";
    } else {
        $message = "❌ לא נבחרו תמונות.";
    }
}

// --- מחיקת תמונה בודדת ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image_submit'])) {
    $image_id = $_POST['image_id'] ?? 0;
    $image_path = $_POST['image_path'] ?? '';

    if (!empty($image_path) && file_exists($image_path)) unlink($image_path);

    $stmt = $conn->prepare("DELETE FROM images WHERE num=?");
    $stmt->bind_param("i", $image_id);
    $message = $stmt->execute() ? "🗑️ התמונה נמחקה בהצלחה!" : "❌ שגיאה במחיקת תמונה: " . $stmt->error;
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<title>ניהול מוצרים</title>
<style>
body { 
    font-family: Arial; 
    background-color: #f4f4f4; 
    margin: 0; 
    padding: 0; 
}
.header {
            padding: 15px 30px;
            display: flex;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            
            position: relative;
            justify-content: space-between; 

        }

        .header h1 {
            font-size: 28px;
            color: #000000ff;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            margin-right: 70px;
        }

        .header h1 span {
            color: rgb(186, 213, 170);
        }

    .btn {
    font-size: 18px;
    background: none;
    border: none;
    cursor: pointer;
    transition: 0.3s ease;
    color:black;
    font-weight: bold;
    padding: 8px 12px;
    border-radius: 6px;
    transition: background-color 0.3s;
}
.btn:hover {
    color: rrgba(7, 74, 66, 0.7)
}
nav { 
    display: flex; 
    justify-content: center; 
    gap: 20px; 
    background: #a5cfa3; 
    padding: 10px; 
}
nav button { 
    background: #fff; 
    border: none; 
    padding: 10px 20px; 
    border-radius: 6px; 
    font-weight: bold; 
    cursor: pointer; 
    transition: 0.3s; 
}
nav button:hover { 
    background: #d8ecd7; 
}
.section { 
    display: none; 
    max-width: 850px; 
    margin: 20px auto; 
    background: white; 
    padding: 30px; 
    border-radius: 12px; 
    box-shadow: 0 0 10px rgba(0,0,0,0.2); 
}
.section.active { 
    display: block; 
}
h2 { 
    color: #333; 
    margin-bottom: 10px; 
}
input, textarea { 
    width: 100%; 
    margin: 8px 0; 
    padding: 10px; 
    border-radius: 8px; 
    border: 1px solid #ccc; 
}
button { 
    background-color: #a5cfa3; 
    border: none; 
    padding: 10px 20px; 
    border-radius: 8px; 
    font-weight: bold; 
    cursor: pointer; 
}
button:hover { 
    background-color: #b7dbb5; 
}
.message-box { 
    max-width: 800px; 
    margin: 20px auto; 
    text-align: center; 
    background: #e8f8e8; 
    padding: 10px; border-radius: 6px; 
    color: #333; 
    font-weight: bold; 
}
.gallery { 
    display: flex; 
    flex-wrap: wrap; 
    gap: 15px; 
    margin-top: 20px; 
    justify-content: center; 
}
.gallery-item { 
    border: 1px solid #ccc;
    padding: 10px; border-radius: 8px; 
    text-align: center; 
    background: #fafafa; 
}
.gallery-item img { 
    width: 120px; height: 120px; 
    object-fit: cover; 
    border-radius: 6px; 
    display: block; 
    margin-bottom: 5px; 
    }
</style>
</head>
<body>
    <div class="header">
        <h1>ניהול תוכניות</span></h1>
        <div>
            <a class="btn" href="homepage.php" class="link admin-link"><i class="fas fa-home"></i></a>
            <a class="btn" href="logout.php" class="link admin-link logout-link"><i class="fas fa-user"></i></a>
        </div>
    </div>

<?php if (!empty($message)) echo "<div class='message-box'>$message</div>"; ?>

<nav>
    <button onclick="showSection('add')">➕ הוספה</button>
    <button onclick="showSection('edit')">✏️ עדכון</button>
    <button onclick="showSection('delete')">🗑️ מחיקה</button>
    <button onclick="showSection('upload')">📸 העלאת תמונות</button>
    <button onclick="showSection('images')">🖼️ ניהול תמונות</button>
</nav>

<!-- הוספת מוצר -->
<div id="add" class="section active">
    <h2>➕ הוספת מוצר חדש</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="text" name="name" placeholder="שם המוצר" required>
        <input type="text" name="category" placeholder="קטגוריה" required>
        <input type="file" name="image" accept="image/*" required>
        <textarea name="description" placeholder="תיאור המוצר" required></textarea>
        <button type="submit" name="add_product_submit">הוסף מוצר</button>
    </form>
</div>

<!-- עריכת מוצר -->
<div id="edit" class="section">
    <h2>✏️ עדכון מוצר קיים</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="number" name="id" placeholder="מזהה מוצר" required>
        <input type="text" name="name" placeholder="שם חדש">
        <input type="text" name="category" placeholder="קטגוריה חדשה">
        <input type="file" name="image" accept="image/*">
        <textarea name="description" placeholder="תיאור חדש"></textarea>
        <input type="hidden" name="existing_image" value="">
        <button type="submit" name="edit_product_submit">עדכן מוצר</button>
    </form>
</div>

<!-- מחיקת מוצר -->
<div id="delete" class="section">
    <h2>🗑️ מחיקת מוצר</h2>
    <form method="post">
        <input type="number" name="id" placeholder="מזהה מוצר" required>
        <button type="submit" name="delete_product_submit">מחק מוצר</button>
    </form>
</div>

<!-- העלאת תמונות נוספות -->
<div id="upload" class="section">
    <h2>📸 העלאת תמונות נוספות למוצר</h2>
    <form method="post" enctype="multipart/form-data">
        <input type="number" name="ProNum" placeholder="מזהה מוצר (ProNum)" required>
        <input type="file" name="image_file[]" accept="image/*" multiple required>
        <div id="preview-container" style="display:flex;flex-wrap:wrap;gap:10px;margin-top:10px;"></div>
        <button type="submit" name="upload_images_submit">העלה תמונות</button>
    </form>
</div>

<!-- ניהול תמונות -->
<div id="images" class="section">
    <h2>🖼️ ניהול תמונות קיימות</h2>

    <!-- 🔍 טופס חיפוש לפי מזהה מוצר -->
    <form method="get" style="margin-bottom:20px; text-align:center;">
        <input type="number" name="searchProNum" placeholder="חפש לפי מזהה מוצר (ProNum)" style="padding:8px; border-radius:6px; border:1px solid #ccc; width:200px;">
        <button type="submit" style="padding:8px 15px; border:none; border-radius:6px; background:#a5cfa3; font-weight:bold; cursor:pointer;">חפש</button>
        <a href="admin.php" style="margin-right:10px; color:#555; text-decoration:none;">אפס חיפוש</a>
    </form>

    <?php
    // בדיקת חיפוש
    $searchProNum = $_GET['searchProNum'] ?? '';
    $searchQuery = "SELECT * FROM images";
    if (!empty($searchProNum)) {
        $searchQuery .= " WHERE ProNum = " . intval($searchProNum);
    }
    $searchQuery .= " ORDER BY num DESC";

    $res = $conn->query($searchQuery);
    if ($res->num_rows > 0) {
        echo "<div class='gallery'>";
        while ($img = $res->fetch_assoc()) {
            echo "<div class='gallery-item'>
                    <img src='{$img['image']}' alt='תמונה'>
                    <small>מוצר #{$img['ProNum']}</small>
                    <form method='post' onsubmit=\"return confirm('למחוק תמונה זו?');\">
                        <input type='hidden' name='image_id' value='{$img['num']}'>
                        <input type='hidden' name='image_path' value='{$img['image']}'>
                        <button type='submit' name='delete_image_submit'>מחק</button>
                    </form>
                  </div>";
        }
        echo "</div>";
    } else {
        if (!empty($searchProNum)) {
            echo "<p style='text-align:center;'>לא נמצאו תמונות עבור מוצר #$searchProNum.</p>";
        } else {
            echo "<p style='text-align:center;'>אין תמונות במערכת.</p>";
        }
    }
    ?>
</div>

<script>
function showSection(id){
    document.querySelectorAll('.section').forEach(s=>s.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    localStorage.setItem('activeSection',id);
}
document.addEventListener('DOMContentLoaded',()=>{
    const last=localStorage.getItem('activeSection');
    if(last&&document.getElementById(last))showSection(last);
});
</script>
<script>
const inputFiles = document.querySelector('input[name="image_file[]"]');
const previewContainer = document.getElementById('preview-container');

inputFiles.addEventListener('change', function() {
    previewContainer.innerHTML = ""; // מנקה תצוגה קודמת
    const files = Array.from(this.files);

    files.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(e) {
            // יצירת בלוק של תמונה + כפתור מחיקה
            const imgWrapper = document.createElement('div');
            imgWrapper.style.position = 'relative';
            imgWrapper.style.display = 'inline-block';

            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.width = '120px';
            img.style.height = '120px';
            img.style.objectFit = 'cover';
            img.style.borderRadius = '8px';
            img.style.boxShadow = '0 0 5px rgba(0,0,0,0.2)';
            img.style.margin = '5px';

            const delBtn = document.createElement('span');
            delBtn.textContent = '✖';
            delBtn.style.position = 'absolute';
            delBtn.style.top = '2px';
            delBtn.style.right = '6px';
            delBtn.style.cursor = 'pointer';
            delBtn.style.background = 'rgba(0,0,0,0.6)';
            delBtn.style.color = 'white';
            delBtn.style.borderRadius = '50%';
            delBtn.style.padding = '2px 6px';
            delBtn.style.fontSize = '14px';

            delBtn.addEventListener('click', () => {
                // הסרה מתצוגה
                imgWrapper.remove();
                // מחיקת הקובץ מה־input (ניצור קובץ חדש ללא הקובץ שנמחק)
                const dt = new DataTransfer();
                files.forEach((f, i) => {
                    if (i !== index) dt.items.add(f);
                });
                inputFiles.files = dt.files;
            });

            imgWrapper.appendChild(img);
            imgWrapper.appendChild(delBtn);
            previewContainer.appendChild(imgWrapper);
        };
        reader.readAsDataURL(file);
    });
});
</script>
    <script>
    // מנגנון למניעת בעיות עם כפתור 'אחורה' בדפדפן לאחר התנתקות
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            window.location.reload();
        }
    });
    </script>
</body>
</html>
