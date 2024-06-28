import './bootstrap';
import { createApp } from "vue";
import App from './App.vue';
import axios from 'axios'
import router from './router'
import store from './store'
import { createVuestic } from "vuestic-ui";
import "vuestic-ui/css";

import "ag-grid-community/styles/ag-grid.css";
import "ag-grid-community/styles/ag-theme-quartz.css";
import "ag-grid-community/styles/ag-theme-material.css";
// import "ag-grid-community/styles/ag-theme-material-dark.css";

import { AgGridVue } from "ag-grid-vue3"; // Vue Data Grid Component

axios.defaults.baseURL = "/api/";
window.axios = axios
axios.defaults.withCredentials = true;

window.store = store
window.router = router

const app = createApp(App)
.use(createVuestic())
.component('AgGridVue', AgGridVue)
.use(router)
.mount('#app');

