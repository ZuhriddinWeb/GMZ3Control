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
        <h3 class="va-h3">
          {{ t('modals.addParamsTitle') }}
        </h3>
        <div>
          <VaForm ref="formRef" class="flex flex-col items-baseline gap-2">
            <VaInput class="w-full" v-model="result.Name"
              :rules="[(value) => (value && value.length > 0) || t('validation.required')]" :label="t('form.name')" />
            <VaInput class="w-full" v-model="result.NameRus"
              :rules="[(value) => (value && value.length > 0) || t('validation.required')]"
              :label="t('form.nameRus')" />
            <VaInput class="w-full" v-model="result.ShortName"
              :rules="[(value) => (value && value.length > 0) || t('validation.required')]"
              :label="t('form.shortName')" />
            <VaInput class="w-full" v-model="result.ShortNameRus"
              :rules="[(value) => (value && value.length > 0) || t('validation.required')]"
              :label="t('form.shortNameRus')" />
            <div class="grid grid-cols-2 md:grid-cols-2 gap-2 items-end w-full">
              <VaInput class="w-full" v-model="result.Min"
                :rules="[(value) => (value && value.length > 0) || t('validation.required')]" :label="t('table.min')" />
              <VaInput class="w-full" v-model="result.Max"
                :rules="[(value) => (value && value.length > 0) || t('validation.required')]" :label="t('table.max')" />
            </div>
            <div class="grid grid-cols-2 md:grid-cols-2 gap-2 items-end w-full">
              <VaSelect v-model="result.ParamsTypeID" value-by="value" class="mb-6" :label="t('menu.paramtypes')"
                :options="paramsOptions" clearable />
              <VaSelect v-model="result.UnitsID" value-by="value" class="mb-6" :label="t('menu.units')"
                :options="unitsOptions" clearable />
            </div>
            <VaTextarea class="w-full" v-model="result.Comment" max-length="125" :label="t('form.comment')" />
          </VaForm>
        </div>
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
import { ref, reactive, onMounted, provide } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import 'vuestic-ui/dist/vuestic-ui.css';
import DeleteParam from '../components/ParamsPageComponent/DeleteParam.vue';
import EditParam from '../components/ParamsPageComponent/EditParam.vue'
import { useForm, useToast, VaValue, VaInput, VaButton, VaForm, VaIcon } from 'vuestic-ui';
const { init } = useToast();
const { t } = useI18n();

const rowData = ref([]);
const gridApi = ref(null);
const showModal = ref(false);
const paramsOptions = ref([]);
const unitsOptions = ref([]);

const result = reactive({
  Name: "",
  NameRus: "",
  ShortNameRus: "",
  ShortName: "",
  ParamsTypeID: "",
  UnitsID: "",
  Min: "",
  Max: "",
  Comment: ""
});

function ondeleted(selectedData) {
  gridApi.value.applyTransaction({ remove: [selectedData] })
}

function onupdated(rowNode, data) {
  rowNode.setData(data)
}
provide('ondeleted', ondeleted);
provide('onupdated', onupdated);

const columnDefs = reactive([
  { headerName: t("table.headerRow"), valueGetter: "node.rowIndex + 1", width: 80 },
  { headerName: t("table.id"), field: "Uuid", hide: true, flex: 1 },
  { headerName: t("table.name"), field: "Name", flex: 1 },
  { headerName: t("table.shortName"), field: "ShortName" },
  { headerName: t("menu.paramtypes"), hide: true, field: "PName" },
  { headerName: t("menu.units"), field: "UName" },
  { headerName: t("table.min"), field: "Min" },
  { headerName: t("table.max"), field: "Max" },
  { headerName: t("table.comment"), field: "Comment", flex: 1 },
  {
    cellClass: ['px-0'],
    headerName: "",
    field: "",
    width: 70,
    cellRenderer: EditParam,
  },
  {
    cellClass: ['px-0'],
    headerName: "",
    field: "",
    width: 70,
    cellRenderer: DeleteParam,
  },
]);

const defaultColDef = {
  sortable: true,
  filter: true
};

const fetchData = async () => {
  try {
    const response = await axios.get('/param');
    rowData.value = Array.isArray(response.data) ? response.data : response.data.items;
  } catch (error) {
    console.error('Error fetching data:', error);
  }
};

const fetchParams = async () => {
  try {
    const responseGraphics = await axios.get('/paramtypes');
    const responseChanges = await axios.get('/units');
    paramsOptions.value = responseGraphics.data.map(graphic => ({
      value: graphic.id,
      text: graphic.Name
    }));
    unitsOptions.value = responseChanges.data.map(change => ({
      value: change.id,
      text: change.Name
    }));
  } catch (error) {
    console.error('Error fetching graphics data:', error);
  }
};

const onSubmit = async () => {
  try {
    const { data } = await axios.post("/param", result);
    if (data.status === 200) {
      showModal.value = false;
      result.Name = '';
      result.NameRus = '';
      result.ShortNameRus = '';
      result.ShortName = '';
      result.ParamsTypeID = '';
      result.UnitsID = '',
      result.Min = '',
      result.Max = '',
      result.Comment = '';
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
  fetchParams()
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
