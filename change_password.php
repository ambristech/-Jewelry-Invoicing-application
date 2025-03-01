<?php
session_start();
require_once 'db_connect.php';
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

$error = $success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $stmt = $conn->prepare("SELECT password FROM admins WHERE id = ?");
    $stmt->execute([$_SESSION['admin']]);
    $admin = $stmt->fetch();

    if (!password_verify($current_password, $admin['password'])) {
        $error = "Current password is incorrect";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords don't match";
    } elseif (strlen($new_password) < 8) {
        $error = "New password must be at least 8 characters";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
        $stmt->execute([$hashed_password, $_SESSION['admin']]);
        $success = "Password updated successfully";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Change Password</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Change Password</h2>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Current Password:</label>
                <input type="password" class="form-control" name="current_password" required>
            </div>
            <div class="form-group">
                <label>New Password:</label>
                <input type="password" class="form-control" name="new_password" required>
            </div>
            <div class="form-group">
                <label>Confirm New Password:</label>
                <input type="password" class="form-control" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary">Change Password</button>
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </form>
    </div>
</body>
</html>