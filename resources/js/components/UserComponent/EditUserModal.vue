<template>
  <main class="h-full w-full text-center content-center">
        <!-- <span class="flex w-full"></span> -->
        <VaButton round icon="edit" preset="primary" class="mt-1" @click="selectedDataEdit = true" />
        <!-- <VaButton @click="showModal = true" class="w-14 h-12 mt-1 mr-1" icon="add" /> -->
      <VaModal v-model="selectedDataEdit" :ok-text="t('modals.apply')" :cancel-text="t('modals.cancel')" @ok="onSubmit"
      @close="selectedDataEdit = false" close-button>
        <h3 class="va-h3">
          {{ t('modals.addUserTitle') }}
        </h3>
        <div>
          <VaForm ref="formRef" class="flex flex-col items-baseline gap-2">
            <div class="grid grid-cols-1 md:grid-cols-1 gap-2 items-end w-full">
              <VaSelect v-model="result.StructureID" :label="t('form.structureName')" :options="factoryOptions"
                multiple />
            </div>
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
</template>


<script setup>
import { ref, reactive, onMounted, provide, computed } from 'vue';
import axios from 'axios';
import 'vuestic-ui/dist/vuestic-ui.css';
import { useI18n } from 'vue-i18n';
import { useForm, useToast, VaValue, VaInput, VaButton, VaForm, VaIcon } from 'vuestic-ui';

const { locale, t } = useI18n();

const rowData = ref([]);
const gridApi = ref(null);
const showModal = ref(false);
const factoryOptions = ref([]);
const selectedDataEdit = ref(false);

const result = reactive({
  Name: "",
  Phone: "",
  Login: "",
  Password: "",
});


// const fetchData = async () => {
//   try {
//     const response = await axios.get('/users');
//     rowData.value = Array.isArray(response.data) ? response.data : response.data.items;
//   } catch (error) {
//     console.error('Error fetching data:', error);
//   }
// };

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
  // fetchData();
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
