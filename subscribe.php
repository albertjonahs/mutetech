<?php
// Simple subscription handler for newsletter form
// Expects POST 'email' from a form. Inserts into subscribers table and redirects back with status.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

require_once __DIR__ . '/includes/db.php';

$email = isset($_POST['email']) ? trim($_POST['email']) : '';

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: index.html?sub=invalid');
    exit;
}

// Prepare table creation just in case includes/db.php didn't succeed earlier
$createSql = "CREATE TABLE IF NOT EXISTS `subscribers` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `subscription_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
@$conn->query($createSql);

// Insert or ignore duplicates
try {
    $stmt = $conn->prepare('INSERT INTO subscribers (email, status) VALUES (?, "active")');
    if (!$stmt) {
        // Could be duplicate key or table missing; fallback to generic error
        header('Location: index.html?sub=error');
        exit;
    }
    $stmt->bind_param('s', $email);
    $ok = $stmt->execute();
    if ($ok) {
        header('Location: index.html?sub=success');
    } else {
        // If duplicate entry, MySQLi returns error code 1062
        if ($conn->errno == 1062) {
            header('Location: index.html?sub=exists');
        } else {
            header('Location: index.html?sub=error');
        }
    }
    $stmt->close();
} catch (Exception $e) {
    header('Location: index.html?sub=error');
}

exit;
?>
