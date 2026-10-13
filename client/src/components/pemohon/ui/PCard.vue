<script setup lang="ts">
withDefaults(defineProps<{ title?: string; subtitle?: string; padded?: boolean; tone?: "default" | "muted" | "ink" }>(), {
  padded: true,
  tone: "default",
});
</script>

<template>
  <section
    class="overflow-hidden rounded-3xl"
    :class="{
      'border border-slate-200 bg-white': tone === 'default',
      'bg-[#f3f6fc]': tone === 'muted',
      'bg-ink-900 text-white shadow-lg shadow-ink-900/20': tone === 'ink',
    }"
  >
    <header v-if="title || $slots.header || $slots.actions" class="flex items-start justify-between gap-3 px-5 pt-5" :class="padded ? '' : 'pb-4'">
      <slot name="header">
        <div class="min-w-0">
          <h2 class="text-[17px] font-semibold" :class="tone === 'ink' ? 'text-white' : 'text-ink-900'">{{ title }}</h2>
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
