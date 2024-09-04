<template>
  <main class="h-full w-full text-center content-center">
    <VaButton round icon="edit" preset="primary" class="mt-1" @click="selectedDataEdit = true, fetchGraphics" />
    <VaModal v-model="selectedDataEdit" :ok-text="t('modals.apply')" :cancel-text="t('modals.cancel')" @ok="onSubmit"
      @close="selectedDataEdit = false" close-button>
      <h3 class="va-h3"@vue:mounted="fetchGraphics">
        {{ t('modals.editFactory') }}
      </h3>
      <div>
        <VaForm ref="formRef" class="flex flex-col items-baseline gap-1">
          <div class="grid grid-cols-1 md:grid-cols-1 gap-2 items-end w-full">
            <VaSelect v-model="result.StructureID" value-by="value" class="mb-1" :label="t('form.structureName')"
              :options="factoryOptions" clearable  />
          </div>
          <VaInput class="w-full" v-model="result.Name" :rules="[(value) => !!value || t('validation.requiredField')]"
            :label="t('form.name')" />
          <VaInput class="w-full" v-model="result.NameRus" :rules="[(value) => !!value || t('validation.requiredField')]"
            :label="t('form.nameRus')" />
          <VaInput class="w-full" v-model="result.ShortName" :rules="[(value) => !!value || t('validation.requiredField')]"
            :label="t('form.shortName')" />
          <VaInput class="w-full" v-model="result.ShortNameRus"
            :rules="[(value) => !!value || t('validation.requiredField')]" :label="t('form.shortNameRus')" />
          <VaTextarea class="w-full" v-model="result.Comment" :max-length="125" :label="t('form.comment')" />
        </VaForm>
      </div>
    </VaModal>
  </main>
</template>

<script setup>
import { ref, reactive, inject, onMounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { useForm, useToast, VaValue, VaInput, VaButton, VaForm, VaIcon } from 'vuestic-ui';
const { init } = useToast();

const props = defineProps(["params"]);
const selectedDataEdit = ref(false);
const onupdated = inject('onupdated');
const factoryOptions = ref([]);

const { t, locale } = useI18n();

const result = reactive({
  StructureID: "",
  Name: "",
  NameRus: "",
  ShortNameRus: "",
  ShortName: "",
  Comment: "",
  id: props.params.data['id'],
});

const fetchGraphics = async () => {
  try {
    const responseGraphics = await axios.get('/structure');
    factoryOptions.value = responseGraphics.data.map(factory => ({
      value: factory.id,
      text: locale.value === 'uz' ? factory.Name : factory.NameRus
    }));

    const response = await axios.get(`blogs/${props.params.data['id']}`);
    result.StructureID = +response.data.StructureID;
    result.Name = response.data.Name;
    result.ShortName = response.data.ShortName;
    result.Comment = response.data.Comment;
  } catch (error) {
    console.error('Error fetching graphics data:', error);
  }
};


const onSubmit = async () => {
  try {
    const { data } = await axios.put("/blogs", result);
    if (data.status === 200) {
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

watch(() => result.StructureID, (newVal) => {
  // console.log('Selected StructureID:', newVal);
});
</script>
