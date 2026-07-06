<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, onMounted } from "vue";
import { FileText, Download, Mail } from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import { fetchSpptDataset } from "@/api/sppt";
import { PENYATA_BAYARAN } from "@/data/bayaran-pembiayaan-dummy";

const { t, tp } = useI18n();

const carianIC = ref("");
const carianPinjaman = ref("");
const tempoh = ref("jan-jun-2025");
const items = ref<(typeof PENYATA_BAYARAN)[number][]>([]);

onMounted(async () => {
  const res = await fetchSpptDataset("pembayaran", "penyata_bayaran");
  items.value = res.data as typeof items.value;
});

function janaPenyata() {
  alert("Jana penyata (dummy). Tiada sambungan API.");
}

function muatTurun(id: string) {
  alert(`Muat turun penyata ${id} (dummy)`);
}

function hantarEmel(id: string) {
  alert(`Hantar salinan ke emel ${id} (dummy)`);
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="Penyata Bayaran"
        :breadcrumb="[{ label: 'Pembayaran', to: '/admin/pembayaran' }, { label: 'Penyata Bayaran' }]"
      />

      <div class="grid gap-4 lg:grid-cols-2">
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <h3 class="text-sm font-semibold text-slate-900">Penyata Atas Permintaan (On-Demand)</h3>
          <p class="mt-1 text-xs text-slate-500">Cari berdasarkan No. IC atau No. Pinjaman.</p>
          <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">No. IC</label>
              <input
                v-model="carianIC"
                type="text"
                placeholder="850101011234"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
              />
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">No. Pinjaman</label>
              <input
                v-model="carianPinjaman"
                type="text"
                placeholder="A-001"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
              />
            </div>
            <div class="sm:col-span-2">
              <label class="mb-1 block text-xs font-medium text-slate-600">Tempoh</label>
              <select
                v-model="tempoh"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
              >
                <option value="jan-jun-2025">Jan–Jun 2025</option>
                <option value="jan-mac-2025">Jan–Mac 2025</option>
                <option value="2024">2024 (Tahunan)</option>
              </select>
            </div>
            <button
              class="flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 sm:col-span-2"
              @click="janaPenyata"
            >
              <FileText class="h-4 w-4" />
              Jana Penyata
            </button>
          </div>
        </article>

        <aside class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <h3 class="text-sm font-semibold text-slate-900">Format Penyata</h3>
          <ul class="mt-2 space-y-1 text-xs text-slate-600">
            <li>• Nama penerima biaya, no. akaun pembiayaan</li>
            <li>• Tarikh & amaun bayaran</li>
            <li>• Baki sebelum & terkini</li>
            <li>• Status: penuh / sebahagian / tertunggak</li>
            <li>• Jumlah terkini dibayar, amaun tunggakan, bayaran lebih</li>
          </ul>
          <p class="mt-3 text-xs text-slate-500">PDF dengan timestamp dan nombor rujukan.</p>
        </aside>
      </div>

      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-3">
          <h2 class="text-sm font-semibold text-slate-900">Penyata Dijana</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Tempoh</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No. Akaun</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.name") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Tarikh Jana</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Format</th>
                <th class="px-4 py-2 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.actions") }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="(item, i) in items" :key="i" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2 text-slate-600">{{ item.tempoh }}</td>
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.noAkaun }}</td>
                <td class="px-4 py-2 font-medium text-slate-900">{{ item.nama }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.tarikhJana }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.format }}</td>
                <td class="px-4 py-2 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <button
                      class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                      title="Muat turun"
                      @click="muatTurun(item.noAkaun)"
                    >
                      <Download class="h-3.5 w-3.5" />
                    </button>
                    <button
                      class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                      title="Hantar emel"
                      @click="hantarEmel(item.noAkaun)"
                    >
                      <Mail class="h-3.5 w-3.5" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="items.length === 0">
                <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-400">{{ tp("Tiada penyata dijumpai.") }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>
    </div>
  </AdminLayout>
</template>
