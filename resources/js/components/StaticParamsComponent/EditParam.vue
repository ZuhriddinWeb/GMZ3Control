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
          <VaInput class="w-full" v-model="result.Value"
            :rules="[(value) => (value && value.length > 0) || t('validation.required')]" :label="t('table.value')" />
          <div class="flex gap-5 flex-wrap w-full mt-4">
            <VaDatePicker v-model="result.PeriodStartDate" stateful highlight-weekend />
            <VaDatePicker v-model="result.PeriodEndDate" stateful highlight-weekend />
          </div>
          <VaSelect v-model="result.PeriodTypeId" value-by="value" class="mb-6 w-full" :label="t('menu.paramtypes')"
            :options="paramsOptions" clearable />
          <VaTextarea class="w-full" v-model="result.Description" max-length="125" :label="t('form.comment')" />
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
  Value: "",
  PeriodTypeId: "",
  PeriodStartDate: "",
  PeriodEndDate: "",
  Comment: "",
  id: props.params.data['id']
});

const fetchParams = async () => {
  try {
    const [paramtypesResponse, paramResponse] = await Promise.all([
      axios.get('/periodType'),
      axios.get(`static/${props.params.data['id']}`)
    ]);

    paramsOptions.value = paramtypesResponse.data.map(param => ({
      value: param.id,
      text: param.name
    }));
    result.Value = +paramResponse.data.value;
    result.PeriodTypeId = +paramResponse.data.period_type_id;
    result.PeriodStartDate = paramResponse.data.period_start_date ? new Date(paramResponse.data.period_start_date) : null;
    result.PeriodEndDate = paramResponse.data.period_end_date ? new Date(paramResponse.data.period_end_date) : null;
    result.Comment = paramResponse.data.Comment;

  } catch (error) {
    console.error('Error fetching data:', error);
  }
};
const formatDateLocal = (date) => {
  if (!date) return null;
  const d = new Date(date);
  d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
  return d.toISOString().split('T')[0];
};

const onSubmit = async () => {
  try {
    const payload = {
      ...result,
      PeriodStartDate: formatDateLocal(result.PeriodStartDate),
      PeriodEndDate: formatDateLocal(result.PeriodEndDate)
    };

    const { data } = await axios.put("/static", payload);
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
