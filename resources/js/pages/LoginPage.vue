<template>
    <main class="flex justify-between fixed inset-0">
        <div class="bg-[#154EC1] flex items-center justify-center text-center min-h-screen w-3/6">
            <div class="flex flex-col items-center">
                <div class="flex flex-col items-center">
                    <div class="mb-4">
                        <img src="../../../public/ngmk.png" alt="" class="w-25 h-32">
                    </div>
                    <span class="text-white text-5xl">"NKMK" AJ</span>
                </div>
                <p class="text-white text-5xl mt-4">3-Gidrometallurgiya zavodi</p>
            </div>
        </div>

        <div class="flex justify-center items-center text-center w-4/6 min-h-screen mx-auto">
            <VaForm ref="form" @submit.prevent="onSubmit" class="flex flex-col items-center">
                <h1 class="font-semibold text-4xl mb-4">Kirish</h1>
                <p class="text-base mb-4 leading-5"></p>
                <VaInput v-model="result.login" :rules="[(value) => (value && value.length > 0) || 'To\'ldirish majburiy bo\'lgan maydon']" class="mb-4 w-1/2" label="Login" type="text" />
                <VaValue v-slot="isPasswordVisible" :default-value="false">
                    <VaInput v-model="result.password" :rules="[(value) => (value && value.length > 0) || 'To\'ldirish majburiy bo\'lgan maydon']" :type="isPasswordVisible.value ? 'text' : 'password'"
                        class="mb-4 w-1/2" label="Parol"
                        @clickAppendInner.stop="isPasswordVisible.value = !isPasswordVisible.value">
                        <template #appendInner>
                            <VaIcon :name="isPasswordVisible.value ? 'mso-visibility_off' : 'mso-visibility'"
                                class="cursor-pointer" color="secondary" />
                        </template>
                    </VaInput>
                </VaValue>
                <div class="flex justify-center mt-4 w-1/2">
                    <VaButton class="w-full" @click="onSubmit">Kirish</VaButton>
                </div>
            </VaForm>
        </div>

    </main>

</template>

<script lang="ts" setup>
import { reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useForm, useToast } from 'vuestic-ui'
import axios from 'axios';
import { storeKey } from "vuex";
import store from '../store'

const { validate } = useForm('form')
const { push } = useRouter()
const { init } = useToast()

const result = reactive({
    login: '',
    password: '',
})
const onSubmit = async () => {
    try {
    const { data } = await store.dispatch('login', result);
    if (data.status === 200) {
        push({ name: 'home' })
    } else {
      console.error('Error saving data:', data.message);
    }
  } catch (error) {
    init({ message: "Login yoki parol xato", color: 'danger' })
    push({ name: 'home' })
  }
    // await store.dispatch('login', result);
    // if (validate()) {
    //     init({ message: "123", color: 'success' })
    //     push({ name: 'home' })
    // }
    // else{
    //     init({ message: "Login yoki parol xato", color: 'danger' })
    //     push({ name: 'home' })
    // }
};

</script>