<template>
    <VaTreeView 
      :nodes="nodes"
      @update:selected="handleNodeClick"
    />
  </template>
  
  <script>
  import { defineComponent } from 'vue';
  import { VaTreeView } from 'vuestic-ui';
  
  export default defineComponent({
    name: 'CustomTreeView',
    components: {
      VaTreeView
    },
    props: {
      nodes: {
        type: Array,
        required: true
      }
    },
    emits: ['node-clicked'],
    methods: {
      handleNodeClick(selectedNodes) {
        console.log('Selected nodes in CustomTreeView:', selectedNodes.id); // Debug log
        if (selectedNodes.length > 0) {
          const node = selectedNodes[0]; // Assuming single selection, use first selected node
          console.log('Node clicked in CustomTreeView:', node); // Debug log
          this.$emit('node-clicked', node);
        }
      }
    }
  });
  </script>
  