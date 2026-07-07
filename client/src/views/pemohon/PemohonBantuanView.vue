<script setup lang="ts">
import { HelpCircle, Mail, MapPin, Phone } from "lucide-vue-next";
import PemohonLayout from "@/layouts/PemohonLayout.vue";
import { FAQ_DUMMY, HUBUNGI_TEKUN } from "@/data/portal-dummy";
</script>

<template>
  <PemohonLayout>
    <div class="mx-auto max-w-5xl space-y-5">
      <div class="pemohon-hero rounded-b-xl p-6 shadow-sm">
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

      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-semibold text-slate-900">Hubungi Kami</h2>
        <div class="mt-3 space-y-3 text-sm text-slate-600">
          <div class="flex items-start gap-2.5">
            <MapPin class="mt-0.5 h-4 w-4 shrink-0 text-blue-600" />
            <span>{{ HUBUNGI_TEKUN.alamat }}</span>
          </div>
          <div class="flex items-center gap-2.5">
            <Phone class="h-4 w-4 shrink-0 text-blue-600" />
            <a :href="`tel:${HUBUNGI_TEKUN.telefon.replace(/[^0-9+]/g, '')}`" class="hover:text-blue-700">{{ HUBUNGI_TEKUN.telefon }}</a>
          </div>
          <div class="flex items-center gap-2.5">
            <Mail class="h-4 w-4 shrink-0 text-blue-600" />
            <a :href="`mailto:${HUBUNGI_TEKUN.emel}`" class="hover:text-blue-700">{{ HUBUNGI_TEKUN.emel }}</a>
          </div>
          <div class="border-t border-slate-100 pt-3 text-xs text-slate-500">
            <p v-for="waktu in HUBUNGI_TEKUN.waktuOperasi" :key="waktu">{{ waktu }}</p>
          </div>
        </div>
      </div>
    </div>
  </PemohonLayout>
</template>
