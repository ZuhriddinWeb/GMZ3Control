<template>
  <div class="grid grid-rows-[55px,1fr]">
    <!-- Add new Elements start -->
    <main>
      <div class="flex justify-between">
        <span class="flex w-full"></span>
        <!-- <VaButton @click="showModal = true" class="w-14 h-12 mt-1 mr-1" icon="add" /> -->
        <VaButton v-if="canCreate" @click="showModal = true" class="w-14 h-12 mt-1 mr-1" icon="add" />
      </div>
      <VaModal v-model="showModal" :ok-text="t('buttons.save')" :cancel-text="t('buttons.cancel')" @ok="onSubmit" close-button>
        <h3 class="va-h3">
        {{ t('modals.addStructureTitle') }}{{  }}
        </h3>
        <div>
          <VaForm ref="formRef" class="flex flex-col items-baseline gap-1">
            <VaInput class="w-full" v-model="result.Name"
              :rules="[(value) => (value && value.length > 0) || t('validation.requiredField')]"
              :label="t('form.name')" />
            <VaInput class="w-full" v-model="result.NameRus"
              :rules="[(value) => (value && value.length > 0) || t('validation.requiredField')]"
              :label="t('form.nameRus')" />
            <VaInput class="w-full" v-model="result.ShortName"
              :rules="[(value) => (value && value.length > 0) || t('validation.requiredField')]"
              :label="t('form.shortName')" />
            <VaInput class="w-full" v-model="result.ShortNameRus"
              :rules="[(value) => (value && value.length > 0) || t('validation.requiredField')]"
              :label="t('form.shortNameRus')" />
            <VaTextarea class="w-full" v-model="result.Comment" max-length="125" :label="t('form.comment')" />
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
import { ref, reactive, onMounted, provide, computed } from 'vue';
import axios from 'axios';
import 'vuestic-ui/dist/vuestic-ui.css';
import DeleteStructure from '../components/StructureComponent/DeleteStructure.vue'
import EditStructure from '../components/StructureComponent/EditStructure.vue';
import { useI18n } from 'vue-i18n';
import { useForm, useToast, VaValue, VaInput, VaButton, VaForm, VaIcon } from 'vuestic-ui';
const { init } = useToast();
import { useStore } from 'vuex';
const { locale, t } = useI18n();

const store = useStore();
const rowData = ref([]);
const gridApi = ref(null);
const showModal = ref(false);
const factoryOptions = ref([]);
// console.log(store.state.user.roles[4]);

const userRole = computed(() => store.state.user.roles[4]); // Faqat 4-chi indexni olish
const hasPermission = (permission) => {
  return userRole.value?.pivot?.[permission] === "1"; // Tekshirayotganda ? bilan mavjudligini tekshiramiz
};

const canCreate = computed(() => hasPermission("create"));
const canUpdate = computed(() => hasPermission("update"));
const canDelete = computed(() => hasPermission("delete"));
const result = reactive({
  Name: "",
  NameRus: "",
  ShortNameRus: "",
  ShortName: "",
  Comment: ""
});

function ondeleted(selectedData){
  gridApi.value.applyTransaction({ remove: [selectedData] })
}

function onupdated(rowNode,data){
  rowNode.setData(data)
}

provide('ondeleted', ondeleted)
provide('onupdated', onupdated)

const columnDefs = computed(() => [
  { headerName: t('table.headerRow'), valueGetter: "node.rowIndex + 1" },
  { headerName: t('table.name'), field: getFieldName(), flex: 1 },
  { headerName: t('table.shortName'), field: getFieldShortName() },
  {
    cellClass: ['px-0'],
    headerName: "",
    field: "",
    width: 70,
    cellRenderer: EditStructure,
    cellRendererParams: { canUpdate: canUpdate.value },
  },
  {
    cellClass: ['px-0'],
    headerName: "",
    field: "",
    width: 70,
    cellRenderer: DeleteStructure,
    cellRendererParams: { canDelete: canDelete.value },
  },
]);

const defaultColDef = {
  sortable: true,
  filter: true
};

// Function to get the correct field name based on the current locale
const getFieldName = () => {
  return locale.value === 'uz' ? 'Name' : 'NameRus';
};

// Function to get the correct field short name based on the current locale
const getFieldShortName = () => {
  return locale.value === 'uz' ? 'ShortName' : 'ShortNameRus';
};

const fetchData = async () => {
  try {
    const response = await axios.get('/structure');
    rowData.value = Array.isArray(response.data) ? response.data : response.data.items; 
  } catch (error) {
    console.error('Error fetching data:', error);
  }
};

const onSubmit = async () => {
  try {
    const { data } = await axios.post("/structure", result);
    if (data.status === 200) {
      showModal.value = false;
      result.Name = '';
      result.NameRus = '';

      result.ShortName = '';
      result.ShortNameRus = '';

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
  const savedLocale = localStorage.getItem('locale');
  if (savedLocale) {
    locale.value = savedLocale;
  }
  fetchData();
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
.ag-theme-material .ag-cell {
  border-right: 1px solid #d1d5db;
}

.ag-theme-material .ag-header-cell {
  border-right: 1px solid #d1d5db;
}

.ag-theme-material .ag-row {
  border-bottom: 1px solid #e5e7eb;
}
</style>
