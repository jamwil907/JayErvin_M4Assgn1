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
    if (!$username || !$password) {
        $error = 'Username and password required.';
    } else {
        $users_file = __DIR__ . '/data/users.json';
        $users = file_exists($users_file) ? json_decode(file_get_contents($users_file), true) : [];
        if (isset($users[$username])) {
            $error = 'Username already exists.';
        } else {
            $users[$username] = [
                'password' => password_hash($password, PASSWORD_DEFAULT)
            ];
            file_put_contents($users_file, json_encode($users));
            session_regenerate_id(true);
            $_SESSION['user'] = $username;
            header('Location: index.php');
            exit;
        }
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - OutCast</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    <div class="min-h-screen bg-gradient-to-br from-blue-200 via-cyan-100 to-blue-400 flex items-center justify-center py-8 px-2">
        <div class="w-full max-w-md bg-white/90 rounded-2xl shadow-2xl p-8">
            <h1 class="text-3xl font-bold text-center text-blue-700 mb-6 tracking-wide">OutCast</h1>
            <h2 class="text-xl font-semibold text-blue-800 text-center mb-6">Register</h2>
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded-lg mb-4 font-medium shadow-sm"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="post" class="flex flex-col gap-3">
                <input type="text" name="username" placeholder="Username" required class="px-4 py-3 rounded-lg border border-blue-200 focus:ring-2 focus:ring-blue-400 focus:outline-none bg-blue-50 text-blue-900" />
                <input type="password" name="password" placeholder="Password" required class="px-4 py-3 rounded-lg border border-blue-200 focus:ring-2 focus:ring-blue-400 focus:outline-none bg-blue-50 text-blue-900" />
                <button type="submit" class="mt-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-3 rounded-lg shadow">Create Account</button>
            </form>
            <p class="mt-4 text-center text-blue-900">Already have an account? <a href="login.php" class="text-blue-700 font-semibold hover:underline">Login</a></p>
        </div>
    </div>
</body>
</html>
