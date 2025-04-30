<template>
  <div class="grid grid-rows-[55px,1fr]">
    <main>
      <div class="flex justify-between">
        <span class="flex w-full"></span>
      </div>
    </main>
    <main class="flex-grow">
      <div class="m-2">
        <VueShiftCalendar v-model="day" :with-slot="true">
          <VaInput v-model="formatted" label="Smena" readonly />
        </VueShiftCalendar>
      </div>

      <div v-if="filterType">
        <VaTabs v-model="activeTab" grow>
          <template #tabs>
            <VaTab v-for="(page, index) in rowData" :key="index" :name="page.page_name">
              {{ page.page_name }}
            </VaTab>
          </template>
        </VaTabs>

        <div v-for="(page, index) in rowData" :key="index" v-show="activeTab === page.page_name" class="mb-6">
          <ag-grid-vue class="ag-theme-material w-full" :rowData="page.params || []" :columnDefs="[
            { headerName: 'Sex', field: 'factory_structure_name', flex: 1 },
            { headerName: 'Sahifa', field: 'page_name', flex: 1 },
            { headerName: 'Parametr', field: 'param_name', flex: 1 },
            { headerName: 'Vaqti', field: 'graphic_time_name', flex: 1 }
          ]" :defaultColDef="defaultColDef" animateRows style="height: 1000px;" />
        </div>
      </div>

      <ag-grid-vue v-if="!filterType" :rowData="rowData" :columnDefs="columnDefs" :defaultColDef="defaultColDef"
        animateRows="true" class="ag-theme-material h-full" @gridReady="(params) => gridApi = params.api"></ag-grid-vue>
    </main>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, provide, computed, watch, onUnmounted } from 'vue';
import axios from 'axios';
import 'vuestic-ui/dist/vuestic-ui.css';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import { useForm, useToast, VaValue, VaInput, VaButton, VaForm, VaIcon, VaTabs, VaTab } from 'vuestic-ui';
import { defineProps } from 'vue'
import { VueShiftCalendar } from 'vue-shift-calendar';

const { init } = useToast();
const route = useRoute();
const filterType = computed(() => route.query.type); // 'inputed' yoki 'not_inputed'
const { locale, t } = useI18n();
const props = defineProps({ id: Number });

const rowData = ref([]);
const gridApi = ref(null);
const activeTab = ref(null);

const day = ref({ smena: null, day: null });
const defaultColDef = { sortable: true, filter: true };

const columnDefs = computed(() => [
  { headerName: t('table.headerRow'), valueGetter: "node.rowIndex + 1", width: 80 },
  { headerName: t('table.structure'), field: getFielBlog(), flex: 1 },
  { headerName: t('table.namePage'), field: getFieldName(), flex: 1 },
  { headerName: 'Operator', field: 'multiplied_manual_count' },
  { headerName: 'Formula', field: 'multiplied_formula_count' },
  { headerName: t('table.want'), field: 'multiplied_parameter_count' },
  { headerName: t('table.be'), field: 'kiritilgan' },
  { headerName: '%', field: 'foiz', valueFormatter: (params) => `${params.value}%` },
  { headerName: t('table.operator'), field: 'kiritgan_operator' },
]);

const formatted = computed(() => {
  return day.value.day ? `${day.value.day} - ${day.value.smena}` : ''
});

const getFieldName = () => locale.value === 'uz' ? 'page_name' : 'page_name';
const getFieldShortName = () => locale.value === 'uz' ? 'ShortName' : 'ShortNameRus';
const getFielBlog = () => locale.value === 'uz' ? 'factory_structure_name' : 'factory_structure_name';

const fetchData = async () => {
  try {
    const params = { day: day.value.day, smena: day.value.smena };
    const response = await axios.get(`/getRowPageResult/${props.id}`, { params });
    const data = Array.isArray(response.data) ? response.data : response.data.items;

    if (filterType.value === 'inputed' || filterType.value === 'not_inputed') {
      rowData.value = data
        .map((page) => {
          const relevantParams = filterType.value === 'inputed'
            ? page.inputed_param_with_times
            : page.not_inputed_param_with_times;
          if (!relevantParams || relevantParams.length === 0) return null;
          const paramRows = relevantParams.map((item) => ({
            factory_structure_name: page.factory_structure_name,
            page_name: page.page_name,
            param_name: item.param,
            graphic_time_name: item.time,
          }));
          return {
            page_name: page.page_name,
            factory_structure_name: page.factory_structure_name,
            params: paramRows,
          };
        })
        .filter((page) => page !== null);

      activeTab.value = rowData.value.length ? rowData.value[0].page_name : null;
    } else {
      rowData.value = data;
    }
  } catch (error) {
    console.error('❌ Maʼlumot yuklashda xatolik:', error);
  }
};

watch(() => day.value, () => { fetchData(); }, { deep: true });

let refreshInterval = null;
onMounted(() => {
  const savedLocale = localStorage.getItem('locale');
  if (savedLocale) locale.value = savedLocale;

  const now = new Date();
  const hour = now.getHours();
  if (hour >= 8 && hour < 20) {
    day.value.smena = 1;
    day.value.day = now.toISOString().split('T')[0];
  } else {
    day.value.smena = 2;
    const yesterday = new Date(now);
    if (hour < 8) yesterday.setDate(now.getDate() - 1);
    day.value.day = yesterday.toISOString().split('T')[0];
  }
  fetchData();
  refreshInterval = setInterval(() => fetchData(), 300000);
});
onUnmounted(() => { if (refreshInterval) clearInterval(refreshInterval); });

const changeLanguage = () => {
  locale.value = locale.value === 'uz' ? 'ru' : 'uz';
  localStorage.setItem('locale', locale.value);
};
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
  border-bottom: 1px solid #e5e7eb;
}
</style>