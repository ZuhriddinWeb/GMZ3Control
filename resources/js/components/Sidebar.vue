<template>
  <div class="h-screen">
    <VaSidebar color="#1c1917">
      <VaAccordion>
        <template v-for="item in items" :key="item.title">
          <VaCollapse v-if="item.children" :key="item.title + 'collapse'" body-color="#00000022">
          </VaCollapse>
          <router-link v-else :to="item.path" class="router-link">
            <VaSidebarItem :key="item.title + 'item'" :active="item.title === activeElement"
              @click="activeElement = item.title">
              <VaSidebarItemContent>
                <VaIcon :name="item.icon" />
                <VaSidebarItemTitle>{{ item.title }}</VaSidebarItemTitle>
              </VaSidebarItemContent>
            </VaSidebarItem>
          </router-link>
        </template>
      </VaAccordion>

      <VaSpacer />

      <router-link to="/settings" class="router-link">
        <VaSidebarItem :active="'Settings' === activeElement" @click="activeElement = 'Settings'">
          <VaSidebarItemContent>
            <VaIcon name="settings" />
            <VaSidebarItemTitle>Settings</VaSidebarItemTitle>
          </VaSidebarItemContent>
        </VaSidebarItem>
      </router-link>
    </VaSidebar>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const activeElement = ref('Address book')

const items = [
  { title: 'Dashboard', icon: 'dashboard', path: '/' },
  { title: 'Units', icon: 'room', path: '/units' },
]
</script>

<style>
.router-link {
  text-decoration: none;
  color: inherit;
}
</style>
