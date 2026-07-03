<script setup lang="ts">
defineProps<{
  negeri: { negeri: string; kod: string; kutipan: number; warna: string }[];
}>();
</script>

<template>
  <div class="grid grid-cols-5 gap-1 text-xs">
    <div
      v-for="n in negeri"
      :key="n.kod"
      class="flex flex-col items-center justify-center rounded border px-1 py-2 transition-colors hover:ring-2 hover:ring-slate-400"
      :class="{
        'bg-emerald-100 text-emerald-800 border-emerald-200': n.warna === 'hijau',
        'bg-amber-100 text-amber-800 border-amber-200': n.warna === 'kuning',
        'bg-rose-100 text-rose-800 border-rose-200': n.warna === 'merah',
      }"
      :title="`${n.negeri}: ${n.kutipan}%`"
    >
      <span class="font-medium">{{ n.kod }}</span>
      <span class="text-[10px]">{{ n.kutipan }}%</span>
    </div>
  </div>
</template>
