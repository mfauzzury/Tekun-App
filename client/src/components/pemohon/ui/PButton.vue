<script setup lang="ts">
import { computed } from "vue";
import { Loader2 } from "lucide-vue-next";
import type { RouteLocationRaw } from "vue-router";

const props = withDefaults(
  defineProps<{
    variant?: "primary" | "secondary" | "outline" | "ghost" | "danger" | "ink";
    size?: "sm" | "md" | "lg";
    loading?: boolean;
    disabled?: boolean;
    block?: boolean;
    to?: RouteLocationRaw;
    type?: "button" | "submit";
  }>(),
  { variant: "primary", size: "md", loading: false, disabled: false, block: false, type: "button" },
);

const classes = computed(() => [
  "inline-flex items-center justify-center gap-2 rounded-xl font-semibold transition-all focus-visible:outline-none focus-visible:ring-4 disabled:cursor-not-allowed disabled:opacity-60 active:scale-[0.98]",
  {
    sm: "h-9 px-3.5 text-sm",
    md: "h-11 px-5 text-sm",
    lg: "h-12 px-6 text-base",
  }[props.size],
  {
    primary: "bg-brand-600 text-white shadow-sm shadow-brand-600/25 hover:bg-brand-700 focus-visible:ring-brand-200",
    ink: "bg-ink-900 text-white shadow-sm hover:bg-ink-800 focus-visible:ring-ink-200",
    secondary: "bg-brand-50 text-brand-700 hover:bg-brand-100 focus-visible:ring-brand-100",
    outline: "border border-slate-300 bg-white text-ink-800 hover:border-slate-400 hover:bg-slate-50 focus-visible:ring-slate-200",
    ghost: "text-ink-700 hover:bg-slate-100 focus-visible:ring-slate-200",
    danger: "bg-rose-600 text-white hover:bg-rose-700 focus-visible:ring-rose-200",
  }[props.variant],
  props.block ? "w-full" : "",
]);
</script>

<template>
  <router-link v-if="to && !disabled" :to="to" :class="classes">
    <slot />
  </router-link>
  <button v-else :type="type" :class="classes" :disabled="disabled || loading">
    <Loader2 v-if="loading" class="h-4 w-4 animate-spin" />
    <slot />
  </button>
</template>
