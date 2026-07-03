<script setup lang="ts">
import { computed } from "vue";
import { ChevronRight, Plus } from "lucide-vue-next";
import { RouterLink, useRoute } from "vue-router";

import { useI18n } from "@/composables/useI18n";

const props = defineProps<{
  title: string;
  routeName?: string;
  breadcrumb?: { label: string; to?: string }[];
  actionLabel?: string;
  actionTo?: string;
}>();

const route = useRoute();
const { t, tp, routeTitle } = useI18n();

const resolvedRouteName = computed(() => props.routeName ?? (route.name as string | undefined));

const displayTitle = computed(() => {
  const name = resolvedRouteName.value;
  return name ? routeTitle(name, props.title) : tp(props.title);
});

const displayBreadcrumb = computed(() =>
  props.breadcrumb?.map((item) => ({ ...item, label: tp(item.label) })),
);

const displayActionLabel = computed(() => (props.actionLabel ? tp(props.actionLabel) : undefined));
</script>

<template>
  <div class="space-y-2">
    <nav v-if="displayBreadcrumb?.length" class="flex items-center gap-1 text-sm text-slate-500">
      <RouterLink to="/admin" class="hover:text-slate-700">{{ t("common.home") }}</RouterLink>
      <template v-for="(item, i) in displayBreadcrumb" :key="i">
        <ChevronRight class="h-4 w-4 shrink-0 text-slate-300" />
        <RouterLink v-if="item.to" :to="item.to" class="hover:text-slate-700">{{ item.label }}</RouterLink>
        <span v-else class="font-medium text-slate-700">{{ item.label }}</span>
      </template>
    </nav>
    <div class="flex items-center justify-between">
      <h1 class="page-title">{{ displayTitle }}</h1>
      <div v-if="$slots.actions" class="flex items-center gap-2">
        <slot name="actions" />
      </div>
      <RouterLink
        v-else-if="displayActionLabel && actionTo"
        :to="actionTo"
        class="flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-1.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800"
      >
        <Plus class="h-4 w-4" />
        {{ displayActionLabel }}
      </RouterLink>
    </div>
  </div>
</template>
