<template>
    <div class="w-full h-screen">
        <p class="text-white">Orif Gilamfurush</p>
        <!-- The AG Grid component -->
        <ag-grid-vue :rowData="rowData" :columnDefs="colDefs"  class="ag-theme-material h-full"></ag-grid-vue>
    </div>
</template>

<script setup>
import { ref } from 'vue';
const rowData = ref([
   { make: "Tesla", model: "Model Y", price: 64950},
   { make: "Ford", model: "F-Series", price: 33850},
   { make: "Toyota", model: "Corolla", price: 29600},
 ]);

 // Column Definitions: Defines the columns to be displayed.
 const colDefs = ref([
   { field: "make",flex:1},
   { field: "model",flex:1},
   { field: "price",flex:1},
 ]);


</script>
