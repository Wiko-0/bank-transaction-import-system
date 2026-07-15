<template>
  <button
    :type="type"
    :disabled="disabled"
    class="px-5 py-2 bg-black text-white font-bold rounded-none hover:bg-gray-800 disabled:bg-gray-200 disabled:text-gray-400 disabled:cursor-not-allowed transition-colors border border-black whitespace-nowrap"
  >
    <slot />
  </button>
</template>

<script setup>
defineProps({
  type: {
    type: String,
    default: 'button'
  },
  disabled: {
    type: Boolean,
    default: false
  }
});
</script>