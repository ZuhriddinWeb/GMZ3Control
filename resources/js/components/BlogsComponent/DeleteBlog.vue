<template>
  <main class="h-full w-full text-center content-center">
    <VaButton round icon="delete" preset="primary" class="mt-1" @click="selectedDataDelete = true" />
    <VaModal 
      v-model="selectedDataDelete" 
      :ok-text="t('modals.apply')" 
      :cancel-text="t('modals.cancel')" 
      @close="selectedDataDelete = false"  
      @ok="onSubmit" 
      close-button
    >
      <h3 class="va-h3">{{ t("modals.title") }}</h3>
      <p>
        {{ t('modals.message') }}
      </p>
    </VaModal>
  </main>
</template>

<script setup>
import { ref, onMounted , inject} from 'vue'
import axios from 'axios';
import { useI18n } from 'vue-i18n';

const { t } = useI18n(); 

const selectedDataDelete = ref(false)
const  props = defineProps(["params"]);

const ondeleted = inject('ondeleted')

const onSubmit = async () => {
  console.log(props.params.data['id']);
  try {
    const { data } = await axios.delete(`/blogs/${props.params.data['id']}`);
    if (data.status === 200) {
      ondeleted(props.params.data)
      selectedDataDelete.value = false;
    } else {
      console.error('Error deleting data:', data.message);
    }
  } catch (error) {
    console.error('Error deleting data:', error);
  }
};
</script>