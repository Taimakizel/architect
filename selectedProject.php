<?php
include "connect.php"; 

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($id <= 0) {
    die("<h2>❌ מוצר לא נבחר או לא תקין.</h2>");
}

$product = $conn->query("SELECT * FROM products WHERE id = $id")->fetch_assoc();
if (!$product) {
    die("<h2>❌ המוצר לא נמצא במסד הנתונים.</h2>");
}

$images = $conn->query("SELECT * FROM images WHERE ProNum = $id ORDER BY num ASC");
$allImages = [];
while ($row = $images->fetch_assoc()) {
    $allImages[] = $row['image'];
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($product['name']); ?> | צפייה במוצר</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body {
    font-family: "Segoe UI", Tahoma;
    background: #f3f4f3;
    margin: 0;
    padding: 0;
    color: #222;
}

/* כותרת */
.header {
    background-color: #222;
    color: #d4efd0;
    text-align: center;
    padding: 25px;
    font-size: 26px;
}

/* תיבת פרטי מוצר */
.product-details {
    max-width: 950px;
    background: #fff;
    margin: 40px auto;
    border-radius: 15px;
    padding: 25px 35px;
    box-shadow: 0 8px 18px rgba(0,0,0,0.15);
    text-align: right;
}
.product-details h2 {
    color: #2d532d;
    font-size: 28px;
    margin-bottom: 10px;
}
.product-details p {
    font-size: 18px;
    color: #555;
    margin: 8px 0;
}

/* גלריה */
.gallery {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    max-width: 1200px;
    margin: 40px auto;
    padding: 10px;
}
.image-card {
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    transition: transform 0.3s;
    cursor: pointer;
}
.image-card:hover { transform: scale(1.03); }
.image-card img {
    width: 100%;
    height: 180px;
    object-fit: cover;
    transition: 0.3s;
}
.image-card img:hover {
    filter: brightness(0.9);
}

/* lightbox */
.lightbox {
    display: none;
    position: fixed;
    z-index: 999;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.9);
    justify-content: center;
    align-items: center;
    flex-direction: column;
}
.lightbox.active {
    display: flex;
}
.lightbox img {
    max-width: 90%;
    max-height: 80vh;
    border-radius: 10px;
    box-shadow: 0 0 20px rgba(255,255,255,0.3);
    transition: transform 0.3s;
}

/* חיצים */
.lightbox .nav {
    position: absolute;
    top: 50%;
    font-size: 40px;
    color: #fff;
    cursor: pointer;
    user-select: none;
    padding: 20px;
    transform: translateY(-50%);
}
.lightbox .nav.prev { left: 5%; }
.lightbox .nav.next { right: 5%; }
.lightbox .nav:hover { color: #a5cfa3; }

/* סגירה */
.lightbox .close-btn {
    position: absolute;
    top: 25px; right: 40px;
    font-size: 35px;
    color: #fff;
    text-decoration: none;
    cursor: pointer;
}
.lightbox .close-btn:hover { color: #a5cfa3; }

/* כפתור חזרה */
a.back {
    display: block;
    text-align: center;
    text-decoration: none;
    color: #fff;
    background: #a5cfa3;
    padding: 12px 25px;
    border-radius: 30px;
    width: 220px;
    margin: 50px auto;
    transition: 0.3s;
    font-weight: bold;
}
a.back:hover { background: #b7dbb5; }
</style>
</head>
<body>

<div class="header">
    🛒 צפייה במוצר מספר <?php echo htmlspecialchars($product['id']); ?>
</div>

<div class="product-details">
    <h2><?php echo htmlspecialchars($product['name']); ?></h2>
    <p><strong>קטגוריה:</strong> <?php echo htmlspecialchars($product['category']); ?></p>
    <p><strong>תיאור:</strong> <?php echo htmlspecialchars($product['description']); ?></p>
</div>

<h2 style="text-align:center; color:#2d532d;">📸 גלריית תמונות</h2>

<div class="gallery">
<?php
if (!empty($allImages)) {
    foreach ($allImages as $index => $imgPath) {
        echo "<div class='image-card'>
                <img src='" . htmlspecialchars($imgPath) . "' alt='תמונה' onclick='openLightbox($index)'>
              </div>";
    }
} else {
    echo "<div class='empty'>לא נמצאו תמונות עבור מוצר זה.</div>";
}
?>
</div>

<!-- Lightbox -->
<div id="lightbox" class="lightbox">
    <span class="close-btn" onclick="closeLightbox()">&times;</span>
    <span class="nav prev" onclick="prevImage()"><i class='fa-solid fa-chevron-left'></i></span>
    <img id="lightbox-img" src="" alt="">
    <span class="nav next" onclick="nextImage()"><i class='fa-solid fa-chevron-right'></i></span>
</div>

<a href="homepage.php" class="back">⬅ חזרה לקטלוג</a>

<script>
let images = <?php echo json_encode($allImages); ?>;
let currentIndex = 0;

function openLightbox(index) {
    currentIndex = index;
    document.getElementById("lightbox-img").src = images[index];
    document.getElementById("lightbox").classList.add("active");
}
function closeLightbox() {
    document.getElementById("lightbox").classList.remove("active");
}
function nextImage() {
    currentIndex = (currentIndex + 1) % images.length;
    document.getElementById("lightbox-img").src = images[currentIndex];
}
function prevImage() {
    currentIndex = (currentIndex - 1 + images.length) % images.length;
    document.getElementById("lightbox-img").src = images[currentIndex];
}
// סגירה בלחיצה מחוץ לתמונה
document.getElementById("lightbox").addEventListener("click", function(e){
    if(e.target === this){ closeLightbox(); }
});
</script>

</body>
</html>
