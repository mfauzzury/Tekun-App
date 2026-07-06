<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, computed, onMounted } from "vue";
import { TrendingUp, TrendingDown, Minus } from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import SpptFilterBar from "@/components/sppt/SpptFilterBar.vue";
import { fetchSpptDataset } from "@/api/sppt";
import { LEBIHAN_KEKURANGAN } from "@/data/bayaran-pembiayaan-dummy";

const { t, tp } = useI18n();

const q = ref("");
const jenisFilter = ref("");

const items = ref<(typeof LEBIHAN_KEKURANGAN)[number][]>([]);

onMounted(async () => {
  const res = await fetchSpptDataset("pembayaran", "lebihan_kekurangan");
  items.value = res.data as typeof items.value;
});

const filteredItems = computed(() => {
  let list = items.value;
  if (q.value) {
    const lower = q.value.toLowerCase();
    list = list.filter((r) => r.nama.toLowerCase().includes(lower) || r.noAkaun.toLowerCase().includes(lower));
  }
  if (jenisFilter.value) {
    list = list.filter((r) => r.jenis === jenisFilter.value);
  }
  return list;
});

function jenisClass(j: string) {
  if (j === "lebihan") return "bg-emerald-100 text-emerald-700";
  if (j === "kekurangan") return "bg-rose-100 text-rose-700";
  return "bg-slate-100 text-slate-600";
}

function statusClass(s: string) {
  if (s === "offset") return "bg-blue-100 text-blue-700";
  if (s === "tunggakan") return "bg-amber-100 text-amber-700";
  if (s === "advance") return "bg-violet-100 text-violet-700";
  return "bg-slate-100 text-slate-600";
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="Pengurusan Lebihan & Kekurangan Bayaran"
        :breadcrumb="[{ label: 'Pembayaran', to: '/admin/pembayaran' }, { label: 'Lebihan & Kekurangan' }]"
      />

      <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <div class="flex items-center gap-2">
            <TrendingUp class="h-5 w-5 text-emerald-600" />
            <span class="text-sm font-medium text-slate-700">Lebihan</span>
          </div>
          <p class="mt-1 text-2xl font-bold text-slate-900">2</p>
          <p class="text-xs text-slate-500">Offset / Refund / Advance</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <div class="flex items-center gap-2">
            <TrendingDown class="h-5 w-5 text-rose-600" />
            <span class="text-sm font-medium text-slate-700">Kekurangan</span>
          </div>
          <p class="mt-1 text-2xl font-bold text-slate-900">1</p>
          <p class="text-xs text-slate-500">Tunggakan sebahagian</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <div class="flex items-center gap-2">
            <Minus class="h-5 w-5 text-slate-500" />
            <span class="text-sm font-medium text-slate-700">Pelarasan Auto</span>
          </div>
          <p class="mt-1 text-2xl font-bold text-slate-900">—</p>
          <p class="text-xs text-slate-500">Tanpa campur tangan manual</p>
        </div>
      </div>

      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
          <h2 class="text-sm font-semibold text-slate-900">Senarai Lebihan & Kekurangan</h2>
          <SpptFilterBar
            v-model:q="q"
            v-model:status="jenisFilter"
            search-placeholder="Cari nama atau no. akaun..."
            :filter-options="[
              { value: 'lebihan', label: 'Lebihan' },
              { value: 'kekurangan', label: 'Kekurangan' },
            ]"
            filter-label="Jenis"
          />
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No. Akaun</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.name") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Jenis</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Ansuran Dijangka</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Diterima</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Beza</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.actions") }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in filteredItems" :key="item.noAkaun" class="transition-colors hover:bg-slate-50">
                <td class="px-4 py-2 font-mono text-slate-600">{{ item.noAkaun }}</td>
                <td class="px-4 py-2 font-medium text-slate-900">{{ item.nama }}</td>
                <td class="px-4 py-2">
                  <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="jenisClass(item.jenis)">
                    {{ item.jenis === "lebihan" ? "Lebihan" : "Kekurangan" }}
                  </span>
                </td>
                <td class="px-4 py-2 text-slate-600">RM {{ item.ansuranDijangka.toLocaleString() }}</td>
                <td class="px-4 py-2 text-slate-600">RM {{ item.diterima.toLocaleString() }}</td>
                <td class="px-4 py-2 font-medium" :class="item.jenis === 'lebihan' ? 'text-emerald-600' : 'text-rose-600'">
                  {{ item.jenis === "lebihan" ? "+" : "-" }} RM {{ item.jumlah.toLocaleString() }}
                </td>
                <td class="px-4 py-2">
                  <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :class="statusClass(item.status)">
                    {{ item.status === "offset" ? "Offset" : item.status === "tunggakan" ? "Tunggakan" : "Advance" }}
                  </span>
                </td>
              </tr>
              <tr v-if="filteredItems.length === 0">
                <td colspan="7" class="px-4 py-6 text-center text-sm text-slate-400">{{ tp("Tiada rekod dijumpai.") }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>
    </div>
  </AdminLayout>
</template>
