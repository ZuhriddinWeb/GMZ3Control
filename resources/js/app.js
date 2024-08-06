import { createApp } from "vue";
import App from './App.vue';
import axios from 'axios';
import router from './router';
import store from './store';
import { createVuestic } from "vuestic-ui";
import "vuestic-ui/css";
import Swal from 'sweetalert2';
import 'sweetalert2/src/sweetalert2.scss';
import "ag-grid-community/styles/ag-grid.css";
import "ag-grid-community/styles/ag-theme-quartz.css";
import "ag-grid-community/styles/ag-theme-material.css";
import { Bar } from 'vue-chartjs';
import { AgGridVue } from "ag-grid-vue3";
import { createI18n } from 'vue-i18n';
import ru from './ru.json'; 
import uz from './uz.json'; 

axios.defaults.baseURL = "/api/";
window.axios = axios;
axios.defaults.withCredentials = true;

window.Swal = Swal;
window.store = store;
window.router = router;

const i18n = createI18n({
    legacy: false,
    locale: 'uz', 
    fallbackLocale: 'ru', 
    messages: {
      ru,
      uz
    },
  });
  

window.i18n = i18n;

async function initApp() {
  await store.dispatch('getUser');
  const app = createApp(App)
    .use(createVuestic())
    .component('AgGridVue', AgGridVue)
    .component('Bar', Bar)
    .use(store)
    .use(router)
    .use(i18n)
    .mount('#app');
}

initApp();
