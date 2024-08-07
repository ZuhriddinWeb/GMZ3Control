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
          {{ t('modals.addGraphicTimesTitle') }}
        </h3>
        <div>
          <VaForm ref="formRef" class="flex flex-col items-baseline gap-2">
            <div class="grid grid-cols-2 md:grid-cols-2 gap-2 items-end w-full">
              <VaSelect v-model="result.GraphicId" class="mb-6" :label="t('form.selectGraphic')" :options="graphicsOptions" clearable />
              <VaSelect v-model="result.ChangeId" class="mb-6" :label="t('form.selectChange')" :options="changesOptions" clearable />
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 items-end">
              <VaTimeInput clearable clearable-icon="cancel" color="textPrimary" :label="t('form.name')" v-model="result.Name" />
              <VaTimeInput clearable clearable-icon="cancel" color="textPrimary" :label="t('form.startTime')" v-model="result.StartTime" />
              <VaTimeInput v-model="result.EndTime" clearable clearable-icon="cancel" color="textPrimary" :label="t('form.endTime')" />
            </div>
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
import EditGraphicTimesModal from '../components/GraphicTimesComponent/EditGraphicTimesModal.vue';
import DeleteGraphicTimesModal from '../components/GraphicTimesComponent/DeleteGraphicTimesModal.vue';
import { useI18n } from 'vue-i18n';

const { locale, t } = useI18n();

const rowData = ref([]);
const gridApi = ref(null);
const showModal = ref(false);
const graphicsOptions = ref([]);
const changesOptions = ref([]);

const result = reactive({
  GraphicId: "",
  ChangeId: "",
  Name: "",
  StartTime: "",
  EndTime: "",
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
  { headerName: t('table.change'), field: "Change", width: 120 },
  { headerName: t('table.graphicName'), field: "GName", flex: 1 },
  { headerName: t('table.name'), field: "Name", flex: 1 },
  { headerName: t('table.startTime'), field: "StartTime" },
  { headerName: t('table.endTime'), field: "EndTime" },
  { headerName: t('table.comment'), field: "Comment", flex: 1 },
  {
    cellClass: ['px-0'],
    headerName: "",
    field: "",
    width: 70,
    cellRenderer: EditGraphicTimesModal,
  },
  {
    cellClass: ['px-0'],
    headerName: "",
    field: "",
    width: 70,
    cellRenderer: DeleteGraphicTimesModal,
  },
]);

const defaultColDef = {
  sortable: true,
  filter: true,
};

const fetchData = async () => {
  try {
    const response = await axios.get('/graphictimes');
    rowData.value = Array.isArray(response.data) ? response.data : response.data.items;
  } catch (error) {
    console.error('Error fetching data:', error);
  }
};

const fetchGraphics = async () => {
  try {
    const responseGraphics = await axios.get('/graphics');
    const responseChanges = await axios.get('/changes');
    graphicsOptions.value = responseGraphics.data.map(graphic => ({
      value: graphic.id,
      text: graphic.Name,
    }));
    changesOptions.value = responseChanges.data.map(change => ({
      value: change.id,
      text: change.Change,
    }));
  } catch (error) {
    console.error('Error fetching graphics data:', error);
  }
};

const onSubmit = async () => {
  try {
    const { data } = await axios.post("/graphictimes", result);
    if (data.status === 200) {
      showModal.value = false;
      result.GraphicId = '';
      result.ChangeId = '';
      result.Name = '';
      result.StartTime = '';
      result.EndTime = '';
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
  fetchGraphics();
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
