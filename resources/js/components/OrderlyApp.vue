<template>
  <div class="orderly-shell">
    <header class="topbar">
      <div class="topbar-brand">
        <svg class="brand-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span>Ordenes</span>
      </div>
      <div class="topbar-status">
        <span :class="['ws-dot', wsConnected ? 'ws-dot--on' : 'ws-dot--off']"></span>
        <span class="ws-label">{{ wsConnected ? 'Tiempo real activo' : 'Conectando…' }}</span>
      </div>
    </header>

    <main class="workspace">
      <section class="panel panel--form">
        <h2 class="panel-title">Nuevo Pedido</h2>

        <form class="order-form" @submit.prevent="submitOrder">
          <div class="form-grid">
            <div class="field">
              <label for="customer_name">Nombre del cliente</label>
              <input id="customer_name" v-model="form.customer_name" type="text" placeholder="Ej. Juan Pérez" required />
            </div>
            <div class="field">
              <label for="customer_email">Correo electrónico</label>
              <input id="customer_email" v-model="form.customer_email" type="email" placeholder="cliente@email.com" required />
            </div>
          </div>

          <div class="field">
            <label for="payment_method">Método de pago</label>
            <select id="payment_method" v-model="form.payment_method" required>
              <option value="card">💳 Tarjeta de crédito/débito</option>
              <option value="cash">💵 Efectivo</option>
              <option value="transfer">🏦 Transferencia bancaria</option>
            </select>
          </div>

          <div class="items-section">
            <div class="items-header">
              <h3>Ítems del Pedido</h3>
              <button type="button" class="btn btn--ghost btn--sm" @click="addItem">+ Agregar ítem</button>
            </div>

            <transition-group name="item-list" tag="div" class="items-list">
              <div v-for="(item, index) in form.items" :key="index" class="item-row">
                <div class="field item-name">
                  <input v-model="item.name" type="text" placeholder="Nombre del plato" required />
                </div>
                <div class="field item-qty">
                  <input v-model.number="item.quantity" type="number" min="1" placeholder="Cant." required />
                </div>
                <div class="field item-price">
                  <input v-model.number="item.unit_price" type="number" min="0.01" step="0.01" placeholder="Precio" required />
                </div>
                <button type="button" class="btn-remove" @click="removeItem(index)" :disabled="form.items.length === 1">×</button>
              </div>
            </transition-group>
          </div>

          <div class="total-preview">
            <span>Total:</span>
            <strong>{{ formatCurrency(orderTotal) }}</strong>
          </div>

          <div v-if="formError" class="alert alert--error">{{ formError }}</div>

          <button type="submit" class="btn btn--primary btn--full" :disabled="submitting">
            <span v-if="submitting" class="spinner"></span>
            {{ submitting ? 'Procesando…' : 'Confirmar Pedido' }}
          </button>
        </form>
      </section>

      <section class="panel panel--orders">
        <div class="panel-header">
          <h2 class="panel-title">Pedidos en Vivo</h2>
          <span class="badge">{{ orders.length }}</span>
        </div>

        <div v-if="loadingOrders" class="orders-loading">
          <div class="pulse-dots">
            <span></span><span></span><span></span>
          </div>
          <p>Cargando pedidos…</p>
        </div>

        <div v-else-if="orders.length === 0" class="orders-empty">
          <svg viewBox="0 0 24 24" fill="none"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
          <p>No hay pedidos aún. ¡Crea el primero!</p>
        </div>

        <transition-group v-else name="order-card" tag="div" class="orders-grid">
          <article v-for="order in orders" :key="order.id" :class="['order-card', `order-card--${order.status}`]">
            <div class="order-card-top">
              <div class="order-id">#{{ order.id }}</div>
              <div :class="['status-chip', `status-chip--${order.status}`]">
                <span class="status-icon">{{ statusIcon(order.status) }}</span>
                {{ order.status_label }}
              </div>
            </div>

            <div class="order-customer">
              <div class="customer-avatar">{{ order.customer_name.charAt(0).toUpperCase() }}</div>
              <div class="customer-info">
                <strong>{{ order.customer_name }}</strong>
                <span>{{ order.customer_email }}</span>
              </div>
            </div>

            <ul class="order-items-list">
              <li v-for="(item, i) in order.items" :key="i">
                <span class="item-qty-badge">{{ item.quantity }}×</span>
                {{ item.name }}
                <span class="item-subtotal">{{ formatCurrency(item.unit_price * item.quantity) }}</span>
              </li>
            </ul>

            <div class="order-footer">
              <div class="order-total">Total: <strong>{{ formatCurrency(order.total) }}</strong></div>
              <div class="order-time">{{ formatTime(order.created_at) }}</div>
            </div>

            <div v-if="order.status === 'pending'" class="order-progress">
              <div class="progress-bar progress-bar--indeterminate"></div>
            </div>
            <div v-else-if="order.status === 'paid' || order.status === 'preparing'" class="order-progress">
              <div class="progress-bar" :style="{ width: progressWidth(order.status) }"></div>
            </div>
          </article>
        </transition-group>
      </section>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const wsConnected = ref(false);
const submitting = ref(false);
const loadingOrders = ref(true);
const formError = ref('');
const orders = ref([]);

const form = ref({
  customer_name: '',
  customer_email: '',
  payment_method: 'card',
  items: [{ name: '', quantity: 1, unit_price: '' }],
});

const orderTotal = computed(() =>
  form.value.items.reduce((sum, item) => {
    const price = parseFloat(item.unit_price) || 0;
    const qty = parseInt(item.quantity) || 0;
    return sum + price * qty;
  }, 0)
);

function addItem() {
  form.value.items.push({ name: '', quantity: 1, unit_price: '' });
}

function removeItem(index) {
  if (form.value.items.length > 1) {
    form.value.items.splice(index, 1);
  }
}

function formatCurrency(value) {
  return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(value);
}

function formatTime(isoString) {
  if (!isoString) return '';
  return new Intl.DateTimeFormat('es-CO', { hour: '2-digit', minute: '2-digit', second: '2-digit' }).format(new Date(isoString));
}

function statusIcon(status) {
  const icons = {
    pending:   '⏳',
    paid:      '✅',
    preparing: '👨‍🍳',
    ready:     '🍽️',
    delivered: '🏍️',
    cancelled: '❌',
  };
  return icons[status] ?? '📋';
}

function progressWidth(status) {
  const map = { pending: '15%', paid: '40%', preparing: '70%', ready: '100%', delivered: '100%' };
  return map[status] ?? '0%';
}

async function fetchOrders() {
  try {
    const res = await window.axios.get('/api/v1/orders');
    orders.value = res.data.data;
  } catch {
    // silent
  } finally {
    loadingOrders.value = false;
  }
}

async function submitOrder() {
  formError.value = '';
  submitting.value = true;
  try {
    const res = await window.axios.post('/api/v1/orders', form.value);
    const newOrder = res.data.data;
    orders.value.unshift({
      ...newOrder,
      items: form.value.items.map(i => ({ ...i })),
      customer_email: form.value.customer_email,
      payment_method: form.value.payment_method,
    });
    form.value = {
      customer_name: '',
      customer_email: '',
      payment_method: 'card',
      items: [{ name: '', quantity: 1, unit_price: '' }],
    };
  } catch (err) {
    if (err.response?.data?.errors) {
      const msgs = Object.values(err.response.data.errors).flat();
      formError.value = msgs.join(' | ');
    } else {
      formError.value = 'Error al crear el pedido. Inténtalo de nuevo.';
    }
  } finally {
    submitting.value = false;
  }
}

function handleOrderCreated(data) {
  const exists = orders.value.find(o => o.id === data.id);
  if (!exists) {
    orders.value.unshift(data);
  }
}

function handleStatusUpdated(data) {
  const index = orders.value.findIndex(o => o.id === data.id);
  if (index !== -1) {
    orders.value[index] = { ...orders.value[index], ...data };
  }
}

let channel = null;

onMounted(() => {
  fetchOrders();

  channel = window.Echo.channel('orders');

  channel
    .listen('.order.created', handleOrderCreated)
    .listen('.order.status.updated', handleStatusUpdated);

  window.Echo.connector.pusher.connection.bind('connected', () => {
    wsConnected.value = true;
  });
  window.Echo.connector.pusher.connection.bind('disconnected', () => {
    wsConnected.value = false;
  });
  window.Echo.connector.pusher.connection.bind('error', () => {
    wsConnected.value = false;
  });
});

onUnmounted(() => {
  if (channel) {
    window.Echo.leave('orders');
  }
});
</script>
