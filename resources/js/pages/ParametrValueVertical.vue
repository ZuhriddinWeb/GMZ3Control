<template>
  <div class="grid grid-rows-[55px,1fr]">
    <!-- Add new Elements start -->

    <main>
      <!-- <VaModal v-model="showModalEdit" ok-text="Saqlash" cancel-text="Bekor qilish" @ok="onSubmitEdit(currentRowNode)"
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
      </VaModal> -->
    </main>
    <!-- Add new Elements end -->
    <main class="flex flex-col ">
      <div class="m-2 flex pt-3">
        <VueShiftCalendar v-model="day" :with-slot="true">
          <VaInput v-model="formatted" label="Smena" readonly />
        </VueShiftCalendar>
        <div class="flex justify-end">
          <VaButton @click="exportToExcelReal" class="btn btn-primary items-center justify-center mt-3 ml-3 w-10"
            icon="file_download">
            Export Excel
          </VaButton>
          <VaButton @click="printTable" icon="print" class="btn btn-primary items-center justify-center mt-3 ml-3 w-10">
            Pechat</VaButton>
          <VaButton @click="toggleFullScreen" class="btn btn-primary items-center justify-center mt-3 ml-3 w-10"
            icon="fullscreen" />

          <VaButton @click="goToRoute" class="btn btn-primary items-center justify-center mt-3 ml-3" icon="grade">
          </VaButton>
        </div>
      </div>
      <div ref="gridContainer" class="ag-grid-container h-full">
        <VaTabs v-model="selectedTab" stateful grow @keydown="handleTabKey" tabindex="0">
          <template #tabs>
            <VaTab v-for="page in pagesValue" :key="page.NumberPage" :name="page.NumberPage">
             <span @contextmenu.prevent.stop="openDashboardFor(page)">{{ page.Name }}</span>
            </VaTab>
          </template>
        </VaTabs>
        <ag-grid-vue :rowData="rowData" :columnDefs="columnDefs" :defaultColDef="defaultColDef"
          :gridOptions="gridOptions" animateRows="true" class="ag-theme-material h-full" @gridReady="onGridReady"
          @cellValueChanged="onCellValueChanged"></ag-grid-vue>
      </div>
      <EditValue v-if="showModalEdit" :showModalEdit="showModalEdit" :resultEdit="resultEdit" @update="handleUpdate" />
    </main>
    <DashboardModal v-model="dashboardVisible" :page="dashboardPage" :day="day.day" :smena="day.smena"
      :factory-id="props.id" :structure-id="store.state.user.structure_id" />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch, onBeforeUnmount, defineProps } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { format, parse } from 'date-fns';
import 'vuestic-ui/dist/vuestic-ui.css';
import EditValue from '../components/ParameterValueComponent/EditValue.vue';
import { useRouter } from 'vue-router';
import { useForm, useToast, VaValue, VaInput, VaButton, VaForm, VaIcon, VaTabs } from 'vuestic-ui';
// import store from '../store';
import { useStore } from 'vuex';
const store = useStore();
import * as XLSX from 'xlsx';
import { VueShiftCalendar } from 'vue-shift-calendar';
import DashboardModal from '@/components/DashboardModal.vue' // yo‘lni loyihangizga moslang

const dashboardVisible = ref(false)
const dashboardPage = ref(null)

const openDashboardFor = (page) => {
  dashboardPage.value = page
  dashboardVisible.value = true
}

const props = defineProps({
  id: Number
})
const router = useRouter();

const { t, locale } = useI18n();
const rowData = ref([]);
const gridApi = ref(null);
const lastEnteredValues = ref({});
const changesOptions = ref([]);
const day = ref({
  smena: null,
  day: null
});

const formatted = computed(() => {
  return day.value.day ? `${day.value.day} - ${day.value.smena}` : ''
})
const showModal = ref(null);
const showModalEdit = ref(null);
const currentRowNode = ref(null);
const gridContainer = ref(null);
const dateFormat = 'yyyy-MM-dd';
const selectedRow = ref(null);
const editingTimeout = ref(null);
const userId = store.state.user.id;
const structureID = store.state.user.structure_id;
const selectedTab = ref("1");
const oldTableData = ref([])
const ParamOptions = ref([]);
const pagesValue = ref([]);
const userRole = ref({});

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
watch(
  () => store.state.user.roles,
  (roles) => {
    if (Array.isArray(roles) && roles.length > 14) {
      userRole.value = roles[14];
    }
  },
  { immediate: true } // sahifa yuklanganda darhol tekshiradi
);
const hasPermission = (perm) => userRole.value?.pivot?.[perm] === "1";
const exportToExcelReal = async () => {
  if (!pagesValue.value.length) return;

  const wb = XLSX.utils.book_new();

  for (const page of pagesValue.value) {
    try {
      const selectedDay = day.value.day;
      const selectedSmena = day.value.smena;

      const [paramsResponse, valuesResponse] = await axios.all([
        axios.get(`/get-params-for-user/${props.id}/${selectedSmena}/${selectedDay}/${page.NumberPage}`),
        axios.get(`/vparams-value/${store.state.user.structure_id}/${selectedDay}/${selectedSmena}`)
      ]);

      const params = Array.isArray(paramsResponse.data) ? paramsResponse.data : [];
      const values = Array.isArray(valuesResponse.data) ? valuesResponse.data : [];

      // Parametr va qiymatlarni qo‘shamiz
      params.forEach((param, index) => {
        const val = values.find(v =>
          v.TimeStr === param.GTName && v.ParametersID === param.ParametersID
        );
        if (val) {
          params[index] = { ...param, ...val };
        }
      });

      const data = params.map((row, index) => ({
        '№': index + 1,
        'Smena': row.Change,
        'Tartib raqami': row.OrderNumber,
        'Parametrlar': locale.value === 'ru' ? row.PNameRus : row.PName,
        'Boshlanish soati': row.STime,
        'Tugash soati': row.ETime,
        'Min': row.Min,
        'Max': row.Max,
        'Qiymat': row.Value,
        'Izoh': row.Comment
      }));

      const ws = XLSX.utils.json_to_sheet(data);
      XLSX.utils.book_append_sheet(wb, ws, page.Name.substring(0, 31)); // Sheet nomi 31 belgidan oshmasin
    } catch (error) {
      console.error(`Xatolik sahifa "${page.Name}" uchun:`, error);
    }
  }

  const dayStr = day.value?.day ?? 'unknown';
  const smenaStr = day.value?.smena ?? 'X';
  XLSX.writeFile(wb, `Export-${dayStr}-smena${smenaStr}.xlsx`);
};


const canCreate = computed(() => hasPermission("create"));
const canView = computed(() => hasPermission("view"));
const resultEdit = reactive({
  id: "",
  Comment: "",
  Value: "",
  userId: store.state.user.id
});


const columnDefs = computed(() => {
  const cols = [
    {
      headerName: t('table.headerRow'),
      valueGetter: "node.rowIndex + 1",
      width: 80,
      headerClass: 'header-center',
      editable: false,
      suppressNavigable: true,
      cellClassRules: {
        'cell-green': (params) => params.data && params.data.Value === lastEnteredValues.value?.[params.data.id],
        'cell-yellow': (params) => params.data && params.data.Value !== lastEnteredValues.value?.[params.data.id] && lastEnteredValues.value?.[params.data.id] === undefined && params.data.WithFormula !== "1",
        'cell-pink': (params) => params.data && params.data.WithFormula === "1",
      },
    },
    {
      headerName: t('table.change'),
      field: "Change",
      width: 80,
      headerClass: 'header-center',
      editable: false,
      suppressNavigable: true,
    },
    {
      headerName: t('table.OrderNumber'),
      field: "OrderNumber",
      width: 80,
      headerClass: 'header-center',
      editable: false,
      suppressNavigable: true,
    },
    {
      headerName: t('table.parameters'),
      field: locale.value === 'ru' ? 'PNameRus' : 'PName',
      flex: 1,
      headerClass: 'header-center',
      editable: false,
      suppressNavigable: true,
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
          editable: false,
          suppressNavigable: true,
        },
        {
          headerName: t('table.endingTime'),
          field: 'ETime',
          width: 120,
          valueFormatter: (params) => format(new Date(`1970-01-01T${params.value}`), 'HH:mm'),
          headerClass: 'header-center',
          editable: false,
          suppressNavigable: true,
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
          editable: false,
          suppressNavigable: true,
        },
        {
          headerName: t('table.max'),
          field: 'Max',
          width: 100,
          headerClass: 'header-center',
          editable: false,
          suppressNavigable: true,
        }
      ]
    },
    {
      headerName: t('table.value'),
      field: "Value",
      width: 150,
      editable: (params) => {
        return canCreate.value && params.data.WithFormula !== "1";
      },
      suppressNavigable: false,
      cellEditor: "agNumberCellEditor",
      cellEditorPopup: false,
      cellClassRules: {
        'cell-green': (params) => params.data && params.data.Value === lastEnteredValues.value?.[params.data.id],
        'cell-yellow': (params) => params.data && params.data.Value !== lastEnteredValues.value?.[params.data.id] && lastEnteredValues.value?.[params.data.id] === undefined && params.data.WithFormula !== "1",
        'cell-pink': (params) => params.data && params.data.WithFormula === "1",
      },
      cellStyle: () => ({
        'font-size': '16px',
        'text-align': 'center',
      }),
      headerClass: 'header-center',
    },
    {
      headerName: t('table.comment'),
      field: "Comment",
      flex: 1,
      editable: () => canCreate.value,
      suppressNavigable: false,
      cellEditor: 'agLargeTextCellEditor',
      cellEditorPopup: true,
      headerClass: 'header-center',
    }
  ];

  return cols;
});



// watch(pagesValue, (newPages) => {
//   if (newPages.length > 0) {
//     selectedTab.value = newPages[0].NumberPage; // Birinchi sahifa avtomatik tanlanadi
//   }
// }, { immediate: true }); // Sahifa ochilganda darhol ishlaydi
// watch(day, (newDay) => {
//   if (selectedTab.value) {
//     getPages(selectedTab.value);
//   }
// }, { deep: true });
const handleTabKey = (event) => {
  if (event.key === " ") {
    event.preventDefault(); // Prevent default space behavior like scrolling

    if (event.shiftKey) {
      // Shift + Space: move to the previous tab
      if (parseInt(selectedTab.value) > 1) {
        selectedTab.value = String(parseInt(selectedTab.value) - 1);
      } else {
        selectedTab.value = String(pagesValue.value.length);
      }
    } else {
      // Space: move to the next tab
      if (parseInt(selectedTab.value) < pagesValue.value.length) {
        selectedTab.value = String(parseInt(selectedTab.value) + 1);
      } else {
        selectedTab.value = "1";
      }
    }
  }
};
const handleArrowKeysForGrid = (event) => {
  if (event.key === 'ArrowUp' || event.key === 'ArrowDown' || event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
    event.preventDefault(); // Prevent default behavior

    // Only handle arrow keys within ag-Grid cells
    if (document.activeElement.classList.contains('ag-cell')) {
      gridApi.navigateToNextCell({ key: event.key });
    }
  }
};
const addKeyboardListeners = () => {
  window.addEventListener('keydown', (event) => {
    if (event.key === ' ') {
      handleSpaceKeyForTabs(event); // ✅ Endi bu funksiya mavjud
    }

    if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(event.key)) {
      handleArrowKeysForGrid(event);
    }
  });
};
// const onCellClicked = (params) => {
//   if (params.colDef.field === 'Value') return; // ❌ Modal chaqirilmasin
//   openEditModal(params);
// };
const handleSpaceKeyForTabs = (event) => {
  if (event.key === " ") {
    event.preventDefault(); // Scrollingni to‘xtatish

    if (event.shiftKey) {
      // Shift + Space → oldingi tabga o‘tish
      if (parseInt(selectedTab.value) > 1) {
        selectedTab.value = String(parseInt(selectedTab.value) - 1);
      } else {
        selectedTab.value = String(pagesValue.value.length);
      }
    } else {
      // Faqat Space → keyingi tabga o‘tish
      if (parseInt(selectedTab.value) < pagesValue.value.length) {
        selectedTab.value = String(parseInt(selectedTab.value) + 1);
      } else {
        selectedTab.value = "1";
      }
    }
  }
};

function toggleFullScreen() {
  const element = gridContainer.value;

  if (!document.fullscreenElement) {

    if (element.requestFullscreen) {
      element.requestFullscreen();
    } else if (element.mozRequestFullScreen) {
      element.mozRequestFullScreen();
    } else if (element.webkitRequestFullscreen) {
      element.webkitRequestFullscreen();
    } else if (element.msRequestFullscreen) {
      element.msRequestFullscreen();
    }
  } else {
    if (document.exitFullscreen) {
      document.exitFullscreen();
    } else if (document.mozCancelFullScreen) {
      document.mozCancelFullScreen();
    } else if (document.webkitExitFullscreen) {
      document.webkitExitFullscreen();
    } else if (document.msExitFullscreen) {
      document.msExitFullscreen();
    }
  }
}
const fetchGraphics = async () => {
  try {
    const responseChanges = await axios.get('/changes');
    const responsePages = await axios.get(`/pages-select/${props.id}`);
    pagesValue.value = responsePages.data

    const responseParams = await axios.get(`/paramWithId/${props.id}`);
    changesOptions.value = responseChanges.data.map(change => ({
      value: change.Change,
      text: change.Change,
    }));
    ParamOptions.value = responseParams.data.map(factory => ({
      value: factory.Pid,
      text: factory.PName
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
  suppressScrollOnNewData: true,
  getRowClass,
  headerHeight: 27,
  rowHeight: 30,
  // suppressTabbing: false,
  navigateToNextCell: (params) => {
    const { key } = params.event;

    // Only allow arrow key navigation
    if (key === 'ArrowUp' || key === 'ArrowDown' || key === 'ArrowLeft' || key === 'ArrowRight') {
      return true; // ag-Grid will handle the navigation
    }

    // Prevent other keys from navigating between cells
    return params.previousCellDef;
  }

};

const openEditModal = (params) => {
  // console.log(params);

  if (params.colDef.field === 'Value') {
    return; // ❌ Value ustunida modal ochilmasin
  }
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
  // console.log('Updated Data:', updatedData);
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
  router.push('/vparamsget');
};

result.Change = determineChange();

// 🚀 Hozirgi vaqtga mos keladigan `smena` ni aniqlash
const getCurrentShift = () => {
  const currentHour = new Date().getHours();
  return currentHour >= 8 && currentHour < 20 ? 1 : 2;
};

// 🚀 API orqali ma'lumot olish (endilikda `selectedTab` orqali)
async function getPages(newTab) {
  try {
    const selectedDay = day.value.day;
    const selectedSmena = day.value.smena;

    const [paramsResponse, valuesResponse] = await axios.all([
      axios.get(`/get-params-for-user/${props.id}/${selectedSmena}/${selectedDay}/${newTab}`),
      axios.get(`/vparams-value/${store.state.user.structure_id}/${selectedDay}/${selectedSmena}`)
    ]);

    // ✅ Har doim array bo'lishini kafolatlaymiz
    const params = Array.isArray(paramsResponse.data) ? paramsResponse.data : [];
    const values = Array.isArray(valuesResponse.data) ? valuesResponse.data : [];

    params.forEach((parametr, index) => {
      const select = values.find(
        (val) =>
          val.TimeStr == parametr.GTName &&
          val.ParametersID == parametr.ParametersID
      );
      if (select) {
        params[index] = { ...parametr, ...select };
      }

    });

    // ✅ Saqlashdan oldin ham tekshirib qo'yish mumkin
    rowData.value = Array.isArray(params) ? params : [];

  } catch (error) {
    console.error("Error fetching data:", error);
    rowData.value = []; // ❗ Hatolikda bo'sh array qilib qo'yamiz
  }
}

watch(
  [() => day.value.day, () => day.value.smena, () => selectedTab.value],
  ([newDay, newSmena, newTab], [oldDay, oldSmena, oldTab]) => {
    // Faqat o‘zgarishda chaqiramiz
    if (newDay && newSmena && newTab && (newDay !== oldDay || newSmena !== oldSmena || newTab !== oldTab)) {
      getPages(newTab);
    }
  },
  { immediate: true }
);



const onCellValueChanged = async (event) => {
  const { data, colDef, newValue, oldValue } = event;

  // Check if the value has changed
  if (newValue !== oldValue) {
    try {
      // Save the updated data to the server
      await saveDataToServer(data, selectedTab.value);

      const rowIndex = event.rowIndex;
      const totalRows = gridApi.value.getDisplayedRowCount();

      // Ensure the row is visible (existing behavior)
      if (gridApi.value) {
        gridApi.value.ensureIndexVisible(rowIndex, 'bottom');
      }

      // Move focus to the next row's "Value" or "Comment" column without starting the editor
      if (rowIndex < totalRows - 1) {
        // Stop editing to save the current state
        gridApi.value.stopEditing();

        // Decide whether to focus on "Value" or "Comment" based on which column was edited
        const nextColumnKey = colDef.field === 'Value' ? 'Value' : 'Comment';

        // Set focus on the next row's respective column
        gridApi.value.setFocusedCell(rowIndex + 1, nextColumnKey);
      }
    } catch (error) {
      console.error('Error saving data', error);
    }
  }
};

const saveDataToServer = async (data, tab) => {
  const change = day.value.smena;
  const daySelect = day.value.day;

  try {
    const response = await axios.post('/vparams', { ...data, userId, change, daySelect });
    removeFocusFromGrid();
    await getPages(tab); // ✅ Pass exact tab back
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

const closeEditorIfOpen = () => {
  if (isEditingCell.value && gridApi.value) {
    gridApi.value.stopEditing(); // Close the cell editor
    isEditingCell.value = false; // Reset the flag
    // fetchData()

  }
};

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
    dataIntervalId = setInterval(() => {
      if (selectedTab.value) {
        getPages(selectedTab.value);
      }
    }, 60000);
  }
  if (!graphicsIntervalId) {
    graphicsIntervalId = setInterval(fetchGraphics, 60000);
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
  console.log(params)
  gridApi.value = params.api;
  params.api.addEventListener('cellFocused', handleCellFocus);
  params.api.addEventListener('cellBlurred', handleCellBlur);
  startIntervals(); // Start intervals when the grid is ready
}
// watch([() => result.Change, () => day.value], fetchData);
// watch(selectedTab, (newTab) => {
//   getPages(newTab);
// });

// 🔄 BUNDA pagesValue o‘zgarganda emas, faqat birinchi marta sahifa yuklanganda bajariladi
onMounted(() => {
  const now = new Date();
  const hours = now.getHours();
  const minutes = now.getMinutes();

  if ((hours >= 8 && hours < 20) || (hours === 19 && minutes <= 59)) {
    // 1-smena
    day.value.smena = 1;
    day.value.day = format(now, "yyyy-MM-dd");
  } else {
    // 2-smena
    day.value.smena = 2;
    if (hours < 8) {
      const yesterday = new Date(now);
      yesterday.setDate(now.getDate() - 1);
      day.value.day = format(yesterday, "yyyy-MM-dd");
    } else {
      day.value.day = format(now, "yyyy-MM-dd");
    }
  }

  fetchGraphics().then(() => {
    // Birinchi tabni avtomatik tanlash
    if (pagesValue.value.length > 0) {
      selectedTab.value = pagesValue.value[0].NumberPage;
    }
  });

  startIntervals();
  addKeyboardListeners();
});


// watch(pagesValue, (newPages) => {
//   if (newPages.length > 0) {
//     selectedTab.value = newPages[0].NumberPage;
//   }
// }, { immediate: true });

onBeforeUnmount(() => {
  stopIntervals(); // Stop intervals when component unmounts
});
</script>

<style>
.ag-grid-container {
  width: 100%;
  height: 100%;
}

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
  color: black;
}

.cell-pink {
  background-color: rgb(234 179 8);
  color: black;
}

.cell-yellow {
  background-color: rgb(22 163 74);
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
