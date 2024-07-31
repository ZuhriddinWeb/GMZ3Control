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
  { headerName: "GTid", field: "id", hide: true, flex: 1, width: 80 },
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
  { headerName: "Qiymat", field: "Value", flex: 1, editable: true, cellEditor: "agNumberCellEditor" },
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

    axios.all([
      axios.get(`/get-params-for-user/${1}`),
      axios.get(`/vparams/${1}`)
    ])
      .then(axios.spread(({ data: params }, { data: values }) => {
        params.forEach((parametr, index) => {
          const select = values.find((val) => val.TimeID == parametr.GTid && val.ParametersID == parametr.ParametersID)
          if(select){
            params[index] = {...parametr, ...select}
          }
        });
        rowData.value = params
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
    } catch (error) {
      console.error('Error saving data', error);
    }
  }
};
const saveDataToServer = async (data) => {
  const response = await axios.post('/vparams', data);
  return response;
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
