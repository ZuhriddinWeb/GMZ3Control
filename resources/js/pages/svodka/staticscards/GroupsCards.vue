<template>
  <div class="grid grid-rows-[55px,1fr]">
    <!-- <div class="flex items-center gap-3 mb-4">
      <VaButton preset="secondary" @click="$router.back()">← Orqaga</VaButton>
      <span class="text-gray-500">Sahifa: {{ page }}</span>
    </div> -->

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 my-6 shadow-sm border border-slate-200 mt-24 p-4">
      <div v-for="g in groups" :key="g.id"
           class="p-4 border-2 bg-white shadow-lg border-slate-400"
           :style="{ backgroundImage: `url('/bgCard.png')`, backgroundSize: 'cover', backgroundPosition: 'center' }">
         <h5 class="mb-2 text-slate-800 text-xl font-semibold flex items-start">
          <span class="material-symbols-outlined w-1">circles_ext</span>
          <span class="flex-grow leading-none w-5/6">{{ locale === 'ru' ? g.Name : g.NameRus }}</span>
        </h5>
        <VaButton preset="primary" round @click="openGroup(g.id)">
          Parametrlar
        </VaButton>
      </div>

      <!-- Guruhsiz (no group) tugmasi -->
      <div class="p-4 border bg-white shadow">
        <h5 class="mb-2 text-slate-800 text-lg font-semibold">Guruhsiz</h5>
        <VaButton preset="primary" round @click="openGroup(null)">
          Parametrlar
        </VaButton>
      </div>
    </div>
  </div>
</template>


<script setup>
import { onMounted, ref } from 'vue'
import axios from 'axios'
import { useRouter } from 'vue-router'

const props = defineProps({ page: { type: Number, required: true } })
const groups = ref([])
const router = useRouter()

onMounted(async () => {
    const { data } = await axios.get(`/getRowGroupWith/${props.page}`)
    groups.value = Array.isArray(data) ? data : []
})

function openGroup(groupId) {
  const id = String(props.page) // NumberPage

  const to = groupId == null
    ? { name: 'static', params: { id } }                       // /static/:id
    : { name: 'staticByGroup', params: { id, groupId: String(groupId) } } // /static/:id/group/:groupId

  const match = router.resolve(to)
  if (!match.matched.length) {
    // Fallback: path orqali
    const path = groupId == null ? `/static/${id}` : `/static/${id}/group/${groupId}`
    router.push(path)
  } else {
    router.push(to)
  }
}


</script>
