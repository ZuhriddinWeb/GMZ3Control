<template>
  <div class="grid grid-rows-[55px,1fr]">
    <!-- Add new Elements start -->
    <main>
      <div class="flex justify-between">
        <span class="flex w-full"></span>
        <VaButton @click="showModal = true" class="w-14 h-12 mt-1 mr-1" icon="add" />
      </div>
      <VaModal v-model="showModal" ok-text="Saqlash" cancel-text="Bekor qilish" @ok="onSubmit" close-button>
        <h3 class="va-h3" @vue:mounted="fetchParams">
          Parametr grafigini yaratish
        </h3>
        <div>
          <VaForm ref="formRef" class="flex flex-col items-baseline gap-2">
            <div class="grid grid-cols-2 md:grid-cols-2 gap-2 items-end w-full">
              <VaSelect v-model="result.ParametersID" class="mb-1" label="Parametrni tanlang" :options="paramsOptions"
                clearable />
              <VaSelect v-model="result.GrapicsID" class="mb-1" label="Vaqt grafigini tanlang"
                :options="GraphicTimeOptions" clearable />
            </div>
            <div class="grid grid-cols-2 md:grid-cols-2 gap-2 items-end w-full">
              <VaSelect v-model="result.FactoryStructureID" class="mb-1" label="Tuzilmani tanlang"
                :options="structureOptions" clearable />
              <VaSelect v-model="result.BlogID" class="mb-1" label="Uchastkani tanlang"
                :options="BlogsOptions" clearable />
            </div>
            <div class="grid grid-cols-2 md:grid-cols-1 gap-1 items-end w-full">
              <VaSelect v-model="result.SourceID" class="mb-1" label="Ma`lumot manbasini tanlang"
                :options="SourceOptions" clearable />
            </div>
            <div class="grid grid-cols-2 md:grid-cols-2 gap-2 items-end w-full">
              <VaTimeInput clearable clearable-icon="cancel" color="textPrimary" label="Joriy etish vaqti"
                v-model="result.CurrentTime" />
              <VaTimeInput v-model="result.EndingTime" clearable clearable-icon="cancel" color="textPrimary"
                label="Tugash vaqti" />
            </div>
            <VaInput type="number" class="w-full" v-model="result.OrderNumber"
              :rules="[(value) => (value && value.length > 0) || 'To\'ldirish majburiy bo\'lgan maydon']"
              label="Tartib raqami" />
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
import DeleteModal from '../components/ParamsGraphComponent/DeleteModal.vue'
import EditModal from '../components/ParamsGraphComponent/EditModal.vue';

const rowData = ref([]);
const gridApi = ref(null);
const showModal = ref(false);
const paramsOptions = ref([]);
const structureOptions = ref([]);
const GraphicTimeOptions = ref([]);
const BlogsOptions = ref([]);
const SourceOptions = ref([]);

const result = reactive({
  ParametersID: "",
  FactoryStructureID: "",
  GrapicsID: "",
  SourceID: "",
  CurrentTime: "",
  EndingTime: "",
  Min: "",
  Max: "",
  OrderNumber: "",
  BlogID:""
});

function ondeleted(selectedData) {
  gridApi.value.applyTransaction({ remove: [selectedData] })
}

function onupdated(rowNode, data) {
  rowNode.setData(data)
}

provide('ondeleted', ondeleted)
provide('onupdated', onupdated)

const columnDefs = reactive([
  { headerName: "T/r", valueGetter: "node.rowIndex + 1" },
  { headerName: "Parametr nomi", field: "PName", flex: 1 },
  { headerName: "GMZ tuzilmasi", field: "FName", flex: 1 },
  { headerName: "Grafik", field: "GName" },
  
  { headerName: "Joriy etish vaqti", field: "CurrentTime" },
  { headerName: "Tugatish vaqti", field: "EndingTime" },

  {
    cellClass: ['px-0'],
    headerName: "",
    field: "",
    width: 70,
    cellRenderer: EditModal,
  },
  {
    cellClass: ['px-0'],
    headerName: "",
    field: "",
    width: 70,
    cellRenderer: DeleteModal,
  },
]);


const defaultColDef = {
  sortable: true,
  filter: true
};

const fetchData = async () => {
  try {
    const response = await axios.get('/paramsgraph');
    rowData.value = Array.isArray(response.data) ? response.data : response.data.items;
  } catch (error) {
    console.error('Error fetching data:', error);
  }
};
const fetchParams = async () => {
  try {
    const responseGraphics = await axios.get('/param');
    const responseChanges = await axios.get('/structure');
    const responseTimes = await axios.get('/graphics');
    const responseBlogs = await axios.get('/blogs');
    const responseSource = await axios.get('/source');

    paramsOptions.value = responseGraphics.data.map(graphic => ({
      value: graphic.Uuid,
      text: graphic.Name
    }));
    structureOptions.value = responseChanges.data.map(change => ({
      value: change.id,
      text: change.Name
    }));
    GraphicTimeOptions.value = responseTimes.data.map(change => ({
      value: change.id,
      text: change.Name
    }));
    BlogsOptions.value = responseBlogs.data.map(change => ({
      value: change.id,
      text: change.Name
    }));
    SourceOptions.value = responseSource.data.map(change => ({
      value: change.id,
      text: change.Name
    }));
  } catch (error) {
    console.error('Error fetching graphics data:', error);
  }
};
const onSubmit = async () => {
  try {
    const { data } = await axios.post("/paramsgraph", result);
    if (data.status === 200) {
      showModal.value = false;
      result.Login = '';
      result.Password = '';
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
