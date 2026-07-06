<script setup lang="ts">
withDefaults(
  defineProps<{
    required?: boolean;
  }>(),
  { required: false },
);
</script>

<template>
  <label class="mb-1 block text-xs font-medium text-slate-600">
    <slot />
    <span v-if="required" class="ml-0.5 text-red-500" aria-hidden="true">*</span>
  </label>
</template>
