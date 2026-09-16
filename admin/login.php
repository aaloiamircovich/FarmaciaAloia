<?php
require_once __DIR__ . '/../config/auth.php';
if (isAdminAuthenticated()) {
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Administración | Farmacia Salud+</title>
  <link rel="stylesheet" href="../public/css/styles.css">
</head>
<body class="login-page">
  <main class="login-card">
    <div class="brand-mark">✚</div>
    <p class="eyebrow">FARMACIA SALUD+</p>
    <h1>Panel administrador</h1>
    <p class="muted">Ingresá con el usuario del trabajo práctico.</p>

    <form id="login-form" class="stack-form">
      <label>Usuario
        <input id="username" class="form-input" autocomplete="username" value="admin" required>
      </label>
      <label>Contraseña
        <input id="password" class="form-input" type="password" autocomplete="current-password" placeholder="admin123" required>
      </label>
      <button class="btn-primary" type="submit">Ingresar</button>
      <p id="login-message" class="form-message"></p>
    </form>

    <a class="text-link" href="../index.php">← Volver a la tienda</a>
  </main>

  <script>
    const form = document.getElementById('login-form');
    const message = document.getElementById('login-message');
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      message.textContent = 'Ingresando...';
      try {
        const response = await fetch('../api/admin/login.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({
            username: document.getElementById('username').value,
            password: document.getElementById('password').value
          })
        });
        const data = await response.json();
        if (!response.ok || data.status !== 'success') throw new Error(data.message || 'No se pudo iniciar sesión.');
        window.location.href = data.redirect;
      } catch (error) {
        message.textContent = error.message;
        message.classList.add('error');
      }
    });
  </script>
</body>
</html>
