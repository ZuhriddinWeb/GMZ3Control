<template>
  <div class="grid grid-rows-[55px,1fr]">
    <!-- Add new Elements start -->
    <main>
      <div class="flex justify-between">
        <span class="flex w-full"></span>
      </div>
      
    </main>
    <!-- Add new Elements end -->
    <main class="flex-grow">
      <div class="m-2">
        <VueShiftCalendar v-model="day" :with-slot="true">
          <VaInput v-model="formatted" label="Smena" readonly/>
        </VueShiftCalendar>
      </div>
      <ag-grid-vue :rowData="rowData" :columnDefs="columnDefs" :defaultColDef="defaultColDef" animateRows="true"
        class="ag-theme-material h-full" @gridReady="(params) => gridApi = params.api"></ag-grid-vue>
    </main>
  </div>
</template>


<script setup>
import { ref, reactive, onMounted, provide, computed,watch,onUnmounted   } from 'vue';
import axios from 'axios';
import 'vuestic-ui/dist/vuestic-ui.css';
import { useI18n } from 'vue-i18n';
import { useForm, useToast, VaValue, VaInput, VaButton, VaForm, VaIcon } from 'vuestic-ui';
const { init } = useToast();
import { defineProps } from 'vue'
import { VueShiftCalendar } from 'vue-shift-calendar';


const { locale, t } = useI18n();
const props = defineProps({
  id: Number
})
const rowData = ref([]);
const gridApi = ref(null);
const day = ref({
  smena: null,
  day: null
});
const columnDefs = computed(() => [
  { headerName: t('table.headerRow'), valueGetter: "node.rowIndex + 1",width:80 },
  { headerName: t('table.structure'), field: getFielBlog(), flex: 1 },
  { headerName: t('table.namePage'), field: getFieldName(), flex: 1 },
  // { headerName: t('table.numberpage'), field: 'page_id' },
  { headerName: t('table.want'), field: 'multiplied_parameter_count' },
  { headerName: t('table.be'), field: 'kiritilgan' },
  { headerName: '%', field: 'foiz',valueFormatter: (params) => `${params.value}%` },
  { headerName: t('table.operator'), field: 'kiritgan_operator' },

]);
const formatted = computed(() => {
  return day.value.day ? `${day.value.day} - ${day.value.smena}` : ''
})
const defaultColDef = {
  sortable: true,
  filter: true
};

const getFieldName = () => {
  return locale.value === 'uz' ? 'page_name' : 'page_name';
};

const getFieldShortName = () => {
  return locale.value === 'uz' ? 'ShortName' : 'ShortNameRus';
};
const getFielBlog = () => {
  return locale.value === 'uz' ? 'factory_structure_name' : 'factory_structure_name';
};

const fetchData = async () => {
  try {
    const params = {
      day: day.value.day,
      smena: day.value.smena
    };
    const response = await axios.get(`/getRowPageResult/${props.id}`, { params });
    rowData.value = Array.isArray(response.data) ? response.data : response.data.items;
  } catch (error) {
    console.error('Error fetching data:', error);
  }
};


// const fetchGraphics = async () => {
//   try {
//     const responseGraphics = await axios.get('/structure');
//     factoryOptions.value = responseGraphics.data.map(factory => ({
//       value: factory.id,
//       text: locale.value === 'uz' ? factory.Name : factory.NameRus
//     }));
//   } catch (error) {
//     console.error('Error fetching graphics data:', error);
//   }
// };
watch(() => day.value, () => {
  fetchData();
}, { deep: true });



let refreshInterval = null;

onMounted(() => {
  const savedLocale = localStorage.getItem('locale');
  if (savedLocale) {
    locale.value = savedLocale;
  }

  const now = new Date();
  const hour = now.getHours();

  if (hour >= 8 && hour < 20) {
    day.value.smena = 1;
    day.value.day = now.toISOString().split('T')[0];
  } else {
    day.value.smena = 2;

    if (hour < 8) {
      const yesterday = new Date(now);
      yesterday.setDate(now.getDate() - 1);
      day.value.day = yesterday.toISOString().split('T')[0];
    } else {
      day.value.day = now.toISOString().split('T')[0];
    }
  }

  // fetchData();
  // fetchGraphics();

  // ⏱ 10 daqiqalik avtomatik yangilanish (600_000 ms)
  refreshInterval = setInterval(() => {
    fetchData();
  }, 300000);
});
onUnmounted(() => {
  if (refreshInterval) {
    clearInterval(refreshInterval);
  }
});


const changeLanguage = () => {
  locale.value = locale.value === 'uz' ? 'ru' : 'uz';
  // Save language preference to localStorage
  localStorage.setItem('locale', locale.value);
  // Refresh factory options with the new language
  // fetchGraphics();
};

const currentLanguageLabel = computed(() => {
  return locale.value === 'uz' ? 'Русский' : 'O‘zbek';
});
</script>


<style>
.material-icons {
  font-family: 'Material Icons';
  font-weight: normal;
  font-style: normal;
  font-size: 24px;
  
  display: inline-block;
  line-height: 1;
  text-transform: none;
  letter-spacing: normal;
  word-wrap: normal;
  white-space: nowrap;
  direction: ltr;
}
.ag-theme-material .ag-cell {
  border-right: 1px solid #d1d5db;
}

.ag-theme-material .ag-header-cell {
  border-right: 1px solid #d1d5db;
}

.ag-theme-material .ag-row {
  border-bottom: 1px solid #e5e7eb; /* pastki border ham ixtiyoriy */
}
</style>
