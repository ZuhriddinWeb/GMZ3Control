<template>
  <main class="h-full w-full text-center content-center">
    <VaButton round icon="restart_alt"  preset="primary" class="mt-1" @click="selectedDataEdit = true" />
    <VaModal v-model="selectedDataEdit" :ok-text="t('modals.apply')" :cancel-text="t('modals.cancel')"
      @close="selectedDataEdit = false" @ok="onSubmit" close-button>
      <h3 class="va-h3">{{ t("modals.restart") }}</h3>
      <p>
        {{ t('modals.messageRestart') }}
      </p>
    </VaModal>
  </main>
</template>

<script setup>
import { ref, onMounted , inject} from 'vue'
import axios from 'axios';
import { useI18n } from 'vue-i18n';

const  props = defineProps(["params"]);
const { t } = useI18n();
const selectedDataEdit = ref(false);
const onupdated = inject('onupdated');

const onSubmit = async () => {
  try {
    const { data } = await axios.get(`/restart-password/${props.params.data['id']}`);
    if (data.status === 200) {
      onupdated(props.params.node, data.unit);
      selectedDataEdit.value = false;
      // init({ message: t('login.successMessage'), color: 'success' });
    } else {
      console.error('Error deleting data:', data.message);
    }
  } catch (error) {
    console.error('Error deleting data:', error);
  }
};
</script>