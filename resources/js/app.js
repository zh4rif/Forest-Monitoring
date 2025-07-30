import './bootstrap';
import { createApp } from 'vue';
import App from './App.vue';
import axios from 'axios';

// Configure axios
axios.defaults.baseURL = '/api';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Set token from localStorage if exists
const token = localStorage.getItem('token');
if (token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

createApp(App).mount('#app');
