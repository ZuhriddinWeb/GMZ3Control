<template>
  <div class="w-full">
    <!-- <DeleteUnitsModal @close="handleClose" :selectedDataDelete="getDeleteRow" v-if="getDeleteRow" /> -->
    <!-- Add new Elements start -->
    <VaModal v-model="showModal" ok-text="Saqlash" cancel-text="Bekor qilish" @ok="onSubmit" close-button>
      <h3 class="va-h3">
        O'lchov birliklarini kiritish
      </h3>
      <div>
        <VaForm ref="formRef" class="flex flex-col items-baseline gap-2">
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
    <!-- Add new Elements end -->
    <!-- Edit new Elements start -->
    <VaModal v-model="getEditRow" ok-text="Saqlash" cancel-text="Bekor qilish" @ok="onSubmit" close-button>
      <h3 class="va-h3">
        O'lchov birliklarini tahrirlash
      </h3>
      <div>
        <VaForm ref="formRef" class="flex flex-col items-baseline gap-2">
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
    <!-- Edit new Elements end -->
    <!-- Delete Modal start -->
    <VaModal v-model="getDeleteRow" ok-text="Tasdiqlash" cancel-text="Bekor qilish" @close="handleClose">
      <h3 class="va-h3">O'chirmoqchimisiz</h3>
      <p>
        O'chirilgan ma'lumot qayta tiklanmaydi.
      </p>
    </VaModal>
    <!-- Delete Modal end -->
    <div class="flex justify-between">
      <div class="flex justify-between">
        <div class="flex justify-end">
          <div class="flex justify-between mb-2">
            <span class="flex w-[100%]"></span>
            <VaButton @click="showModal = true" class="w-14 h-12" icon="add" />
          </div>
        </div>
      </div>
    </div>
    <ag-grid-vue :rowData="rowData" :columnDefs="columnDefs" :defaultColDef="defaultColDef" animateRows="true"
      class="ag-theme-material h-screen"></ag-grid-vue>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, defineComponent } from 'vue';
import axios from 'axios';
import 'vuestic-ui/dist/vuestic-ui.css';
import DeleteUnitsModal from '../components/UnitsComponent/DeleteUnitsModal.vue'

const rowData = ref([]);
const showModal = ref(false);
const getDeleteRow = ref(false);
const getEditRow = ref(false);

const result = reactive({
  Name: "",
  ShortName: "",
  Comment: ""
});

const columnDefs = reactive([
  { headerName: "T/r", valueGetter: "node.rowIndex + 1" },
  { headerName: "Nomlanishi", field: "Name", flex: 1 },
  { headerName: "Izoh", field: "Comment" },
  {
    headerName: "",
    field: "",
    width: 70,
    onCellClicked: function (select) {
      return getEdit(select.data);
    },
    cellRenderer: (params) =>
      "<div><button @click='LessonEdit=true'><i class='fal fa-edit  text-xl hover:text-green-400'></i></button></div>",
  },
  {
    headerName: "",
    field: "",
    width: 70,
    onCellClicked: function (select) {
      return getDelete(select.data);
    },
    cellRenderer: (params) =>
      "<div><button @click='LessonEdit=true'><i class='fal fa-trash-alt  text-xl hover:text-red-400'></i></button></div>",
  },
]);

const defaultColDef = {
  sortable: true,
  filter: true
};
async function getEdit(e) {
  if (e.id != "") {
    axios.get(`units/${e.id}`).then((res) => {
    result.Name = res.data.Name
    result.ShortName = res.data.ShortName
    result.Comment = res.data.Comment
    
})
    store.state.selectedRowId = e.id;
    getEditRow.value=true;
  }
}
async function getDelete(e) {
  if (e.id !== "") {
    store.state.selectedRowId = e.id;
    getDeleteRow.value = true;
  }
}

const fetchData = async () => {
  try {
    const response = await axios.get('/paramtypes');
    rowData.value = Array.isArray(response.data) ? response.data : response.data.items; // Adjust based on actual structure
  } catch (error) {
    console.error('Error fetching data:', error);
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
const handleClose = () => {
  getDeleteRow.value = false;
};
onMounted(fetchData);
</script>

<style>
.material-icons {
  font-family: 'Material Icons';
  font-weight: normal;
  font-style: normal;
  font-size: 24px;
  /* Preferred icon size */
  display: inline-block;
  line-height: 1;
  text-transform: none;
  letter-spacing: normal;
  word-wrap: normal;
  white-space: nowrap;
  direction: ltr;
}
</style>
