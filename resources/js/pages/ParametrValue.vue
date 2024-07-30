<template>
  <div class="grid grid-rows-[55px,1fr]">
    <main>     
    </main>
    <main class="flex-grow">
      <ag-grid-vue :rowData="rowData" :columnDefs="columnDefs" :defaultColDef="defaultColDef" animateRows="true"
        class="ag-theme-material h-full" @gridReady="(params) => gridApi = params.api"
        @cellValueChanged="onCellValueChanged"></ag-grid-vue>
    </main>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import 'vuestic-ui/dist/vuestic-ui.css';

const rowData = ref([]);
const gridApi = ref(null);

const columnDefs = reactive([
  { headerName: "T/r", valueGetter: "node.rowIndex + 1", width: 80 },
  // { headerName: "Kod", field: store.state.selectedRowId, flex: 1, width: 80 },
  { headerName: "GTid", field: "GTid",hide: true, flex: 1, width: 80 },
  { headerName: "Smena", field: "Change", flex: 1, width: 80 },
  { headerName: "Parametrlar", field: "PName", flex: 1 },
  {
    headerName: "Grafik vaqti", children: [

      { headerName: "Boshlanish vaqti", field: 'STime' },
      { headerName: "Tugash vaqti", field: 'ETime' }
    ]
  },
  {
    headerName: "Oraliq", children: [
      { field: 'Min' },
      { field: 'Max' }
    ]
  },
  { headerName: "Qiymat", field: "Value", flex: 1, editable: true, cellEditor: "agNumberCellEditor", },
  {
    headerName: "Izoh", field: "Comment", flex: 1, editable: true, cellEditor: 'agLargeTextCellEditor', cellEditorPopup: true,
  },
]);

const defaultColDef = {
  sortable: true,
  filter: true,
  resizable: true,
};

const fetchData = async () => {
  try {
    const response = await axios.get(`/vparams/${1}`);
    rowData.value = Array.isArray(response.data) ? response.data : response.data.items;
  } catch (error) {
    console.error('Error fetching data:', error);
  }
};
const onCellValueChanged = async (event) => {
  const { data, colDef, newValue, oldValue } = event;
  if (newValue !== oldValue) {
    try {
      await saveDataToServer(data);
      console.log(data.pvuid);
      updateRowData(data.pvuid);
    } catch (error) {
      console.error('Error saving data', error);
    }
  }
};
const saveDataToServer = async (data) => {
  const response = await axios.post('/vparams', data);
  return response;
};
const updateRowData = (updatedUnit) => {
  console.log(updatedUnit);
  const index = rowData.value.findIndex(row => row.pvuid === updatedUnit.pvuid);
  if (index !== -1) {
    rowData.value[index] = { ...rowData.value[index], id: updatedUnit.id };
    gridApi.value.applyTransaction({ update: [rowData.value[index]] });
  }
};
onMounted(() => {
  fetchData()
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
