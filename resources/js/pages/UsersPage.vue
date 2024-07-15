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
          Foydalanuvchi yaratish
        </h3>
        <div>
          <VaForm ref="formRef" class="flex flex-col items-baseline gap-2">
            <VaInput class="w-full" v-model="result.Name"
              :rules="[(value) => (value && value.length > 0) || 'To\'ldirish majburiy bo\'lgan maydon']"
              label="Foydalanuvchi F.I.Sh" />
            <VaInput v-model="result.Phone" class="w-full" label="Foydalanuvchi telefon raqami" type="input"
              mask="creditCard" placeholder="###-##-##" />
            <VaInput class="w-full" v-model="result.Login"
              :rules="[(value) => (value && value.length > 0) || 'To\'ldirish majburiy bo\'lgan maydon']"
              label="Login" />
            <VaInput class="w-full" v-model="result.Password"
              :rules="[(value) => (value && value.length > 0) || 'To\'ldirish majburiy bo\'lgan maydon']"
              label="Parol" />
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
import DeleteUserModal from '../components/UserComponent/DeleteUserModal.vue'
import EditUserModal from '../components/UserComponent/EditUserModal.vue';
import RolesComponent from '../components/UserComponent/RolesComponent.vue';

const rowData = ref([]);
const gridApi = ref(null);
const showModal = ref(false);

const result = reactive({
  Name: "",
  Phone:"",
  Login: "",
  Password: "",
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
  { headerName: "Name", field: "name", flex: 1 },
  { headerName: "Login", field: "login", flex: 1 },
  { headerName: "Telefon raqami", field: "phone" },
  {
    cellClass: ['px-0'],
    headerName: "",
    field: "",
    width: 70,
    cellRenderer: RolesComponent,
  },
  // {
  //   cellClass: ['px-0'],
  //   headerName: "",
  //   field: "",
  //   width: 70,
  //   cellRenderer: EditUserModal,
  // },
  {
    cellClass: ['px-0'],
    headerName: "",
    field: "",
    width: 70,
    cellRenderer: DeleteUserModal,
  },
]);


const defaultColDef = {
  sortable: true,
  filter: true
};

const fetchData = async () => {
  try {
    const response = await axios.get('/users');
    rowData.value = Array.isArray(response.data) ? response.data : response.data.items;
  } catch (error) {
    console.error('Error fetching data:', error);
  }
};

const onSubmit = async () => {
  try {
    const { data } = await axios.post("/users", result);
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
