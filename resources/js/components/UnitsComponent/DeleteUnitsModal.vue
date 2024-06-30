<template>
    <VaModal v-if="selectedDataDelete" v-model="getDeleteRow" ok-text="Apply" @close="handleClose">
      <h3 class="va-h3">Title</h3>
      <p>
        Classic modal overlay which represents a dialog box or other interactive
        component, such as a dismissible alert, sub-window, etc.
      </p>
    </VaModal>
  </template>
  
  <script setup>
  import { ref, watch, defineProps, defineEmits } from 'vue';
  const getDeleteRow = ref(false);
  
  const { selectedDataDelete } = defineProps(['selectedDataDelete']);
  const emit = defineEmits(['close']);
  
  watch(() => selectedDataDelete, (newVal) => {
    getDeleteRow.value = newVal;
  });
  
  const handleClose = () => {
    emit('close');
  };
  </script>
  