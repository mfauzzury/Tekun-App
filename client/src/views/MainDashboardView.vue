<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import { ArrowRight, ChartNoAxesCombined, FileText, Image, LayoutGrid, Settings } from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import { useI18n } from "@/composables/useI18n";
import { fetchDashboardSummary } from "@/api/cms";

const router = useRouter();
const { t } = useI18n();
const counts = ref({ posts: 0, pages: 0, media: 0 });
const loading = ref(true);

onMounted(async () => {
  try {
    const response = await fetchDashboardSummary();
    counts.value = response.data.counts;
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-5">
      <div class="rounded-2xl border border-slate-200 bg-gradient-to-r from-slate-50 to-white p-6 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ t('dashboard.mainLabel') }}</p>
        <h1 class="mt-2 text-2xl font-semibold text-slate-900">{{ t('dashboard.controlCenter') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ t('dashboard.mainDescription') }}</p>
        <button
          class="mt-4 inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50"
          @click="router.push('/admin/portal/dashboard')"
        >
          {{ t('dashboard.openDashboard') }}
          <ArrowRight class="h-4 w-4" />
        </button>
      </div>

      <div class="grid gap-3 sm:grid-cols-3">
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <div class="flex items-center justify-between">
            <p class="text-xs font-medium text-slate-500">{{ t('dashboard.posts') }}</p>
            <FileText class="h-4 w-4 text-slate-400" />
          </div>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ loading ? '-' : counts.posts }}</p>
        </article>
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <div class="flex items-center justify-between">
            <p class="text-xs font-medium text-slate-500">{{ t('dashboard.pages') }}</p>
            <LayoutGrid class="h-4 w-4 text-slate-400" />
          </div>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ loading ? '-' : counts.pages }}</p>
        </article>
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <div class="flex items-center justify-between">
            <p class="text-xs font-medium text-slate-500">{{ t('dashboard.media') }}</p>
            <Image class="h-4 w-4 text-slate-400" />
          </div>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ loading ? '-' : counts.media }}</p>
        </article>
      </div>

      <div class="grid gap-3 md:grid-cols-3">
        <button class="rounded-lg border border-slate-200 bg-white p-4 text-left shadow-sm transition-colors hover:bg-slate-50" @click="router.push('/admin/portal/dashboard')">
          <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
            <ChartNoAxesCombined class="h-4 w-4" /> {{ t('dashboard.mainLabel') }}
          </div>
          <p class="mt-1 text-xs text-slate-500">{{ t('dashboard.dashboardCardDesc') }}</p>
        </button>
        <button class="rounded-lg border border-slate-200 bg-white p-4 text-left shadow-sm transition-colors hover:bg-slate-50" @click="router.push('/admin/posts')">
          <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
            <FileText class="h-4 w-4" /> {{ t('dashboard.manageContent') }}
          </div>
          <p class="mt-1 text-xs text-slate-500">{{ t('dashboard.manageContentDesc') }}</p>
        </button>
        <button class="rounded-lg border border-slate-200 bg-white p-4 text-left shadow-sm transition-colors hover:bg-slate-50" @click="router.push('/admin/settings')">
          <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
            <Settings class="h-4 w-4" /> {{ t('dashboard.siteSettings') }}
          </div>
          <p class="mt-1 text-xs text-slate-500">{{ t('dashboard.siteSettingsDesc') }}</p>
        </button>
      </div>
    </div>
  </AdminLayout>
</template>
