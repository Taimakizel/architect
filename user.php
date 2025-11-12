<?php
class User {

    public $first_name;
    public $last_name;
    public $email;
    public $password;
    public $confirm_password;

    public function __construct($first_name, $last_name, $email, $password, $confirm_password) {
        $this->first_name = $first_name;
        $this->last_name = $last_name;
        $this->email = $email;
        // הערה: יש לאחסן סיסמאות מוצפנות (hashed) במסד הנתונים, לא סיסמא בטקסט רגיל!
        $this->password = $password; 
        $this->confirm_password = $confirm_password;
    }

    // --- פונקציות אימות והרשאה ---

    public function isValidUser($conn) {
        $sql = "SELECT * FROM users WHERE email = ? AND password = ?";
        $stmt = $conn->prepare($sql);
        
        // בדיקת שגיאה בהכנת השאילתה
        if ($stmt === false) {
             // שגיאה בהכנה, מחזיר false ומונע Fatal Error
             return false; 
        }
        
        $stmt->bind_param("ss", $this->email, $this->password);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->num_rows > 0;
    }

    public function isAdmin($conn) {
        // הערה: טבלת 'admin' נפרדת היא מודל נפוץ, אך ייתכן שמומלץ להשתמש בעמודה 'is_admin' בטבלת 'users'
        $sql = "SELECT * FROM admin WHERE email = ? AND password = ?";
        $stmt = $conn->prepare($sql);
        
        // בדיקת שגיאה בהכנת השאילתה
        if ($stmt === false) {
             return false; 
        }
        
        $stmt->bind_param("ss", $this->email, $this->password);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->num_rows > 0;
    }

    // --- פונקציות ניהול מוצרים ---

    public function AddProduct($conn, $name, $image, $description) {
        $sql = "INSERT INTO products (name, image, description) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        // בדיקת שגיאה בהכנת השאילתה
        if ($stmt === false) {
            return false;
        }
        
        // "sss" ל-name, image, description (כולם מחרוזות)
        $stmt->bind_param("sss", $name, $image, $description); 
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    public function UploudImage($conn, $ProNum, $image) {
        $sql = "INSERT INTO images (ProNum, image) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        
        // בדיקת שגיאה בהכנת השאילתה
        if ($stmt === false) {
            return false;
        }
        
        // "sss" ל-name, image, description (כולם מחרוזות)
        $stmt->bind_param("is", $ProNum, $image); 
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    public function deleteProduct($conn, $id) {
        $sql = "DELETE FROM products WHERE id = ?"; 
        $stmt = $conn->prepare($sql);
        
        // בדיקת שגיאה בהכנת השאילתה
        if ($stmt === false) {
            return false;
        }
        
        // "i" למספר שלם (ID)
        $stmt->bind_param("i", $id); 
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    public function editProduct($conn, $id, $name, $description) {
        // הערה: הפונקציה הזו לא כוללת עדכון שדה ה-'image'
        $sql = "UPDATE products SET name = ?, description = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        
        // בדיקת שגיאה בהכנת השאילתה
        if ($stmt === false) {
            return false;
        }
        
        // "ssi" ל-name, description, id (שתי מחרוזות, מספר שלם)
        $stmt->bind_param("ssi", $name, $description, $id); 
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
}
?>