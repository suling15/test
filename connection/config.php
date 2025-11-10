<?php
class config {
    function connectDB() {
        require_once 'conn.php';
        $conn = new mysqli(H, U, P, DB);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        $conn->set_charset("utf8");
        return $conn;
    }
}
?>