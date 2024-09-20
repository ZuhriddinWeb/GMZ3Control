<template>
  <VaModal v-model="showModalEdit" ok-text="Save" cancel-text="Cancel" @ok="onSubmitEdit" close-button>
    <h3>Edit Value</h3>
    <VaForm ref="formRef" class="flex flex-col">
      <VaInput v-model="result.Value" label="Value" />
      <VaInput v-model="result.Comment" label="Comment" />
      <!-- Add any additional fields you need here -->
    </VaForm>
  </VaModal>
</template>

<script setup>
import { ref, reactive, inject,onMounted } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { useToast } from 'vuestic-ui';

const { init } = useToast();
const props = defineProps(['params']);
const showModalEdit = ref(false); // Make sure you define this
// const onupdated = inject('onupdated');
const { t } = useI18n();

const result = reactive({
  id: "",
  Comment: "",
  Value: "",
  userId: "" // Ensure you get the user ID from the correct source
});

// Fetch data when the modal is opened
const fetchData = async () => {
  try {
    const res = await axios.get(`/vparams/${props.params.data.id}`);
    Object.assign(result, res.data); // Use Object.assign to update the reactive object
  } catch (error) {
    console.error('Error fetching data:', error);
    init({ message: 'Error fetching data', color: 'danger' });
  }
};

// Call fetchData when the modal is opened
onMounted(() => {
  if (props.params) {
    fetchData();
  }
});

const onSubmitEdit = async () => {
  try {
    const { data } = await axios.post("/vparamsEdit", result);
    if (data.status === 200) {
      showModalEdit.value = false;

      // Clear the result object
      result.id = "";
      result.Comment = '';
      result.Value = '';
      result.userId = '';

      // onupdated(props.params.node, data.unit);
      // init({ message: t('login.successMessage'), color: 'success' });
    } else {
      console.error('Error saving data:', data.message);
    }
  } catch (error) {
    console.error('Error saving data:', error);
    init({ message: 'Error saving data', color: 'danger' });
  }
};
</script>
