
import { createApp } from "vue";
import App from "./App.vue";
import axios from "axios";
import router from "./router";
import store from "./store";
import { createVuestic } from "vuestic-ui";
import "vuestic-ui/css";
// import Swal from "sweetalert2";
// import "sweetalert2/src/sweetalert2.scss";
import "ag-grid-community/styles/ag-grid.css";
import "ag-grid-community/styles/ag-theme-quartz.css";
import "ag-grid-community/styles/ag-theme-material.css";
import 'devextreme/dist/css/dx.material.blue.light.css'; 
// import 'devextreme/dist/css/dx.common.css';
import { Bar } from "vue-chartjs";
import { AgGridVue } from "ag-grid-vue3";
import { createI18n } from "vue-i18n";
import locale from "./locale.js";
import "material-icons/iconfont/material-icons.css";
import { useToast } from "vuestic-ui";
import InputMask from 'vue-input-mask';
import 'material-symbols/outlined.css';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import HighchartsVue from 'highcharts-vue';
axios.defaults.baseURL = "/api/";
window.axios = axios;
axios.defaults.withCredentials = true;


// window.Swal = Swal;
window.store = store;
window.router = router;
window.router = useToast;
// window.Pusher = Pusher;

// console.log(window.location.hostname);

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: import.meta.env.VITE_PUSHER_APP_KEY,
//     cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
//     wsHost: window.location.hostname,
//     wsPort: 6001,
//     forceTLS: false,
//     disableStats: true,
// });

const i18n = createI18n({
  legacy: false,
  locale: "uz",
  fallbackLocale: "ru",
  messages: locale,
});

window.i18n = i18n;

async function initApp() {
  await store.dispatch("getUser");
  const app = createApp(App)
    .use(createVuestic())
    .component("AgGridVue", AgGridVue)
    .component("Bar", Bar)
    .component('input-mask', InputMask)
    .use(store)
    .use(router)
    .use(i18n)
    .use(useToast)
    .use(HighchartsVue)
    .mount("#app");
}

initApp();
