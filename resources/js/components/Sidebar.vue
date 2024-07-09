<template>
  <VaLayout
    :top="{ fixed: true, order: 1 }"
    :left="{ 
      fixed: true, 
      absolute: breakpoints.smDown, 
      order: 2, 
      overlay: breakpoints.smDown && isSidebarVisible,
      class: 'custom-sidebar'  // Add a custom class for styling
    }"
    @left-overlay-click="isSidebarVisible = false"
  >
    <template #top>
      <VaNavbar>
        <template #left>
          <VaButton
            preset="secondary"
            :icon="isSidebarVisible ? 'menu_open' : 'menu'"
            @click="isSidebarVisible = !isSidebarVisible"
          />
        </template>
      </VaNavbar>
      <VaDivider style="margin: 0" />
    </template>

    <template #left>
      <VaSidebar v-model="isSidebarVisible" class="custom-sidebar">
        <template v-for="menuItem in menu" :key="menuItem.icon">
          <router-link :to="menuItem.path" class="router-link">
            <VaSidebarItem>
              <VaSidebarItemContent>
                <VaIcon :name="menuItem.icon" />
                <VaSidebarItemTitle>
                  {{ menuItem.title }}
                </VaSidebarItemTitle>
              </VaSidebarItemContent>
            </VaSidebarItem>
          </router-link>
        </template>
      </VaSidebar>
    </template>

    <template #content>
      <main>
        <article>
          <slot />
        </article>
      </main>
    </template>
  </VaLayout>
</template>

<script setup>
  import { ref, watchEffect } from 'vue'
  import { useBreakpoint } from 'vuestic-ui'

  const breakpoints = useBreakpoint()

  const isSidebarVisible = ref(breakpoints.smUp)

  watchEffect(() => {
    isSidebarVisible.value = breakpoints.smUp
  })

  const menu = [
    { title: 'Dashboard', icon: 'dashboard', path: '/' },
    { title: 'Birliklar', icon: 'ad_units', path: '/units' },
    { title: 'Grafiklar', icon: 'schedule', path: '/graphics' },
    { title: 'Grafik vaqtlari', icon: 'alarm', path: '/graphictimes' },
    { title: 'Parameter turlari', icon: 'format_list_numbered', path: '/paramtypes' },
    { title: 'Parameterlar', icon: 'format_list_bulleted', path: '/params' },
    { title: 'Manbalar', icon: 'content_copy', path: '/sources' },
    { title: 'Smenalar', icon: 'content_copy', path: '/changes' },
  ]
</script>

<style >
.custom-sidebar {
  background-color: #57534e; /* Example background color */
}
</style>
