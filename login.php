<?php
session_start();
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $users = file_exists(__DIR__ . '/data/users.json') ? json_decode(file_get_contents(__DIR__ . '/data/users.json'), true) : [];
    if (isset($users[$username]) && password_verify($password, $users[$username]['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = $username;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - OutCast</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    <div class="min-h-screen bg-gradient-to-br from-blue-200 via-cyan-100 to-blue-400 flex items-center justify-center py-8 px-2">
        <div class="w-full max-w-md bg-white/90 rounded-2xl shadow-2xl p-8">
            <h1 class="text-3xl font-bold text-center text-blue-700 mb-6 tracking-wide">OutCast</h1>
            <h2 class="text-xl font-semibold text-blue-800 text-center mb-6">Login</h2>
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded-lg mb-4 font-medium shadow-sm"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="post" class="flex flex-col gap-3">
                <input type="text" name="username" placeholder="Username" required class="px-4 py-3 rounded-lg border border-blue-200 focus:ring-2 focus:ring-blue-400 focus:outline-none bg-blue-50 text-blue-900" />
                <input type="password" name="password" placeholder="Password" required class="px-4 py-3 rounded-lg border border-blue-200 focus:ring-2 focus:ring-blue-400 focus:outline-none bg-blue-50 text-blue-900" />
                <button type="submit" class="mt-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-3 rounded-lg shadow">Login</button>
            </form>
            <p class="mt-4 text-center text-blue-900">Don't have an account? <a href="register.php" class="text-blue-700 font-semibold hover:underline">Register</a></p>
        </div>
    </div>
</body>
</html>
