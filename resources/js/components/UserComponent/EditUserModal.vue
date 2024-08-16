<template>
  <div class="grid grid-rows-[55px,1fr]">
    <!-- Add new Elements start -->
    <main>
      <div class="flex justify-between">
        <span class="flex w-full"></span>
        <VaButton @click="showModal = true" class="w-14 h-12 mt-1 mr-1" icon="add" />
      </div>
      <VaModal v-model="showModal" :ok-text="t('buttons.save')" :cancel-text="t('buttons.cancel')" @ok="onSubmit"
        close-button>
        <h3 class="va-h3">
          {{ t('modals.addUserTitle') }}
        </h3>
        <div>
          <VaForm ref="formRef" class="flex flex-col items-baseline gap-2">
            <VaInput class="w-full" v-model="result.Name"
              :rules="[(value) => (value && value.length > 0) || t('form.requiredField')]"
              :label="t('form.userName')" />
            <VaInput class="w-full" v-model="result.Phone"
              :rules="[(value) => (value && value.length > 0) || t('form.requiredField')]" type="tel"
              :label="t('form.userPhone')" placeholder="597****" />
            <VaInput class="w-full" v-model="result.Login"
              :rules="[(value) => (value && value.length > 0) || t('form.requiredField')]" :label="t('form.login')" />
            <VaInput class="w-full" v-model="result.Password"
              :rules="[(value) => (value && value.length > 0) || t('form.requiredField')]"
              :label="t('form.password')" />
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
import { useI18n } from 'vue-i18n';

const { locale, t } = useI18n();

const rowData = ref([]);
const gridApi = ref(null);
const showModal = ref(false);
const factoryOptions = ref([]);

const result = reactive({
  Name: "",
  Phone: "",
  Login: "",
  Password: "",
});

const defaultColDef = {
  sortable: true,
  filter: true,
};

const fetchData = async () => {
  try {
    const response = await axios.get('/users');
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
      text: factory.Name,
    }));
  } catch (error) {
    console.error('Error fetching graphics data:', error);
  }
};

const onSubmit = async () => {
  try {
    const { data } = await axios.put('/users', result);
    if (data.status === 200) {
      showModal.value = false;
      result.Name = '';
      result.Phone = '';
      result.Login = '';
      result.Password = '';
      result.StructureID = [];
      await fetchData();
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
</style>
