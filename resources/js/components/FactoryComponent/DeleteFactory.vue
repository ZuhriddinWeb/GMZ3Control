<template>
  <main class="h-full w-full text-center content-center">
    <VaButton round icon="delete" color="red" preset="primary" class="mt-1" @click="selectedDataDelete = true" />
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
import { ref } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';

const selectedDataDelete = ref(false);
const { t } = useI18n();

const props = defineProps(["params"]);

const onSubmit = async () => {
  try {
    const { data } = await axios.delete(`/factory/${props.params.data['id']}`);
    if (data.status === 200) {
      props.onDelete(props.params.data);
      selectedDataDelete.value = false;
    } else {
      console.error('Error deleting data:', data.message);
    }
  } catch (error) {
    console.error('Error deleting data:', error);
  }
};
</script>
