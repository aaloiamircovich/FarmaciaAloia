<?php
require_once __DIR__ . '/../config/auth.php';
requireAdminAuth();
$adminUser = $_SESSION['admin_user'];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Panel | Farmacia Salud+</title>
  <link rel="stylesheet" href="../public/css/styles.css">
</head>
<body class="admin-page">
  <header class="admin-topbar">
    <div>
      <p class="eyebrow">FARMACIA SALUD+</p>
      <h1>Panel de administración</h1>
      <p class="muted">Hola, <?= htmlspecialchars($adminUser['nombre']) ?>.</p>
    </div>
    <div class="admin-actions">
      <a class="btn-secondary" href="../index.php" target="_blank">Ver tienda ↗</a>
      <a class="btn-danger" href="logout.php">Cerrar sesión</a>
    </div>
  </header>

  <main class="admin-main">
    <section class="stats-grid">
      <article class="stat-card"><span>Productos</span><strong id="stat-products">…</strong></article>
      <article class="stat-card"><span>Bajo stock (&lt;10)</span><strong id="stat-low">…</strong></article>
      <article class="stat-card"><span>Órdenes</span><strong id="stat-orders">…</strong></article>
      <article class="stat-card"><span>Recaudación aprobada</span><strong id="stat-revenue">$ …</strong></article>
    </section>

    <nav class="admin-tabs">
      <button class="tab-btn active" data-tab="products-panel">Productos</button>
      <button class="tab-btn" data-tab="orders-panel">Órdenes</button>
    </nav>

    <section id="products-panel" class="admin-panel">
      <div class="section-heading">
        <div><p class="eyebrow">INVENTARIO</p><h2>Productos de farmacia y perfumería</h2></div>
        <button id="new-product" class="btn-primary">+ Nuevo producto</button>
      </div>
      <div class="table-wrap">
        <table class="admin-table">
          <thead><tr><th>Imagen</th><th>Producto</th><th>Categoría</th><th>Precio</th><th>Stock</th><th>Receta</th><th>Acciones</th></tr></thead>
          <tbody id="products-body"><tr><td colspan="7">Cargando…</td></tr></tbody>
        </table>
      </div>
    </section>

    <section id="orders-panel" class="admin-panel" hidden>
      <div class="section-heading"><div><p class="eyebrow">VENTAS</p><h2>Órdenes de Mercado Pago</h2></div></div>
      <div class="table-wrap">
        <table class="admin-table">
          <thead><tr><th>Referencia</th><th>Total</th><th>Estado</th><th>Productos</th><th>Receta</th><th>Pago MP</th><th>Fecha</th></tr></thead>
          <tbody id="orders-body"><tr><td colspan="7">Cargando…</td></tr></tbody>
        </table>
      </div>
    </section>
  </main>

  <div id="product-modal" class="modal-overlay" aria-hidden="true">
    <div class="modal-card modal-wide">
      <div class="modal-header"><div><p class="eyebrow">PRODUCTO</p><h3 id="product-modal-title">Nuevo producto</h3></div><button class="icon-btn" id="close-product-modal">×</button></div>
      <form id="product-form" class="form-grid">
        <input type="hidden" id="prod-id" value="0">
        <label class="full">Nombre<input id="prod-name" class="form-input" required></label>
        <label>Categoría<select id="prod-category" class="form-input">
          <option value="medicamentos">Medicamentos</option><option value="perfumeria">Perfumería</option><option value="higiene">Higiene</option><option value="cuidado_personal">Cuidado personal</option><option value="bebe">Bebé</option>
        </select></label>
        <label>Precio ARS<input id="prod-price" class="form-input" type="number" min="0.01" step="0.01" required></label>
        <label>Stock<input id="prod-stock" class="form-input" type="number" min="0" required></label>
        <label>Destacado<select id="prod-featured" class="form-input"><option value="0">No</option><option value="1">Sí</option></select></label>
        <label>¿Requiere receta?<select id="prod-prescription" class="form-input"><option value="0">No</option><option value="1">Sí</option></select></label>
        <label class="full">Ruta/URL de imagen<input id="prod-image" class="form-input" placeholder="public/img/medicamento.svg"></label>
        <label class="full">Descripción<textarea id="prod-description" class="form-input" rows="3"></textarea></label>
        <div class="full form-actions"><button type="button" class="btn-secondary" id="cancel-product">Cancelar</button><button class="btn-primary" type="submit">Guardar</button></div>
      </form>
    </div>
  </div>

  <div id="toast-container" class="toast-container"></div>

<script>
let products = [];
const qs = s => document.querySelector(s);
const money = n => '$ ' + Number(n).toLocaleString('es-AR',{minimumFractionDigits:2});
const esc = value => String(value ?? '').replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));
const imgSrc = url => /^(https?:|data:|\/)/i.test(url || '') ? url : '../' + (url || 'public/img/medicamento.svg');
function toast(msg){const el=document.createElement('div');el.className='toast';el.textContent=msg;qs('#toast-container').appendChild(el);setTimeout(()=>el.remove(),3000)}

async function loadProducts(){
  const r=await fetch('../api/get_products.php'); const j=await r.json();
  if(j.status!=='success') throw new Error(j.message||'Error');
  products=j.data; renderProducts();
  qs('#stat-products').textContent=products.length;
  qs('#stat-low').textContent=products.filter(p=>p.stock<10).length;
}
function renderProducts(){
  qs('#products-body').innerHTML = products.length ? products.map(p=>`<tr>
    <td><img class="admin-thumb" src="${esc(imgSrc(p.imagen_url))}" alt=""></td>
    <td><strong>${esc(p.nombre)}</strong><div class="table-sub">${esc(p.descripcion||'')}</div></td>
    <td><span class="chip">${esc(p.categoria.replace('_',' '))}</span></td>
    <td>${money(p.precio)}</td><td>${p.stock}</td>
    <td>${p.requiere_receta?'<span class="rx-badge">Rx Sí</span>':'No'}</td>
    <td><div class="row-actions"><button class="btn-small" onclick="editProduct(${p.id})">Editar</button><button class="btn-small danger" onclick="deleteProduct(${p.id})">Eliminar</button></div></td>
  </tr>`).join('') : '<tr><td colspan="7">No hay productos.</td></tr>';
}
async function loadOrders(){
  const r=await fetch('../api/admin/get_orders.php'); const j=await r.json();
  if(j.status!=='success') throw new Error(j.message||'Error');
  qs('#stat-orders').textContent=j.stats.total_orders;
  qs('#stat-revenue').textContent=money(j.stats.total_revenue);
  qs('#orders-body').innerHTML=j.data.length?j.data.map(o=>`<tr>
    <td><code>${esc(o.external_reference)}</code></td><td>${money(o.monto_total)}</td>
    <td><span class="status ${esc(o.estado)}">${esc(o.estado)}</span></td>
    <td>${o.items.map(i=>`${i.cantidad}× ${esc(i.producto_nombre||'Producto')}`).join('<br>')}</td>
    <td>${o.tiene_receta?`<a class="text-link" target="_blank" href="view_prescription.php?order_id=${o.id}">Ver receta</a>`:'—'}</td>
    <td>${esc(o.mp_payment_id||'—')}</td><td>${esc(o.created_at)}</td>
  </tr>`).join(''):'<tr><td colspan="7">Todavía no hay órdenes.</td></tr>';
}

function openModal(){qs('#product-modal').classList.add('open');qs('#product-modal').setAttribute('aria-hidden','false')}
function closeModal(){qs('#product-modal').classList.remove('open');qs('#product-modal').setAttribute('aria-hidden','true')}
qs('#new-product').onclick=()=>{qs('#product-form').reset();qs('#prod-id').value=0;qs('#product-modal-title').textContent='Nuevo producto';openModal()};
qs('#close-product-modal').onclick=closeModal; qs('#cancel-product').onclick=closeModal;
window.editProduct=id=>{const p=products.find(x=>x.id===id);if(!p)return;qs('#prod-id').value=p.id;qs('#prod-name').value=p.nombre;qs('#prod-category').value=p.categoria;qs('#prod-price').value=p.precio;qs('#prod-stock').value=p.stock;qs('#prod-featured').value=p.destacado?'1':'0';qs('#prod-prescription').value=p.requiere_receta?'1':'0';qs('#prod-image').value=p.imagen_url||'';qs('#prod-description').value=p.descripcion||'';qs('#product-modal-title').textContent='Editar producto';openModal()};
window.deleteProduct=async id=>{if(!confirm('¿Eliminar este producto?'))return;const r=await fetch('../api/admin/delete_product.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})});const j=await r.json();toast(j.message||'Listo');if(r.ok)loadProducts()};
qs('#product-form').addEventListener('submit',async e=>{e.preventDefault();const payload={id:+qs('#prod-id').value,nombre:qs('#prod-name').value,categoria:qs('#prod-category').value,precio:+qs('#prod-price').value,stock:+qs('#prod-stock').value,destacado:+qs('#prod-featured').value,requiere_receta:+qs('#prod-prescription').value,imagen_url:qs('#prod-image').value,descripcion:qs('#prod-description').value};const r=await fetch('../api/admin/save_product.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});const j=await r.json();if(!r.ok){toast(j.message||'Error');return}toast(j.message);closeModal();loadProducts()});

document.querySelectorAll('.tab-btn').forEach(btn=>btn.addEventListener('click',()=>{document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));btn.classList.add('active');document.querySelectorAll('.admin-panel').forEach(p=>p.hidden=true);qs('#'+btn.dataset.tab).hidden=false;}));
Promise.all([loadProducts(),loadOrders()]).catch(e=>toast(e.message));
</script>
</body>
</html>
