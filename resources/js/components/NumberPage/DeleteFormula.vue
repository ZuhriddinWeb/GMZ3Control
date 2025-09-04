<template>
  <main class="h-full w-full text-center content-center">
    <VaButton round icon="delete" preset="primary" class="mt-1" @click="selectedDataDelete = true" />
    <VaModal v-model="selectedDataDelete" :ok-text="t('modals.apply')" :cancel-text="t('modals.cancel')"
      @close="selectedDataDelete = false" @ok="onSubmit" close-button>
      <h3 class="va-h3">{{ t("modals.title") }}</h3>
      <p>
        {{ t('modals.message') }}
      </p>
    </VaModal>
  </main>
</template>

<script setup>
import { ref, inject } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { useForm, useToast, VaValue, VaInput, VaButton, VaForm, VaIcon } from 'vuestic-ui';
const { init } = useToast();
const selectedDataDelete = ref(false);
const props = defineProps(["params"]);
const ondeleted = inject('ondeleted');
const { t } = useI18n();

const onSubmit = async () => {
  try {
    const { data } = await axios.delete(`/svodkaFormula/${row.formulaId}`);
    if (data.status === 200) {
      ondeleted(props.params.data);
      selectedDataDelete.value = false;
      init({ message: t('login.successMessage'), color: 'success' });

    } else {
      console.error('Error deleting data:', data.message);
      // Optionally, you can show an error notification here.
    }
  } catch (error) {
    console.error('Error deleting data:', error);
    // Optionally, you can show an error notification here.
  }
};
</script>
