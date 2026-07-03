<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import {
  FileText,
  Image,
  ArrowRight,
  TrendingUp,
} from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import { useI18n } from "@/composables/useI18n";
import { fetchDashboardSummary } from "@/api/cms";
import type { Page, Post } from "@/types";

const router = useRouter();
const { t } = useI18n();
const counts = ref({ posts: 0, pages: 0, media: 0 });
const recentPosts = ref<Post[]>([]);
const recentPages = ref<Page[]>([]);

onMounted(async () => {
  const response = await fetchDashboardSummary();
  counts.value = response.data.counts;
  recentPosts.value = response.data.recent.posts;
  recentPages.value = response.data.recent.pages;
});

function statusColor(status: string) {
  switch (status) {
    case "published":
      return "bg-emerald-100 text-emerald-700";
    case "draft":
      return "bg-amber-100 text-amber-700";
    case "archived":
      return "bg-slate-100 text-slate-600";
    default:
      return "bg-slate-100 text-slate-600";
  }
}

function statusLabel(status: string) {
  return t(`common.${status}`, status);
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <div class="flex items-center justify-between">
        <h1 class="page-title">{{ t('routes.dashboard') }}</h1>
      </div>

      <div class="grid gap-3 sm:grid-cols-3">
        <div class="group rounded-lg border border-slate-200 bg-white p-3 shadow-sm transition-all hover:shadow-md">
          <div class="flex items-center justify-between">
            <div class="flex h-7 w-7 items-center justify-center rounded-md bg-blue-100">
              <FileText class="h-3.5 w-3.5 text-blue-600" />
            </div>
            <TrendingUp class="h-3.5 w-3.5 text-emerald-500" />
          </div>
          <p class="mt-2 text-xl font-bold text-slate-900">{{ counts.posts }}</p>
          <p class="mt-0.5 text-xs text-slate-500">{{ t('dashboard.totalPosts') }}</p>
        </div>
        <div class="group rounded-lg border border-slate-200 bg-white p-3 shadow-sm transition-all hover:shadow-md">
          <div class="flex items-center justify-between">
            <div class="flex h-7 w-7 items-center justify-center rounded-md bg-emerald-100">
              <FileText class="h-3.5 w-3.5 text-emerald-600" />
            </div>
            <TrendingUp class="h-3.5 w-3.5 text-emerald-500" />
          </div>
          <p class="mt-2 text-xl font-bold text-slate-900">{{ counts.pages }}</p>
          <p class="mt-0.5 text-xs text-slate-500">{{ t('dashboard.totalPages') }}</p>
        </div>
        <div class="group rounded-lg border border-slate-200 bg-white p-3 shadow-sm transition-all hover:shadow-md">
          <div class="flex items-center justify-between">
            <div class="flex h-7 w-7 items-center justify-center rounded-md bg-amber-100">
              <Image class="h-3.5 w-3.5 text-amber-600" />
            </div>
            <TrendingUp class="h-3.5 w-3.5 text-emerald-500" />
          </div>
          <p class="mt-2 text-xl font-bold text-slate-900">{{ counts.media }}</p>
          <p class="mt-0.5 text-xs text-slate-500">{{ t('dashboard.mediaFiles') }}</p>
        </div>
      </div>

      <div class="grid gap-4 md:grid-cols-2">
        <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2.5">
            <div class="flex items-center gap-2">
              <FileText class="h-4 w-4 text-blue-600" />
              <h2 class="text-sm font-semibold text-slate-900">{{ t('dashboard.recentPosts') }}</h2>
            </div>
            <button class="flex items-center gap-1 text-sm font-medium text-slate-500 transition-colors hover:text-slate-900" @click="router.push('/admin/posts')">
              {{ t('common.viewAll') }}
              <ArrowRight class="h-3.5 w-3.5" />
            </button>
          </div>
          <div class="divide-y divide-slate-100">
            <div v-for="post in recentPosts" :key="post.id" class="flex items-center justify-between px-4 py-2 transition-colors hover:bg-slate-50">
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-slate-900">{{ post.title }}</p>
                <p class="text-xs text-slate-400">{{ post.slug }}</p>
              </div>
              <span class="ml-3 shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusColor(post.status)">
                {{ statusLabel(post.status) }}
              </span>
            </div>
            <div v-if="recentPosts.length === 0" class="px-4 py-6 text-center text-sm text-slate-400">
              {{ t('common.noPostsYet') }}
            </div>
          </div>
        </article>

        <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2.5">
            <div class="flex items-center gap-2">
              <FileText class="h-4 w-4 text-emerald-600" />
              <h2 class="text-sm font-semibold text-slate-900">{{ t('dashboard.recentPages') }}</h2>
            </div>
            <button class="flex items-center gap-1 text-sm font-medium text-slate-500 transition-colors hover:text-slate-900" @click="router.push('/admin/pages')">
              {{ t('common.viewAll') }}
              <ArrowRight class="h-3.5 w-3.5" />
            </button>
          </div>
          <div class="divide-y divide-slate-100">
            <div v-for="page in recentPages" :key="page.id" class="flex items-center justify-between px-4 py-2 transition-colors hover:bg-slate-50">
              <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-slate-900">{{ page.title }}</p>
                <p class="text-xs text-slate-400">{{ page.slug }}</p>
              </div>
              <span class="ml-3 shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusColor(page.status)">
                {{ statusLabel(page.status) }}
              </span>
            </div>
            <div v-if="recentPages.length === 0" class="px-4 py-6 text-center text-sm text-slate-400">
              {{ t('common.noPagesYet') }}
            </div>
          </div>
        </article>
      </div>
    </div>
  </AdminLayout>
</template>
