<template>
  <div class="grid grid-rows-[55px,1fr]">
    <!-- Add new Elements start -->
    <main>
      <div class="flex justify-between">
        <span class="flex w-full"></span>
        <VaButton @click="showModal = true" class="w-14 h-12 mt-1 mr-1" icon="add" />
      </div>
      <VaModal v-model="showModal" ok-text="Saqlash" cancel-text="Bekor qilish" @ok="onSubmit" close-button>
        <h3 class="va-h3">
          Zavod tuzilmasini  yaratish
        </h3>
        <div>
          <VaForm ref="formRef" class="flex flex-col items-baseline gap-1">
            <div class="grid grid-cols-1 md:grid-cols-1 gap-2 items-end w-full">
              <VaSelect v-model="result.StructureID" class="mb-1" label="Tuzilma nomi":options="factoryOptions"
                clearable @change="onSelectChange" />
               
            </div>
            <VaInput class="w-full" v-model="result.Name"
              :rules="[(value) => (value && value.length > 0) || 'To\'ldirish majburiy bo\'lgan maydon']"
              label="Nomlanishi" />
            <VaInput class="w-full" v-model="result.ShortName"
              :rules="[(value) => (value && value.length > 0) || 'To\'ldirish majburiy bo\'lgan maydon']"
              label="Qisqa nomi" />
            <VaTextarea class="w-full" v-model="result.Comment" max-length="125" label="Izoh" />
          </VaForm>
        </div>
      </VaModal>
    </main>
    <!-- Add new Elements end -->
    <main class="flex-grow">
      <ag-grid-vue :rowData="rowData" :columnDefs="columnDefs" :defaultColDef="defaultColDef" animateRows="true"
        class="ag-theme-material h-full" @gridReady="(params) => gridApi = params.api"></ag-grid-vue>
    </main>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, provide } from 'vue';
import axios from 'axios';
import 'vuestic-ui/dist/vuestic-ui.css';
import DeleteBlog from '../components/BlogsComponent/DeleteBlog.vue'
import EditBlog from '../components/BlogsComponent/EditBlog.vue';

const rowData = ref([]);
const gridApi = ref(null);
const showModal = ref(false);
const factoryOptions = ref([]);

const result = reactive({
  StructureID:"",
  Name: "",
  ShortName: "",
  Comment: ""
});

function ondeleted(selectedData){
  gridApi.value.applyTransaction({ remove: [selectedData] })
}

function onupdated(rowNode,data){
  rowNode.setData(data)
}

provide('ondeleted',ondeleted)
provide('onupdated',onupdated)

const columnDefs = reactive([
  { headerName: "T/r", valueGetter: "node.rowIndex + 1" },
  { headerName: "Tuzilma", field: "SName" },
  { headerName: "Nomlanishi", field: "Name", flex: 1 },
  { headerName: "Qisqa nomi", field: "ShortName" },
  {
    cellClass: ['px-0'],
    headerName: "",
    field: "",
    width: 70,
    cellRenderer: EditBlog,
  },
  {
    cellClass: ['px-0'],
    headerName: "",
    field: "",
    width: 70,
    cellRenderer: DeleteBlog,
  },
]);


const defaultColDef = {
  sortable: true,
  filter: true
};

const fetchData = async () => {
  try {
    const response = await axios.get('/blogs');
    rowData.value = Array.isArray(response.data) ? response.data : response.data.items; 
  } catch (error) {
    console.error('Error fetching data:', error);
  }
};
const fetchGraphics = async () => {
  try {
    const responseGraphics = await axios.get('/structure');
    factoryOptions.value = responseGraphics.data.map(factory => ({
      value: factory.id,
      text: factory.Name
    }));
  } catch (error) {
    console.error('Error fetching graphics data:', error);
  }
};
const onSubmit = async () => {
  try {
    const { data } = await axios.post("/blogs", result);
    if (data.status === 200) {
      showModal.value = false;
      result.Name = '';
      result.ShortName = '';
      result.Comment = '';
      await fetchData();
    } else {
      console.error('Error saving data:', data.message);
    }
  } catch (error) {
    console.error('Error saving data:', error);
  }
};
onMounted(() => {
  fetchData()
  fetchGraphics()
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
