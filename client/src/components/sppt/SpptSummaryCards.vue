<script setup lang="ts">
defineProps<{
  items: { label: string; value: string | number; icon?: string; alert?: string }[];
}>();
</script>

<template>
  <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
    <article
      v-for="(item, i) in items"
      :key="i"
      class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm"
    >
      <p class="text-xs font-medium text-slate-500">{{ item.label }}</p>
      <p class="mt-1 text-xl font-semibold" :class="item.alert || 'text-slate-900'">{{ item.value }}</p>
    </article>
  </div>
</template>
