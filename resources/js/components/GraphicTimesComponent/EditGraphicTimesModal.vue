<template>
  <main class="h-full w-full text-center content-center">
    <VaButton round icon="edit" preset="primary" class="mt-1"  @click="selectedDataEdit = true" />
    <VaModal v-model="selectedDataEdit" :ok-text="t('modals.apply')" :cancel-text="t('modals.cancel')" @ok="onSubmit"
      @close="selectedDataEdit = false" close-button>
      <h3 class="va-h3" @vue:mounted="fetchGraphics">
        {{ t('modals.editFactory') }}
      </h3>
      <div>
        <VaForm ref="formRef" class="flex flex-col items-baseline gap-2">
          <div class="grid grid-cols-2 md:grid-cols-2 gap-2 items-end w-full">
            <VaSelect v-model="result.GraphicId" value-by="value" class="mb-6" :label="t('form.selectGraphic')"
              :options="graphicsOptions" clearable />
            <VaSelect v-model="result.ChangeId" value-by="value" class="mb-6" :label="t('form.selectChange')" :options="changesOptions"
              clearable />
          </div>
          <div class="grid grid-cols-2 md:grid-cols-3 gap-3 items-end">
            <VaTimeInput clearable clearable-icon="cancel" color="textPrimary" :label="t('form.name')"
              v-model="result.Name" />
            <VaTimeInput clearable clearable-icon="cancel" color="textPrimary" :label="t('form.startTime')"
              v-model="result.StartTime" />
            <VaTimeInput v-model="result.EndTime" clearable clearable-icon="cancel" color="textPrimary"
              :label="t('form.endTime')" />
          </div>
        </VaForm>
      </div>
    </VaModal>
  </main>
</template>

<script setup>
import { ref, reactive, inject } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { useForm, useToast, VaValue, VaInput, VaButton, VaForm, VaIcon } from 'vuestic-ui';
const { init } = useToast();

const props = defineProps(["params"]);
const selectedDataEdit = ref(false);
const graphicsOptions = ref([]);
const changesOptions = ref([]);
const onupdated = inject('onupdated');

const result = reactive({
  GraphicId: "",
  ChangeId: "",
  Name: "",
  StartTime: "",
  EndTime: "",
  id: props.params.data['id'],
});

const { t } = useI18n();

const fetchGraphics = async () => {
  try {
    const responseGraphics = await axios.get('/graphics');
    const responseChanges = await axios.get('/changes');

    graphicsOptions.value = responseGraphics.data.map(graphic => ({
      value: graphic.id,
      text: graphic.Name,
    }));

    changesOptions.value = responseChanges.data.map(change => ({
      value: change.id,
      text: change.Change,
    }));

    const response = await axios.get(`graphictimes/${props.params.data['id']}`);
    
    result.GraphicId = +response.data.Gid;
    result.ChangeId = +response.data.Chid;
    
    // result.Name = response.data.Name;
    // result.StartTime = response.data.StartTime.toString();
    // result.EndTime = response.data.EndTime.toString();
  } catch (error) {
    console.error('Error fetching data:', error);
  }
};

const onSubmit = async () => {
  try {
    const { data } = await axios.put("/graphictimes", result);
    if (data.status === 200) {
      selectedDataEdit.value = false;
      onupdated(props.params.node, data.unit);
      init({ message: t('login.successMessage'), color: 'success' });
    } else {
      console.error('Error saving data:', data.message);
    }
  } catch (error) {
    console.error('Error saving data:', error);
  }
};

</script>
