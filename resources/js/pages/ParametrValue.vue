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
          Qiymatlarni kiritish
        </h3>
        <div>
          <VaForm ref="formRef" class="flex flex-col items-baseline gap-2">
            <div class="grid grid-cols-3 md:grid-cols-3 gap-2 items-end w-full">
              <VaSelect v-model="result.ParamsTypeID" class="mb-1" label="Parametr turini tanlang" :options="ParamOptions"
                clearable  />
              <VaSelect v-model="result.UnitsID" class="mb-1" label="Manbani tanlang" :options="SourceOptions"
                clearable  />
                <VaSelect v-model="result.GTime" class="mb-1" label="Grafik vaqtini tanlang" :options="TimesOptions"
                clearable  />
            </div>
            <VaInput class="w-full" v-model="result.Name"
              :rules="[(value) => (value && value.length > 0) || 'To\'ldirish majburiy bo\'lgan maydon']"
              label="Qiymat" />
            <!-- <VaInput class="w-full" v-model="result.ShortName"
              :rules="[(value) => (value && value.length > 0) || 'To\'ldirish majburiy bo\'lgan maydon']"
              label="Qisqa nomi" /> -->
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
import DeleteUnitsModal from '../components/UnitsComponent/DeleteUnitsModal.vue'
import EditUnitsModal from '../components/UnitsComponent/EditUnitsModal.vue';

const rowData = ref([]);
const gridApi = ref(null);
const showModal = ref(false);
const ParamOptions = ref([]);
const SourceOptions = ref([]);
const TimesOptions = ref([]);

const result = reactive({
  Name: "",
  ShortName: "",
  Comment: "",
  GTime: ""

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
  { headerName: "Nomlanishi", field: "Name", flex: 1 },
  { headerName: "Qisqa nomi", field: "ShortName" },
  { headerName: "Izoh", field: "Comment", flex: 1 },
  {
    cellClass: ['px-0'],
    headerName: "",
    field: "",
    width: 70,
    cellRenderer: EditUnitsModal,
  },
  {
    cellClass: ['px-0'],
    headerName: "",
    field: "",
    width: 70,
    cellRenderer: DeleteUnitsModal,
  },
]);


const defaultColDef = {
  sortable: true,
  filter: true
};

const fetchData = async () => {
  try {
    const response = await axios.get('/vparams');
    rowData.value = Array.isArray(response.data) ? response.data : response.data.items; 
  } catch (error) {
    console.error('Error fetching data:', error);
  }
};
const fetchGraphics = async () => {
  try {
    const responseParams = await axios.get('/param');
    ParamOptions.value = responseParams.data.map(factory => ({
      value: factory.Uuid,
      text: factory.Name
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
const onSubmit = async () => {
  try {
    const { data } = await axios.post("/units", result);
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
