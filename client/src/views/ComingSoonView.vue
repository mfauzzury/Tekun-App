<script setup lang="ts">
import { computed } from "vue";
import { Construction } from "lucide-vue-next";
import { useRoute } from "vue-router";

import AdminLayout from "@/layouts/AdminLayout.vue";
import { useI18n } from "@/composables/useI18n";

const route = useRoute();
const { t, routeTitle } = useI18n();
const title = computed(() => {
  const fallback = (route.meta.title as string) || t("common.comingSoon");
  const routeName = typeof route.name === "string" ? route.name : "";
  return routeName ? routeTitle(routeName, fallback) : fallback;
});
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl">
      <div class="flex items-center justify-between">
        <h1 class="page-title">{{ title }}</h1>
      </div>

      <article class="mt-4 rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col items-center justify-center px-4 py-16 text-center">
          <Construction class="h-12 w-12 text-slate-300" />
          <h2 class="mt-4 text-lg font-semibold text-slate-700">{{ t('common.underDevelopment') }}</h2>
          <p class="mt-1 max-w-md text-sm text-slate-400">
            {{ t('common.moduleComingSoon') }}
          </p>
        </div>
      </article>
    </div>
  </AdminLayout>
</template>
