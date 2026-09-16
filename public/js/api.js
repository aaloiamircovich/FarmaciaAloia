export const API = {
  async getProducts(category = 'todos', search = '') {
    const params = new URLSearchParams();
    if (category && category !== 'todos') params.set('category', category);
    if (search) params.set('q', search);

    const response = await fetch(`api/get_products.php?${params.toString()}`);
    const data = await response.json();
    if (!response.ok || data.status !== 'success') {
      throw new Error(data.message || 'No se pudieron cargar los productos.');
    }
    return data.data;
  },

  async uploadPrescription(file) {
    const formData = new FormData();
    formData.append('receta', file);

    const response = await fetch('api/upload_prescription.php', {
      method: 'POST',
      body: formData
    });

    const data = await response.json();
    if (!response.ok || data.status !== 'success') {
      throw new Error(data.message || 'No se pudo cargar la receta.');
    }
    return data;
  },

  async createCheckoutPreference(cartItems, prescriptionToken = null) {
    const response = await fetch('api/create_preference.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        items: cartItems.map(item => ({id: item.id, quantity: item.quantity})),
        prescription_token: prescriptionToken
      })
    });

    const data = await response.json();
    if (!response.ok || data.status !== 'success') {
      throw new Error(data.message || 'No se pudo iniciar el pago.');
    }
    return data;
  }
};
