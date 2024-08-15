<template>
  <div class="grid grid-rows-[55px,1fr]">
    <main></main>
    <main class="flex justify-between mt-[20px]">
      <VaTreeView :nodes="nodes" class="w-1/6 cursor-pointer border-b-4"  @update:selected="handleNodeClick" />
      <ag-grid-vue :rowData="rowData" :columnDefs="columnDefs" :defaultColDef="defaultColDef" animateRows="true"
        class="ag-theme-material h-full w-4/6" @gridReady="(params) => gridApi = params.api"></ag-grid-vue>
    </main>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import 'vuestic-ui/dist/vuestic-ui.css';
import ChartModal from '../components/HomeComponent/ChartModal.vue'
const rowData = ref([]);
const gridApi = ref(null);
const nodes = ref([]);

const columnDefs = reactive([
  { headerName: "T/r", valueGetter: "node.rowIndex + 1" },
  { headerName: "Id", field: "ParametersID"},
  { headerName: "Nomlanishi", field: "Name", flex: 1 },
  { headerName: "Qisqa nomi", field: "ShortName",flex: 1 },
  // { headerName: "Izoh", field: "Comment", flex: 1 },
  {
    cellClass: ['px-0'],
    headerName: "",
    field: "",
    width: 70,
    cellRenderer: ChartModal,
  },
]);

const defaultColDef = {
  sortable: true,
  filter: true
};

const fetchData = async () => {
  try {
    const response = await axios.get('/tree');
    nodes.value = response.data;
    // console.log('Fetched nodes:', nodes.value); // Debug log
  } catch (error) {
    console.error('Error fetching data:', error);
  }
};

const handleNodeClick = async (selectedNodes) => {
  try {
    const response = await axios.get(`/paramsgraph/${selectedNodes.id }`);
    rowData.value = Array.isArray(response.data) ? response.data : response.data.items; 
    // console.log("Node clicked response:", response.data);
  } catch (error) {
    console.error("There was an error processing the node click!", error);
  }
};

onMounted(() => {
  fetchData();
});
</script>
