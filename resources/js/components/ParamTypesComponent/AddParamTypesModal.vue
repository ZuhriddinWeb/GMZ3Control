<template>
    <main>
      <div class="flex justify-between">
        <span class="flex w-full"></span>
        <VaButton @click="showModal = true" class="w-14 h-12 mt-1 mr-1" icon="add" />
      </div>
      <VaModal v-model="showModal" ok-text="Saqlash" cancel-text="Bekor qilish" @ok="onSubmit" close-button>
        <h3 class="va-h3">
          O'lchov birliklarini kiritish
        </h3>
        <div>
          <VaForm ref="formRef" class="flex flex-col items-baseline gap-2">
            <VaInput class="w-full" v-model="result.Name"
              :rules="[(value) => (value && value.length > 0) || 'To\'ldirish majburiy bo\'lgan maydon']"
              label="Nomlanishi" />
            <VaInput class="w-full" v-model="result.ShortName"
              :rules="[(value) => (value && value.length > 0) || 'To\'ldirish majburiy bo\'lgan maydon']"
              label="Qisqa nomi" />
            <VaTextarea class="w-full" v-model="result.Comment" max-length="125" label="Izoh" />
          </VaForm>
        </div>
      </VaModal>
    </main>
    
  </template>
  <script setup>
import { ref, reactive, onMounted, defineComponent } from 'vue';
import axios from 'axios';

const result = reactive({
  Name: "",
  ShortName: "",
  Comment: ""
});

  </script>