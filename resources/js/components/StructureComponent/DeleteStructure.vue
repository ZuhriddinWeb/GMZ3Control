<template>
  <main class="h-full w-full text-center content-center">
    <VaButton round icon="delete" preset="primary" class="mt-1" @click="selectedDataDelete = true" />
    <VaModal v-model="selectedDataDelete" ok-text="Apply" @close="selectedDataDelete = false"  @ok="onSubmit" close-button>
      <h3 class="va-h3">Ma'lumotni o'chirish</h3>
      <p>
        Classic modal overlay which represents a dialog box or other interactive
        component, such as a dismissible alert, sub-window, etc.
      </p>
    </VaModal>
  </main>
</template>

<script setup>
import { ref, onMounted , inject} from 'vue'
import axios from 'axios';

const selectedDataDelete = ref(false)
const  props = defineProps(["params"]);

const ondeleted = inject('ondeleted')

const onSubmit = async () => {
  console.log(props.params.data['id']);
  try {
    const { data } = await axios.delete(`/structure/${props.params.data['id']}`);
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