<script setup lang="ts">
import { computed } from "vue";
import { useRoute } from "vue-router";
import { Construction } from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import { useI18n } from "@/composables/useI18n";

const route = useRoute();
const { t, routeTitle } = useI18n();
const title = computed(() => {
  const fallback = (route.meta.title as string) || t("common.module");
  const routeName = typeof route.name === "string" ? route.name : "";
  return routeName ? routeTitle(routeName, fallback) : fallback;
});
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-2xl py-12 text-center">
      <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-slate-100">
        <Construction class="h-8 w-8 text-slate-500" />
      </div>
      <h1 class="mt-4 text-xl font-semibold text-slate-900">{{ title }}</h1>
      <p class="mt-2 text-sm text-slate-500">{{ t('common.pageComingSoon') }}</p>
    </div>
  </AdminLayout>
</template>
