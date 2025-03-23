<?php include 'backend/conn.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iStyle POS | Login</title>

    <!-- CDNs -->
    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="login-card animate__animated animate__fadeInUp p-8 w-96">
        <div class="text-center mb-8">
            <img src="logo/b_k_logo.png" alt="TextilePOS" class="w-100 mx-auto mb-4">
            <h1 class="text-2xl font-bold text-gray-800">POS Login</h1>
            <?php if (isset($_REQUEST['error'])): ?>
              <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded relative mt-3 text-sm">
  <strong class="font-semibold">Error:</strong> Invalid login details. Please try again.
</div>

            <?php endif; ?>
        </div>

        <form action="./backend/signin.php" id="loginForm">
            <div class="input-group">
                <input type="text" id="username" name="username"  class="input-field" placeholder=" ">
                <label for="username" class="floating-label">Username</label>
            </div>


            <div class="input-group">
                <input type="password" name="pass" id="password" class="input-field" placeholder=" ">
                <label for="password" class="floating-label">Password</label>
            </div>

            <button type="submit" class="login-btn">
                <span class="login-text">Sign In</span>
                <div class="loading hidden">
                    <i class="fas fa-spinner fa-spin"></i>
                </div>
            </button>
        </form>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="assets/js/login.js"></script>
</body>
</html>
