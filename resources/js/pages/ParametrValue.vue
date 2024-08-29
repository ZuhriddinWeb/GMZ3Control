<template>
  <div class="grid grid-rows-[55px,1fr]">
    <main></main>
    <main class="flex flex-col">
      <div class="m-2">
        <VaSelect v-model="result.Change" value-by="value" class="mr-2" :label="t('menu.changes')"
          :options="changesOptions" clearable />
        <VaDateInput v-model="day" label="Day" />
      </div>
      <ag-grid-vue :rowData="rowData" :columnDefs="columnDefs" :defaultColDef="defaultColDef" :gridOptions="gridOptions"
        animateRows="true" class="ag-theme-material h-full" @gridReady="onGridReady"
        @cellValueChanged="onCellValueChanged"></ag-grid-vue>
      <!-- <VaModal v-model="showModal" ok-text="Apply">
        <h3 class="va-h3"> @rowClicked="onRowClicked"
          Title
        </h3>
        <p>
          Classic modal overlay which represents a dialog box or other interactive
          component, such as a dismissible alert, sub-window, etc.
        </p>
      </VaModal> -->
    </main>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch, onBeforeUnmount } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { format, parse } from 'date-fns';
import 'vuestic-ui/dist/vuestic-ui.css';

const { t, locale } = useI18n();
const rowData = ref([]);
const gridApi = ref(null);
const lastEnteredValues = ref({});
const changesOptions = ref([]);
const day = ref(new Date());
const showModal = ref(null);
const dateFormat = 'yyyy-MM-dd';
const selectedRow = ref(null);
const editingTimeout = ref(null);
const userId = store.state.user.id;
const oldTableData = ref([])
const result = reactive({
  Change: "1",
});

const columnDefs = ref([
  {
    headerName: t('table.headerRow'),
    valueGetter: "node.rowIndex + 1",
    width: 80,
    headerClass: 'header-center',
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
    field: computed(() => locale.value === 'ru' ? 'PNameRus' : 'PName'),
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
]);

const fetchGraphics = async () => {
  try {
    const responseChanges = await axios.get('/changes');
    changesOptions.value = responseChanges.data.map(change => ({
      value: change.Change,
      text: change.Change,
    }));
  } catch (error) {
    console.error('Error fetching graphics data:', error);
  }
};

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
  columnDefs: columnDefs.value,
  getRowClass,
  headerHeight: 43,
};


const fetchData = async () => {
  try {
    const currentChange = result.Change;
    const currentTime = format(day.value, dateFormat);

    const [paramsResponse, valuesResponse] = await axios.all([
      axios.get(`/get-params-for-user/${store.state.user.structure_id}/${currentChange}/${currentTime}`),
      axios.get(`/vparams/${store.state.user.structure_id}`)
    ]);

    const firstIds = paramsResponse.data.map((item) => `${item.ParametersID}_${item.GTid}`)
    const secondIds = oldTableData.value.map((item) => `${item.ParametersID}_${item.GTid}`)

    const arrayOne = new Set(firstIds)
    const arrayTwo = new Set(secondIds)

    const first = arrayOne.difference(arrayTwo);
    const second = arrayTwo.difference(arrayOne);

    const diffs = [...first].concat([...second])

    const newItemsGrid = diffs.map((difference) => {
      return paramsResponse.data.find(item => {
        return difference == `${item.ParametersID}_${item.GTid}`
      })
    })

    const values = valuesResponse.data;

    newItemsGrid.forEach((parametr, index) => {
      const select = values.find((val) => val.TimeID == parametr.GTid && val.ParametersID == parametr.ParametersID);
      if (select) {
        newItemsGrid[index] = { ...parametr, ...select };
      }
    });



    gridApi.value.applyTransaction({
      add: newItemsGrid,
      addIndex: 0,
    });



    oldTableData.value = paramsResponse.data
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
      if (gridApi.value) {
        gridApi.value.ensureIndexVisible(rowIndex, 'bottom');
      }
    } catch (error) {
      console.error('Error saving data', error);
    }
  }
};
console.log(store.state.user.id);

const onRowClicked = (event) => {
  selectedRow.value = event.data;
  showModal.value = true;
};

const saveDataToServer = async (data) => {
  try {
    const response = await axios.post('/vparams', {...data,userId});
    removeFocusFromGrid();
    // focusOnMinColumn();
    fetchData()
    return response;
  } catch (error) {
    console.error('Error saving data', error);
  }
};
const removeFocusFromGrid = () => {
  if (gridApi.value) {
    gridApi.value.clearFocusedCell(); // Clear focus from all cells
  }
};
const focusOnMinColumn = () => {
  if (gridApi.value) {
    const allNodes = gridApi.value.getDisplayedRowAtIndex(0); // Get the first row node
    if (allNodes) {
      const cell = gridApi.value.getCellRendererInstances({ rowNodes: [allNodes] })[0]; // Get the first cell renderer instance
      gridApi.value.setFocusedCell(allNodes.rowIndex, 'Min'); // Set focus to the 'Min' column in the first row
    }
  }
};

const closeEditorIfOpen = () => {
  if (isEditingCell.value && gridApi.value) {
    gridApi.value.stopEditing(); // Close the cell editor
    isEditingCell.value = false; // Reset the flag
    fetchData()

  }
};
// const onGridReady = (params) => {
//   gridApi.value = params.api;
//   params.api.addEventListener('cellFocused', handleCellFocus);
//   params.api.addEventListener('cellBlurred', handleCellBlur);
// };
let dataIntervalId = null;
let graphicsIntervalId = null;
let isEditingCell = ref(false);

const handleCellFocus = (event) => {
  const { column } = event;
  if (column) {
    const field = column.getColId();
    if (field === 'Value' || field === 'Comment') {
      stopIntervals();
      isEditingCell.value = true;
      // Start a timer to close the editor after 30 seconds
      if (editingTimeout.value) {
        clearTimeout(editingTimeout.value);
      }
      editingTimeout.value = setTimeout(closeEditorIfOpen, 30000);
    } else {
      startIntervals();
    }
  }
};

const handleCellBlur = (event) => {
  const { column } = event;
  if (column) {
    const field = column.getColId();
    if (field === 'Value' || field === 'Comment') {
      stopIntervals();
      isEditingCell.value = false;
      // Clear the timer if cell loses focus before 30 seconds
      if (editingTimeout.value) {
        clearTimeout(editingTimeout.value);
        editingTimeout.value = null;
      }
    }
  }
};

const startIntervals = () => {
  if (!dataIntervalId) {
    dataIntervalId = setInterval(fetchData, 5000);
  }
  if (!graphicsIntervalId) {
    graphicsIntervalId = setInterval(fetchGraphics, 5000);
  }
};

const stopIntervals = () => {
  if (dataIntervalId) {
    clearInterval(dataIntervalId);
    dataIntervalId = null;
  }
  if (graphicsIntervalId) {
    clearInterval(graphicsIntervalId);
    graphicsIntervalId = null;
  }
};
const onGridReady = (params) => {
  gridApi.value = params.api;
  params.api.addEventListener('cellFocused', handleCellFocus);
  params.api.addEventListener('cellBlurred', handleCellBlur);
  startIntervals(); // Start intervals when the grid is ready
}
watch([() => result.Change, () => day.value], fetchData);


onMounted(() => {
  fetchData();
  fetchGraphics();
  startIntervals(); // Start intervals when component mounts
});

onBeforeUnmount(() => {
  stopIntervals(); // Stop intervals when component unmounts
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
  background-color: rgb(211, 207, 207);
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
