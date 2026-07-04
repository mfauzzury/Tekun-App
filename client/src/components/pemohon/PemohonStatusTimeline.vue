<script setup lang="ts">
import { Check } from "lucide-vue-next";
import type { StatusMilestone } from "@/data/portal-dummy";

defineProps<{
  milestones: StatusMilestone[];
}>();
</script>

<template>
  <ol class="space-y-0">
    <li v-for="(milestone, index) in milestones" :key="milestone.label" class="relative flex gap-3 pb-6 last:pb-0">
      <div
        v-if="index < milestones.length - 1"
        class="absolute left-3.5 top-7 h-full w-px"
        :class="milestone.done ? 'bg-emerald-300' : 'bg-slate-200'"
      />
      <span
        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold"
        :class="milestone.done ? 'bg-emerald-600 text-white' : 'border-2 border-slate-200 bg-white text-slate-400'"
      >
        <Check v-if="milestone.done" class="h-3.5 w-3.5" />
        <span v-else>{{ index + 1 }}</span>
      </span>
      <div class="pt-0.5">
        <p class="text-sm font-medium" :class="milestone.done ? 'text-slate-900' : 'text-slate-400'">
          {{ milestone.label }}
        </p>
      </div>
    </li>
  </ol>
</template>
