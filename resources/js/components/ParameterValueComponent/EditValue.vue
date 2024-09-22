<template>
  <main class="h-full w-full text-center content-center">
    <VaModal v-model="showModalEdit" ok-text="Saqlash" cancel-text="Bekor qilish" @ok="onSubmitEdit(currentRowNode)" close-button>
        <h3 class="va-h3">
          Qiymatni tahrirlash
        </h3>
        <div>
          <VaForm ref="formRef" class="flex flex-col items-baseline gap-2">
            <VaInput class="w-full" v-model="resultEdit.Value"
              :rules="[(value) => (value && value.length > 0) || 'To\'ldirish majburiy bo\'lgan maydon']"
              label="Qiymat" />

            <VaTextarea class="w-full" v-model="resultEdit.Comment" max-length="125" label="Izoh" />
          </VaForm>
        </div>
      </VaModal>
  </main>
</template>

<script setup>
import { ref, reactive, inject, onMounted } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import { useForm, useToast, VaValue, VaInput, VaButton, VaForm, VaIcon } from 'vuestic-ui';
const { init } = useToast();

const props = defineProps(["params"]);
const selectedDataEdit = ref(false);
const onupdated = inject('onupdated');
const { t } = useI18n();

const result = reactive({
  id: "",
  Comment: "",
  Value: "",
  userId: store.state.user.id
});
// Fetch unit data on mount
// onMounted(() => {
//   axios.get(`/vparams/${props.params.data['id']}`)
//     .then((res) => {
//       result.NameRus = res.data.NameRus;
//       result.Name = res.data.Name;
//       result.ShortName = res.data.ShortName;
//       result.ShortNameRus = res.data.ShortNameRus;
//       result.Comment = res.data.Comment;
//     })
//     .catch((error) => {
//       console.error('Error fetching data:', error);
//       // Optionally, you can show an error notification here.
//     });
// });

const onSubmitEdit = async (rowNode) => {
  try {
    const { data } = await axios.post("/vparamsEdit", resultEdit);
    if (data.status === 200) {
      showModal.value = false;

      resultEdit.id = "";
      resultEdit.Comment = '';
      resultEdit.Value = '';
      resultEdit.userId = '';

      onupdated(props.params.node, data.unit);
      selectedDataEdit.value = false;
      init({ message: t('login.successMessage'), color: 'success' });
    } else {
      console.error('Error saving data:', data.message);
    }
  } catch (error) {
    console.error('Error saving data:', error);
  }
};

const onSubmit = async () => {
  try {
    const { data } = await axios.put("/units", result);
    if (data.status === 200) {
      onupdated(props.params.node, data.unit);
      selectedDataEdit.value = false;
      init({ message: t('login.successMessage'), color: 'success' });

    } else {
      console.error('Error saving data:', data.message);
      // Optionally, you can show an error notification here.
    }
  } catch (error) {
    console.error('Error saving data:', error);
    // Optionally, you can show an error notification here.
  }
};
</script>
