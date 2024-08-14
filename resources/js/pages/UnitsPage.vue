<template>
  <div class="grid grid-rows-[55px,1fr]">
    <!-- Add new Elements start -->
    <main>
      <div class="flex justify-between">
        <span class="flex w-full"></span>
        <VaButton @click="showModal = true" class="w-14 h-12 mt-1 mr-1" icon="add" />
      </div>
      <VaModal v-model="showModal" :ok-text="t('buttons.save')" :cancel-text="t('buttons.cancel')" @ok="onSubmit" close-button>
        <h3 class="va-h3">
          {{ t('modals.addUnitTitle') }}
        </h3>
        <div>
          <VaForm ref="formRef" class="flex flex-col items-baseline gap-2">
            <VaInput class="w-full" v-model="result.Name"
              :rules="[(value) => (value && value.length > 0) || t('validation.requiredField')]"
              :label="t('form.name')" />
            <VaInput class="w-full" v-model="result.NameRus"
              :rules="[(value) => (value && value.length > 0) || t('validation.requiredField')]"
              :label="t('form.nameRus')" />
            <VaInput class="w-full" v-model="result.ShortName"
              :rules="[(value) => (value && value.length > 0) || t('validation.requiredField')]"
              :label="t('form.shortName')" />
            <VaInput class="w-full" v-model="result.ShortNameRus"
              :rules="[(value) => (value && value.length > 0) || t('validation.requiredField')]"
              :label="t('form.shortNameRus')" />
            <VaTextarea class="w-full" v-model="result.Comment" max-length="125" :label="t('form.comment')" />
          </VaForm>
        </div>
      </VaModal>
    </main>
    <!-- Add new Elements end -->
    <main class="flex-grow">
      <ag-grid-vue :rowData="rowData" :columnDefs="columnDefs" :defaultColDef="defaultColDef" animateRows="true"
        class="ag-theme-material h-full" @gridReady="(params) => gridApi = params.api"></ag-grid-vue>
    </main>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, provide, computed } from 'vue';
import axios from 'axios';
import 'vuestic-ui/dist/vuestic-ui.css';
import DeleteUnitsModal from '../components/UnitsComponent/DeleteUnitsModal.vue';
import EditUnitsModal from '../components/UnitsComponent/EditUnitsModal.vue';
import { useI18n } from 'vue-i18n';
import { useForm, useToast, VaValue, VaInput, VaButton, VaForm, VaIcon } from 'vuestic-ui';
const { init } = useToast();
const { locale, t } = useI18n();

const rowData = ref([]);
const gridApi = ref(null);
const showModal = ref(false);

const result = reactive({
  Name: "",
  ShortName: "",
  NameRus: "",
  ShortNameRus: "",
  Comment: ""
});

function ondeleted(selectedData) {
  gridApi.value.applyTransaction({ remove: [selectedData] });
}

function onupdated(rowNode, data) {
  rowNode.setData(data);
}

provide('ondeleted', ondeleted);
provide('onupdated', onupdated);

const columnDefs = computed(() => [
  { headerName: t('table.headerRow'), valueGetter: "node.rowIndex + 1" },
  { headerName: t('table.name'), field: getFieldName(), flex: 1 },
  { headerName: t('table.shortName'), field: getFieldShortName() },
  { headerName: t('table.comment'), field: 'Comment', flex: 1 },
  {
    cellClass: ['px-0'],
    headerName: "",
    field: "",
    width: 70,
    cellRenderer: EditUnitsModal,
  },
  {
    cellClass: ['px-0'],
    headerName: "",
    field: "",
    width: 70,
    cellRenderer: DeleteUnitsModal,
  },
]);

const defaultColDef = {
  sortable: true,
  filter: true
};

const getFieldName = () => {
  return locale.value === 'uz' ? 'Name' : 'NameRus';
};

const getFieldShortName = () => {
  return locale.value === 'uz' ? 'ShortName' : 'ShortNameRus';
};

const fetchData = async () => {
  try {
    const response = await axios.get('/units');
    rowData.value = Array.isArray(response.data) ? response.data : response.data.items;
  } catch (error) {
    console.error('Error fetching data:', error);
  }
};

const onSubmit = async () => {
  try {
    const { data } = await axios.post("/units", result);
    if (data.status === 200) {
      showModal.value = false;
      result.Name = '';
      result.ShortName = '';
      result.NameRus = '';
      result.ShortNameRus = '';
      result.Comment = '';
      await fetchData();
      init({ message: t('login.successMessage'), color: 'success' });

    } else {
      console.error('Error saving data:', data.message);
    }
  } catch (error) {
    console.error('Error saving data:', error);
  }
};

onMounted(() => {
  // Load language preference from localStorage
  const savedLocale = localStorage.getItem('locale');
  if (savedLocale) {
    locale.value = savedLocale;
  }
  fetchData();
});

const changeLanguage = () => {
  locale.value = locale.value === 'uz' ? 'ru' : 'uz';
  // Save language preference to localStorage
  localStorage.setItem('locale', locale.value);
  // Refresh grid data with the new language
  fetchData();
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
</style>
