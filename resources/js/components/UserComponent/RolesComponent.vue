<template>
  <main class="h-full w-full text-center content-center">
    <VaButton round icon="settings" preset="primary" class="mt-1" @click="selectedDataEdit = true" />
    <VaModal v-model="selectedDataEdit" ok-text="Saqlash" cancel-text="Bekor qilish" @ok="onSubmit"
      @close="selectedDataEdit = false" close-button>
      <h3 class="va-h3">
        Foydalanuvchi parametrlari
      </h3>
      <div>
        <div class="va-table-responsive">
          <table class="va-table va-table--clickable w-full">
            <thead>
              <tr>
                <th>Nomi</th>
                <th>Ko'rish</th>
                <th>Yaratish</th>
                <th>O'zgartirish</th>
                <th>O'chirish</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="role in roles" :key="role.id">
                <td>{{ role.name }}</td>
                <td>
                  <VaSwitch  @change="onChange" :checked="checked"  color="success" />
                </td>
                <td>
                  <VaSwitch v-model="checked" color="success" />
                </td>
                <td>
                  <VaSwitch v-model="checked" color="success" />
                </td>
                <td>
                  <VaSwitch v-model="checked" color="success" />
                </td>            
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </VaModal>
  </main>
</template>
<script setup>
import { ref, reactive, inject, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps(["params"]);
const selectedDataEdit = ref(false);
const onupdated = inject('onupdated');
const roles = ref([]);
const checked = ref(false);

const result = reactive({
  Name: "",
  ShortName: "",
  Comment: "",
  id: props.params.data['id']
});
const  onChange= () =>{
  checked.value = !checked.value;
  console.log(checked.value);
}
const fetchData = async () => {
  try {
    const response = await axios.get('/role');
    roles.value = Array.isArray(response.data) ? response.data : response.data.items;
  } catch (error) {
    console.error('Error fetching data:', error);
  }
};

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
onMounted(() => {
  fetchData()
});

</script>