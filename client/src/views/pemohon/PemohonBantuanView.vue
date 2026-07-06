<script setup lang="ts">
import { HelpCircle } from "lucide-vue-next";
import PemohonLayout from "@/layouts/PemohonLayout.vue";
import { FAQ_DUMMY } from "@/data/portal-dummy";
</script>

<template>
  <PemohonLayout>
    <div class="mx-auto max-w-5xl space-y-5">
      <div class="rounded-xl bg-gradient-to-br from-blue-700 to-blue-900 p-6 shadow-sm">
        <div class="flex items-center gap-2">
          <HelpCircle class="h-5 w-5 text-blue-200" />
          <h1 class="text-xl font-semibold text-white">Bantuan &amp; Soalan Lazim</h1>
        </div>
        <p class="mt-1 text-sm text-blue-100">Jawapan kepada soalan yang kerap ditanya mengenai permohonan pembiayaan SPPT.</p>
      </div>

      <div class="space-y-3">
        <details v-for="item in FAQ_DUMMY" :key="item.q" class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <summary class="cursor-pointer text-sm font-medium text-slate-900">{{ item.q }}</summary>
          <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ item.a }}</p>
        </details>
      </div>
    </div>
  </PemohonLayout>
</template>
