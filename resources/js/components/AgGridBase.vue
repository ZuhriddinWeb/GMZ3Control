<template>
    <div>
        <!-- <h3 class="font-bold text-lg mb-2">Jadval: {{ props.rowData[0]?.GroupName }}</h3> -->
        <ag-grid-vue class="ag-theme-material w-full" style="width: 100%"
            :columnDefs="props.columnDefs" :rowData="props.rowData"
            :defaultColDef="{ sortable: true, filter: true, resizable: true, flex: 1 }"
            @cellEditingStopped="emit('startInterval')" @cellEditingStarted="emit('stopInterval')"
            @cellValueChanged="onCellValueChanged" @gridReady="(params) => gridApi = params.api"
            :gridOptions="gridOptions"
            
            />
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
const { t, locale } = useI18n();

const emit = defineEmits(['stopInterval', 'startInterval'])
const props = defineProps(['rowData', 'userRole', 'columnDefs','daySelect',
  'change'])
const gridApi = ref(null);

const hasPermission = (perm) => props.userRole?.pivot?.[perm] === "1";

const canCreate = computed(() => hasPermission("create"));
const canView = computed(() => hasPermission("view"));

// const getPivotedRowDataForGroup = (groupData) => {
//     const times = [...new Set(groupData.map(r => r.GTName))];
//     const parameters = [...new Set(groupData.map(r => locale.value === 'ru' ? r.PNameRus : r.PName))];

//     return times.map(time => {
//         const row = { time };
//         parameters.forEach(param => {
//             const match = groupData.find(r =>
//                 r.GTName === time &&
//                 (locale.value === 'ru' ? r.PNameRus : r.PName) === param
//             );
//             if (match) {
//                 row[param] = match.Value;
//                 row[`WithFormula${param}`] = match.WithFormula;

//                 // 🔥 Save metadata like in the old version
//                 row[`meta_${param}`] = {
//                     ParametersID: match.ParametersID,
//                     GTName: match.GTName,
//                     TimeID: match.GTid,
//                     GraphicsTimesID: match.GTid,
//                     GrapicsID: match.GrapicsID,
//                     PageId: match.PageId,
//                     OrderNumber: match.OrderNumber,
//                     PName: match.PName,
//                     PNameRus: match.PNameRus,
//                     SourceID: match.SourceID,
//                     BlogID: match.BlogsID || store.state.user.structure_id,
//                     FactoryStructureID: match.FactoryStructureID,
//                     GroupID: match.GroupID,
//                     id: match.id, // Existing row ID if available
//                 };
//             }
//         });
//         return row;
//     });
// };


const getRowClass = (params) => {
    if (params.data.STime && params.data.ETime && params.data.Value) {
        return 'row-green';
    }
    return '';
};


const onCellValueChanged = async (event) => {
  const { data, colDef, newValue, oldValue } = event;
  if (newValue === oldValue) return; // Qiymat o'zgarmagan bo'lsa

  const time = data.time;
  const parameterName = colDef.field;

  // Pivot row ichidan to'g'ri qatorni topamiz
  const indexToUpdate = data.findIndex(row =>
    row.time === time &&
    // Agar pivot qatorida parameter nomi bo'lmasa, uni qatorning meta maydonidan olishimiz mumkin
    (row[parameterName] !== undefined || (row[`meta_${parameterName}`] && row[`meta_${parameterName}`].PName)) &&
    row.PageId == selectedTab.value
  );

  if (indexToUpdate === -1) {
    console.warn("Qator topilmadi:", parameterName, time);
    return;
  }

  const updatedRow = { ...group.rowData[indexToUpdate] };
  updatedRow.Value = newValue; // Qatorning umumiy qiymatini yangilash (agar shunday logika talab qilinsa)

  // Tayyor payload
  const savePayload = {
    ...updatedRow,
    Comment: data[`comment_${parameterName}`] || null,
    userId,
    change: day.value.smena,
    daySelect: day.value.day,
  };

  try {
    await saveDataToServer(savePayload, selectedTab.value, groupId);

    // Faqat shu guruh uchun gridni yangilash
    const gridApiForGroup = gridApiMap.value[String(groupId)];
    if (gridApiForGroup) {
      // Guruhdagi barcha pivot qatorlarida id maydonlari mavjud bo'lishi kerak
      const rowNodes = group.rowData
        .map(row => row.id)
        .map(id => gridApiForGroup.getRowNode(id))
        .filter(node => node !== null);

      if (rowNodes.length) {
        gridApiForGroup.refreshCells({ rowNodes });
      } else {
        console.warn("Hech qanday amal qilinadigan row node topilmadi uchun guruh:", groupId);
      }
    }
  } catch (error) {
    console.error("❌ Saqlashda xatolik:", error);
  }
};





const saveDataToServer = async (data, tabId, groupId) => {
    const change = day.value.smena;
    const daySelect = day.value.day;

    try {
        const response = await axios.post('/vparams', { ...data, userId, change, daySelect });
        await getPages(tabId);  // Bu yerda aniq groupId yuboriladi
        return response;
    } catch (error) {
        console.error('Error saving data', error);
    }
};

const gridOptions = {
    domLayout: 'autoHeight',
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

// onMounted(() => {
    
// })
</script>