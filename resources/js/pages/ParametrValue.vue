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
import { format } from 'date-fns';

const { t, locale } = useI18n();
const rowData = ref([]);
const gridApi = ref(null);
const lastEnteredValues = ref({});

const computedColumnDefs = computed(() => {
  const parameterField = locale.value === 'ru' ? 'PNameRus' : 'PName';

  return [
    {
      headerName: t('table.headerRow'),
      valueGetter: "node.rowIndex + 1",
      width: 80,
      headerClass: 'header-center', // Ensure this class is applied for styling
    },
    {
      headerName: t('table.change'),
      field: "Change",
      width: 80,
      headerClass: 'header-center',
    },
    {
      headerName: t('table.OrderNumber'),
      field: "OrderNumber",
      width: 80,
      headerClass: 'header-center',
    },
    {
      headerName: t('table.parameters'),
      field: parameterField,
      flex: 1,
      headerClass: 'header-center',
    },
    {
      headerName: t('table.graphictimes'),
      headerClass: 'header-center',
      children: [
        {
          headerName: t('table.startingTime'),
          field: 'STime',
          width: 120,
          valueFormatter: (params) => format(new Date(`1970-01-01T${params.value}`), 'HH:mm'),
          headerClass: 'header-center',
        },
        {
          headerName: t('table.endingTime'),
          field: 'ETime',
          width: 120,
          valueFormatter: (params) => format(new Date(`1970-01-01T${params.value}`), 'HH:mm'),
          headerClass: 'header-center',
        }
      ]
    },
    {
      headerName: t('table.interval'),
      headerClass: 'header-center',
      children: [
        {
          headerName: t('table.min'),
          field: 'Min',
          width: 100,
          headerClass: 'header-center',
        },
        {
          headerName: t('table.max'),
          field: 'Max',
          width: 100,
          headerClass: 'header-center',
        }
      ]
    },
    {
      headerName: t('table.value'),
      field: "Value",
      flex: 1,
      editable: true,
      cellEditor: "agNumberCellEditor",
      cellClassRules: {
        'cell-green': (params) => params.data && params.data.Value === lastEnteredValues.value[params.data.id],
        'cell-yellow': (params) => params.data && params.data.Value !== lastEnteredValues.value[params.data.id] && lastEnteredValues.value[params.data.id] === undefined
      },
      cellStyle: {
        'font-size': '16px', 
        'text-align': 'center',
      },
      headerClass: 'header-center',
    },
    {
      headerName: t('table.comment'),
      field: "Comment",
      flex: 1,
      editable: true,
      cellEditor: 'agLargeTextCellEditor',
      cellEditorPopup: true,
      headerClass: 'header-center',
    }
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
  headerHeight: 43,
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

      // Find the row index
      const rowIndex = event.rowIndex;

      // Scroll to the row
      if (gridApi.value) {
        gridApi.value.ensureIndexVisible(rowIndex, 'bottom');
      }
    } catch (error) {
      console.error('Error saving data', error);
    }
  }
};

const saveDataToServer = async (data) => {
  const response = await axios.post('/vparams', data);
  return response;
};

const onGridReady = (params) => {
  gridApi.value = params.api;
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

/* .row-green {
  background-color: green;
  color: white;
} */

.cell-green {
  background-color: rgb(185, 181, 181);
  color: white;
}

.cell-yellow {
  background-color: rgb(185, 181, 181);
  color: black;
}

.ag-row-hover {
  color: black !important;
  /* Change text color to black on hover */
}

.ag-theme-material .ag-row:hover {
  background-color: #f0f0f0;
}
.header-center {
  text-align: center;
}
.ag-header-cell {
  height: 60px; 
  line-height: 60px; 
  padding: 0 10px; 
  /* font-size: 16px; Adjust font size if needed */
}

/* Center align header text */
.header-center {
  text-align: center;
}

.header-center .ag-header-cell-label {
  font-weight: bold; 
}

</style>
