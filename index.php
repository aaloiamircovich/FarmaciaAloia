<?php
require_once __DIR__ . '/env.php';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Farmacia Salud+ | Farmacia y Perfumería Online</title>
  <meta name="description" content="Trabajo práctico de Farmacia y Perfumería con PHP, MySQL y Mercado Pago.">
  <link rel="stylesheet" href="public/css/styles.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>💊</text></svg>">
</head>
<body>
  <header class="site-header">
    <div class="nav-shell">
      <a href="index.php" class="brand">
        <span class="brand-mark">✚</span>
        <span><strong>Farmacia Salud+</strong><small>FARMACIA & PERFUMERÍA</small></span>
      </a>

      <div class="search-box">
        <span>⌕</span>
        <input id="search-input" type="search" placeholder="Buscar medicamentos, perfumes, higiene…" autocomplete="off">
      </div>

      <div class="nav-actions">
        <a href="admin/index.php" class="admin-link">Administración</a>
        <button id="cart-trigger" class="cart-button">
          <span>Carrito</span><span id="cart-badge" class="cart-count">0</span>
        </button>
      </div>
    </div>
  </header>

  <main>
    <section class="hero">
      <div class="hero-copy">
        <p class="eyebrow">COMPRA ONLINE</p>
        <h1>Farmacia, cuidado y perfumería en un solo lugar.</h1>
        <p>Catálogo online con carrito, stock, panel administrador e integración con Mercado Pago.</p>
        <div class="hero-pills"><span>✓ Compra segura</span><span>✓ Stock actualizado</span><span>✓ Receta protegida</span></div>
      </div>
      <div class="hero-card">
        <div class="hero-card-icon">Rx</div>
        <div>
          <strong>Medicamentos bajo receta</strong>
          <p>Para los productos identificados con <b>Rx</b>, el sistema solicita una imagen de la receta antes de habilitar el pago.</p>
        </div>
      </div>
    </section>

    <section class="catalog-shell">
      <div class="section-heading catalog-heading">
        <div><p class="eyebrow">CATÁLOGO</p><h2>Encontrá lo que necesitás</h2></div>
        <p class="muted">Los productos marcados con Rx requieren receta médica.</p>
      </div>

      <nav class="categories-bar" aria-label="Categorías">
        <button class="category-btn active" data-category="todos">Todos</button>
        <button class="category-btn" data-category="medicamentos">💊 Medicamentos</button>
        <button class="category-btn" data-category="perfumeria">🌸 Perfumería</button>
        <button class="category-btn" data-category="higiene">🧼 Higiene</button>
        <button class="category-btn" data-category="cuidado_personal">☀️ Cuidado personal</button>
        <button class="category-btn" data-category="bebe">👶 Bebé</button>
      </nav>

      <section id="products-grid" class="products-grid" aria-live="polite"></section>
    </section>
  </main>

  <div id="cart-overlay" class="drawer-overlay"></div>
  <aside id="cart-drawer" class="cart-drawer" aria-label="Carrito de compras">
    <div class="drawer-header">
      <div><p class="eyebrow">TU COMPRA</p><h2>Carrito</h2></div>
      <button id="cart-close" class="icon-btn" aria-label="Cerrar">×</button>
    </div>
    <div id="cart-body" class="cart-body"></div>
    <div class="cart-footer">
      <div class="cart-total-row"><span>Total</span><strong id="cart-total">$ 0,00</strong></div>
      <button id="checkout-btn" class="btn-checkout" disabled>Pagar con Mercado Pago</button>
      <button id="clear-cart" class="btn-ghost">Vaciar carrito</button>
    </div>
  </aside>

  <div id="prescription-modal" class="modal-overlay" aria-hidden="true">
    <div class="modal-card prescription-modal-card">
      <div class="modal-header">
        <div><p class="eyebrow">RECETA MÉDICA</p><h3>Este producto requiere receta</h3></div>
        <button id="close-prescription-modal" class="icon-btn">×</button>
      </div>
      <div class="rx-info">
        <span class="rx-icon">Rx</span>
        <p>Subí una foto clara de la receta. Hasta que no se cargue correctamente, el medicamento no se agregará y el checkout permanecerá bloqueado.</p>
      </div>
      <form id="prescription-form" class="stack-form">
        <label class="upload-box" for="prescription-file">
          <span class="upload-icon">↑</span>
          <strong>Seleccionar imagen</strong>
          <small>JPG, PNG o WEBP · máximo 5 MB</small>
          <input id="prescription-file" name="receta" type="file" accept="image/jpeg,image/png,image/webp" required hidden>
        </label>
        <p id="selected-file" class="selected-file">Ningún archivo seleccionado.</p>
        <div class="modal-actions">
          <button type="button" id="cancel-prescription" class="btn-secondary">Cancelar</button>
          <button type="submit" id="upload-prescription" class="btn-primary">Subir receta</button>
        </div>
      </form>
    </div>
  </div>

  <div id="toast-container" class="toast-container"></div>

  <footer class="site-footer">
    <strong>Farmacia Salud+</strong>
    <span>Trabajo práctico educativo · PHP + MySQL + Mercado Pago</span>
  </footer>

  <script type="module" src="public/js/app.js"></script>
</body>
</html>
