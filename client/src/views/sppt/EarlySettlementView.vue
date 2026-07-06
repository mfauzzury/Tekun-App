<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, onMounted } from "vue";
import { Calculator } from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import { fetchSpptDataset } from "@/api/sppt";

const { t, tp } = useI18n();

const noAkaun = ref("");
const nama = ref("");
const bakiPinjaman = ref(0);
const ansuranBulanan = ref(0);
const rebatDuaBulan = ref(0);
const amaunBersih = ref(0);

onMounted(async () => {
  const res = await fetchSpptDataset("pembayaran", "early_settlement");
  const d = res.data as { noAkaun: string; nama: string; bakiPinjaman: number; ansuranBulanan: number; rebatDuaBulan: number; amaunBersih: number };
  noAkaun.value = d.noAkaun;
  nama.value = d.nama;
  bakiPinjaman.value = d.bakiPinjaman;
  ansuranBulanan.value = d.ansuranBulanan;
  rebatDuaBulan.value = d.rebatDuaBulan;
  amaunBersih.value = d.amaunBersih;
});

function kiraSemula() {
  rebatDuaBulan.value = ansuranBulanan.value * 2;
  amaunBersih.value = bakiPinjaman.value - rebatDuaBulan.value;
}

function prosesSettlement() {
  alert("Proses penyelesaian awal (dummy). Tiada sambungan API.");
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-3xl space-y-4">
      <SpptPageHeader
        title="Pengurusan Early Settlement"
        :breadcrumb="[{ label: 'Pembayaran', to: '/admin/pembayaran' }, { label: 'Early Settlement' }]"
      />

      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-3">
          <Calculator class="h-4 w-4 text-slate-600" />
          <h2 class="text-sm font-semibold text-slate-900">Pengiraan Penyelesaian Awal</h2>
        </div>
        <div class="space-y-4 p-4">
          <p class="text-sm text-slate-600">
            Pengiraan rebat secara automatik selepas bayaran akhir. Caj dua (2) bulan diambil kira. Usahawan hanya dibenarkan melihat amaun bersih (net).
          </p>

          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">No. Akaun</label>
              <input
                v-model="noAkaun"
                type="text"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
              />
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">Nama</label>
              <input
                v-model="nama"
                type="text"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
              />
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">Baki Pinjaman (RM)</label>
              <input
                v-model.number="bakiPinjaman"
                type="number"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                @input="kiraSemula"
              />
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">Ansuran Bulanan (RM)</label>
              <input
                v-model.number="ansuranBulanan"
                type="number"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                @input="kiraSemula"
              />
            </div>
          </div>

          <div class="rounded-lg bg-slate-50 p-4">
            <div class="flex justify-between text-sm">
              <span class="text-slate-600">Rebat (2 bulan caj)</span>
              <span class="font-medium">RM {{ rebatDuaBulan.toLocaleString() }}</span>
            </div>
            <div class="mt-2 flex justify-between border-t border-slate-200 pt-2 text-base font-semibold">
              <span class="text-slate-900">Amaun Bersih (Net) untuk Dijelaskan</span>
              <span class="text-emerald-600">RM {{ amaunBersih.toLocaleString() }}</span>
            </div>
          </div>

          <div class="flex gap-2">
            <button
              class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
              @click="kiraSemula"
            >
              Kira Semula
            </button>
            <button
              class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800"
              @click="prosesSettlement"
            >
              Proses Penyelesaian Awal
            </button>
          </div>
        </div>
      </article>
    </div>
  </AdminLayout>
</template>
