<template>
    <VaButton @click="showModal = true">
      Show modal with close icon
    </VaButton>
    
  </template>
  <script>
  export default {
    data() {
      return {
        showModal: false,
      };
    },
  };
  </script>