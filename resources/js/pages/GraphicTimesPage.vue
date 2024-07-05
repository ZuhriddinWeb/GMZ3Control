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
          O'lchov birliklarini kiritish
        </h3>
        <div>
          <VaForm ref="formRef" class="flex flex-col items-baseline gap-2">
            <div class="grid grid-cols-2 md:grid-cols-2 gap-2 items-end w-full">
              <VaSelect v-model="result.GraphicId" class="mb-6" label="Grafikni tanlang":options="graphicsOptions"
                clearable @change="onSelectChange" />
                <VaSelect v-model="result.ChangeId" class="mb-6" label="Semenani tanlang":options="changesOptions"
                clearable @change="onSelectChange" />
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 items-end">
              <VaTimeInput clearable clearable-icon="cancel" color="textPrimary" label="Nomlanishi"
                v-model="result.Name" />
              <VaTimeInput clearable clearable-icon="cancel" color="textPrimary" label="Boshlanish vaqti"
                v-model="result.Name" />
              <VaTimeInput v-model="result.EndTime" clearable clearable-icon="cancel" color="textPrimary"
                label="Tugash vaqti" />
            </div>
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
import EditGraphicTimesModal from '../components/GraphicTimesComponent/EditGraphicTimesModal.vue';
import DeleteGraphicTimesModal from '../components/GraphicTimesComponent/DeleteGraphicTimesModal.vue';

const rowData = ref([]);
const gridApi = ref(null);
const showModal = ref(false);
const graphicsOptions = ref([]);
const changesOptions = ref([]);

const result = reactive({
  GraphicId: "",
  ChangeId: "",
  Name: "",
  StartTime: "",
  EndTime: "",
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
  { headerName: "T/r", valueGetter: "node.rowIndex + 1", width: 120 },
  { headerName: "ID", field: "id", width: 120 },
  { headerName: "Smena", field: "Change", width: 120 },
  { headerName: "Grafik Nomi", field: "GName", flex: 1 },
  { headerName: "Nomlanishi", field: "Name", flex: 1 },
  { headerName: "Boshlanish vaqti", field: "StartTime" },
  { headerName: "Tugash vaqti", field: "EndTime" },
  { headerName: "Izoh", field: "Comment", flex: 1 },
  {
    cellClass: ['px-0'],
    headerName: "",
    field: "",
    width: 70,
    cellRenderer: EditGraphicTimesModal,
  },
  {
    cellClass: ['px-0'],
    headerName: "",
    field: "",
    width: 70,
    cellRenderer: DeleteGraphicTimesModal,
  },
]);


const defaultColDef = {
  sortable: true,
  filter: true
};

const fetchData = async () => {
  try {
    const response = await axios.get('/graphictimes');
    console.log(response);
    rowData.value = Array.isArray(response.data) ? response.data : response.data.items;
  } catch (error) {
    console.error('Error fetching data:', error);
  }
};
const fetchGraphics = async () => {
  try {
    const responseGraphics = await axios.get('/graphics');
    const responseChanges = await axios.get('/changes');
    graphicsOptions.value = responseGraphics.data.map(graphic => ({
      value: graphic.id,
      text: graphic.Name
    }));
    changesOptions.value = responseChanges.data.map(change => ({
      value: change.id,
      text: change.Change
    }));
  } catch (error) {
    console.error('Error fetching graphics data:', error);
  }
};
const onSubmit = async () => {
  try {
    const { data } = await axios.post("/graphictimes", result);
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
const onSelectChange = (value) => {
  console.log('Selected graphic:', value);
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
