<script setup lang="ts">
defineProps<{ label?: string; hint?: string; error?: string | null; required?: boolean; for?: string }>();
</script>

<template>
  <div class="space-y-1.5">
    <label v-if="label" :for="$props.for" class="block text-sm font-medium text-ink-800">
      {{ label }}<span v-if="required" class="ml-0.5 text-brand-600">*</span>
    </label>
    <slot />
    <p v-if="error" class="text-xs font-medium text-rose-600">{{ error }}</p>
    <p v-else-if="hint" class="text-xs text-slate-500">{{ hint }}</p>
  </div>
</template>
