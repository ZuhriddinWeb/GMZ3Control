<template>
  <div class="grid grid-rows-[55px,1fr]">
    <!-- Add new Elements start -->
    <main>
      <div class="flex justify-between">
        <span class="flex w-full"></span>
        <VaButton @click="showModal = true" class="w-14 h-12 mt-1 mr-1" icon="add" />
      </div>
      <VaModal v-model="showModal" :ok-text="t('modals.apply')" :cancel-text="t('modals.cancel')" @ok="onSubmit"
        close-button>
        <h3 class="va-h3" @vue:mounted="fetchParams">
          {{ t('modals.addParamsGrafTitle') }}
        </h3>
        <VaForm ref="formRef" class="flex flex-col items-baseline gap-2">
          <div class="grid grid-cols-2 md:grid-cols-2 gap-2 items-end w-full">
            <VaSelect v-model="result.ParametersID" value-by="value" class="mb-1" :label="t('menu.params')"
              :options="paramsOptions" clearable />
            <VaSelect v-model="result.GrapicsID" value-by="value" class="mb-1" :label="t('menu.graphictimes')"
              :options="GraphicTimeOptions" clearable />
          </div>
          <div class="grid grid-cols-2 md:grid-cols-2 gap-2 items-end w-full">
            <VaSelect v-model="result.FactoryStructureID" value-by="value" class="mb-1" :label="t('menu.structure')"
              :options="structureOptions" clearable />
            <VaSelect v-model="result.BlogID" value-by="value" class="mb-1" :label="t('menu.blogs')"
              :options="BlogsOptions" clearable />
          </div>
          <div class="grid grid-cols-2 md:grid-cols-1 gap-1 items-end w-full">
            <VaSelect v-model="result.SourceID" value-by="value" class="mb-1" :label="t('menu.sources')"
              :options="SourceOptions" clearable />
          </div>
          <div class="flex gap-5 flex-wrap w-full mt-4">
            <VaDatePicker v-model="result.CurrentTime" stateful highlight-weekend />
            <VaDatePicker v-model="result.EndingTime" stateful highlight-weekend :weekends="getWeekends" />
          </div>
          <!-- <div class="flex justify-between mt-4">
            <div class="w-1/2 mr-48">
              <va-date-picker v-model="result.CurrentTime" :label="t('modals.startTime')" class="w-1/2" />
            </div>
            <div class="w-1/2">
              <va-date-picker v-model="result.EndingTime" :label="t('modals.startTime')" class="w-1/2" />
            </div>
          </div> -->
          <VaInput type="number" class="w-full" v-model="result.OrderNumber"
            :rules="[(value) => (value && value.length > 0) || 'To\'ldirish majburiy bo\'lgan maydon']"
            :label="t('modals.ordernumber')" />
        </VaForm>
      </VaModal>
    </main>
    <!-- Add new Elements end -->
    <main class="flex-grow">
      <ag-grid-vue :rowData="rowData" :columnDefs="columnDefs" :defaultColDef="defaultColDef" animateRows="true"
        class="ag-theme-material h-full" @gridReady="(params) => gridApi = params.api">
      </ag-grid-vue>
    </main>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, provide,computed } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import 'vuestic-ui/dist/vuestic-ui.css';
import EditModal from '../components/ParamsGraphComponent/EditModal.vue';
import DeleteModal from '../components/ParamsGraphComponent/DeleteModal.vue'
import { useForm, useToast, VaValue, VaInput, VaButton, VaForm, VaIcon } from 'vuestic-ui';
const { init } = useToast();
const { t } = useI18n();
import { format  } from 'date-fns';

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
  OrderNumber: "",
  BlogID: ""
});

function ondeleted(selectedData) {
  gridApi.value.applyTransaction({ remove: [selectedData] })
}

function onupdated(rowNode, data) {
  rowNode.setData(data)
}

provide('ondeleted', ondeleted)
provide('onupdated', onupdated)

const columnDefs = computed(() => [
  { headerName: "T/r", valueGetter: "node.rowIndex + 1", width: 80 },
  { headerName: "Parametr nomi", field: "PName", flex: 1 },
  { headerName: "GMZ tuzilmasi", field: "FName", flex: 1 },
  { headerName: "Grafik", field: "GName" },

  {
    headerName: "Joriy etish vaqti",
    field: "CurrentTime",
    valueFormatter: (params) => {
      const [datePart, timePart] = params.value.split(' ');
      const [year, month, day] = datePart.split('-');
      const [hour, minute] = timePart.split(':');
      const date = new Date(year, month - 1, day); 
      return format(date, 'dd/MM/yyyy');
    },
  },
  {
    headerName: "Tugatish vaqti",
    field: "EndingTime",
    valueFormatter: (params) => {
      const [datePart, timePart] = params.value.split(' ');
      const [year, month, day] = datePart.split('-');
      const [hour, minute] = timePart.split(':');
      const date = new Date(year, month - 1, day); 
      return format(date, 'dd/MM/yyyy');
    },
  },

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
      result.ParametersID = "",
        result.FactoryStructureID = "",
        result.GrapicsID = "",
        result.SourceID = "",
        result.CurrentTime = "",
        result.EndingTime = "",
        result.OrderNumber = "",
        result.BlogID = "",
        await fetchData();
      init({ message: t('login.successMessage'), color: 'success' });
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
