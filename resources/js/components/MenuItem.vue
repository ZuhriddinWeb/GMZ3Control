<template>
    <div>
      <div
        :class="[
          'menu-item',
          { 'has-children': menuItem.children, 'active': isActive },
          menuItem.path === '/logout' ? 'hover-logout' : 'hover-item'
        ]"
        @click="handleClick"
      >
        <VaIcon :name="menuItem.icon" class="icon" />
        {{ t(menuItem.title) }}
        <span v-if="menuItem.children" class="toggle-icon">
          <VaIcon :name="showChildren ? 'expand_less' : 'expand_more'" />
        </span>
      </div>
      <div v-if="menuItem.children && showChildren" class="sub-menu">
        <MenuItem
          v-for="child in menuItem.children"
          :key="child.title"
          :menuItem="child"
        />
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, computed } from 'vue';
  import { useI18n } from 'vue-i18n';
  import { useRouter, useRoute } from 'vue-router'; // Import useRouter and useRoute
  
  const props = defineProps({
    menuItem: Object
  });
  
  const { t } = useI18n();
  const router = useRouter(); // Initialize router
  const route = useRoute(); // Initialize route
  const showChildren = ref(false);
  
  // Check if the current route matches the menu item path
  const isActive = computed(() => route.path === props.menuItem.path);
  
  const handleClick = () => {
    if (props.menuItem.path) {
      router.push(props.menuItem.path);
    } else if (props.menuItem.children) {
      showChildren.value = !showChildren.value;
    }
  };
  </script>
  
  <style scoped>
  .menu-item {
    display: flex;
    align-items: center;
    cursor: pointer;
    padding: 1rem;
    /* color: white;
    transition: background-color 0.3s; */
  }
  
  .menu-item.active {
    background-color: #154EC1; /* Highlight color for active item */
    color:white;
  }
  
  .menu-item:hover {
    background-color: #333; /* Hover background color */
  }
  
  .menu-item .icon {
    margin-right: 0.5rem;
  }
  
  .sub-menu {
    padding-left: 1rem;
  }
  
  .toggle-icon {
    margin-left: auto;
  }
  
  .hover-item {
    /* Default hover styles for items */
  }
  .hover-item:hover {
    background-color: #154EC1; /* Highlight color for item hover */
    color: white; /* Text color on hover */
  }
  
  .hover-logout {
    /* Default styles for logout item */
  }
  .hover-logout:hover {
    background-color: rgb(220, 38, 38); /* Highlight color for logout hover */
    color: white; /* Text color on hover */
  }
  </style>
  