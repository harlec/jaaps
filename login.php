<?php
require_once __DIR__ . '/config/config.php';

if (isLoggedIn()) {
    redirect(APP_URL . '/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Ingrese su correo y contraseña.';
    } else {
        try {
            $pdo  = getDB();
            $stmt = $pdo->prepare("SELECT * FROM usuarios_sistema WHERE email = ? AND activo = 1 LIMIT 1");
            $stmt->execute([$email]);
            $us = $stmt->fetch();

            if ($us && password_verify($password, $us['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']     = $us['id'];
                $_SESSION['user_nombre'] = $us['nombre'];
                $_SESSION['user_rol']    = $us['rol'];
                redirect(APP_URL . '/dashboard.php');
            } else {
                $error = 'Credenciales incorrectas. Verifique e intente nuevamente.';
            }
        } catch (PDOException $e) {
            $error = 'Error de conexión a la base de datos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar sesión | JAAP</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: { extend: { colors: { brand: { 600: '#0d9488', 700: '#0f766e' } } } }
    }
  </script>
</head>
<body class="min-h-screen bg-gradient-to-br from-brand-600 to-teal-800 flex items-center justify-center p-4">

  <div class="w-full max-w-md">

    <!-- Logo -->
    <div class="text-center mb-8">
      <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white/20 backdrop-blur mb-4">
        <svg class="w-9 h-9 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
        </svg>
      </div>
      <h1 class="text-2xl font-bold text-white">JAAP</h1>
      <p class="text-teal-200 text-sm mt-1">Junta Administradora de Agua Potable</p>
    </div>

    <!-- Card -->
    <div class="bg-white rounded-2xl shadow-xl p-8">
      <h2 class="text-lg font-semibold text-gray-800 mb-1">Bienvenido</h2>
      <p class="text-sm text-gray-400 mb-6">Inicia sesión en tu cuenta.</p>

      <?php if ($error): ?>
        <div class="mb-4 px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
          <?= e($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="" class="space-y-4" novalidate>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1" for="email">Correo electrónico</label>
          <input id="email" name="email" type="email" required autocomplete="email"
                 value="<?= e($_POST['email'] ?? '') ?>"
                 class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg
                        focus:outline-none focus:ring-2 focus:ring-brand-600 focus:border-brand-600">
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1" for="password">Contraseña</label>
          <input id="password" name="password" type="password" required autocomplete="current-password"
                 class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg
                        focus:outline-none focus:ring-2 focus:ring-brand-600 focus:border-brand-600">
        </div>

        <button type="submit"
                class="w-full bg-brand-600 hover:bg-brand-700 text-white font-semibold py-2.5 rounded-lg
                       transition-colors text-sm">
          Iniciar sesión
        </button>
      </form>
    </div>

    <p class="text-center text-teal-200/60 text-xs mt-6">
      &copy; <?= date('Y') ?> JAAP &mdash; Sistema de Gestión de Agua Potable
    </p>
  </div>
</body>
</html>
