<template>
  <main class="h-full w-full text-center content-center">
    <VaButton round icon="functions" preset="primary" class="mt-1 w-" @click="() => { selectedDataEdit = true }"  />
    <VaModal v-model="selectedDataEdit" :ok-text="null" :cancel-text="null" 
      @close="selectedDataEdit = false" close-button class="custom-modal " size="large">
      <h3 class="va-h3">
        {{ t('modals.addFormula') }}
      </h3>
      <div class="flex justify-between">
        <main class="h-full w-full">
          <ag-grid-vue :rowData="rowData" :columnDefs="columnDefs" :defaultColDef="defaultColDef" animateRows="true"
            class="ag-theme-material" style="height: 800px; width: 100%;" @gridReady="(params) => gridApi = params.api">
          </ag-grid-vue>
        </main>
      </div>
    </VaModal>
  </main>
</template>

<script setup>
import { ref, reactive, onMounted, provide, computed,watch  } from 'vue';
import axios from 'axios';
import 'vuestic-ui/dist/vuestic-ui.css';
// import EditGraphicTimesModal from '../components/GraphicTimesComponent/EditGraphicTimesModal.vue';
// import DeleteGraphicTimesModal from '../components/GraphicTimesComponent/DeleteGraphicTimesModal.vue';
import { useI18n } from 'vue-i18n';
const { locale, t } = useI18n();
import { format } from 'date-fns';

import { useForm, useToast, VaValue, VaInput, VaButton, VaForm, VaIcon } from 'vuestic-ui';
import Calculator from './Calculator.vue';
import EditCalculator from './EditCalculator.vue';

const { init } = useToast();
const selectedDataEdit = ref(false);
const rowData = ref([]);
const gridApi = ref(null);
const showModal = ref(false);
const graphicsOptions = ref([]);
const changesOptions = ref([]);
const props = defineProps(["params"]);

const result = reactive({
  GraphicId: props.params.data['GrapicsID'],
  ChangeId: "",
  Name: "",
  StartTime: "",
  EndTime: "",
  // Current: false
});

function ondeleted(selectedData) {
  gridApi.value.applyTransaction({ remove: [selectedData] });
}

function onupdated(rowNode, data) {
  rowNode.setData(data);
}

provide('ondeleted', ondeleted);
provide('onupdated', onupdated);

// const updateTimes = () => {
//   console.log(result.Current);
// };

const columnDefs = computed(() => [
  { headerName: t('table.headerRow'), valueGetter: "node.rowIndex + 1", width: 80 },
  { headerName: "ID", field: "id", width: 80 },
  { headerName: t('table.change'), field: "Change", width: 120 },
  { headerName: t('table.graphicName'), field: "GName", flex: 1 },
  {
    headerName: t('table.name'), field: "Name", flex: 1, valueFormatter: (params) => {
      return format(new Date(`1970-01-01T${params.value}`), 'HH:mm');
    },
  },
  {
    headerName: t('table.startTime'),
    field: "StartTime",
    valueFormatter: (params) => {
      return format(new Date(`1970-01-01T${params.value}`), 'HH:mm');
    },
  },
  {
    headerName: t('table.endTime'),
    field: "EndTime",
    valueFormatter: (params) => {
      return format(new Date(`1970-01-01T${params.value}`), 'HH:mm');
    },
  },
  // { headerName: t('table.comment'), field: "Comment", flex: 1 },
  // {
  //   cellClass: ['px-0'],
  //   headerName: "",
  //   field: "",
  //   width: 70,
  //   cellRenderer: EditGraphicTimesModal,
  // },
  {
    cellClass: ['px-0'],
    headerName: "",
    field: "",
    width: 70,
    cellRenderer: EditCalculator,
  },
  {
    cellClass: ['px-0'],
    headerName: "",
    field: "",
    width: 70,
    cellRenderer: Calculator,
  },
]);

const defaultColDef = {
  sortable: true,
  filter: true,
};

const fetchData = async () => {
  store.state.GParamID = props.params.data['ParametersID'];
  store.state.GPid=props.params.data['id'];
  store.state.GrapicsID=props.params.data['Gid'];

  try {
    const response = await axios.get(`/getRowTimes/${result.GraphicId}/${store.state.GParamID}/${store.state.GPid}/${props.params.data['Gid']}`);
    rowData.value = Array.isArray(response.data) ? response.data : response.data.items;
  } catch (error) {
    console.error('Error fetching data:', error);
  }
};

// const fetchGraphics = async () => {
//   try {
//     const responseGraphics = await axios.get('/graphics');
//     const responseChanges = await axios.get('/changes');
//     graphicsOptions.value = responseGraphics.data.map(graphic => ({
//       value: graphic.id,
//       text: graphic.Name,
//     }));
//     changesOptions.value = responseChanges.data.map(change => ({
//       value: change.Change,
//       text: change.Change,
//     }));
//   } catch (error) {
//     console.error('Error fetching graphics data:', error);
//   }
// };

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
      // result.Current=false;
      await fetchData();
      init({ message: t('login.successMessage'), color: 'success' });
    } else {
      console.error('Error saving data:', data.message);
    }
  } catch (error) {
    console.error('Error saving data:', error);
  }
};
// selectedDataEdit qiymatini kuzatish
watch(selectedDataEdit, (newValue) => {
  if (newValue) {
    fetchData();
  }
});
// onMounted(() => {
//   const savedLocale = localStorage.getItem('locale');
//   if (savedLocale) {
//     locale.value = savedLocale;
//   }
//   fetchData();
//   // fetchGraphics();
// });

</script>