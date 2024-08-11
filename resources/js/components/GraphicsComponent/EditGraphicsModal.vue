<template>
  <main class="h-full w-full text-center content-center">
    <VaButton round icon="edit" preset="primary" class="mt-1" @click="selectedDataEdit = true" />
    <VaModal v-model="selectedDataEdit" :ok-text="t('modals.apply')" :cancel-text="t('modals.cancel')" @ok="onSubmit"
      @close="selectedDataEdit = false" close-button>
      <h3 class="va-h3">{{ t('modals.editFactory') }}</h3>
      <div>
        <VaForm ref="formRef" class="flex flex-col items-baseline gap-2">
          <VaInput class="w-full" v-model="result.Name" :rules="[(value) => !!value || t('validation.required')]"
            :label="t('form.name')" />
            <VaInput class="w-full" v-model="result.NameRus" :rules="[(value) => !!value || t('validation.required')]"
            :label="t('form.nameRus')" />
          <VaTextarea class="w-full" v-model="result.Comment" :max-length="125" :label="t('form.comment')" />
        </VaForm>
      </div>
    </VaModal>
  </main>
</template>

<script setup>
import { ref, reactive, inject, onMounted } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';

const props = defineProps(['params']);
const selectedDataEdit = ref(false);
const onupdated = inject('onupdated');
const { t } = useI18n();

const result = reactive({
  Name: '',
  NameRus: '',
  Comment: '',
  id: props.params.data['id'],
});

onMounted(() => {
  axios
    .get(`graphics/${result.id}`)
    .then((res) => {
      result.Name = res.data.Name;
      result.NameRus = res.data.NameRus;

      result.Comment = res.data.Comment;
    })
    .catch((error) => {
      console.error('Error fetching data:', error);
      // Optionally, you can show an error notification here.
    });
});

const onSubmit = async () => {
  try {
    const { data } = await axios.put(`/graphics/${result.id}`, result);
    if (data.status === 200) {
      onupdated(props.params.node, data.unit);
      selectedDataEdit.value = false;
    } else {
      console.error('Error saving data:', data.message);
      // Optionally, you can show an error notification here.
    }
  } catch (error) {
    console.error('Error saving data:', error);
    // Optionally, you can show an error notification here.
  }
};
</script>
