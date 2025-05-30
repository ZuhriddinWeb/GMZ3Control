<template>
  <div class="grid grid-rows-[55px,1fr]">
    <!-- Add new Elements start -->
    <main>
      <div class="flex justify-between">
        <span class="flex w-full"></span>
        <VaButton v-if="canCreate" @click="showModal = true" class="w-14 h-12 mt-1 mr-1" icon="add" />
      </div>
      <VaModal v-model="showModal" :ok-text="t('buttons.save')" :cancel-text="t('buttons.cancel')" @ok="onSubmit"
        close-button>
        <h3 class="va-h3">
          {{ t('modals.addGraphicTimesTitle') }}
        </h3>
        <div>
          <VaForm ref="formRef" class="flex flex-col items-baseline gap-2">
            <div class="grid grid-cols-1 md:grid-cols-1 gap-1 items-end w-full">
              <!-- <VaSelect v-model="result.GraphicId" value-by="value" class="mb-6" :label="t('form.selectGraphic')"
                :options="graphicsOptions" clearable /> -->
              <VaSelect v-model="result.ChangeId" value-by="value" class="mb-6" :label="t('form.selectChange')"
                :options="changesOptions" clearable />
              <!-- <VaSwitch v-model="result.Current" class="mb-2" @change="updateTimes">
                <template #innerLabel>
                  Joriy vaqtda
                </template>
              </VaSwitch> -->
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3 items-end">
              <VaTimeInput clearable clearable-icon="cancel" color="textPrimary" :label="t('form.name')"
                v-model="result.Name" />
              <VaTimeInput clearable clearable-icon="cancel" color="textPrimary" :label="t('form.startTime')"
                v-model="result.Name" />
              <VaTimeInput v-model="result.EndTime" clearable clearable-icon="cancel" color="textPrimary"
                :label="t('form.endTime')" />
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
import { ref, reactive, onMounted, provide, computed } from 'vue';
import axios from 'axios';
import 'vuestic-ui/dist/vuestic-ui.css';
import EditGraphicTimesModal from '../components/GraphicTimesComponent/EditGraphicTimesModal.vue';
import DeleteGraphicTimesModal from '../components/GraphicTimesComponent/DeleteGraphicTimesModal.vue';
import { useI18n } from 'vue-i18n';
const { locale, t } = useI18n();
import { format } from 'date-fns';
import { defineProps } from 'vue'
import { useStore } from 'vuex';
const store = useStore();
import { useForm, useToast, VaValue, VaInput, VaButton, VaForm, VaIcon } from 'vuestic-ui';
const { init } = useToast();
const props = defineProps({
  id: Number
})
const rowData = ref([]);
const gridApi = ref(null);
const showModal = ref(false);
const graphicsOptions = ref([]);
const changesOptions = ref([]);

const userRole = computed(() => store.state.user.roles[8]);
const hasPermission = (permission) => userRole.value?.pivot?.[permission] === "1";
const canCreate = computed(() => hasPermission("create"));
const canUpdate = computed(() => hasPermission("update"));
const canDelete = computed(() => hasPermission("delete"));


const result = reactive({
  GraphicId: props.id,
  ChangeId: "",
  Name: "",
  StartTime: "",
  EndTime: "",
  // Current: false
});

function ondeleted(selectedData) {
  gridApi.value.applyTransaction({ remove: [selectedData] });
}

function onupdated(rowNode, data) {
  rowNode.setData(data);
}

provide('ondeleted', ondeleted);
provide('onupdated', onupdated);

// const updateTimes = () => {
//   console.log(result.Current);
// };

const columnDefs = computed(() => {
  const cols = [
    { headerName: t('table.headerRow'), valueGetter: "node.rowIndex + 1", width: 80 },
    { headerName: "ID", field: "id", width: 80 },
    { headerName: t('table.change'), field: "Change", width: 120 },
    { headerName: t('table.graphicName'), field: "GName", flex: 1 },
    {
      headerName: t('table.name'),
      field: "Name",
      flex: 1,
      valueFormatter: (params) => {
        return format(new Date(`1970-01-01T${params.value}`), 'HH:mm');
      },
    },
    {
      headerName: t('table.startTime'),
      field: "StartTime",
      valueFormatter: (params) => {
        return format(new Date(`1970-01-01T${params.value}`), 'HH:mm');
      },
    },
    {
      headerName: t('table.endTime'),
      field: "EndTime",
      valueFormatter: (params) => {
        return format(new Date(`1970-01-01T${params.value}`), 'HH:mm');
      },
    },
    { headerName: t('table.comment'), field: "Comment", flex: 1 },
  ];

  if (canUpdate.value) {
    cols.push({
      cellClass: ['px-0'],
      headerName: "",
      field: "",
      width: 70,
      cellRenderer: EditGraphicTimesModal,
    });
  }

  if (canDelete.value) {
    cols.push({
      cellClass: ['px-0'],
      headerName: "",
      field: "",
      width: 70,
      cellRenderer: DeleteGraphicTimesModal,
    });
  }

  return cols;
});


const defaultColDef = {
  sortable: true,
  filter: true,
};

const fetchData = async () => {
  try {
    const response = await axios.get(`/getTimes/${props.id}`);
    rowData.value = Array.isArray(response.data) ? response.data : response.data.items;
  } catch (error) {
    console.error('Error fetching data:', error);
  }
};

const fetchGraphics = async () => {
  try {
    const responseGraphics = await axios.get('/graphics');
    const responseTerms = await axios.get(`/graphicterms/${props.id}`);
    console.log(responseTerms);
    
    const responseChanges = await axios.get('/changes');
    graphicsOptions.value = responseGraphics.data.map(graphic => ({
      value: graphic.id,
      text: graphic.Name,
    }));
    changesOptions.value = responseChanges.data.map(change => ({
      value: change.Change,
      text: change.Change,
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
      // result.GraphicId = '';
      result.ChangeId = '';
      result.Name = '';
      result.StartTime = '';
      result.EndTime = '';
      // result.Current=false;
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
