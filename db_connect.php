
<?php
$servername = "localhost";
$username = "h520594_PrcGroup";
$password = "Puster123456789";
$dbname = "h520594_PrcGroup";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn -> set_charset("utf8");

?>