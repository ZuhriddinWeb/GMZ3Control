<template>
  <main class="h-full w-full text-center content-center">
    <VaButton round icon="edit" preset="primary" class="mt-1" @click="selectedDataEdit = true" />
    <VaModal v-model="selectedDataEdit" ok-text="Saqlash" cancel-text="Bekor qilish" @ok="onSubmit"
      @close="selectedDataEdit = false" close-button>
      <h3 class="va-h3">
        Parametr turlarini tahrirlash
      </h3>
      <div>
        <VaForm ref="formRef" class="flex flex-col items-baseline gap-2">
          <VaInput class="w-full" v-model="result.Name"
            :rules="[(value) => (value && value.length > 0) || 'To\'ldirish majburiy bo\'lgan maydon']"
            label="Nomlanishi" />
          <VaInput class="w-full" v-model="result.NameRus"
            :rules="[(value) => (value && value.length > 0) || 'To\'ldirish majburiy bo\'lgan maydon']"
            label="Nomlanishi Rus" />
          <VaInput class="w-full" v-model="result.ShortName"
            :rules="[(value) => (value && value.length > 0) || 'To\'ldirish majburiy bo\'lgan maydon']"
            label="Qisqa nomi" />
          <VaInput class="w-full" v-model="result.ShortNameRus"
            :rules="[(value) => (value && value.length > 0) || 'To\'ldirish majburiy bo\'lgan maydon']"
            label="Qisqa nomi Rus" />
          <div class="grid grid-cols-2 md:grid-cols-2 gap-2 items-end w-full">
            <VaInput class="w-full" v-model="result.Min"
              :rules="[(value) => (value && value.length > 0) || 'To\'ldirish majburiy bo\'lgan maydon']" label="Min" />
            <VaInput class="w-full" v-model="result.Max"
              :rules="[(value) => (value && value.length > 0) || 'To\'ldirish majburiy bo\'lgan maydon']" label="Max" />
          </div>
          <div class="grid grid-cols-2 md:grid-cols-2 gap-2 items-end w-full">
            <VaSelect v-model="result.ParamsTypeID" class="mb-6" label="Parametr turini tanlang" :options="paramsOptions"
              clearable />
            <VaSelect v-model="result.UnitsID" class="mb-6" label="Birlik qiymatini tanlang" :options="unitsOptions"
              clearable />
          </div>
          <VaTextarea class="w-full" v-model="result.Comment" max-length="125" label="Izoh" />
        </VaForm>
      </div>
    </VaModal>
  </main>
</template>
<script setup>
import { ref, reactive, inject } from 'vue';
import axios from 'axios';

const props = defineProps(["params"]);
const selectedDataEdit = ref(false);
const onupdated = inject('onupdated');
const paramsOptions = ref([]);
const unitsOptions = ref([]);

const result = reactive({
  Name: "",
  NameRus: "",
  ShortNameRus: "",
  ShortName: "",
  ParamsTypeID: "",
  UnitsID: "",
  Comment: "",
  id: props.params.data['Uuid']
});

const fetchParams = async () => {
  try {
    const responseGraphics = await axios.get('/paramtypes');
    const responseChanges = await axios.get('/units');
    paramsOptions.value = responseGraphics.data.map(graphic => ({
      value: graphic.id,
      text: graphic.Name
    }));
    unitsOptions.value = responseChanges.data.map(change => ({
      value: change.id,
      text: change.Name
    }));
    axios.get(`param/${props.params.data['Uuid']}`).then((res) => {
      console.log(res.data);
      result.Name = res.data.Name
      result.Comment = res.data.Comment
    })
  } catch (error) {
    console.error('Error fetching graphics data:', error);
  }
};
const onSubmit = async () => {
  try {
    const { data } = await axios.put("/param", result);
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
fetchParams()

</script>