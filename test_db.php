<?php
// test_db.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'cosmopredict-db-ssmum0';  // Your MySQL container name or IP
$dbname = 'Stripe';
$username = 'stripe-db';
$password = 'g0uSoA6dFAMo8O';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connection SUCCESSFUL!";
    
    // Test if we can create tables
    $pdo->exec("CREATE TABLE IF NOT EXISTS test_install (id INT)");
    echo "✅ Table creation test SUCCESSFUL!";
    
} catch (PDOException $e) {
    echo "❌ Database ERROR: " . $e->getMessage();
}
?>