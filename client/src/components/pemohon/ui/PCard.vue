<script setup lang="ts">
withDefaults(defineProps<{ title?: string; subtitle?: string; padded?: boolean; tone?: "default" | "muted" | "ink" }>(), {
  padded: true,
  tone: "default",
});
</script>

<template>
  <section
    class="overflow-hidden rounded-2xl"
    :class="{
      'border border-slate-200/80 bg-white shadow-[0_1px_2px_rgba(16,24,40,0.04),0_4px_16px_-8px_rgba(16,24,40,0.08)]': tone === 'default',
      'border border-slate-200/70 bg-slate-50': tone === 'muted',
      'bg-ink-900 text-white shadow-lg shadow-ink-900/20': tone === 'ink',
    }"
  >
    <header v-if="title || $slots.header || $slots.actions" class="flex items-start justify-between gap-3 px-5 pt-5" :class="padded ? '' : 'pb-4'">
      <slot name="header">
        <div class="min-w-0">
          <h2 class="text-[15px] font-semibold" :class="tone === 'ink' ? 'text-white' : 'text-ink-900'">{{ title }}</h2>
          <p v-if="subtitle" class="mt-0.5 text-sm" :class="tone === 'ink' ? 'text-ink-200' : 'text-slate-500'">{{ subtitle }}</p>
        </div>
      </slot>
      <div v-if="$slots.actions" class="shrink-0"><slot name="actions" /></div>
    </header>
    <div :class="padded ? 'p-5' : ''">
      <slot />
    </div>
    <footer v-if="$slots.footer" class="border-t border-slate-100 bg-slate-50/60 px-5 py-3">
      <slot name="footer" />
    </footer>
  </section>
</template>
