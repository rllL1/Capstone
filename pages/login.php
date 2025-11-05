<?php
include '../config/db.php';
session_start();

$error = '';

if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Simple login check (for testing)
    $res = $conn->query("SELECT * FROM users WHERE username='$username'");
    if ($res->num_rows > 0) {
        $user = $res->fetch_assoc();
        // Assuming password is plain text for testing, or use password_verify if hashed
        if ($user['password'] == $password) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Incorrect password!";
        }
    } else {
        $error = "User not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Payroll System</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body { display:flex; justify-content:center; align-items:center; height:100vh; background:#f4f7f6; }
        .login-box { background:#fff; padding:30px; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.1); width:350px; }
        input { width:100%; padding:10px; margin:10px 0; border-radius:5px; border:1px solid #ccc; }
        button { width:100%; padding:10px; background:#2e7d32; color:#fff; border:none; border-radius:5px; cursor:pointer; }
        button:hover { background:#1b5e20; }
        .error { color:red; font-size:14px; text-align:center; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Login</h2>
        <?php if($error) echo "<p class='error'>$error</p>"; ?>
        <form method="POST" action="">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Login</button>
        </form>
    </div>
</body>
</html>
