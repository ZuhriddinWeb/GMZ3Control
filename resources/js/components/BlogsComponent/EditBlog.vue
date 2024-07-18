<template>
  <main class="h-full w-full text-center content-center">
    <VaButton round icon="edit" preset="primary" class="mt-1" @click="selectedDataEdit = true" />
    <VaModal v-model="selectedDataEdit" ok-text="Saqlash" cancel-text="Bekor qilish" @ok="onSubmit" @close="selectedDataEdit = false" close-button>
      <h3 class="va-h3">
        Uchastka ma'lumotlarini tahrirlash 
      </h3>
      <div>
        <VaForm ref="formRef" class="flex flex-col items-baseline gap-1">
            <div class="grid grid-cols-1 md:grid-cols-1 gap-2 items-end w-full">
              <VaSelect v-model="result.StructureID" class="mb-1" label="Tuzilma nomi":options="factoryOptions"
                clearable @change="onSelectChange" />               
            </div>
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
import { ref, reactive,inject } from 'vue';
import axios from 'axios';

const props = defineProps(["params"]);
const selectedDataEdit = ref(false);
const onupdated = inject('onupdated');
const result = reactive({
  StructureID:"",
  Name: "",
  ShortName: "",
  Comment: "",
  id:props.params.data['id']
});

axios.get(`blogs/${props.params.data['id']}`).then((res) => {
  result.Name = res.data.Name
  result.ShortName = res.data.ShortName
  result.Comment = res.data.Comment
})

const onSubmit = async () => {
  try {
    const { data } = await axios.put("/blogs", result);
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