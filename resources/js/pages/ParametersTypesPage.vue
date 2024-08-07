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
          {{ t('modals.addParamTypesTitle') }}
        </h3>
        <div>
          <VaForm ref="formRef" class="flex flex-col items-baseline gap-2">
            <VaInput class="w-full" v-model="result.Name"
              :rules="[(value) => (value && value.length > 0) || t('form.requiredField')]"
              :label="t('form.name')" />
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
import EditParamTypesModal from '../components/ParamTypesComponent/EditParamTypesModal.vue';
import DeleteParamTypesModal from '../components/ParamTypesComponent/DeleteParamTypesModal.vue';
import { useI18n } from 'vue-i18n';

const { locale, t } = useI18n();

const rowData = ref([]);
const gridApi = ref(null);
const showModal = ref(false);

const result = reactive({
  Name: "",
  Comment: "",
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
  { headerName: t('table.headerRow'), valueGetter: "node.rowIndex + 1", width: 120 },
  { headerName: "ID", field: "id", width: 120 },
  { headerName: t('table.name'), field: "Name", flex: 1 },
  { headerName: t('table.comment'), field: "Comment", flex: 1 },
  {
    cellClass: ['px-0'],
    headerName: "",
    field: "",
    width: 70,
    cellRenderer: EditParamTypesModal,
  },
  {
    cellClass: ['px-0'],
    headerName: "",
    field: "",
    width: 70,
    cellRenderer: DeleteParamTypesModal,
  },
]);

const defaultColDef = {
  sortable: true,
  filter: true,
};

const fetchData = async () => {
  try {
    const response = await axios.get('/paramtypes');
    rowData.value = Array.isArray(response.data) ? response.data : response.data.items;
  } catch (error) {
    console.error('Error fetching data:', error);
  }
};

const onSubmit = async () => {
  try {
    const { data } = await axios.post("/paramtypes", result);
    if (data.status === 200) {
      showModal.value = false;
      result.Name = '';
      result.Comment = '';
      await fetchData();
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
