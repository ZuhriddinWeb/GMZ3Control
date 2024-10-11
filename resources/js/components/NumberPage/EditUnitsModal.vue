<template>
  <main class="h-full w-full text-center content-center">
    <VaButton round icon="edit" preset="primary" class="mt-1" @click="selectedDataEdit = true" />
    <VaModal v-model="selectedDataEdit" :ok-text="t('modals.apply')" :cancel-text="t('modals.cancel')" 
      @ok="onSubmit" @close="selectedDataEdit = false" close-button>
      <h3 class="va-h3">
        {{ t('modals.editFactory') }}
      </h3>
      <div>
        <VaForm ref="formRef" class="flex flex-col items-baseline gap-2">
          <VaInput class="w-full" v-model="result.Name"
            :rules="[(value) => !!value || t('validation.required')]" :label="t('form.name')" />
            <VaInput class="w-full" v-model="result.NameRus"
            :rules="[(value) => !!value || t('validation.required')]" :label="t('form.nameRus')" />
          <VaInput class="w-full" v-model="result.ShortName"
            :rules="[(value) => !!value || t('validation.required')]" :label="t('form.shortName')" />
            <VaInput class="w-full" v-model="result.ShortNameRus"
            :rules="[(value) => !!value || t('validation.required')]" :label="t('form.shortNameRus')" />
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
import { useForm, useToast, VaValue, VaInput, VaButton, VaForm, VaIcon } from 'vuestic-ui';
const { init } = useToast();

const props = defineProps(["params"]);
const selectedDataEdit = ref(false);
const onupdated = inject('onupdated');
const { t } = useI18n();

const result = reactive({
  Name: "",
  NameRus: "",
  ShortName: "",
  ShortNameRus: "",
  Comment: "",
  id: props.params.data['id'],
});

// Fetch unit data on mount
onMounted(() => {
  axios.get(`/units/${props.params.data['id']}`)
    .then((res) => {
      result.NameRus = res.data.NameRus;
      result.Name = res.data.Name;
      result.ShortName = res.data.ShortName;
      result.ShortNameRus = res.data.ShortNameRus;
      result.Comment = res.data.Comment;
    })
    .catch((error) => {
      console.error('Error fetching data:', error);
      // Optionally, you can show an error notification here.
    });
});

const onSubmit = async () => {
  try {
    const { data } = await axios.put("/units", result);
    if (data.status === 200) {
      onupdated(props.params.node, data.unit);
      selectedDataEdit.value = false;
      init({ message: t('login.successMessage'), color: 'success' });

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
