<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, computed, onMounted } from "vue";
import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import SpptFilterBar from "@/components/sppt/SpptFilterBar.vue";
import LitigasiSubNav from "@/components/sppt/litigasi/LitigasiSubNav.vue";
import { fetchSpptModuleDatasets, pickSpptModuleDataset } from "@/api/sppt";
import { type KesLitigasi, NEGERI_OPTIONS, CAWANGAN_OPTIONS, PRODUK_OPTIONS, LAPORAN_KEPUTUSAN_BULANAN } from "@/data/litigasi-dummy";

const { t, tp } = useI18n();

const kesLitigasi = ref<KesLitigasi[]>([]);
const chartData = ref<(typeof LAPORAN_KEPUTUSAN_BULANAN)[number][]>([]);

onMounted(async () => {
  const data = await fetchSpptModuleDatasets("litigasi");
  kesLitigasi.value = pickSpptModuleDataset(data, "kes_litigasi", [] as KesLitigasi[]);
  chartData.value = pickSpptModuleDataset(data, "laporan_keputusan_bulanan", [] as typeof chartData.value);
});

const q = ref("");
const negeri = ref("");
const cawangan = ref("");
const produk = ref("");

const kesPenghakiman = computed(() => kesLitigasi.value.filter((k) => ["penghakiman", "selesai", "execution"].includes(k.status) || k.jenisPenghakiman));

function statusClass(s: string) {
  const m: Record<string, string> = { menang: "bg-emerald-100 text-emerald-700", kalah: "bg-rose-100 text-rose-700", "penyelesaian-luar": "bg-blue-100 text-blue-700" };
  return m[s] ?? "bg-slate-100 text-slate-600";
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="Penghakiman"
        :breadcrumb="[{ label: 'Undang-Undang', to: '/admin/undang-undang/litigasi' }, { label: 'Penghakiman' }]"
      />

      <LitigasiSubNav />

      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs font-medium text-slate-500">Kes Aktif</p>
          <p class="mt-1 text-2xl font-semibold text-slate-900">8</p>
        </article>
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs font-medium text-slate-500">Selesai</p>
          <p class="mt-1 text-2xl font-semibold text-slate-900">5</p>
        </article>
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs font-medium text-slate-500">Menang</p>
          <p class="mt-1 text-2xl font-semibold text-emerald-600">4</p>
        </article>
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs font-medium text-slate-500">Dalam Rayuan</p>
          <p class="mt-1 text-2xl font-semibold text-slate-900">1</p>
        </article>
      </div>

      <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold text-slate-900">Graf Keputusan Bulanan (7.8.4)</h3>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold text-slate-500">Bulan</th>
                <th class="px-4 py-2 text-xs font-semibold text-slate-500">Menang</th>
                <th class="px-4 py-2 text-xs font-semibold text-slate-500">Kalah</th>
                <th class="px-4 py-2 text-xs font-semibold text-slate-500">Selesai</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="row in chartData" :key="row.bulan" class="hover:bg-slate-50">
                <td class="px-4 py-2 font-medium text-slate-900">{{ row.bulan }}</td>
                <td class="px-4 py-2 text-emerald-600">{{ row.menang }}</td>
                <td class="px-4 py-2 text-rose-600">{{ row.kalah }}</td>
                <td class="px-4 py-2 text-slate-600">{{ row.selesai }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>

      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
          <h2 class="text-sm font-semibold text-slate-900">Senarai Kes Penghakiman (7.8)</h2>
          <div class="flex gap-2">
            <select v-model="negeri" class="rounded-lg border border-slate-300 py-1.5 pl-3 pr-8 text-sm">
              <option v-for="n in NEGERI_OPTIONS" :key="n" :value="n === 'Semua' ? '' : n">{{ n }}</option>
            </select>
            <select v-model="cawangan" class="rounded-lg border border-slate-300 py-1.5 pl-3 pr-8 text-sm">
              <option v-for="c in CAWANGAN_OPTIONS" :key="c" :value="c === 'Semua' ? '' : c">{{ c }}</option>
            </select>
            <select v-model="produk" class="rounded-lg border border-slate-300 py-1.5 pl-3 pr-8 text-sm">
              <option v-for="p in PRODUK_OPTIONS" :key="p" :value="p === 'Semua' ? '' : p">{{ p }}</option>
            </select>
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No. Kes</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.name") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Jenis Penghakiman</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Keputusan</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.date") }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in kesPenghakiman" :key="item.id" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.noKes }}</td>
                <td class="px-4 py-2 font-medium text-slate-900">{{ item.nama }}</td>
                <td class="px-4 py-2 text-slate-600">{{ item.jenisPenghakiman ? item.jenisPenghakiman.replace(/-/g, " ") : "—" }}</td>
                <td class="px-4 py-2">
                  <span v-if="item.keputusan" class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(item.keputusan)">{{ item.keputusan }}</span>
                  <span v-else class="text-slate-400">—</span>
                </td>
                <td class="px-4 py-2 text-slate-600">{{ item.tarikhPenghakiman ?? item.tarikhBuka }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>

      <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="mb-2 text-sm font-semibold text-slate-900">Jenis Penghakiman (7.8.1)</h3>
        <p class="text-sm text-slate-600">Ingkar kehadiran, Ingkar pembelaan, Terus, Persetujuan, Perintah selepas bicara penuh. Jika berpihak TEKUN: serah perintah ke usahawan, pantau pembayaran.</p>
      </article>
    </div>
  </AdminLayout>
</template>
