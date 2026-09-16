const CART_KEY = 'farmacia_online_cart_v1';
const PRESCRIPTION_KEY = 'farmacia_online_prescription_v1';

class CartStore {
  constructor() {
    this.items = this.read(CART_KEY, []);
    this.prescription = this.read(PRESCRIPTION_KEY, null);
    this.listeners = [];
  }

  read(key, fallback) {
    try {
      const value = localStorage.getItem(key);
      return value ? JSON.parse(value) : fallback;
    } catch {
      return fallback;
    }
  }

  persist() {
    localStorage.setItem(CART_KEY, JSON.stringify(this.items));
    if (this.prescription) {
      localStorage.setItem(PRESCRIPTION_KEY, JSON.stringify(this.prescription));
    } else {
      localStorage.removeItem(PRESCRIPTION_KEY);
    }
  }

  subscribe(listener) {
    this.listeners.push(listener);
  }

  notify() {
    this.persist();
    const snapshot = this.getSnapshot();
    this.listeners.forEach(fn => fn(snapshot));
  }

  getSnapshot() {
    return {
      items: this.getItems(),
      total: this.getTotal(),
      count: this.getItemCount(),
      prescription: this.getPrescription(),
      needsPrescription: this.needsPrescription()
    };
  }

  getItems() {
    return this.items.map(item => ({...item}));
  }

  addItem(product) {
    const existing = this.items.find(item => item.id === product.id);
    if (existing) {
      if (existing.quantity >= product.stock) {
        throw new Error(`Solo hay ${product.stock} unidades disponibles.`);
      }
      existing.quantity += 1;
    } else {
      if (product.stock < 1) throw new Error('Producto sin stock.');
      this.items.push({
        id: product.id,
        nombre: product.nombre,
        precio: Number(product.precio),
        imagen_url: product.imagen_url,
        stock: Number(product.stock),
        requiere_receta: Boolean(product.requiere_receta),
        quantity: 1
      });
    }
    this.notify();
  }

  updateQuantity(id, delta) {
    const item = this.items.find(product => product.id === id);
    if (!item) return;
    const next = item.quantity + delta;
    if (next <= 0) {
      this.removeItem(id);
      return;
    }
    if (next > item.stock) throw new Error(`Solo hay ${item.stock} unidades disponibles.`);
    item.quantity = next;
    this.notify();
  }

  removeItem(id) {
    this.items = this.items.filter(item => item.id !== id);
    this.notify();
  }

  clearCart() {
    this.items = [];
    this.prescription = null;
    this.notify();
  }

  getTotal() {
    return this.items.reduce((sum, item) => sum + item.precio * item.quantity, 0);
  }

  getItemCount() {
    return this.items.reduce((sum, item) => sum + item.quantity, 0);
  }

  needsPrescription() {
    return this.items.some(item => item.requiere_receta);
  }

  setPrescription(data) {
    this.prescription = {
      token: data.token,
      filename: data.filename,
      uploadedAt: Date.now()
    };
    this.notify();
  }

  getPrescription() {
    return this.prescription ? {...this.prescription} : null;
  }

  clearPrescription() {
    this.prescription = null;
    this.notify();
  }
}

export const Cart = new CartStore();
