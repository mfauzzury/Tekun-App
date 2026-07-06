<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, onMounted } from "vue";
import { KATEGORI_COLORS, type UsahawanItem } from "@/data/pemantauan-usahawan-dummy";
import { fetchSpptDataset } from "@/api/sppt";

const usahawanList = ref<UsahawanItem[]>([]);

onMounted(async () => {
  const res = await fetchSpptDataset("pemantauan", "usahawan_list");
  usahawanList.value = res.data as UsahawanItem[];
});

// Dummy: Geospatial Risk Heatmap (Lite) - grid representation
// Real map would use Leaflet/Mapbox - for dummy we show a grid of negeri with risk levels
const { t, tp } = useI18n();

const negeriRisk = [
  { negeri: "Selangor", count: 2, highRisk: 0, lat: 3.0733, lng: 101.5185 },
  { negeri: "Johor", count: 1, highRisk: 0, lat: 1.4927, lng: 103.7414 },
  { negeri: "Kedah", count: 1, highRisk: 1, lat: 6.1164, lng: 100.3685 },
  { negeri: "Perak", count: 2, highRisk: 1, lat: 4.5975, lng: 101.0901 },
];
</script>

<template>
  <div class="space-y-4">
    <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
      <div class="border-b border-slate-100 px-4 py-3">
        <h2 class="text-sm font-semibold text-slate-900">Geospatial Risk Heatmap (Lite)</h2>
        <p class="mt-1 text-xs text-slate-500">Lokasi usahawan dan kawasan berisiko tinggi di peta digital.</p>
      </div>
      <div class="p-4">
        <!-- Simulated map placeholder - grid of risk zones -->
        <div class="mb-4 rounded-lg border-2 border-dashed border-slate-300 bg-slate-50 p-12 text-center">
          <p class="text-sm font-medium text-slate-600">[ Peta Digital – Lokasi Usahawan ]</p>
          <p class="mt-1 text-xs text-slate-500">Integrasi Leaflet/Mapbox – titik merah = risiko tinggi, hijau = rendah</p>
        </div>

        <!-- Legend -->
        <div class="mb-4 flex flex-wrap gap-4 text-xs">
          <span class="flex items-center gap-2">
            <span class="h-3 w-3 rounded-full bg-red-500"></span>
            Risiko Tinggi
          </span>
          <span class="flex items-center gap-2">
            <span class="h-3 w-3 rounded-full bg-amber-500"></span>
            Risiko Sederhana
          </span>
          <span class="flex items-center gap-2">
            <span class="h-3 w-3 rounded-full bg-emerald-500"></span>
            Risiko Rendah
          </span>
        </div>

        <!-- Lokasi by negeri -->
        <div class="space-y-2">
          <h3 class="text-xs font-semibold uppercase text-slate-500">Lokasi Usahawan mengikut Negeri</h3>
          <div class="grid gap-2 sm:grid-cols-2">
            <div
              v-for="n in negeriRisk"
              :key="n.negeri"
              class="rounded-lg border p-3"
              :class="n.highRisk > 0 ? 'border-amber-200 bg-amber-50' : 'border-slate-100'"
            >
              <div class="flex items-center justify-between">
                <span class="font-medium text-slate-900">{{ n.negeri }}</span>
                <span class="text-xs font-medium" :class="n.highRisk > 0 ? 'text-amber-700' : 'text-slate-600'">
                  {{ n.count }} usahawan · {{ n.highRisk }} risiko tinggi
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Points list -->
        <div class="mt-4 overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase text-slate-500">Usahawan</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase text-slate-500">Lokasi</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase text-slate-500">Parlimen / DUN</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase text-slate-500">Risiko</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="u in usahawanList" :key="u.id" class="hover:bg-slate-50">
                <td class="px-4 py-2 font-medium">{{ u.nama }}</td>
                <td class="px-4 py-2 text-slate-600">{{ u.lat.toFixed(4) }}, {{ u.lng.toFixed(4) }}</td>
                <td class="px-4 py-2 text-slate-600">{{ u.parlimen }} · {{ u.dun }}</td>
                <td class="px-4 py-2">
                  <span
                    class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                    :class="KATEGORI_COLORS[u.kategoriRisiko]"
                  >
                    {{ u.kategoriRisiko }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </article>
  </div>
</template>
