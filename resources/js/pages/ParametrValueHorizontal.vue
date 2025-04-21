<template>
  <div class="grid grid-rows-[55px,1fr]">
    <!-- Add new Elements start -->

    <main>

    </main>
    <!-- Add new Elements end -->
    <main class="flex flex-col ">
      <div class="m-2 flex pt-3">
        <VueShiftCalendar v-model="day" :with-slot="true">
          <VaInput v-model="formatted" label="Smena" readonly />
        </VueShiftCalendar>
        <div class="flex justify-end">
          <VaButton @click="exportToPDF" class="btn btn-primary items-center justify-center mt-3 ml-3 w-10"
            icon="file_download">
            Export PDF
          </VaButton>
          <VaButton @click="toggleFullScreen" class="btn btn-primary items-center justify-center mt-3 ml-3 w-10"
            icon="fullscreen" />

          <VaButton @click="goToRoute" class="btn btn-primary items-center justify-center mt-3 ml-3" icon="grade">
          </VaButton>
        </div>
      </div>
      <div ref="gridContainer" class="ag-grid-container h-full w-full">
        <VaTabs v-model="selectedTab" stateful grow @keydown="handleTabKey" tabindex="0">
          <template #tabs>
            <VaTab v-for="page in pagesValue" :key="page.NumberPage" :name="page.NumberPage">
              {{ page.Name }}
            </VaTab>
          </template>
        </VaTabs>

        <div ref="pdfTarget" class="ag-grid-container h-full w-full print-mode">
          <div v-for="(groupData, groupId) in groupedByGroupId" :key="`group-${groupId}`" class="mb-6 w-full">
            <h3 class="font-bold text-lg mb-2">Jadval: {{ groupData[0].GroupName }}</h3>
            <ag-grid-vue :key="`grid-${selectedTab}-${groupId}-${day.day}-${day.smena}`"
              class="ag-theme-material w-full" style="width: 100%" :columnDefs="getColumnDefsForGroup(groupData)"
              :rowData="getPivotedRowDataForGroup(groupData)"
              :defaultColDef="{ sortable: true, filter: true, resizable: true, flex: 1 }" :gridOptions="{
                domLayout: 'autoHeight', headerHeight: 50,
                rowHeight: 35,
              }" @cellValueChanged="onCellValueChanged" @gridReady="params => onGridReady(params, groupId)" />

          </div>

        </div>




      </div>

      <!-- <EditValue v-if="showModalEdit" :showModalEdit="showModalEdit" :resultEdit="resultEdit" @update="handleUpdate" /> -->
    </main>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch, onBeforeUnmount, defineProps, toRaw } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { format, parse } from 'date-fns';
import 'vuestic-ui/dist/vuestic-ui.css';
// import EditValue from '../components/ParameterValueComponent/EditValue.vue';
import { useRouter } from 'vue-router';
import { useForm, useToast, VaValue, VaInput, VaButton, VaForm, VaIcon, VaTabs } from 'vuestic-ui';
// import store from '../store';
import { useStore } from 'vuex';
import html2pdf from 'html2pdf.js';
const store = useStore();

import { VueShiftCalendar } from 'vue-shift-calendar';
const props = defineProps({
  id: 'id'
})
const router = useRouter();
const gridApiMap = ref({});
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
const showModalEdit = ref(null);
const gridContainer = ref(null);
const editingTimeout = ref(null);
const userId = store.state.user.id;
const structureID = store.state.user.structure_id;
const lastEditedGroupId = ref(null);
const lastViewedGroupId = ref(null);
const pdfTarget = ref(null); // bu qatorni script ichiga qo‘shing
const selectedTab = ref("1");
const ParamOptions = ref([]);
const pagesValue = ref([]);
const userRole = ref({});
let previousScrollTop = 0;
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
const exportToPDF = () => {
  const element = gridContainer.value;

  const options = {
    margin: 0.5,
    filename: `jadval-${day.value.day}-smena${day.value.smena}.pdf`,
    image: { type: 'jpeg', quality: 1 },
    html2canvas: {
      scale: 3, // yuqori aniqlik
      useCORS: true,
      scrollY: -window.scrollY,
      windowWidth: element.scrollWidth, // to'liq kenglikni o'lchaydi
      windowHeight: element.scrollHeight // to'liq balandlikni o'lchaydi
    },
    jsPDF: {
      unit: 'px',
      format: [element.scrollWidth, element.scrollHeight],
      orientation: 'landscape'
    }
  };

  html2pdf().set(options).from(element).save();
};



watch(
  () => store.state.user.roles,
  (roles) => {
    if (Array.isArray(roles) && roles.length > 14) {
      userRole.value = roles[14];
    }
  },
  { immediate: true } // sahifa yuklanganda darhol tekshiradi
);
watch(selectedTab, async (newTab) => {
  localStorage.setItem('selectedTab', newTab);

  const groupIds = getGroupIdsByTab(newTab);

  if (groupIds.length > 0) {
    for (const groupId of groupIds) {
      await getPages(newTab, groupId);
    }
  } else {
    await getPages(newTab); // fallback agar group topilmasa
  }
});

const hasPermission = (perm) => userRole.value?.pivot?.[perm] === "1";

const canCreate = computed(() => hasPermission("create"));
const canView = computed(() => hasPermission("view"));
// ✅ Extract unique parameter names from your rowData


const columnDefs = computed(() => {
  const baseColumns = [
    {
      headerName: t("table.headerRow"),
      valueGetter: "node.rowIndex + 1",
      width: 60,
      pinned: "left",
      headerClass: "header-center",
    },
    {
      headerName: t('table.startingTime'),
      field: 'STime',
      width: 100,

      pinned: "left",
      headerClass: "header-center",
      cellStyle: { textAlign: "center", fontWeight: "bold" },
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
        'cell-green': (params) =>
          params.data && params.data.Value === lastEnteredValues.value?.[params.data.id],

        'cell-yellow': (params) =>
          params.data &&
          params.data.Value !== lastEnteredValues.value?.[params.data.id] &&
          lastEnteredValues.value?.[params.data.id] === undefined &&
          params.data.WithFormula !== "1",

        'cell-pink': (params) =>
          params.data && params.data.WithFormula === "1",
      },
      cellStyle: () => ({
        'font-size': '16px',
        'text-align': 'center',
      }),
      headerClass: 'header-center',
    }
  ];

  const uniqueParameters = [...new Set(rowData.value.map(row => locale.value === 'ru' ? row.PNameRus : row.PName))];

  const parameterColumns = uniqueParameters.map(param => ({
    headerName: param,
    field: param,
    editable: (params) =>
      canCreate.value && params.data?.[`WithFormula${param}`] !== "1",
    cellEditor: "agNumberCellEditor",
    flex: 1,
    headerClass: "header-center",
    cellStyle: { textAlign: "center", fontSize: "15px" },
    cellClassRules: {
      'cell-green': (params) =>
        params.data && params.value === lastEnteredValues.value?.[params.data.id]?.[param] &&
        params.data?.[`WithFormula${param}`] !== "1",

      'cell-yellow': (params) =>
        params.data &&
        params.value !== lastEnteredValues.value?.[params.data.id]?.[param] &&
        lastEnteredValues.value?.[params.data.id]?.[param] === undefined &&
        params.data?.[`WithFormula${param}`] !== "1",

      'cell-pink': (params) =>
        params.data?.[`WithFormula${param}`] === "1",
    }
  }));

  return [...baseColumns, ...parameterColumns];
});

const getColumnDefsForGroup = (groupData) => {
  const baseColumns = [
    {
      headerName: t("table.headerRow"),
      valueGetter: "node.rowIndex + 1",
      width: 60,
      pinned: "left",
      headerClass: "header-center",
    },
    {
      headerName: t("table.startingTime"),
      field: "time",
      width: 100,
      pinned: "left",
      valueFormatter: (params) => format(new Date(`1970-01-01T${params.value}`), 'HH:mm'),
      headerClass: "header-center",
      cellStyle: { textAlign: "center", fontWeight: "bold",backgroundColor: "#f0f8ff" },
    },
  ];

  // 🟢 Faqat ushbu groupData uchun unikal parametrlar
  const uniqueParameters = [
    ...new Set(groupData.map(row => (locale.value === 'ru' ? row.PNameRus : row.PName)))
  ];

  const parameterColumns = uniqueParameters.map(param => ({
    headerName: param,
    field: param,
    editable: (params) =>
      canCreate.value && params.data?.[`WithFormula${param}`] !== "1",
    cellEditor: "agNumberCellEditor",
    flex: 1,
    headerClass: "header-center",
    cellStyle: { textAlign: "center", fontSize: "15px" },
    cellClassRules: {
      'cell-green': (params) =>
        params.data && params.value === lastEnteredValues.value?.[params.data.id]?.[param] &&
        params.data?.[`WithFormula${param}`] !== "1",

      'cell-yellow': (params) =>
        params.data &&
        params.value !== lastEnteredValues.value?.[params.data.id]?.[param] &&
        lastEnteredValues.value?.[params.data.id]?.[param] === undefined &&
        params.data?.[`WithFormula${param}`] !== "1",

      'cell-pink': (params) =>
        params.data?.[`WithFormula${param}`] === "1",
    }
  }));

  return [...baseColumns, ...parameterColumns];
};


// Ma'lumotlarni har bir guruhga mos ravishda qaytarish
const getPivotedRowDataForGroup = (groupData) => {
  const times = [...new Set(groupData.map(r => r.GTName))];
  const parameters = [...new Set(groupData.map(r => locale.value === 'ru' ? r.PNameRus : r.PName))];

  return times.map(time => {
    const row = { time };
    parameters.forEach(param => {
      const match = groupData.find(r =>
        r.GTName === time &&
        (locale.value === 'ru' ? r.PNameRus : r.PName) === param
      );
      if (match) {
        row[param] = match.Value;
        row[`WithFormula${param}`] = match.WithFormula;

        // 🔥 Save metadata like in the old version
        row[`meta_${param}`] = {
          ParametersID: match.ParametersID,
          GTName: match.GTName,
          TimeID: match.GTid,
          GraphicsTimesID: match.GTid,
          GrapicsID: match.GrapicsID,
          PageId: match.PageId,
          OrderNumber: match.OrderNumber,
          PName: match.PName,
          PNameRus: match.PNameRus,
          SourceID: match.SourceID,
          BlogID: match.BlogsID || store.state.user.structure_id,
          FactoryStructureID: match.FactoryStructureID,
          GroupID: match.GroupID,
          id: match.id, // Existing row ID if available
        };
      }
    });
    return row;
  });
};

const groupedByGroupId = computed(() => {
  const groups = {};
  rowData.value.forEach(item => {
    if (!item.GroupID || !item.id) return; // Har bir satrda id mavjudligini tekshiring
    const groupId = item.GroupID;
    if (!groups[groupId]) groups[groupId] = [];
    groups[groupId].push(item);
  });
  return groups;
});

const getCurrentVisibleGroupIds = () => {
  const groupIds = new Set();
  const currentTab = selectedTab.value;

  rowData.value.forEach(item => {
    if (item.PageId == currentTab && item.GroupID) {
      groupIds.add(item.GroupID);
    }
  });

  return Array.from(groupIds);
};
const getGroupIdsByTab = (tabId) => {
  const groupIds = new Set();
  rowData.value.forEach(item => {
    if (item.PageId == tabId && item.GroupID) {
      groupIds.add(item.GroupID);
    }
  });
  return Array.from(groupIds);
};





// watch(pagesValue, (newPages) => {
//   if (newPages.length > 0) {
//     selectedTab.value = newPages[0].NumberPage; // Birinchi sahifa avtomatik tanlanadi
//   }
// }, { immediate: true }); // Sahifa ochilganda darhol ishlaydi


watch(day, async () => {
  const currentTab = selectedTab.value;
  const visibleGroupIds = getCurrentVisibleGroupIds();

  if (visibleGroupIds.length > 0) {
    for (const groupId of visibleGroupIds) {
      await getPages(currentTab, groupId);
    }
  } else {
    // Fallback: hech narsa ko‘rinmay qolganda barcha jadvalni yuklaymiz
    await getPages(currentTab);
  }
}, { deep: true });








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


async function getPages(newTab, groupId = null) {
  try {
    const selectedDay = day.value.day;
    const selectedSmena = day.value.smena;

    const [paramsResponse, valuesResponse] = await axios.all([
      axios.get(`/get-params-for-user-horizontal/${props.id}/${selectedSmena}/${selectedDay}/${newTab}`),
      axios.get(`/vparams-value/${store.state.user.structure_id}/${selectedDay}/${selectedSmena}`)
    ]);

    const params = Array.isArray(paramsResponse.data) ? paramsResponse.data : [];
    const values = Array.isArray(valuesResponse.data) ? valuesResponse.data : [];

    const merged = params.map(param => {
      const match = values.find(val => val.TimeStr === param.GTName && val.ParametersID === param.ParametersID);
      return match ? { ...param, ...match } : param;
    });

    if (groupId) {
      // 🟢 Faqat bir guruh uchun rowData ni yangilash
      const updatedGroupData = merged.filter(row => row.GroupID === groupId);
      const groupData = groupedByGroupId.value[groupId];
      if (groupData) {
        groupedByGroupId.value[groupId] = updatedGroupData;
        const api = gridApiMap.value[groupId];
        if (api) {
          api.setRowData(getPivotedRowDataForGroup(updatedGroupData)); // 🟢 Faqat shu jadval yangilanadi
        }
      }
    } else {
      // 🔁 Barcha rowData umumiy yangilanadi
      rowData.value = merged;
    }

  } catch (error) {
    console.error("❌ getPages error:", error);
    if (!groupId) rowData.value = [];
  }
}



















const onGridReady = (params, groupId) => {
  gridApiMap.value[groupId] = params.api;
  lastViewedGroupId.value = groupId; // 👈 Oxirgi ko‘rilgan jadval
};





const onCellValueChanged = async (event) => {
  const { data, colDef, newValue, oldValue } = event;
  if (newValue === oldValue) return;

  const time = data.time;
  const parameterName = colDef.field;

  const indexToUpdate = rowData.value.findIndex(row =>
    row.GTName === time &&
    (locale.value === 'ru' ? row.PNameRus : row.PName) === parameterName &&
    row.PageId == selectedTab.value
  );

  if (indexToUpdate === -1) {
    console.warn("Topilmadi:", parameterName, time);
    return;
  }

  const updatedRow = { ...rowData.value[indexToUpdate] };
  updatedRow.Value = newValue;

  const groupId = updatedRow.GroupID;

  lastEditedGroupId.value = groupId;

  const savePayload = {
    ...updatedRow,
    Comment: data[`comment_${parameterName}`] || null,
    userId,
    change: day.value.smena,
    daySelect: day.value.day,
  };

  try {
    await saveDataToServer(savePayload, selectedTab.value, groupId);
  } catch (error) {
    console.error("❌ Saqlashda xatolik:", error);
  }
};


















const saveDataToServer = async (data, tabId, groupId) => {
  const change = day.value.smena;
  const daySelect = day.value.day;

  try {
    const response = await axios.post('/vparams', { ...data, userId, change, daySelect });
    await getPages(tabId, groupId);  // Bu yerda aniq groupId yuboriladi
    return response;
  } catch (error) {
    console.error('Error saving data', error);
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
      const visibleGroupIds = getCurrentVisibleGroupIds();
      const tabId = selectedTab.value;

      visibleGroupIds.forEach(groupId => {
        getPages(tabId, groupId);
      });
    }, 60000); // har 10 soniyada
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



onMounted(async () => {
  const now = new Date();
  const hours = now.getHours();
  const minutes = now.getMinutes();

  if ((hours >= 8 && hours < 20) || (hours === 19 && minutes <= 59)) {
    // 1-smena: 08:00 - 19:59
    day.value.smena = 1;
    day.value.day = format(now, "yyyy-MM-dd");
  } else {
    // 2-smena
    day.value.smena = 2;

    if (hours < 8) {
      // 2-smena, lekin tunda → kechagi kunni olish kerak
      const yesterday = new Date(now);
      yesterday.setDate(now.getDate() - 1);
      day.value.day = format(yesterday, "yyyy-MM-dd");
    } else {
      day.value.day = format(now, "yyyy-MM-dd");
    }
  }
  const savedTab = localStorage.getItem('selectedTab');
  selectedTab.value = savedTab ? savedTab : "1";
  // await getPages(selectedTab.value); // sahifa ochilganda hamma guruhlar yuklanadi
  fetchGraphics();
  startIntervals();
  addKeyboardListeners();
  // if (pagesValue.value.length > 0) {
  //   selectedTab.value = pagesValue.value[0].NumberPage;
  // }
});



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
  background-color: #fff;
  color: black;
}

.cell-yellow {
  background-color: rgb(22 163 74);
  /* yoki sariqroq rang tanlasa bo’ladi */
  color: black;
}

.cell-pink {
  background-color: rgb(234 179 8);
  /* sariq rang */
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
@media print {
  .print-mode {
    height: auto !important;
    overflow: visible !important;
  }
  .ag-root-wrapper {
    height: auto !important;
  }
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