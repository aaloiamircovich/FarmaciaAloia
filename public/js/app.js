import { API } from './api.js';
import { Cart } from './cart.js';

const state = {
  products: [],
  category: 'todos',
  search: '',
  pendingProduct: null,
  replaceOnly: false
};

const $ = selector => document.querySelector(selector);
const money = value => `$ ${Number(value).toLocaleString('es-AR', {minimumFractionDigits: 2})}`;
const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, char => ({
  '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'
}[char]));

const dom = {
  grid: $('#products-grid'),
  search: $('#search-input'),
  categoryButtons: document.querySelectorAll('.category-btn'),
  cartTrigger: $('#cart-trigger'),
  cartBadge: $('#cart-badge'),
  cartOverlay: $('#cart-overlay'),
  cartDrawer: $('#cart-drawer'),
  cartClose: $('#cart-close'),
  cartBody: $('#cart-body'),
  cartTotal: $('#cart-total'),
  checkout: $('#checkout-btn'),
  clearCart: $('#clear-cart'),
  prescriptionModal: $('#prescription-modal'),
  prescriptionForm: $('#prescription-form'),
  prescriptionFile: $('#prescription-file'),
  selectedFile: $('#selected-file'),
  closePrescription: $('#close-prescription-modal'),
  cancelPrescription: $('#cancel-prescription'),
  uploadPrescription: $('#upload-prescription'),
  toastContainer: $('#toast-container')
};

function toast(message, type = 'info') {
  const node = document.createElement('div');
  node.className = `toast toast-${type}`;
  node.textContent = message;
  dom.toastContainer.appendChild(node);
  setTimeout(() => node.remove(), 3200);
}

async function loadProducts() {
  dom.grid.innerHTML = '<div class="loading-card"><span class="spinner"></span><p>Cargando catálogo…</p></div>';
  try {
    state.products = await API.getProducts(state.category, state.search);
    renderProducts();
  } catch (error) {
    dom.grid.innerHTML = `<div class="empty-state"><strong>No se pudo cargar el catálogo</strong><p>${escapeHtml(error.message)}</p></div>`;
  }
}

function renderProducts() {
  if (!state.products.length) {
    dom.grid.innerHTML = '<div class="empty-state"><strong>No encontramos productos</strong><p>Probá con otra categoría o búsqueda.</p></div>';
    return;
  }

  dom.grid.innerHTML = state.products.map(product => `
    <article class="product-card">
      <div class="product-media">
        ${product.destacado ? '<span class="featured-badge">Destacado</span>' : ''}
        ${product.requiere_receta ? '<span class="rx-badge floating">Rx · Receta</span>' : ''}
        <img src="${escapeHtml(product.imagen_url || 'public/img/medicamento.svg')}" alt="${escapeHtml(product.nombre)}" loading="lazy">
      </div>
      <div class="product-content">
        <span class="product-category">${escapeHtml(product.categoria.replace('_', ' '))}</span>
        <h3>${escapeHtml(product.nombre)}</h3>
        <p>${escapeHtml(product.descripcion || '')}</p>
        ${product.requiere_receta ? '<div class="prescription-note">🔒 Requiere receta médica</div>' : ''}
        <div class="product-bottom">
          <div><strong class="product-price">${money(product.precio)}</strong><small>${product.stock} en stock</small></div>
          <button class="add-btn" data-id="${product.id}" ${product.stock <= 0 ? 'disabled' : ''}>${product.stock <= 0 ? 'Sin stock' : 'Agregar'}</button>
        </div>
      </div>
    </article>
  `).join('');

  document.querySelectorAll('.add-btn').forEach(button => {
    button.addEventListener('click', () => addProduct(Number(button.dataset.id)));
  });
}

function addProduct(id) {
  const product = state.products.find(item => item.id === id);
  if (!product) return;

  if (product.requiere_receta && !Cart.getPrescription()) {
    state.pendingProduct = product;
    state.replaceOnly = false;
    openPrescriptionModal();
    return;
  }

  try {
    Cart.addItem(product);
    toast(`${product.nombre} agregado al carrito.`, 'success');
  } catch (error) {
    toast(error.message, 'warning');
  }
}

function openCart() {
  dom.cartOverlay.classList.add('open');
  dom.cartDrawer.classList.add('open');
}
function closeCart() {
  dom.cartOverlay.classList.remove('open');
  dom.cartDrawer.classList.remove('open');
}

function openPrescriptionModal() {
  dom.prescriptionFile.value = '';
  dom.selectedFile.textContent = 'Ningún archivo seleccionado.';
  dom.prescriptionModal.classList.add('open');
  dom.prescriptionModal.setAttribute('aria-hidden', 'false');
}
function closePrescriptionModal() {
  dom.prescriptionModal.classList.remove('open');
  dom.prescriptionModal.setAttribute('aria-hidden', 'true');
  state.pendingProduct = null;
  state.replaceOnly = false;
}

function renderCart(snapshot) {
  const {items, total, count, prescription, needsPrescription} = snapshot;
  dom.cartBadge.textContent = count;
  dom.cartTotal.textContent = money(total);

  if (!items.length) {
    dom.cartBody.innerHTML = '<div class="cart-empty"><div>🛒</div><strong>Tu carrito está vacío</strong><p>Agregá productos para comenzar.</p></div>';
    dom.checkout.disabled = true;
    dom.clearCart.hidden = true;
    return;
  }

  dom.clearCart.hidden = false;
  const prescriptionOk = !needsPrescription || Boolean(prescription);
  dom.checkout.disabled = !prescriptionOk;

  const itemsHtml = items.map(item => `
    <article class="cart-item">
      <img src="${escapeHtml(item.imagen_url || 'public/img/medicamento.svg')}" alt="">
      <div class="cart-item-info">
        <strong>${escapeHtml(item.nombre)}</strong>
        <span>${money(item.precio)}</span>
        ${item.requiere_receta ? '<small class="rx-inline">Rx · bajo receta</small>' : ''}
        <div class="quantity-control">
          <button data-action="minus" data-id="${item.id}">−</button><span>${item.quantity}</span><button data-action="plus" data-id="${item.id}">+</button>
        </div>
      </div>
      <button class="remove-btn" data-action="remove" data-id="${item.id}" title="Quitar">×</button>
    </article>
  `).join('');

  let prescriptionHtml = '';
  if (needsPrescription) {
    prescriptionHtml = prescription ? `
      <div class="prescription-status ok">
        <div><strong>✓ Receta cargada</strong><small>${escapeHtml(prescription.filename)}</small></div>
        <button id="replace-prescription" class="text-button">Cambiar</button>
      </div>` : `
      <div class="prescription-status warning">
        <div><strong>⚠ Falta la receta médica</strong><small>No podés pagar hasta cargarla.</small></div>
        <button id="load-prescription-cart" class="text-button">Cargar</button>
      </div>`;
  }

  dom.cartBody.innerHTML = itemsHtml + prescriptionHtml;

  dom.cartBody.querySelectorAll('[data-action]').forEach(button => {
    button.addEventListener('click', () => {
      const id = Number(button.dataset.id);
      try {
        if (button.dataset.action === 'minus') Cart.updateQuantity(id, -1);
        if (button.dataset.action === 'plus') Cart.updateQuantity(id, 1);
        if (button.dataset.action === 'remove') Cart.removeItem(id);
      } catch (error) {
        toast(error.message, 'warning');
      }
    });
  });

  const replace = $('#replace-prescription');
  if (replace) replace.onclick = () => { state.replaceOnly = true; state.pendingProduct = null; openPrescriptionModal(); };
  const load = $('#load-prescription-cart');
  if (load) load.onclick = () => { state.replaceOnly = true; state.pendingProduct = null; openPrescriptionModal(); };
}

Cart.subscribe(renderCart);
renderCart(Cart.getSnapshot());

dom.cartTrigger.addEventListener('click', openCart);
dom.cartClose.addEventListener('click', closeCart);
dom.cartOverlay.addEventListener('click', closeCart);

dom.clearCart.addEventListener('click', () => {
  if (confirm('¿Vaciar todo el carrito?')) Cart.clearCart();
});

dom.prescriptionFile.addEventListener('change', () => {
  const file = dom.prescriptionFile.files[0];
  dom.selectedFile.textContent = file ? `${file.name} · ${(file.size / 1024 / 1024).toFixed(2)} MB` : 'Ningún archivo seleccionado.';
});

dom.prescriptionForm.addEventListener('submit', async event => {
  event.preventDefault();
  const file = dom.prescriptionFile.files[0];
  if (!file) return toast('Seleccioná una imagen de la receta.', 'warning');

  const oldText = dom.uploadPrescription.textContent;
  dom.uploadPrescription.disabled = true;
  dom.uploadPrescription.textContent = 'Subiendo…';

  try {
    const result = await API.uploadPrescription(file);
    Cart.setPrescription(result);
    if (state.pendingProduct && !state.replaceOnly) {
      Cart.addItem(state.pendingProduct);
      toast('Receta cargada y medicamento agregado.', 'success');
    } else {
      toast('Receta actualizada correctamente.', 'success');
    }
    closePrescriptionModal();
    openCart();
  } catch (error) {
    toast(error.message, 'warning');
  } finally {
    dom.uploadPrescription.disabled = false;
    dom.uploadPrescription.textContent = oldText;
  }
});

dom.closePrescription.addEventListener('click', closePrescriptionModal);
dom.cancelPrescription.addEventListener('click', closePrescriptionModal);

dom.checkout.addEventListener('click', async () => {
  const snapshot = Cart.getSnapshot();
  if (!snapshot.items.length) return;
  if (snapshot.needsPrescription && !snapshot.prescription) {
    toast('Tenés que cargar la receta antes de pagar.', 'warning');
    return;
  }

  const oldText = dom.checkout.textContent;
  dom.checkout.disabled = true;
  dom.checkout.textContent = 'Generando pago…';

  try {
    const result = await API.createCheckoutPreference(snapshot.items, snapshot.prescription?.token || null);
    const redirect = result.init_point || result.sandbox_init_point;
    if (!redirect) throw new Error('Mercado Pago no devolvió una URL de pago.');
    window.location.href = redirect;
  } catch (error) {
    toast(error.message, 'warning');
    dom.checkout.disabled = false;
    dom.checkout.textContent = oldText;
  }
});

let searchTimer;
dom.search.addEventListener('input', event => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    state.search = event.target.value.trim();
    loadProducts();
  }, 300);
});

dom.categoryButtons.forEach(button => {
  button.addEventListener('click', () => {
    dom.categoryButtons.forEach(item => item.classList.remove('active'));
    button.classList.add('active');
    state.category = button.dataset.category;
    loadProducts();
  });
});

loadProducts();
