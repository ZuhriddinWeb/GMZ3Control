<template>
  <div class="grid grid-rows-[55px,1fr]">
    <!-- Add new Elements start -->

    <main>

      <!-- <div class="flex justify-between">
        <span class="flex w-full"></span>
        <VaButton @click="showModal = true" class="w-14 h-12 mt-3 mr-1" icon="add" />
      </div>
      <VaModal v-model="showModal" ok-text="Saqlash" cancel-text="Bekor qilish" @ok="onSubmit" close-button>
        <h3 class="va-h3">
          Qiymatlarni kiritish
        </h3>
        <div>
          <VaForm ref="formRef" class="flex flex-col items-baseline gap-2">
            <div class="grid grid-cols-1 md:grid-cols-1 gap-1 items-end w-full">
              <VaSelect v-model="result.ParametersID" value-by="value" class="mb-1" label="Parametr turini tanlang"
                :options="ParamOptions" clearable />
             
            </div>
            <VaInput class="w-full" v-model="result.Value"
              :rules="[(value) => (value && value.length > 0) || 'To\'ldirish majburiy bo\'lgan maydon']"
              label="Qiymat" />
            
            <VaTextarea class="w-full" v-model="result.Comment" max-length="125" label="Izoh" />
          </VaForm>
        </div>
      </VaModal> -->

      <VaModal v-model="showModalEdit" ok-text="Saqlash" cancel-text="Bekor qilish" @ok="onSubmitEdit(currentRowNode)"
        close-button>
        <h3 class="va-h3">
          Qiymatni tahrirlash
        </h3>
        <div>
          <VaForm ref="formRef" class="flex flex-col items-baseline gap-2">
            <VaInput class="w-full" v-model="resultEdit.Value"
              :rules="[(value) => (value && value.length > 0) || 'To\'ldirish majburiy bo\'lgan maydon']"
              label="Qiymat" />
            <VaTextarea class="w-full" v-model="resultEdit.Comment" max-length="125" label="Izoh" />
          </VaForm>
        </div>
      </VaModal>
    </main>
    <!-- Add new Elements end -->
    <main class="flex flex-col ">
      <div class="m-2 flex">
        <VaDateInput v-model="day" class="mr-2" label="Day" />
        <VaSelect v-model="result.Change" value-by="value" :label="t('menu.changes')" :options="changesOptions"
          clearable />

        <VaButton @click="goToRoute" class="btn btn-primary items-center justify-center mt-3 ml-3" icon="grade">
        </VaButton>
      </div>
      <ag-grid-vue :rowData="rowData" :columnDefs="columnDefs" :defaultColDef="defaultColDef" :gridOptions="gridOptions"
        animateRows="true" class="ag-theme-material h-full" @gridReady="onGridReady"
        @cellValueChanged="onCellValueChanged" @cellDoubleClicked="onCellDoubleClicked"></ag-grid-vue>

      <EditValue v-if="showModalEdit" :showModalEdit="showModalEdit" :resultEdit="resultEdit" @update="handleUpdate" />
    </main>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch, onBeforeUnmount } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { format, parse } from 'date-fns';
import 'vuestic-ui/dist/vuestic-ui.css';
import { Value } from 'sass';
import EditValue from '../components/ParameterValueComponent/EditValue.vue';
import { useRouter } from 'vue-router';
const router = useRouter();

const { t, locale } = useI18n();
const rowData = ref([]);
const gridApi = ref(null);
const lastEnteredValues = ref({});
const changesOptions = ref([]);
const day = ref(new Date());
const showModal = ref(null);
const showModalEdit = ref(null);
const currentRowNode = ref(null);

const dateFormat = 'yyyy-MM-dd';
const selectedRow = ref(null);
const editingTimeout = ref(null);
const userId = store.state.user.id;
const structureID = store.state.user.structure_id;

const oldTableData = ref([])
const ParamOptions = ref([]);
const SourceOptions = ref([]);
const TimesOptions = ref([]);
const variableValue = ref(0); // Initialize the variable to 0
const result = reactive({
  ParametersID: "",
  Change: "",
  Name: "",
  ShortName: "",
  Comment: "",
  GTime: "",
  Value: "",
  BlogsID: store.state.user.structure_id,
  SourceID: "",
  userId: store.state.user.id
});
const resultEdit = reactive({
  id: "",
  Comment: "",
  Value: "",
  userId: store.state.user.id
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
    width: 150,
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
    const responseParams = await axios.get(`/paramWithId/${structureID}`);
    changesOptions.value = responseChanges.data.map(change => ({
      value: change.Change,
      text: change.Change,
    }));
    ParamOptions.value = responseParams.data.map(factory => ({
      value: factory.Pid,
      text: factory.PName
    }));
    const responseSources = await axios.get('/source');
    SourceOptions.value = responseSources.data.map(factory => ({
      value: factory.id,
      text: factory.Name
    }));
    const responseTimes = await axios.get('/graphictimes');
    TimesOptions.value = responseTimes.data.map(factory => ({
      value: factory.id,
      text: factory.GName
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
  // onCellDoubleClicked : (params) => {
  //   const { colDef, data } = params; // Get the entire row data
  //   if (colDef.field === 'Value' && data.Value) {
  //     openEditModal(params);
  //   }
  // },
};
// const onCellDoubleClicked = (params) => {
//   const { colDef, data } = params; // Get the entire row data
//   if (colDef.field === 'Value' && data.Value) {
//     openEditModal(params);
//   }
// };
const openEditModal = (params) => {
  console.log(params);

  rowData.value = { ...params.data }; // Store the row data
  currentRowNode.value = params.node; // Store the row node

  // Update resultEdit with current row data
  resultEdit.id = rowData.value.id;
  resultEdit.Value = rowData.value.Value;
  resultEdit.Comment = rowData.value.Comment;

  showModalEdit.value = true; // Open the modal
};
const handleUpdate = (updatedData) => {
  // Process the updated data
  console.log('Updated Data:', updatedData);
};
const getCurrentTimeInMinutes = () => {
  const now = new Date();
  return now.getHours() * 60 + now.getMinutes();
};
const startTime = 8 * 60; // 8:00 in minutes
const endTime = 19 * 60 + 59; // 19:59 in minutes

const determineChange = () => {
  const currentTimeInMinutes = getCurrentTimeInMinutes();
  if (currentTimeInMinutes >= startTime && currentTimeInMinutes <= endTime) {
    return 1;
  } else {
    return 2;
  }
};


const goToRoute = () => {
  // variableValue.value = variableValue.value === 1 ? 0 : 1; 
  router.push('/vparamsget');
};

result.Change = determineChange();
const currentChange = computed(() => determineChange());
// const fetchData = async () => {
//   try {
//     const currentChange = result.Change;
//     const currentTime = format(day.value, dateFormat);

//     const [paramsResponse, valuesResponse] = await axios.all([
//       axios.get(`/get-params-for-user/${store.state.user.structure_id}/${currentChange}/${currentTime}`),
//       // axios.get(`/vparamsuser/${store.state.user.structure_id}/${currentChange}/${currentTime}`)
//       axios.get(`/vparams/${store.state.user.structure_id}`)

//     ]);

//     // Log response to debug
//     // console.log('paramsResponse:', paramsResponse);
//     // console.log('valuesResponse:', valuesResponse);

//     // Ensure paramsResponse.data is an array
//     const paramsData = Array.isArray(paramsResponse.data) ? paramsResponse.data : [];

//     const firstIds = paramsData.map(item => `${item.ParametersID}_${item.GTid}`);
//     const secondIds = oldTableData.value.map(item => `${item.ParametersID}_${item.GTid}`);

//     const arrayOne = new Set(firstIds);
//     const arrayTwo = new Set(secondIds);

//     const first = [...arrayOne].filter(x => !arrayTwo.has(x));
//     const second = [...arrayTwo].filter(x => !arrayOne.has(x));

//     const diffs = [...first, ...second];

//     const newItemsGrid = diffs.map(difference => {
//       return paramsData.find(item => difference === `${item.ParametersID}_${item.GTid}`);
//     }).filter(item => item !== undefined); // Filter out undefined items

//     const values = valuesResponse.data;

//     newItemsGrid.forEach((parametr, index) => {
//       const select = values.find(val => val.TimeID === parametr.GTid && val.ParametersID === parametr.ParametersID);
//       if (select) {
//         newItemsGrid[index] = { ...parametr, ...select };
//       }
//     });

//     gridApi.value.applyTransaction({
//       add: newItemsGrid,
//       addIndex: 0,
//     });

//     oldTableData.value = paramsData;
//   } catch (error) {
//     console.error('Error fetching data:', error);
//   }
// };
const fetchData = async () => {
  
  const currentChange = result.Change;
  const currentTime = format(day.value, dateFormat);
  const currentHour = new Date().getHours();
  const change = (currentHour >= 8 && currentHour < 20) ? 1 : 2;
  try {
    axios.all([
      axios.get(`/get-params-for-user/${store.state.user.structure_id}/${currentChange}/${currentTime}`),
      axios.get(`/vparams-value/${store.state.user.structure_id}/${currentTime}`)
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
      const rowIndex = event.rowIndex;
      if (gridApi.value) {
        gridApi.value.ensureIndexVisible(rowIndex, 'bottom');
      }
    } catch (error) {
      console.error('Error saving data', error);
    }
  }
};

const onRowClicked = (event) => {
  selectedRow.value = event.data;
  showModal.value = true;
};

const saveDataToServer = async (data) => {
  console.log(data);
  try {
    const response = await axios.post('/vparams', { ...data, userId });
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
      editingTimeout.value = setTimeout(closeEditorIfOpen, 60000);
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
    dataIntervalId = setInterval(fetchData, 60000);
  }
  if (!graphicsIntervalId) {
    graphicsIntervalId = setInterval(fetchGraphics, 60000);
  }
};

const onSubmit = async () => {
  try {
    const { data } = await axios.post("/vparams", result);
    if (data.status === 200) {
      showModal.value = false;
      result.ParametersID = "",
        result.Name = '';
      result.ShortName = '';
      result.Comment = '';
      result.Value = '';

      await fetchData();
    } else {
      console.error('Error saving data:', data.message);
    }
  } catch (error) {
    console.error('Error saving data:', error);
  }
};
const onSubmitEdit = async (rowNode) => {
  try {
    const { data } = await axios.post("/vparamsEdit", resultEdit);
    if (data.status === 200) {
      showModal.value = false;

      resultEdit.id = "";
      resultEdit.Comment = '';
      resultEdit.Value = '';
      resultEdit.userId = '';

      rowNode.setData(data.updatedRowData);
      gridOptions.api.refreshCells();
    } else {
      console.error('Error saving data:', data.message);
    }
  } catch (error) {
    console.error('Error saving data:', error);
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
