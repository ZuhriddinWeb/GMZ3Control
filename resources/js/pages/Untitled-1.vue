<template>
  <div class="grid grid-rows-[60px,1fr]">
    <main></main>
    <!-- Add new Elements end -->
    <main class="flex flex-col ">
      <div class="m-2 flex pt-3">
        <VueShiftCalendar v-model="day" :with-slot="true">
          <VaInput v-model="formatted" label="Smena" readonly />
        </VueShiftCalendar>
        <div class="flex justify-end">
          <VaButton @click="toggleFullScreen(gridContainer)"
            class="btn btn-primary items-center justify-center mt-3 ml-3 w-10" icon="fullscreen" />
        </div>
      </div>
      <div ref="gridContainer" class="ag-grid-container h-full w-full">
        <VaTabs v-model="selectedTab" stateful grow tabindex="0">
          <template #tabs>
            <VaTab v-for="page in pagesValue" :key="page.NumberPage" :name="page.NumberPage">
              {{ page.Name }}
            </VaTab>
          </template>
        </VaTabs>

        <div v-for="(groupData, groupId) in groupedByGroupId" :key="groupId" class="mb-6 w-full">
          <AgGridBase :key="groupId + '-' + selectedTab" :rowData="groupData.rowData" :columnDefs="groupData.columnDefs"
            :userRole="userRole" @startInterval="startIntervals" @stopInterval="stopIntervals" :daySelect="day.day"
            :change="day.smena" />
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { toggleFullScreen } from '../helpers/DOM';
import AgGridBase from '../components/AgGridBase.vue';
import { ref, computed, onMounted, watch, onBeforeUnmount, defineProps, toRaw } from 'vue';
import axios from 'axios';
import { format } from 'date-fns';
import { useI18n } from 'vue-i18n';
const { t, locale } = useI18n();
import 'vuestic-ui/dist/vuestic-ui.css';
import { VaInput, VaButton, VaTabs } from 'vuestic-ui';
import { useStore } from 'vuex';
const store = useStore();
import { VueShiftCalendar } from 'vue-shift-calendar';


let dataIntervalId = null;
let graphicsIntervalId = null;

const props = defineProps(['id'])
const groupedByGroupId = ref({})
const lastEnteredValues = ref({});
const changesOptions = ref([]);
const day = ref({
  smena: null,
  day: null
});

const formatted = computed(() => {
  return day.value.day ? `${day.value.day} - ${day.value.smena}` : ''
})
const gridContainer = ref(null);
const selectedTab = ref(1);
const pagesValue = ref([]);
const userRole = ref({});

watch(selectedTab, async (newTab) => {
  await getPages(newTab)
})


const fetchGraphics = async () => {
  try {
    const responseChanges = await axios.get('/changes');
    const responsePages = await axios.get(`/pages-select/${ props.id }`);
    pagesValue.value = responsePages.data

    const responseParams = await axios.get(`/paramWithId/${ props.id }`);
    changesOptions.value = responseChanges.data.map(change => ({
      value: change.Change,
      text: change.Change,
    }));
  } catch (error) {
    console.error('Error fetching graphics data:', error);
  }
};

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
      headerClass: "header-center",
      cellStyle: { textAlign: "center", fontWeight: "bold" },
    },
  ];

  // 🟢 Faqat ushbu groupData uchun unikal parametrlar
  const uniqueParameters = [
    ...new Set(groupData.map(row => (locale.value === 'ru' ? row.PNameRus : row.PName)))
  ];


  const parameterColumns = uniqueParameters.map(param => ({
    headerName: param,
    field: param,
    editable: true,
    flex: 1,
    headerClass: "header-center",
    cellStyle: { textAlign: "center", fontSize: "15px" },
    cellClassRules: {
      'cell-green': (params) =>
        params.data?.[param] === lastEnteredValues.value?.[params.data.id]?.[param] &&
        params.data?.[`WithFormula${param}`] !== "1",

      'cell-yellow': (params) =>
        params.data?.[param] !== lastEnteredValues.value?.[params.data.id]?.[param] &&
        lastEnteredValues.value?.[params.data.id]?.[param] === undefined &&
        params.data?.[`WithFormula${param}`] !== "1",

      'cell-pink': (params) =>
        params.data?.[`WithFormula${param}`] === "1",
    }
  }));
  return [...baseColumns, ...parameterColumns]
}

const tableData = ref([]);

const getPivotedRowDataForGroup = (groupData) => {
  console.log(groupData);

  if (!Array.isArray(groupData) || groupData.length === 0) return [];

  const times = [...new Set(groupData.map(r => r.GTName))];
  const parameters = [...new Set(groupData.map(r =>
    (locale.value === 'ru' ? r.PNameRus : r.PName)?.trim()
  ))];

  return times.map(time => {
    const originalRow = groupData.find(r => r.GTName === time);

    const row = {
      time,
      id: String(originalRow?.id || time)
    };

    parameters.forEach(param => {
      const cleanParam = param?.trim();
      const match = groupData.find(r =>
        r.GTName === time && (r.PName)?.trim() === cleanParam
      );

      if (match) {
        row[cleanParam] = match?.Value ?? '';
        row[`WithFormula${cleanParam}`] = match.WithFormula ?? '0';
        row[`meta_${cleanParam}`] = {
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
          id: match.id
        };
      } else {
        row[cleanParam] = '';
        row[`WithFormula${cleanParam}`] = '0';
      }
    });
    console.log(row, 'row');

    return row;
  });
};






async function getTableData(currentDay, tabValue) {
  const { data } = await axios.get(`/get-params-for-user/${props.id}/${currentDay.smena}/${currentDay.day}/${tabValue}`)
  tableData.value = data


  const groups = {};
  data.forEach(item => {
    const groupId = item.GroupID;
    if (groups[groupId]) groups[groupId].push(item)
    else groups[groupId] = [item]
  })

  for (const key in groups) {
    groupedByGroupId.value[key] = {
      columnDefs: getColumnDefsForGroup(groups[key]),
      rowData: [],
    }
  }


  // console.log(groupedByGroupId.value);

}



async function getMainParametrs() {
  const selectedDay = day.value.day;
  const selectedSmena = day.value.smena;

  const response = await axios.all([
    axios.get(`/vparams-value/${store.state.user.structure_id}/${selectedDay}/${selectedSmena}`)
  ]);

  return response;

}



async function getPages() {
  const [paramsResponse] = await getMainParametrs();
  const params = Array.isArray(paramsResponse.data) ? paramsResponse.data : [];
  const values = Array.isArray(tableData.value) ? tableData.value : [];

  const merged = params.map(param => {
    const match = values.find(val =>
      val.TimeStr === param.GTName &&
      val.ParametersID === param.ParametersID
    );

    const groupId = param.GroupID || match?.GroupID;

    const { id: matchId, ...restMatch } = match || {};

    return {
      ...param,
      ...restMatch,
      GroupID: groupId
    };
  });
  console.log(groupedByGroupId, 'groupedByGroupId')
  const groups = {};
  merged.forEach(item => {
    const groupId = item.GroupID;
    if (groupId !== null && groupId !== undefined) {
      if (groups[groupId]) groups[groupId].push(item);
      else groups[groupId] = [item];
    }
  });


  for (const key in groups) {
    if (!groupedByGroupId.value[key]) continue;
    groupedByGroupId.value[key].rowData = getPivotedRowDataForGroup(groups[key]);
  }

}






const startIntervals = () => {
  const timer = 15000

  if (!dataIntervalId) {
    dataIntervalId = setInterval(() => getPages(selectedTab.value), timer);
  }
  if (!graphicsIntervalId) {
    graphicsIntervalId = setInterval(fetchGraphics, timer);
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

  await getTableData(day.value, selectedTab.value)
  await getPages();
  // await getPages(); // sahifa ochilganda hamma guruhlar yuklanadi
  fetchGraphics();
  startIntervals();
});


onBeforeUnmount(() => stopIntervals());
</script>