<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, computed, onMounted } from "vue";
import { AlertTriangle, TrendingUp, Activity } from "lucide-vue-next";
import {
  type AIForecastItem,
  type UsahawanItem,
} from "@/data/pemantauan-usahawan-dummy";
import { fetchSpptModuleDatasets, pickSpptModuleDataset } from "@/api/sppt";

const { t, tp } = useI18n();

const usahawanList = ref<UsahawanItem[]>([]);
const aiForecastData = ref<AIForecastItem[]>([]);
const lawatanList = ref<{ tarikh: string }[]>([]);

onMounted(async () => {
  const data = await fetchSpptModuleDatasets("pemantauan");
  usahawanList.value = pickSpptModuleDataset(data, "usahawan_list", [] as UsahawanItem[]);
  aiForecastData.value = pickSpptModuleDataset(data, "ai_forecast", [] as AIForecastItem[]);
  lawatanList.value = pickSpptModuleDataset(data, "lawatan_list", [] as typeof lawatanList.value);
});

const activeSub = ref<"ews" | "risiko" | "forecast" | "anomaly">("ews");

const earlyWarningList = computed(() => usahawanList.value.filter((u) => u.earlyWarning));

const summaryCards = computed(() => {
  const aktif = usahawanList.value.filter((u) => u.statusPerniagaan === "aktif_berkembang" || u.statusPerniagaan === "stabil").length;
  const lawatanBulanIni = lawatanList.value.filter((l) => {
    const d = new Date(l.tarikh);
    const n = new Date();
    return d.getMonth() === n.getMonth() && d.getFullYear() === n.getFullYear();
  }).length;
  const prestasiBaik = usahawanList.value.filter((u) => u.skorPrestasi >= 3).length;
  const perluPerhatian = usahawanList.value.filter((u) => u.earlyWarning || u.skorPrestasi < 3).length;
  return [
    { label: "Usahawan Aktif", value: aktif },
    { label: "Lawatan Bulan Ini", value: lawatanBulanIni },
    { label: "Prestasi Baik", value: prestasiBaik },
    { label: "Perlu Perhatian", value: perluPerhatian },
  ];
});

function getUsahawanName(id: string) {
  return usahawanList.value.find((u) => u.id === id)?.nama ?? id;
}

function fmtRm(n: number) {
  return `RM ${n.toLocaleString("en-MY")}`;
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex flex-wrap gap-2">
      <button
        v-for="t in [
          { id: 'ews' as const, label: 'Early Warning', icon: AlertTriangle },
          { id: 'risiko' as const, label: 'AI Risk Scoring', icon: Activity },
          { id: 'forecast' as const, label: 'Ramalan Jualan', icon: TrendingUp },
          { id: 'anomaly' as const, label: 'Anomaly Detection', icon: AlertTriangle },
        ]"
        :key="t.id"
        type="button"
        class="flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-medium transition-colors"
        :class="activeSub === t.id ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
        @click="activeSub = t.id"
      >
        <component :is="t.icon" class="h-4 w-4" />
        {{ t.label }}
      </button>
    </div>

    <!-- Early Warning System -->
    <article v-if="activeSub === 'ews'" class="rounded-lg border border-slate-200 bg-white shadow-sm">
      <div class="border-b border-slate-100 px-4 py-3">
        <h2 class="text-sm font-semibold text-slate-900">Early Warning System</h2>
        <p class="mt-1 text-xs text-slate-500">Amaran apabila skor prestasi jatuh di bawah paras had. Cadangan: bimbingan, kaunseling, penjadualan semula.</p>
      </div>
      <div class="divide-y divide-slate-100">
        <div
          v-for="u in earlyWarningList"
          :key="u.id"
          class="flex flex-wrap items-center justify-between gap-4 px-4 py-4"
        >
          <div>
            <h3 class="font-medium text-slate-900">{{ u.nama }}</h3>
            <p class="mt-1 text-sm text-amber-800">{{ u.earlyWarningMsg }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ u.perniagaan }} · {{ u.negeri }}</p>
          </div>
          <div class="flex gap-2">
            <button class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">
              Jemput Bimbingan
            </button>
            <button class="rounded-lg border border-amber-300 px-3 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-50">
              Penjadualan Semula
            </button>
          </div>
        </div>
        <div v-if="earlyWarningList.length === 0" class="px-4 py-8 text-center text-sm text-slate-400">
          Tiada amaran aktif.
        </div>
      </div>
    </article>

    <!-- AI Risk Scoring -->
    <article v-if="activeSub === 'risiko'" class="rounded-lg border border-slate-200 bg-white shadow-sm">
      <div class="border-b border-slate-100 px-4 py-3">
        <h2 class="text-sm font-semibold text-slate-900">AI Risk Scoring & Early Warning</h2>
        <p class="mt-1 text-xs text-slate-500">Penilaian prestasi perniagaan dan pengesanan risiko NPF secara automatik.</p>
      </div>
      <div class="grid gap-4 p-4 sm:grid-cols-2 lg:grid-cols-4">
        <div v-for="s in summaryCards" :key="s.label" class="rounded-lg border border-slate-200 p-4">
          <p class="text-xs font-medium text-slate-500">{{ s.label }}</p>
          <p class="mt-1 text-2xl font-semibold text-slate-900">{{ s.value }}</p>
        </div>
      </div>
      <div class="border-t border-slate-100 px-4 py-3">
        <p class="text-xs text-slate-500">Skor AI dikira berdasarkan indikator kewangan dan bukan kewangan. Integrasi dengan modul Kutipan bagi peningkatan risiko bayaran.</p>
      </div>
    </article>

    <!-- Ramalan Jualan (Time Series) -->
    <article v-if="activeSub === 'forecast'" class="rounded-lg border border-slate-200 bg-white shadow-sm">
      <div class="border-b border-slate-100 px-4 py-3">
        <h2 class="text-sm font-semibold text-slate-900">AI Time Series Forecasting</h2>
        <p class="mt-1 text-xs text-slate-500">Ramalan jualan, keuntungan, pengesanan prestasi luar jangka.</p>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-slate-100 text-left">
              <th class="px-4 py-2 text-xs font-semibold uppercase text-slate-500">Usahawan</th>
              <th class="px-4 py-2 text-xs font-semibold uppercase text-slate-500">Bulan</th>
              <th class="px-4 py-2 text-xs font-semibold uppercase text-slate-500">Jualan Ramalan</th>
              <th class="px-4 py-2 text-xs font-semibold uppercase text-slate-500">Jualan Aktual</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="f in aiForecastData" :key="f.usahawanId + f.bulan" class="hover:bg-slate-50">
              <td class="px-4 py-2 font-medium">{{ getUsahawanName(f.usahawanId) }}</td>
              <td class="px-4 py-2">{{ f.bulan }}</td>
              <td class="px-4 py-2">{{ fmtRm(f.jualanRamalan) }}</td>
              <td class="px-4 py-2">{{ f.jualanAktual ? fmtRm(f.jualanAktual) : "–" }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </article>

    <!-- Anomaly Detection -->
    <article v-if="activeSub === 'anomaly'" class="rounded-lg border border-slate-200 bg-white shadow-sm">
      <div class="border-b border-slate-100 px-4 py-3">
        <h2 class="text-sm font-semibold text-slate-900">Anomaly Detection</h2>
        <p class="mt-1 text-xs text-slate-500">Pengesanan prestasi luar jangka – jualan turun mendadak, risiko NPF.</p>
      </div>
      <div class="divide-y divide-slate-100">
        <div
          v-for="f in aiForecastData.filter((x) => x.anomaly)"
          :key="f.usahawanId + f.bulan"
          class="flex items-center justify-between gap-4 px-4 py-4"
        >
          <div>
            <h3 class="font-medium text-slate-900">{{ getUsahawanName(f.usahawanId) }}</h3>
            <p class="mt-1 text-sm text-amber-800">{{ f.anomalyReason }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ f.bulan }} · Ramalan: {{ fmtRm(f.jualanRamalan) }}</p>
          </div>
          <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-800">Anomaly</span>
        </div>
        <div v-if="!aiForecastData.some((x) => x.anomaly)" class="px-4 py-8 text-center text-sm text-slate-400">
          Tiada anomaly dikesan.
        </div>
      </div>
    </article>
  </div>
</template>
