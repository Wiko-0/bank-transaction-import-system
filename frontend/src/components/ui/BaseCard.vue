<template>
  <component 
    :is="tag" 
    class="bg-white p-6 border-2 border-black rounded-none"
  >
    <slot />
  </component>
</template>

<script setup>
defineProps({
  tag: {
    type: String,
    default: 'div'
  }
});
</script>