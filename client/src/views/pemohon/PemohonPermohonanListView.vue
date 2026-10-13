<script setup lang="ts">
import { computed, ref } from "vue";
import { ChevronRight, FileText, History, Plus, RefreshCw, Search, SearchX } from "lucide-vue-next";

import ApplicationCard from "@/components/pemohon/status/ApplicationCard.vue";
import { deriveView, useApplicationStatuses } from "@/components/pemohon/status/useApplicationStatus";
import PBadge from "@/components/pemohon/ui/PBadge.vue";
import PButton from "@/components/pemohon/ui/PButton.vue";
import PEmptyState from "@/components/pemohon/ui/PEmptyState.vue";
import PPageHeader from "@/components/pemohon/ui/PPageHeader.vue";
import PemohonLayout from "@/layouts/PemohonLayout.vue";
import { usePemohonStore, type PermohonanItem } from "@/stores/pemohon";
import type { PTone } from "@/utils/pemohon-status";
import { pInputClass } from "@/utils/pemohon-ui";

type Kategori = "semua" | "proses" | "tindakan" | "lulus" | "tolak";

const pemohon = usePemohonStore();
const { snapshots, loading, refresh } = useApplicationStatuses(() => pemohon.permohonanSaya);

const search = ref("");
const filter = ref<Kategori>("semua");

const DUMMY_KATEGORI: Record<string, Kategori> = { lulus: "lulus", tidak_lulus: "tolak", dokumen_tambahan: "tindakan" };
const DUMMY_TONE: Record<string, PTone> = { lulus: "success", tidak_lulus: "danger", dokumen_tambahan: "warning" };

function realKategori(id: number): Kategori {
  const k = deriveView(snapshots.value[id]).keputusan;
  return k === "lulus" ? "lulus" : k === "tolak" ? "tolak" : "proses";
}
const dummyKategori = (item: PermohonanItem): Kategori => DUMMY_KATEGORI[item.statusKey] ?? "proses";

function matches(q: string, ...fields: (string | undefined)[]) {
  return !q || fields.some((f) => f?.toLowerCase().includes(q));
}

const query = computed(() => search.value.trim().toLowerCase());

const realFiltered = computed(() =>
  pemohon.permohonanSaya.filter(
    (p) => (filter.value === "semua" || realKategori(p.id) === filter.value) && matches(query.value, p.noRujukan, p.produk),
  ),
);
const dummyFiltered = computed(() =>
  pemohon.permohonanList.filter(
    (p) => (filter.value === "semua" || dummyKategori(p) === filter.value) && matches(query.value, p.id, p.produk),
  ),
);

const chips = computed(() => {
  const all = [...pemohon.permohonanSaya.map((p) => realKategori(p.id)), ...pemohon.permohonanList.map(dummyKategori)];
  const count = (k: Kategori) => (k === "semua" ? all.length : all.filter((x) => x === k).length);
  return (
    [
      ["semua", "Semua"],
      ["proses", "Dalam proses"],
      ["tindakan", "Perlu tindakan"],
      ["lulus", "Diluluskan"],
      ["tolak", "Tidak berjaya"],
    ] as [Kategori, string][]
  ).map(([value, label]) => ({ value, label, count: count(value) }));
});

const nothingAtAll = computed(() => !pemohon.permohonanSaya.length && !pemohon.permohonanList.length);
const nothingMatches = computed(() => !nothingAtAll.value && !realFiltered.value.length && !dummyFiltered.value.length);

function resetFilters() {
  search.value = "";
  filter.value = "semua";
}
</script>

<template>
  <PemohonLayout>
    <div class="space-y-6">
      <PPageHeader title="Permohonan saya" subtitle="Jejak status setiap permohonan pembiayaan anda secara masa nyata.">
        <template #actions>
          <PButton to="/pemohon/permohonan/baru"><Plus class="h-4 w-4" aria-hidden="true" /> Mohon pembiayaan</PButton>
        </template>
      </PPageHeader>

      <!-- Search + filters -->
      <div v-if="!nothingAtAll" class="space-y-3">
        <div class="relative">
          <Search class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
          <input v-model="search" type="search" :class="pInputClass" class="pl-10!" placeholder="Cari no. rujukan atau produk" aria-label="Cari permohonan" />
        </div>
        <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:px-0" role="group" aria-label="Tapis mengikut status">
          <button
            v-for="c in chips"
            :key="c.value"
            type="button"
            class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-full border px-3.5 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-100"
            :class="filter === c.value ? 'border-ink-900 bg-ink-900 text-white' : 'border-slate-200 bg-white text-ink-700 hover:border-slate-300'"
            :aria-pressed="filter === c.value"
            @click="filter = c.value"
          >
            {{ c.label }}
            <span class="rounded-full px-1.5 text-xs tabular-nums" :class="filter === c.value ? 'bg-white/15' : 'bg-slate-100 text-slate-500'">{{ c.count }}</span>
          </button>
        </div>
      </div>

      <div v-if="nothingAtAll" class="rounded-2xl border border-slate-200/80 bg-white">
        <PEmptyState :icon="FileText" title="Belum ada permohonan" description="Mulakan permohonan pembiayaan pertama anda. Ia hanya mengambil masa beberapa minit.">
          <PButton to="/pemohon/permohonan/baru"><Plus class="h-4 w-4" aria-hidden="true" /> Mohon pembiayaan</PButton>
        </PEmptyState>
      </div>

      <div v-else-if="nothingMatches" class="rounded-2xl border border-slate-200/80 bg-white">
        <PEmptyState :icon="SearchX" title="Tiada padanan" description="Tiada permohonan sepadan dengan carian atau penapis anda.">
          <PButton variant="outline" size="sm" @click="resetFilters">Set semula penapis</PButton>
        </PEmptyState>
      </div>

      <template v-else>
        <!-- Real applications -->
        <section v-if="realFiltered.length" aria-labelledby="sec-real">
          <div class="mb-3 flex items-center justify-between">
            <h2 id="sec-real" class="text-[15px] font-semibold text-ink-900">Permohonan aktif</h2>
            <button
              type="button"
              class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-sm font-medium text-slate-500 hover:bg-slate-100 hover:text-ink-900"
              :disabled="loading"
              @click="refresh"
            >
              <RefreshCw class="h-3.5 w-3.5" :class="loading ? 'animate-spin' : ''" aria-hidden="true" /> Muat semula
            </button>
          </div>
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <ApplicationCard v-for="p in realFiltered" :key="p.id" :item="p" :snapshot="snapshots[p.id]" :loading="loading" />
          </div>
        </section>

        <!-- Sample history (dummy) -->
        <section v-if="dummyFiltered.length" aria-labelledby="sec-dummy">
          <div class="mb-3 flex items-center gap-2">
            <History class="h-4 w-4 text-slate-400" aria-hidden="true" />
            <h2 id="sec-dummy" class="text-sm font-semibold text-slate-600">Contoh rekod terdahulu</h2>
          </div>
          <ul class="divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-200/70 bg-white/70">
            <li v-for="item in dummyFiltered" :key="item.id">
              <router-link :to="`/pemohon/permohonan/${item.id}`" class="flex items-center gap-3 px-4 py-3.5 transition-colors hover:bg-slate-50 sm:px-5">
                <div class="min-w-0 flex-1">
                  <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                    <p class="font-mono text-xs font-medium text-slate-500">{{ item.id }}</p>
                    <PBadge :tone="DUMMY_TONE[item.statusKey] ?? 'info'">{{ item.status }}</PBadge>
                  </div>
                  <p class="mt-1 truncate text-sm text-ink-800">
                    <span class="font-medium">{{ item.produk }}</span>
                    <span class="text-slate-400"> · </span>{{ item.jumlah }}
                    <span class="text-slate-400"> · {{ item.tarikh }}</span>
                  </p>
                </div>
                <ChevronRight class="h-4 w-4 shrink-0 text-slate-300" aria-hidden="true" />
              </router-link>
            </li>
          </ul>
        </section>
      </template>
    </div>
  </PemohonLayout>
</template>
