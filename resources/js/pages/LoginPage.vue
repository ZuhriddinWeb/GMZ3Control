<template>
    <main class="flex justify-between fixed inset-0">
      <div class="bg-[#154EC1] flex items-center justify-center text-center min-h-screen w-3/6">
        <div class="flex flex-col items-center">
          <div class="flex flex-col items-center">
            <div class="mb-4">
              <img src="../../../public/ngmk.png" alt="" class="w-25 h-32">
            </div>
            <span class="text-white text-5xl">{{ t('login.titlePage') }}</span>
          </div>
          <p class="text-white text-5xl mt-4">{{ t('login.plantName') }}</p>
        </div>
      </div>
      <div class="flex justify-center items-center text-center w-4/6 min-h-screen mx-auto">
        <VaForm ref="form" @submit.prevent="onSubmit" class="flex flex-col items-center">
          <h1 class="font-semibold text-4xl mb-4">{{ t('login.title') }}</h1>
          <p class="text-base mb-4 leading-5"></p>
          <VaInput v-model="result.login" :rules="[(value) => (value && value.length > 0) || t('form.requiredField')]" class="mb-4 w-1/2" :label="t('form.login')" type="text" />
          <VaValue v-slot="isPasswordVisible" :default-value="false">
            <VaInput v-model="result.password" :rules="[(value) => (value && value.length > 0) || t('form.requiredField')]" :type="isPasswordVisible.value ? 'text' : 'password'"
              class="mb-4 w-1/2" :label="t('form.password')"
              @clickAppendInner.stop="isPasswordVisible.value = !isPasswordVisible.value">
              <template #appendInner>
                <VaIcon :name="isPasswordVisible.value ? 'mso-visibility_off' : 'mso-visibility'"
                  class="cursor-pointer" color="secondary" />
              </template>
            </VaInput>
          </VaValue>
          <div class="flex justify-center mt-4 w-1/2">
            <VaButton class="w-full" @click="onSubmit">{{ t('login.submitButton') }}</VaButton>
          </div>
        </VaForm>
      </div>
    </main>
  </template>
  

  <script lang="ts" setup>
  import { reactive,onMounted } from 'vue';
  import { useRouter } from 'vue-router';
  import { useForm, useToast, VaValue, VaInput, VaButton, VaForm, VaIcon } from 'vuestic-ui';
  import axios from 'axios';
  import store from '../store';
  import { useI18n } from 'vue-i18n';
  
  const { validate } = useForm('form');
  const { push } = useRouter();
  const { init } = useToast();
  const { locale, t } = useI18n();
  
  const result = reactive({
    login: '',
    password: '',
  });
  
  const onSubmit = async () => {
    try {
      const { data } = await store.dispatch('login', result);
      if (data.status === 200) {
        push({ name: 'home' });
      } else {
        console.error('Error saving data:', data.message);
      }
    } catch (error) {
      init({ message: t('login.errorMessage'), color: 'danger' });
    }
  };
  

  onMounted(() => {
    const savedLocale = localStorage.getItem('locale');
    if (savedLocale) {
      locale.value = savedLocale;
    }
  });
  </script>
  