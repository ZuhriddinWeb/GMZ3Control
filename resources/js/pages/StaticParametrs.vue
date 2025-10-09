<template>
  <div class="grid grid-rows-[55px,1fr]">
    <!-- Add new Elements start -->
    <main>
      <div class="flex justify-between">
        <span class="flex w-full"></span>
        <VaButton v-if="canCreate" @click="showModal = true" class="w-14 h-12 mt-1 mr-1" icon="add" />
      </div>
      <VaModal v-model="showModal" :ok-text="t('modals.apply')" :cancel-text="t('modals.cancel')" @ok="onSubmit"
        close-button>
        <h3 class="va-h3" @vue:mounted="fetchParams">
          {{ t('modals.addParamsTitle') }}
        </h3>
        <div>
          <VaForm ref="formRef" class="flex flex-col items-baseline gap-2">
            <VaSelect v-model="result.FactoryStructureID" value-by="value" class="mb-1 w-full"
              @update:modelValue="getPages" :label="t('menu.structure')" :options="structureOptions" clearable />
            <VaSelect v-model="result.ParameterID" value-by="value" class="mb-1 w-full" :label="t('menu.params')"
              :options="params" searchable clearable />
            <!-- <VaSelect v-model="result.NumberPage" value-by="value" class="mb-1 w-full" :label="t('table.page')"
              :options="pageseOptions" @update:modelValue="onPageOrStructureChange" clearable /> -->
            <!-- <VaSelect v-model="result.GroupID" value-by="value" class="mb-1 w-full" :label="t('menu.groups')"
              :options="GroupsOptions" searchable /> -->
            <!-- <VaInput class="w-full" v-model="result.Value"
              :rules="[(value) => (value && value.length > 0) || t('validation.required')]" :label="t('table.value')" /> -->
            <VaInput class="w-full" v-model="result.OrderNumber"
              :rules="[(value) => (value && value.length > 0) || t('validation.required')]"
              :label="t('table.OrderNumber')" />
            <div class="flex gap-5 flex-wrap w-full mt-4">
              <VaDatePicker v-model="result.PeriodStartDate" stateful highlight-weekend :text-input="true" />
              <VaDatePicker v-model="result.PeriodEndDate" stateful highlight-weekend :text-input="true" />
            </div>
            <VaSelect v-model="result.PeriodTypeId" value-by="value" class="mb-6 w-full" :label="t('menu.paramtypes')"
              :options="paramsOptions" clearable />
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
import { ref, reactive, onMounted, provide, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import 'vuestic-ui/dist/vuestic-ui.css';
import DeleteParam from '../components/StaticParamsComponent/DeleteParam.vue';
import EditParam from '../components/StaticParamsComponent/EditParam.vue'
import { useStore } from 'vuex';
const store = useStore();
import { useForm, useToast, VaValue, VaInput, VaButton, VaForm, VaIcon } from 'vuestic-ui';
import { defineProps } from 'vue'
const props = defineProps({
  id: { type: Number, required: true },              // NumberPage (route'dan)
  groupId: { type: [Number, String, null], default: null },
})

const { init } = useToast();
const { t } = useI18n();
const formatDate = (date) => date ? date.toISOString().split('T')[0] : null;
const rowData = ref([]);
const gridApi = ref(null);
const showModal = ref(false);
const paramsOptions = ref([]);
const serversOptions = ref([]);
const params = ref([]);
const structureOptions = ref([]);
const pageseOptions = ref([]);
const GroupsOptions = ref([]);


const unitsOptions = ref([]);


const userRole = computed(() => store.state.user.roles[10]);
const hasPermission = (permission) => userRole.value?.pivot?.[permission] === "1";
const pageId = computed(() => Number(props.id))
const activeGroupId = computed(() => {
  // null/undefined/''/'null'/0 → guruhsiz
  if (
    props.groupId === null ||
    props.groupId === undefined ||
    props.groupId === '' ||
    props.groupId === 'null'
  ) return null
  const n = Number(props.groupId)
  return Number.isFinite(n) && n !== 0 ? n : null
})
const result = reactive({
  FactoryStructureID: "",
  ParameterID: "",
  Value: "",
  NumberPage: props.id,
  GroupID: activeGroupId.value,
  OrderNumber: "",
  PeriodTypeId: "",
  PeriodStartDate: null,
  PeriodEndDate: null,
  Comment: "",
});
const canCreate = computed(() => hasPermission("create"));
const canUpdate = computed(() => hasPermission("update"));
const canDelete = computed(() => hasPermission("delete"));
function ondeleted(selectedData) {
  gridApi.value.applyTransaction({ remove: [selectedData] })
}

function onupdated(rowNode, data) {
  rowNode.setData(data)
}
provide('ondeleted', ondeleted);
provide('onupdated', onupdated);

const columnDefs = computed(() => {
  const cols = [
    { headerName: t("table.headerRow"), valueGetter: "node.rowIndex + 1", width: 80 },
    { headerName: t("table.id"), field: "Uuid", hide: true, flex: 1 },
    { headerName: t("table.name"), field: "PName", flex: 1 },
    { headerName: t("menu.paramtypes"), field: "PTName" },
    { headerName: t("menu.units"), field: "UName" },
    { headerName: t("table.OrderNumber"), field: "OrderNumber" },

    { headerName: t("table.startingDay"), field: "period_start_date" },
    { headerName: t("table.endingDay"), field: "period_end_date" },
    { headerName: t("table.comment"), field: "Comment", flex: 1 },
  ];
  if (canUpdate.value) {
    cols.push({
      cellClass: ['px-0'],
      headerName: "",
      field: "",
      width: 70,
      cellRenderer: EditParam,
    });
  }
  if (canDelete.value) {
    cols.push({
      cellClass: ['px-0'],
      headerName: "",
      field: "",
      width: 70,
      cellRenderer: DeleteParam,
    });
  }
  return cols;
});
const defaultColDef = {
  sortable: true,
  filter: true
};
console.log(props);


const isNoGroup = (v) => {
  // backend ba'zan null/undefined/''/0/'0'/'null' qilib yuborishi mumkin — barchasini "guruhsiz" deb qabul qilamiz
  return v === null || v === undefined || v === '' || v === 'null' || Number(v) === 0
}

const fetchData = async () => {
  const { data } = await axios.get(`/staticCard/${props.id}`)
  let arr = Array.isArray(data) ? data : (data.items || [])

  if (props.groupId === null || props.groupId === undefined) {
    // 🔹 Guruhsiz tugmasidan kelingan holat: GroupID yo‘q bo‘lganlarni ko‘rsatamiz
    arr = arr.filter(r => isNoGroup(r.GroupID ?? r.group_id))
  } else {
    // 🔹 Muayyan guruh tanlangan: o‘sha guruhdagilar
    arr = arr.filter(r => Number(r.GroupID ?? r.group_id) === Number(props.groupId))
  }

  // 🔹 Har bir guruh ichida OrderNumber bo‘yicha tartib (guruhsizda ham ishlaydi)
  const getOrder = r => {
    const o = r.OrderNumber ?? r.order_number ?? r.order
    const n = Number(o)
    return Number.isFinite(n) ? n : Infinity
  }
  arr.sort((a, b) => {
    const d = getOrder(a) - getOrder(b)
    if (d !== 0) return d
    return String(a.PName ?? a.Name ?? '')
      .localeCompare(String(b.PName ?? b.Name ?? ''), undefined, { numeric: true })
  })

  rowData.value = arr
}

const fetchParams = async () => {
  try {
    const response = await axios.get('/param');
    const responseGraphics = await axios.get('/periodType');
    const responseChanges = await axios.get('/structure');
    const responsePage = await axios.get(`/pages-svodka/${22}`);

    params.value = response.data.map(graphic => ({
      value: graphic.Uuid,
      text: graphic.Name
    }));
    structureOptions.value = responseChanges.data.map(change => ({
      value: change.id,
      text: change.Name
    }));
    paramsOptions.value = responseGraphics.data.map(graphic => ({
      value: graphic.id,
      text: graphic.name
    }));
    pageseOptions.value = responsePage.data.map(graphic => ({
      value: graphic.NumberPage,
      text: graphic.Name
    }));
    if (result.NumberPage) {
      await onPageOrStructureChange();
    }
  } catch (error) {
    console.error('Error fetching graphics data:', error);
  }
};

const onPageOrStructureChange = async () => {
  try {
    const page = props.id || result.NumberPage
    if (!page) { GroupsOptions.value = []; result.GroupID = null; return }

    const { data } = await axios.get(`/getRowGroupWith/${page}`)
    GroupsOptions.value = [
      { value: null, text: '— Guruhsiz —' },      // 🔹 shu qatordan boshlab
      ...data.map(g => ({ value: g.id, text: g.Name }))
    ]

    if (!GroupsOptions.value.some(g => g.value === result.GroupID)) {
      result.GroupID = null
    }
  } catch (e) {
    GroupsOptions.value = [{ value: null, text: '— Guruhsiz —' }]
    result.GroupID = null
  }
}

const formatDateLocal = (date) => {
  if (!date) return null;
  const d = new Date(date);
  d.setMinutes(d.getMinutes() - d.getTimezoneOffset()); // Lokal va UTC farqini hisoblaydi
  return d.toISOString().split('T')[0];
};

const onSubmit = async () => {

  try {
    const payload = {
      ...result,
      PeriodStartDate: formatDateLocal(result.PeriodStartDate),
      PeriodEndDate: formatDateLocal(result.PeriodEndDate)
    };
    // console.log(payload);

    const { data } = await axios.post("/static", payload);
    if (data.status === 200) {
      showModal.value = false;
      result.FactoryStructureID = '';
      result.ParameterID = '';
      result.Value = '';
      result.Comment = '';
      result.OrderNumber = '',
       result.NumberPage = "";
      result.PeriodTypeId = '';
      result.PeriodStartDate = null;
      result.PeriodEndDate = null;
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
  result.NumberPage = props.id
  onPageOrStructureChange()
  // fetchParams()
});
watch(() => props.groupId, fetchData)
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
