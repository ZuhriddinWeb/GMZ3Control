import './bootstrap';
import { createApp } from "vue";
import App from './App.vue';
import axios from 'axios'
import router from './router'
import store from './store'
import { createVuestic } from "vuestic-ui";
import "vuestic-ui/css";
import Swal from 'sweetalert2'
// import Swal from 'sweetalert2/dist/sweetalert2.js'
import 'sweetalert2/src/sweetalert2.scss'
import "ag-grid-community/styles/ag-grid.css";
import "ag-grid-community/styles/ag-theme-quartz.css";
import "ag-grid-community/styles/ag-theme-material.css";
// import "ag-grid-community/styles/ag-theme-material-dark.css";
import { Bar } from 'vue-chartjs'
import { AgGridVue } from "ag-grid-vue3"; // Vue Data Grid Component

axios.defaults.baseURL = "/api/";
window.axios = axios
axios.defaults.withCredentials = true;

window.Swal = Swal
window.store = store
window.router = router




async function initApp(){
    await store.dispatch('getUser')
    const app = createApp(App)
    .use(createVuestic())
    .component('AgGridVue', AgGridVue)
    .component('Bar', Bar)
    .use(store)
    .use(router)
    .mount('#app');

}

initApp()