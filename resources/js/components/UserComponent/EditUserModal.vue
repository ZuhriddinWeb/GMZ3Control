<template>
  <main class="h-full w-full text-center content-center">
    <!-- <span class="flex w-full"></span> -->
    <VaButton round icon="edit" preset="primary" class="mt-1" @click="selectedDataEdit = true" />
    <!-- <VaButton @click="showModal = true" class="w-14 h-12 mt-1 mr-1" icon="add" /> -->
    <VaModal v-model="selectedDataEdit" :ok-text="t('modals.apply')" :cancel-text="t('modals.cancel')" @ok="onSubmit"
      @close="selectedDataEdit = false" close-button>
      <h3 class="va-h3" @vue:mounted="fetchGraphics">
        {{ t('menu.edit') }}
      </h3>
      <div>
        <VaForm ref="formRef" class="flex flex-col items-baseline gap-2">
          <div class="grid grid-cols-1 md:grid-cols-1 gap-2 items-end w-full">
            <VaSelect v-model="result.StructureID" :label="t('form.structureName')" :options="factoryOptions" multiple
              trackBy="value" textBy="text" />


          </div>
          <VaInput class="w-full" v-model="result.Name"
            :rules="[(value) => (value && value.length > 0) || t('form.requiredField')]" :label="t('form.userName')" />
          <VaInput class="w-full" v-model="result.Phone"
            :rules="[(value) => (value && value.length > 0) || t('form.requiredField')]" type="tel"
            :label="t('form.userPhone')" placeholder="597****" />
          <VaInput class="w-full" v-model="result.Login"
            :rules="[(value) => (value && value.length > 0) || t('form.requiredField')]" :label="t('form.login')" />
          <VaInput class="w-full" v-model="result.Password"
            :rules="[(value) => (value && value.length > 0) || t('form.requiredField')]" :label="t('form.password')" />
        </VaForm>
      </div>
    </VaModal>
  </main>
</template>


<script setup>
import { ref, reactive, onMounted, provide, computed, watch } from 'vue';
import axios from 'axios';
import 'vuestic-ui/dist/vuestic-ui.css';
import { useI18n } from 'vue-i18n';
import { useForm, useToast, VaValue, VaInput, VaButton, VaForm, VaIcon } from 'vuestic-ui';
const { init } = useToast();

const props = defineProps(['params']);
const { locale, t } = useI18n();

const isGraphicsLoaded = ref(false);
const factoryOptions = ref([]);
const selectedDataEdit = ref(false);

const result = reactive({
  Name: "",
  Phone: "",
  Login: "",
  Password: "",
  StructureID: [] // bu endi [{value: 1, text: 'Sex1'}] formatda bo‘ladi
});

const fetchGraphics = async () => {
  try {
    const response = await axios.get('/structure');
    factoryOptions.value = response.data.map(factory => ({
      value: factory.id,
      text: factory.Name,
    }));
  } catch (error) {
    console.error('Strukturalarni olishda xatolik:', error);
  }
};

const fetchUserById = async () => {
  try {
    const userId = props.params.data.id;
    const { data } = await axios.get(`/users/${userId}`);

    result.Name = data.name || '';
    result.Phone = data.phone || '';
    result.Login = data.login || '';
    result.Password = '';

    // ✅ ID larni object formatga o'tkazamiz
    result.StructureID = factoryOptions.value.filter(option =>
      (data.structure_id || []).includes(option.value)
    );
  } catch (error) {
    console.error('Foydalanuvchini olishda xatolik:', error);
  }
};

const onSubmit = async () => {
  try {
    const payload = {
      id: props.params.data.id, // ✅ ID ni qo‘shamiz
      ...result,
      StructureID: result.StructureID.map(item => item.value)
    };

    const response = await axios.put('/users', payload);

    if (response.data.status === 200) {
      selectedDataEdit.value = false;
      result.Name = '';
      result.Phone = '';
      result.Login = '';
      result.Password = '';
      result.StructureID = [];
      init({ message: t('login.successMessage'), color: 'success' });

    } else {
      console.error('Saqlashda xatolik:', response.data.message);
    }
  } catch (error) {
    console.error('Saqlashda xatolik:', error);
  }
};


watch(selectedDataEdit, async (val) => {
  if (val) {
    if (!isGraphicsLoaded.value) {
      await fetchGraphics();
      isGraphicsLoaded.value = true;
    }
    await fetchUserById();
  }
});

onMounted(() => {
  const savedLocale = localStorage.getItem('locale');
  if (savedLocale) {
    locale.value = savedLocale;
  }
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
