<template>
  <main class="h-full w-full text-center content-center">
    <VaButton round icon="edit" preset="primary" class="mt-1" @click="selectedDataEdit = true" />
    <VaModal v-model="selectedDataEdit" :ok-text="t('modals.apply')" :cancel-text="t('modals.cancel')" @ok="onSubmit"
      @close="selectedDataEdit = false" close-button>
      <h3 class="va-h3" @vue:mounted="fetchParams">
        {{ t('modals.editFactory') }}
      </h3>
      <div>
        <VaForm ref="formRef" class="flex flex-col items-baseline gap-2">
          <VaInput class="w-full" v-model="result.Name"
            :rules="[(value) => (value && value.length > 0) || t('form.requiredField')]" :label="t('form.name')" />
          <VaInput class="w-full" v-model="result.NameRus"
            :rules="[(value) => (value && value.length > 0) || t('form.requiredField')]" :label="t('form.nameRus')" />
          <VaInput class="w-full" v-model="result.ShortName"
            :rules="[(value) => (value && value.length > 0) || t('form.requiredField')]"
            :label="t('form.shortName')" />
          <VaInput class="w-full" v-model="result.ShortNameRus"
            :rules="[(value) => (value && value.length > 0) || t('form.requiredField')]"
            :label="t('form.shortNameRus')" />
            <VaInput class="w-full" v-model="result.WinCC"
            :rules="[(value) => (value && value.length > 0) || t('form.requiredField')]"
            :label="t('form.wincc')" />
            <VaSelect v-model="result.ServerId" value-by="value" class="w-full" :label="t('menu.servers')"
                :options="serversOptions" clearable />
          <div class="grid grid-cols-2 md:grid-cols-2 gap-2 items-end w-full">
            <VaInput class="w-full" v-model="result.Min"
              :rules="[(value) => (value && value.length > 0) || t('form.requiredField')]" :label="t('table.min')" />
            <VaInput class="w-full" v-model="result.Max"
              :rules="[(value) => (value && value.length > 0) || t('form.requiredField')]" :label="t('table.max')" />
          </div>
          <div class="grid grid-cols-2 md:grid-cols-2 gap-2 items-end w-full">
            <VaSelect v-model="result.ParamsTypeID" value-by="value" class="mb-6" :label="t('menu.paramtypes')"
              :options="paramsOptions" clearable />
            <VaSelect v-model="result.UnitsID" value-by="value" class="mb-6" :label="t('menu.units')" :options="unitsOptions"
              clearable />
          </div>
          <VaTextarea class="w-full" v-model="result.Comment" max-length="125" :label="t('form.comment')" />
        </VaForm>
      </div>
    </VaModal>
  </main>
</template>

<script setup>
import { ref, reactive, inject, onMounted } from 'vue';
import { useI18n } from 'vue-i18n'; // Import useI18n from vue-i18n
import axios from 'axios';
import { useForm, useToast, VaValue, VaInput, VaButton, VaForm, VaIcon } from 'vuestic-ui';
const { init } = useToast();
const { t } = useI18n(); // Use i18n

const props = defineProps(["params"]);
const selectedDataEdit = ref(false);
const onupdated = inject('onupdated');
const paramsOptions = ref([]);
const unitsOptions = ref([]);
const serversOptions = ref([]);


const result = reactive({
  Name: "",
  NameRus: "",
  ShortNameRus: "",
  ShortName: "",
  WinCC: "",
  ServerId:"",
  ParamsTypeID: "",
  UnitsID: "",
  Min: "",
  Max: "",
  Comment: "",
  id: props.params.data['Uuid']
});

const fetchParams = async () => {
  try {
    const [responseGraphics, responseChanges,serversResponse, paramResponse] = await Promise.all([
      axios.get('/paramtypes'),
      axios.get('/units'),
      axios.get('/servers'),
      axios.get(`param/${props.params.data['Uuid']}`)
    ]);
    
    paramsOptions.value = responseGraphics.data.map(graphic => ({
      value: graphic.id,
      text: graphic.Name
    }));
    unitsOptions.value = responseChanges.data.map(change => ({
      value: change.id,
      text: change.Name
    }));
    serversOptions.value = serversResponse.data.map(server => ({
      value: server.id,
      text: server.name
    }));

    result.Name = paramResponse.data.Name;
    result.NameRus = paramResponse.data.NameRus;
    result.ShortNameRus = paramResponse.data.ShortNameRus;
    result.ShortName = paramResponse.data.ShortName;
    result.WinCC = paramResponse.data.WinCC;
    result.ServerId = +paramResponse.data.Ipid;
    result.ParamsTypeID = +paramResponse.data.Pid;
    result.UnitsID = +paramResponse.data.Uid;
    result.Min = +paramResponse.data.Min;
    result.Max = +paramResponse.data.Max;
    result.Comment = paramResponse.data.Comment;
    
  } catch (error) {
    console.error('Error fetching data:', error);
  }
};

const onSubmit = async () => {
  try {
    const { data } = await axios.put("/param", result);
    if (data.status === 200) {
      onupdated(props.params.node, data.unit);
      selectedDataEdit.value = false;
      init({ message: t('login.successMessage'), color: 'success' });

    } else {
      console.error('Error saving data:', data.message);
    }
  } catch (error) {
    console.error('Error saving data:', error);
  }
};


</script>
