import './bootstrap';
import { createApp } from 'vue';
import OrderlyApp from './components/OrderlyApp.vue';

const app = createApp(OrderlyApp);
app.mount('#app');
