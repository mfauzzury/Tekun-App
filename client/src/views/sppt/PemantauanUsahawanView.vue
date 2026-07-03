<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, computed } from "vue";
import { Eye, MapPin, Calendar, GraduationCap, Map, Cpu } from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import SpptFilterBar from "@/components/sppt/SpptFilterBar.vue";
import SpptSummaryCards from "@/components/sppt/SpptSummaryCards.vue";
import UsahawanProfilModal from "@/components/sppt/pemantauan/UsahawanProfilModal.vue";
import LawatanTab from "@/components/sppt/pemantauan/LawatanTab.vue";
import ProgramLatihanTab from "@/components/sppt/pemantauan/ProgramLatihanTab.vue";
import HeatmapTab from "@/components/sppt/pemantauan/HeatmapTab.vue";
import AITab from "@/components/sppt/pemantauan/AITab.vue";

import {
  usahawanList,
  getSummary,
  KATEGORI_COLORS,
  STATUS_LABELS,
} from "@/data/pemantauan-usahawan-dummy";

const { t, tp } = useI18n();

const q = ref("");
const status = ref("");
const activeTab = ref<"senarai" | "lawatan" | "program" | "peta" | "ai">("senarai");
const profilUsahawan = ref<typeof usahawanList[0] | null>(null);
const showProfilModal = ref(false);

const summary = getSummary();

const filteredItems = computed(() => {
  let list = [...usahawanList];
  if (q.value) {
    const lower = q.value.toLowerCase();
    list = list.filter(
      (u) =>
        u.nama.toLowerCase().includes(lower) ||
        u.perniagaan.toLowerCase().includes(lower)
    );
  }
  if (status.value === "baik") {
    list = list.filter((u) => u.skorPrestasi >= 3);
  } else if (status.value === "perhatian") {
    list = list.filter((u) => u.earlyWarning || u.skorPrestasi < 3);
  }
  return list;
});

function openProfil(u: typeof usahawanList[0]) {
  profilUsahawan.value = u;
  showProfilModal.value = true;
}

function openMap(u: typeof usahawanList[0]) {
  const url = `https://www.google.com/maps?q=${u.lat},${u.lng}`;
  window.open(url, "_blank");
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="Pemantauan Usahawan"
        :breadcrumb="[
          { label: 'Pemantauan', to: '/admin/pemantauan/usahawan' },
          { label: 'Pemantauan Usahawan' },
        ]"
      />

      <SpptSummaryCards :items="summary" />

      <!-- Tabs -->
      <div class="flex flex-wrap gap-2">
        <button
          v-for="t in [
            { id: 'senarai' as const, label: 'Senarai Usahawan', icon: Eye },
            { id: 'lawatan' as const, label: 'Lawatan & Laporan', icon: Calendar },
            { id: 'program' as const, label: 'Program Latihan', icon: GraduationCap },
            { id: 'peta' as const, label: 'Peta Risiko', icon: Map },
            { id: 'ai' as const, label: 'AI & Early Warning', icon: Cpu },
          ]"
          :key="t.id"
          type="button"
          class="flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-medium transition-colors"
          :class="
            activeTab === t.id
              ? 'bg-slate-900 text-white'
              : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
          "
          @click="activeTab = t.id"
        >
          <component :is="t.icon" class="h-4 w-4" />
          {{ t.label }}
        </button>
      </div>

      <!-- Tab: Senarai Usahawan -->
      <article
        v-if="activeTab === 'senarai'"
        class="rounded-lg border border-slate-200 bg-white shadow-sm"
      >
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
          <h2 class="text-sm font-semibold text-slate-900">Senarai Usahawan</h2>
          <SpptFilterBar
            v-model:q="q"
            v-model:status="status"
            search-placeholder="Cari nama atau perniagaan..."
            :filter-options="[
              { value: 'baik', label: 'Prestasi Baik' },
              { value: 'perhatian', label: 'Perlu Perhatian' },
            ]"
          />
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">
                  No. Usahawan
                </th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">
                  Nama
                </th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">
                  Perniagaan
                </th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">
                  Negeri
                </th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">
                  PMKS
                </th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">
                  Status
                </th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">
                  Skor / Risiko
                </th>
                <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                  Tindakan
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="item in filteredItems"
                :key="item.id"
                class="transition-colors hover:bg-slate-50"
              >
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.id }}</td>
                <td class="px-4 py-2 font-medium text-slate-900">{{ item.nama }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.perniagaan }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.negeri }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.pmks }}</td>
                <td class="px-4 py-2">
                  <span class="rounded-full px-2.5 py-0.5 text-xs font-medium text-slate-700">
                    {{ STATUS_LABELS[item.statusPerniagaan] }}
                  </span>
                </td>
                <td class="px-4 py-2">
                  <span
                    class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                    :class="KATEGORI_COLORS[item.kategoriRisiko]"
                  >
                    {{ item.kategoriRisiko }}
                  </span>
                  <span
                    v-if="item.earlyWarning"
                    class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700"
                  >
                    EWS
                  </span>
                </td>
                <td class="px-4 py-2 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <button
                      class="group relative flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700"
                      @click="openProfil(item)"
                    >
                      <Eye class="h-3.5 w-3.5" />
                      <span
                        class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100"
                      >
                        Lihat
                      </span>
                    </button>
                    <button
                      class="group relative flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700"
                      @click="openMap(item)"
                    >
                      <MapPin class="h-3.5 w-3.5" />
                      <span
                        class="pointer-events-none absolute -top-8 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100"
                      >
                        Lokasi
                      </span>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="filteredItems.length === 0">
                <td
                  colspan="8"
                  class="px-4 py-6 text-center text-sm text-slate-400"
                >
                  {{ tp("Tiada rekod dijumpai.") }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>

      <!-- Tab: Lawatan -->
      <LawatanTab v-if="activeTab === 'lawatan'" />

      <!-- Tab: Program Latihan -->
      <ProgramLatihanTab v-if="activeTab === 'program'" />

      <!-- Tab: Peta Risiko -->
      <HeatmapTab v-if="activeTab === 'peta'" />

      <!-- Tab: AI -->
      <AITab v-if="activeTab === 'ai'" />
    </div>

    <UsahawanProfilModal
      :usahawan="profilUsahawan"
      :show="showProfilModal"
      @close="showProfilModal = false"
    />
  </AdminLayout>
</template>
