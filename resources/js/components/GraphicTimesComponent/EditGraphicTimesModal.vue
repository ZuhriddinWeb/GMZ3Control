<template>
  <main class="h-full w-full text-center content-center">
    <VaButton round icon="edit" preset="primary" class="mt-1" @click="selectedDataEdit = true" />
    <VaModal v-model="selectedDataEdit" ok-text="Saqlash" cancel-text="Bekor qilish" @ok="onSubmit" close-button>
      <h3 class="va-h3">
       Grafik vaqtlarini tahrirlash
      </h3>
      <div>
        <VaForm ref="formRef" class="flex flex-col items-baseline gap-2">
          <div class="grid grid-cols-2 md:grid-cols-2  gap-2 items-end w-full">
            <VaSelect v-model="value" class="mb-6" label="Grafikni tanlang" :options="options" clearable />
            <VaSelect v-model="value" class="mb-6" label="Smenani tanlang" :options="options" clearable />
          </div>
          <div class="grid grid-cols-2 md:grid-cols-3 gap-3 items-end">
            <VaTimeInput clearable clearable-icon="cancel" color="textPrimary" label="Nomlanishi" v-model="result.Name" />
            <VaTimeInput clearable clearable-icon="cancel" color="textPrimary" label="Boshlanish vaqti"
              v-model="result.Name" />
            <VaTimeInput v-model="result.EndTime" clearable clearable-icon="cancel" color="textPrimary"
              label="Tugash vaqti" />
          </div>
          <VaTextarea class="w-full" v-model="result.Comment" max-length="125" label="Izoh" />
        </VaForm>
      </div>
    </VaModal>
  </main>
</template>
<script setup>
import { ref, reactive,inject } from 'vue';
import axios from 'axios';

const props = defineProps(["params"]);
const selectedDataEdit = ref(false);
const onupdated = inject('onupdated');
const result = reactive({
  Name: "",
  ShortName: "",
  Comment: "",
  id:props.params.data['id']
});

// axios.get(`units/${props.params.data['id']}`).then((res) => {
//   result.Name = res.data.Name
//   result.ShortName = res.data.ShortName
//   result.Comment = res.data.Comment
// })

const onSubmit = async () => {
  try {
    const { data } = await axios.put("/units", result);
    if (data.status === 200) {
      onupdated(props.params.node, data.unit);
      selectedDataEdit.value = false;
    } else {
      console.error('Error saving data:', data.message);
    }
  } catch (error) {
    console.error('Error saving data:', error);
  }
};


</script>