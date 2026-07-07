<script setup lang="ts">
import { computed, ref } from "vue";
import { ChevronRight, PlusCircle, Search } from "lucide-vue-next";
import PemohonLayout from "@/layouts/PemohonLayout.vue";
import { usePemohonStore } from "@/stores/pemohon";
import { STATUS_OPTIONS } from "@/data/portal-dummy";

const pemohon = usePemohonStore();
const search = ref("");
const statusFilter = ref("semua");

const filtered = computed(() => {
  return pemohon.permohonanList.filter((item) => {
    const matchesStatus = statusFilter.value === "semua" || item.statusKey === statusFilter.value;
    const matchesSearch = !search.value || item.id.toLowerCase().includes(search.value.toLowerCase()) || item.produk.toLowerCase().includes(search.value.toLowerCase());
    return matchesStatus && matchesSearch;
  });
});

function statusBadgeClass(statusKey: string) {
  switch (statusKey) {
    case "lulus": return "bg-emerald-50 text-emerald-700 border-emerald-200";
    case "tidak_lulus": return "bg-rose-50 text-rose-700 border-rose-200";
    case "dokumen_tambahan": return "bg-amber-50 text-amber-700 border-amber-200";
    default: return "bg-slate-100 text-slate-700 border-slate-200";
  }
}
</script>

<template>
  <PemohonLayout>
    <div class="mx-auto max-w-5xl space-y-5">
      <div class="pemohon-hero flex flex-col gap-3 rounded-b-2xl p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-xl font-semibold text-white">Permohonan Saya</h1>
          <p class="mt-1 text-sm text-blue-100">Senarai dan status permohonan pembiayaan anda.</p>
        </div>
        <router-link
          to="/pemohon/permohonan/baru"
          class="flex shrink-0 items-center gap-1.5 rounded-md bg-white px-4 py-2 text-sm font-medium text-blue-700 transition-colors hover:bg-blue-50"
        >
          <PlusCircle class="h-4 w-4" />
          Mohon Baharu
        </router-link>
      </div>

      <div class="flex flex-col gap-3 sm:flex-row">
        <div class="relative flex-1">
          <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
          <input
            v-model="search"
            type="text"
            placeholder="Cari mengikut rujukan atau produk..."
            class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
          />
        </div>
        <select
          v-model="statusFilter"
          class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
        >
          <option v-for="opt in STATUS_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
        </select>
      </div>

      <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div v-if="filtered.length === 0" class="p-8 text-center text-sm text-slate-500">Tiada permohonan dijumpai.</div>
        <router-link
          v-for="item in filtered"
          :key="item.id"
          :to="`/pemohon/permohonan/${item.id}`"
          class="flex flex-col gap-2 border-b border-slate-100 px-5 py-4 transition-colors last:border-b-0 hover:bg-slate-50"
        >
          <div class="flex items-center justify-between gap-2">
            <p class="text-sm font-semibold text-slate-900">{{ item.id }}</p>
            <ChevronRight class="h-4 w-4 shrink-0 text-slate-400" />
          </div>
          <p class="text-sm text-slate-500">{{ item.produk }} · {{ item.jumlah }} · {{ item.tarikh }}</p>
          <span class="w-fit rounded-full border px-2.5 py-1 text-xs font-medium" :class="statusBadgeClass(item.statusKey)">
            {{ item.status }}
          </span>
        </router-link>
      </div>
    </div>
  </PemohonLayout>
</template>
