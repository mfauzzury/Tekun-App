<script setup lang="ts">
import { computed } from "vue";
import { useRoute } from "vue-router";
import { CalendarClock, CheckCircle2, Circle, Clock, MapPin } from "lucide-vue-next";

import PemohonLayout from "@/layouts/PemohonLayout.vue";
import PemohonStatusTimeline from "@/components/pemohon/PemohonStatusTimeline.vue";
import { usePemohonStore } from "@/stores/pemohon";
import { DOKUMEN_CHECKLIST_DUMMY, STATUS_TIMELINE, TEMUDUGA_DUMMY } from "@/data/portal-dummy";

const route = useRoute();
const pemohon = usePemohonStore();

const application = computed(() => pemohon.permohonanList.find((p) => p.id === route.params.id));
const milestones = computed(() => (application.value ? STATUS_TIMELINE[application.value.statusKey] ?? [] : []));
const temuduga = computed(() => TEMUDUGA_DUMMY.find((t) => t.permohonanId === application.value?.id));

function docStatusLabel(status: string) {
  if (status === "lengkap") return "Lengkap";
  if (status === "menunggu_semakan") return "Menunggu Semakan";
  return "Diperlukan";
}

function docStatusClass(status: string) {
  if (status === "lengkap") return "text-emerald-600";
  if (status === "menunggu_semakan") return "text-amber-600";
  return "text-slate-400";
}
</script>

<template>
  <PemohonLayout>
    <div v-if="!application" class="mx-auto max-w-5xl rounded-xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500 shadow-sm">
      Permohonan tidak dijumpai.
    </div>

    <div v-else class="mx-auto max-w-5xl space-y-5">
      <div class="pemohon-hero rounded-b-2xl p-6 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-200">{{ application.id }}</p>
        <h1 class="mt-2 text-xl font-semibold text-white">{{ application.produk }}</h1>
        <p class="mt-1 text-sm text-blue-100">{{ application.jumlah }} · Dimohon pada {{ application.tarikh }}</p>
      </div>

      <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="mb-4 text-sm font-semibold text-slate-900">Status Permohonan</h2>
          <PemohonStatusTimeline :milestones="milestones" />
        </div>

        <div class="space-y-5">
          <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-3 text-sm font-semibold text-slate-900">Senarai Semak Dokumen</h2>
            <ul class="space-y-2.5">
              <li v-for="doc in DOKUMEN_CHECKLIST_DUMMY" :key="doc.id" class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                  <CheckCircle2 v-if="doc.status === 'lengkap'" class="h-4 w-4 shrink-0 text-emerald-500" />
                  <Clock v-else-if="doc.status === 'menunggu_semakan'" class="h-4 w-4 shrink-0 text-amber-500" />
                  <Circle v-else class="h-4 w-4 shrink-0 text-slate-300" />
                  <span class="text-sm text-slate-700">{{ doc.nama }}</span>
                </div>
                <span class="shrink-0 text-xs font-medium" :class="docStatusClass(doc.status)">{{ docStatusLabel(doc.status) }}</span>
              </li>
            </ul>
          </div>

          <div v-if="temuduga" class="rounded-xl border border-blue-200 bg-blue-50 p-5">
            <div class="flex items-center gap-2">
              <CalendarClock class="h-4 w-4 text-blue-600" />
              <h2 class="text-sm font-semibold text-blue-900">Temuduga Dijadualkan</h2>
            </div>
            <p class="mt-2 text-sm text-blue-800">{{ temuduga.tarikh }}, {{ temuduga.masa }}</p>
            <p class="mt-1 flex items-center gap-1 text-xs text-blue-700">
              <MapPin class="h-3 w-3" />
              {{ temuduga.lokasi }} ({{ temuduga.jenis }})
            </p>
            <span class="mt-2 inline-block rounded-full bg-blue-200 px-2 py-0.5 text-xs font-medium text-blue-800">{{ temuduga.status }}</span>
          </div>
        </div>
      </div>
    </div>
  </PemohonLayout>
</template>
