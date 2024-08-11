<template>
  <div class="grid grid-rows-[55px,1fr]">
    <main></main>
    <main class="flex-grow">
      <ag-grid-vue :rowData="rowData" :columnDefs="computedColumnDefs" :defaultColDef="defaultColDef"
        :gridOptions="gridOptions" animateRows="true" class="ag-theme-material h-full" @gridReady="onGridReady"
        @cellValueChanged="onCellValueChanged"></ag-grid-vue>
    </main>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import 'vuestic-ui/dist/vuestic-ui.css';

const { t, locale } = useI18n();
const rowData = ref([]);
const gridApi = ref(null);
const valueCount = ref(0);

// Compute column definitions based on the current language
const computedColumnDefs = computed(() => {
  const parameterField = locale.value === 'ru' ? 'PNameRus' : 'PName';

  return [
    { headerName: t('table.headerRow'), valueGetter: "node.rowIndex + 1", width: 80 },
    { headerName: t('table.change'), field: "id", hide: true, flex: 1, width: 80 },
    { headerName: t('table.change'), field: "Change", width: 60 },
    { headerName: t('table.parameters'), field: parameterField, flex: 1 },
    {
      headerName: t('table.graphictimes'),
      children: [
        { headerName: t('table.startingTime'), field: 'STime' },
        { headerName: t('table.endingTime'), field: 'ETime' }
      ]
    },
    {
      headerName: t('table.interval'),
      children: [
        { headerName: t('table.min'), field: 'Min' },
        { headerName: t('table.max'), field: 'Max' }
      ]
    },
    { headerName: t('table.value'), field: "Value", flex: 1, editable: true, cellEditor: "agNumberCellEditor" },
    {
      headerName: t('table.comment'), field: "Comment", flex: 1, editable: true, cellEditor: 'agLargeTextCellEditor', cellEditorPopup: true
    },
  ];
});

const defaultColDef = {
  sortable: true,
  filter: true,
  resizable: true,
};

const getRowClass = (params) => {
  if (params.data.STime && params.data.ETime && params.data.Value) {
    return 'row-green';
  }
  return '';
};

const gridOptions = {
  columnDefs: computedColumnDefs.value,
  getRowClass,
};

const fetchData = async () => {
  const currentHour = new Date().getHours();
  const change = (currentHour >= 8 && currentHour < 20) ? 1 : 2;
  try {
    axios.all([
      axios.get(`/get-params-for-user/${store.state.user.structure_id}/${change}`),
      axios.get(`/vparams/${store.state.user.structure_id}`)
    ])
      .then(axios.spread(({ data: params }, { data: values }) => {
        params.forEach((parametr, index) => {
          const select = values.find((val) => val.TimeID == parametr.GTid && val.ParametersID == parametr.ParametersID);
          if (select) {
            params[index] = { ...parametr, ...select };
          }
        });
        // params.sort((a, b) => (a.Value ? 1 : -1));
        rowData.value = params;

        store.state.countInputedParams = params.filter(param => param.Value !== null && param.Value !== undefined && param.Value !== '').length;
      }));
  } catch (error) {
    console.error('Error fetching data:', error);
  }
};

const onCellValueChanged = async (event) => {
  const { data, colDef, newValue, oldValue } = event;
  if (newValue !== oldValue) {
    try {
      await saveDataToServer(data);

      const rowIndex = event.rowIndex;
      console.log(gridApi.value);
      gridApi.value.ensureIndexVisible(rowIndex, 'bottom');
    } catch (error) {
      console.error('Error saving data', error);
    }
  }
};
const onGridReady = (params) => {
  gridApi.value = params.api;
};

const saveDataToServer = async (data) => {
  const response = await axios.post('/vparams', data);
  return response;
};

watch(
  () => locale.value,
  () => {
    gridOptions.columnDefs = computedColumnDefs.value;
  },
  { immediate: true }
);

onMounted(() => {
  fetchData();
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

.row-green {
  background-color: rgb(11, 151, 11);
  color: white;
}

.ag-row-hover {
  color: black !important;
  /* Change text color to black on hover */
}

/* Ensure the hover class applies only to rows with specific conditions */

</style>
