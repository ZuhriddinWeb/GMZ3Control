<template>
  <div class="grid grid-rows-[55px,1fr]">
    <!-- Add new Elements start -->
    <main>
      <div class="flex justify-between">
        <span class="flex w-full"></span>
        <VaButton v-if="canCreate" @click="showModal = true,fetchGraphics" class="w-14 h-12 mt-1 mr-1" icon="add" />
      </div>
      <VaModal v-model="showModal" :ok-text="t('buttons.save')" :cancel-text="t('buttons.cancel')" @ok="onSubmit" close-button>
        <h3 class="va-h3">
          {{ t('modals.addPageTitle') }}
        </h3>
        <div>
          <VaForm ref="formRef" class="flex flex-col items-baseline gap-1">
            <!-- <div class="grid grid-cols-1 md:grid-cols-1 gap-2 items-end w-full">
              <VaSelect v-model="result.StructureID" value-by="value" class="mb-1" :label="t('form.structureName')" :options="factoryOptions"
                clearable />
            </div> -->
            <VaInput class="w-full" v-model="result.NumberPage" type="number"
              :rules="[(value) => (value && value.length > 0) || t('validation.requiredField')]"
              :label="t('form.numberpage')" />
            <VaInput class="w-full" v-model="result.Name"
              :rules="[(value) => (value && value.length > 0) || t('validation.requiredField')]"
              :label="t('form.name')" />
            <VaInput class="w-full" v-model="result.NameRus"
              :rules="[(value) => (value && value.length > 0) || t('validation.requiredField')]"
              :label="t('form.nameRus')" />
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
import DeleteBlog from '../components/NumberPage/DeleteBlog.vue';
import EditBlog from '../components/NumberPage/EditBlog.vue';
import { useI18n } from 'vue-i18n';
import { useForm, useToast, VaValue, VaInput, VaButton, VaForm, VaIcon } from 'vuestic-ui';
const { init } = useToast();
import { defineProps } from 'vue'
import { useStore } from 'vuex';
const store = useStore();
const { locale, t } = useI18n();
const props = defineProps({
  id: Number
})
const rowData = ref([]);
const gridApi = ref(null);
const showModal = ref(false);
const factoryOptions = ref([]);

const result = reactive({
  StructureID:props.id,
  Name: "",
  NameRus: "",
  NumberPage: "",
  Comment: ""
});
const userRole = computed(() => store.state.user.roles[17]);
const hasPermission = (permission) => userRole.value?.pivot?.[permission] === "1";
const canCreate = computed(() => hasPermission("create"));
const canUpdate = computed(() => hasPermission("update"));
const canDelete = computed(() => hasPermission("delete"));


function ondeleted(selectedData) {
  gridApi.value.applyTransaction({ remove: [selectedData] });
}

function onupdated(rowNode, data) {
  rowNode.setData(data);
}

provide('ondeleted', ondeleted);
provide('onupdated', onupdated);

const columnDefs = computed(() => {
  const cols = [
    { headerName: t('table.headerRow'), valueGetter: "node.rowIndex + 1" },
    { headerName: t('table.structure'), field: getFielBlog(), flex: 1 },
    { headerName: t('table.name'), field: getFieldName(), flex: 1 },
    { headerName: t('table.numberpage'), field: 'NumberPage' },
    { headerName: t('table.comment'), field: getFieldShortName() },
  ];

  if (canUpdate.value) {
    cols.push({
      cellClass: ['px-0'],
      headerName: "",
      field: "",
      width: 70,
      cellRenderer: EditBlog,
    });
  }

  if (canDelete.value) {
    cols.push({
      cellClass: ['px-0'],
      headerName: "",
      field: "",
      width: 70,
      cellRenderer: DeleteBlog,
    });
  }

  return cols;
});


const defaultColDef = {
  sortable: true,
  filter: true
};

const getFieldName = () => {
  return locale.value === 'uz' ? 'Name' : 'NameRus';
};

const getFieldShortName = () => {
  return locale.value === 'uz' ? 'ShortName' : 'ShortNameRus';
};
const getFielBlog = () => {
  return locale.value === 'uz' ? 'SName' : 'NameRus';
};

const fetchData = async () => {
  try {
    const response = await axios.get(`/getRowPage/${props.id}`);
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
      text: locale.value === 'uz' ? factory.Name : factory.NameRus
    }));
  } catch (error) {
    console.error('Error fetching graphics data:', error);
  }
};

const onSubmit = async () => {
  try {
    const { data } = await axios.post("/pages", result);
    if (data.status === 200) {
      showModal.value = false;
      result.StructureID = '';
      result.Name = '';
      result.NameRus = '';
      result.NumberPage = '';

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
  fetchGraphics();
});

const changeLanguage = () => {
  locale.value = locale.value === 'uz' ? 'ru' : 'uz';
  // Save language preference to localStorage
  localStorage.setItem('locale', locale.value);
  // Refresh factory options with the new language
  // fetchGraphics();
};

const currentLanguageLabel = computed(() => {
  return locale.value === 'uz' ? 'Русский' : 'O‘zbek';
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
