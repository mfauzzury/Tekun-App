<script setup lang="ts">
import { ref, type Component } from "vue";
import { ChevronDown } from "lucide-vue-next";

const props = withDefaults(
  defineProps<{
    title: string;
    subtitle?: string;
    icon?: Component;
    iconClass?: string;
    collapsible?: boolean;
    defaultOpen?: boolean;
  }>(),
  { subtitle: "", icon: undefined, iconClass: "text-slate-500", collapsible: true, defaultOpen: true },
);

const open = ref(props.defaultOpen);

function toggle() {
  if (props.collapsible) open.value = !open.value;
}
</script>

<template>
  <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
    <header
      class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5"
      :class="[collapsible ? 'cursor-pointer select-none hover:bg-slate-50/60' : '', !open ? 'border-b-0' : '']"
      @click="toggle"
    >
      <component :is="icon" v-if="icon" class="h-4 w-4 shrink-0" :class="iconClass" />
      <div class="min-w-0">
        <h3 class="truncate text-sm font-semibold text-slate-900">{{ title }}</h3>
        <p v-if="subtitle" class="truncate text-[11px] text-slate-500">{{ subtitle }}</p>
      </div>
      <div class="ml-auto flex shrink-0 items-center gap-2" @click.stop>
        <slot name="actions" />
      </div>
      <ChevronDown
        v-if="collapsible"
        class="h-4 w-4 shrink-0 text-slate-400 transition-transform"
        :class="open ? 'rotate-180' : ''"
      />
    </header>
    <div v-show="open" class="p-4">
      <slot />
    </div>
  </article>
</template>
