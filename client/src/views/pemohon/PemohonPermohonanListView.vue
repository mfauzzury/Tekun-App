<script setup lang="ts">
import { computed, ref } from "vue";
import {
  ArrowRight,
  ArrowUpDown,
  CheckCircle2,
  ChevronRight,
  FileText,
  Hourglass,
  Plus,
  RefreshCw,
  Search,
  SearchX,
  Store,
  Wallet,
} from "lucide-vue-next";

import StageTracker from "@/components/pemohon/status/StageTracker.vue";
import { deriveView, formatTarikh, useApplicationStatuses } from "@/components/pemohon/status/useApplicationStatus";
import PBadge from "@/components/pemohon/ui/PBadge.vue";
import PButton from "@/components/pemohon/ui/PButton.vue";
import PEmptyState from "@/components/pemohon/ui/PEmptyState.vue";
import PPageHeader from "@/components/pemohon/ui/PPageHeader.vue";
import PStatCard from "@/components/pemohon/ui/PStatCard.vue";
import PemohonLayout from "@/layouts/PemohonLayout.vue";
import { usePemohonStore, type PermohonanItem } from "@/stores/pemohon";
import type { PTone } from "@/utils/pemohon-status";
import { formatRinggit, pInputClass } from "@/utils/pemohon-ui";

type Kategori = "proses" | "tindakan" | "lulus" | "tolak";
type Penapis = "semua" | Kategori;
type Susunan = "terbaru" | "terlama" | "jumlah";

interface Row {
  key: string;
  routeId: string;
  noRujukan: string;
  produk: string;
  jumlah: number;
  tarikh: number;
  tarikhLabel: string;
  statusLabel: string;
  tone: PTone;
  stage: number;
  outcome: "lulus" | "tolak" | null;
  kategori: Kategori;
  contoh: boolean;
  memuat: boolean;
  mesej: string | null;
}

const pemohon = usePemohonStore();
const { snapshots, loading, refresh } = useApplicationStatuses(() => pemohon.permohonanSaya);

const BULAN: Record<string, number> = { jan: 0, feb: 1, mac: 2, apr: 3, mei: 4, jun: 5, jul: 6, ogos: 7, ogo: 7, sep: 8, okt: 9, nov: 10, dis: 11 };

function parseTarikhMelayu(label: string): number {
  const [d, m, y] = label.trim().split(/\s+/);
  const month = BULAN[(m ?? "").toLowerCase().slice(0, 4)] ?? BULAN[(m ?? "").toLowerCase().slice(0, 3)];
  return month === undefined ? 0 : new Date(Number(y), month, Number(d)).getTime();
}

const DUMMY_STATUS: Record<string, Pick<Row, "tone" | "stage" | "outcome" | "kategori">> = {
  dalam_semakan: { tone: "info", stage: 2, outcome: null, kategori: "proses" },
  dokumen_tambahan: { tone: "warning", stage: 2, outcome: null, kategori: "tindakan" },
  lulus: { tone: "success", stage: 3, outcome: "lulus", kategori: "lulus" },
  tidak_lulus: { tone: "danger", stage: 3, outcome: "tolak", kategori: "tolak" },
};

const rows = computed<Row[]>(() => {
  const real = pemohon.permohonanSaya.map((p): Row => {
    const snap = snapshots.value[p.id];
    const view = deriveView(snap);
    return {
      key: `r-${p.id}`,
      routeId: String(p.id),
      noRujukan: p.noRujukan,
      produk: snap?.record?.kategoriPembiayaan || p.produk || "Pembiayaan",
      jumlah: Number(snap?.record?.jumlahPermohonan ?? p.jumlah ?? 0),
      tarikh: new Date(p.dihantarPada).getTime() || 0,
      tarikhLabel: formatTarikh(p.dihantarPada),
      statusLabel: view.statusLabel,
      tone: view.tone,
      stage: view.stage,
      outcome: view.keputusan,
      kategori: view.keputusan === "lulus" ? "lulus" : view.keputusan === "tolak" ? "tolak" : "proses",
      contoh: false,
      memuat: !snap && loading.value,
      mesej: view.mesej,
    };
  });

  const sample = pemohon.permohonanList.map((p: PermohonanItem): Row => {
    const s = DUMMY_STATUS[p.statusKey] ?? { tone: "info" as PTone, stage: 1, outcome: null, kategori: "proses" as Kategori };
    return {
      key: `d-${p.id}`,
      routeId: p.id,
      noRujukan: p.id,
      produk: p.produk,
      jumlah: Number(String(p.jumlah).replace(/[^\d.]/g, "")) || 0,
      tarikh: parseTarikhMelayu(p.tarikh),
      tarikhLabel: p.tarikh,
      statusLabel: p.status,
      contoh: true,
      memuat: false,
      mesej: null,
      ...s,
    };
  });

  return [...real, ...sample];
});

const stats = computed(() => {
  const all = rows.value;
  return {
    jumlah: all.length,
    proses: all.filter((r) => r.kategori === "proses" || r.kategori === "tindakan").length,
    lulus: all.filter((r) => r.kategori === "lulus").length,
    nilai: all.reduce((sum, r) => sum + r.jumlah, 0),
  };
});

const terkini = computed(() => {
  const real = rows.value.filter((r) => !r.contoh).sort((a, b) => b.tarikh - a.tarikh);
  return real[0] ?? [...rows.value].sort((a, b) => b.tarikh - a.tarikh)[0] ?? null;
});

const carian = ref("");
const penapis = ref<Penapis>("semua");
const susunan = ref<Susunan>("terbaru");

const chips = computed(() => {
  const count = (k: Penapis) => (k === "semua" ? rows.value.length : rows.value.filter((r) => r.kategori === k).length);
  return (
    [
      ["semua", "Semua"],
      ["proses", "Dalam proses"],
      ["tindakan", "Perlu tindakan"],
      ["lulus", "Diluluskan"],
      ["tolak", "Tidak berjaya"],
    ] as [Penapis, string][]
  ).map(([value, label]) => ({ value, label, count: count(value) }));
});

const senarai = computed(() => {
  const q = carian.value.trim().toLowerCase();
  const list = rows.value.filter(
    (r) =>
      (penapis.value === "semua" || r.kategori === penapis.value) &&
      (!q || r.noRujukan.toLowerCase().includes(q) || r.produk.toLowerCase().includes(q)),
  );
  const by: Record<Susunan, (a: Row, b: Row) => number> = {
    terbaru: (a, b) => b.tarikh - a.tarikh,
    terlama: (a, b) => a.tarikh - b.tarikh,
    jumlah: (a, b) => b.jumlah - a.jumlah,
  };
  return list.sort(by[susunan.value]);
});

function setSemula() {
  carian.value = "";
  penapis.value = "semua";
}
</script>

<template>
  <PemohonLayout>
    <div class="space-y-6">
      <PPageHeader title="Permohonan saya" subtitle="Jejak status setiap permohonan pembiayaan anda — dari penghantaran hingga keputusan.">
        <template #actions>
          <PButton to="/pemohon/permohonan/baru"><Plus class="h-4 w-4" aria-hidden="true" /> Mohon pembiayaan</PButton>
        </template>
      </PPageHeader>

      <div v-if="!rows.length" class="rounded-2xl border border-slate-200/80 bg-white">
        <PEmptyState :icon="FileText" title="Belum ada permohonan" description="Mulakan permohonan pembiayaan pertama anda. Ia hanya mengambil masa beberapa minit.">
          <PButton to="/pemohon/permohonan/baru"><Plus class="h-4 w-4" aria-hidden="true" /> Mohon pembiayaan</PButton>
        </PEmptyState>
      </div>

      <template v-else>
        <!-- Summary strip -->
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
          <PStatCard label="Jumlah permohonan" :value="stats.jumlah" :icon="FileText" tone="neutral" />
          <PStatCard label="Dalam proses" :value="stats.proses" :icon="Hourglass" tone="info" />
          <PStatCard label="Diluluskan" :value="stats.lulus" :icon="CheckCircle2" tone="success" />
          <PStatCard label="Jumlah dipohon" :value="formatRinggit(stats.nilai).replace('.00', '')" :icon="Wallet" tone="brand" />
        </div>

        <!-- Latest application -->
        <router-link
          v-if="terkini"
          :to="`/pemohon/permohonan/${terkini.routeId}`"
          class="group relative block overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-[0_1px_2px_rgba(16,24,40,0.04),0_8px_24px_-12px_rgba(16,24,40,0.12)] transition-shadow hover:shadow-[0_1px_2px_rgba(16,24,40,0.04),0_12px_32px_-12px_rgba(16,24,40,0.18)] sm:p-6"
        >
          <span class="absolute inset-y-0 left-0 w-1 bg-brand-600" aria-hidden="true" />
          <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:gap-8">
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-brand-600">Permohonan terkini</span>
                <PBadge v-if="terkini.contoh" tone="neutral">contoh</PBadge>
              </div>
              <div class="mt-3 flex items-start gap-3.5">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
                  <Store class="h-6 w-6" aria-hidden="true" />
                </span>
                <div class="min-w-0">
                  <p class="truncate text-lg font-semibold text-ink-900">{{ terkini.produk }}</p>
                  <p class="font-mono text-xs text-slate-500">{{ terkini.noRujukan }} · Dihantar {{ terkini.tarikhLabel }}</p>
                </div>
              </div>
              <div class="mt-4 flex flex-wrap items-end gap-x-6 gap-y-2">
                <div>
                  <p class="text-xs text-slate-500">Jumlah dipohon</p>
                  <p class="text-2xl font-bold tracking-tight text-ink-900">{{ formatRinggit(terkini.jumlah) }}</p>
                </div>
                <PBadge :tone="terkini.tone" dot size="md">{{ terkini.memuat ? "Memuatkan…" : terkini.statusLabel }}</PBadge>
              </div>
            </div>
            <div class="lg:w-[44%]">
              <StageTracker :stage="terkini.stage" :outcome="terkini.outcome" />
              <p v-if="terkini.mesej" class="mt-3 text-sm text-slate-600">{{ terkini.mesej }}</p>
              <p class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600 group-hover:gap-2.5 transition-all">
                Lihat butiran <ArrowRight class="h-4 w-4" aria-hidden="true" />
              </p>
            </div>
          </div>
        </router-link>

        <!-- Toolbar -->
        <div class="space-y-3">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative flex-1">
              <Search class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
              <input v-model="carian" type="search" :class="pInputClass" class="pl-10!" placeholder="Cari no. rujukan atau produk" aria-label="Cari permohonan" />
            </div>
            <div class="flex items-center gap-2">
              <div class="relative">
                <ArrowUpDown class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
                <select v-model="susunan" :class="pInputClass" class="w-auto! pl-9!" aria-label="Susun mengikut">
                  <option value="terbaru">Terbaru</option>
                  <option value="terlama">Terlama</option>
                  <option value="jumlah">Jumlah tertinggi</option>
                </select>
              </div>
              <button
                v-if="pemohon.permohonanSaya.length"
                type="button"
                class="flex h-[42px] w-[42px] shrink-0 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-500 transition-colors hover:text-ink-900"
                :disabled="loading"
                aria-label="Muat semula status"
                title="Muat semula status"
                @click="refresh"
              >
                <RefreshCw class="h-4 w-4" :class="loading ? 'animate-spin' : ''" aria-hidden="true" />
              </button>
            </div>
          </div>
          <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:px-0" role="group" aria-label="Tapis mengikut status">
            <button
              v-for="c in chips"
              :key="c.value"
              type="button"
              class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-full border px-3.5 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-100"
              :class="penapis === c.value ? 'border-ink-900 bg-ink-900 text-white' : 'border-slate-200 bg-white text-ink-700 hover:border-slate-300'"
              :aria-pressed="penapis === c.value"
              @click="penapis = c.value"
            >
              {{ c.label }}
              <span class="rounded-full px-1.5 text-xs tabular-nums" :class="penapis === c.value ? 'bg-white/15' : 'bg-slate-100 text-slate-500'">{{ c.count }}</span>
            </button>
          </div>
        </div>

        <div v-if="!senarai.length" class="rounded-2xl border border-slate-200/80 bg-white">
          <PEmptyState :icon="SearchX" title="Tiada padanan" description="Tiada permohonan sepadan dengan carian atau penapis anda.">
            <PButton variant="outline" size="sm" @click="setSemula">Set semula penapis</PButton>
          </PEmptyState>
        </div>

        <template v-else>
          <!-- Desktop table -->
          <div class="hidden overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-[0_1px_2px_rgba(16,24,40,0.04)] md:block">
            <table class="w-full text-left text-sm">
              <thead class="border-b border-slate-100 bg-slate-50/70 text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                  <th scope="col" class="px-5 py-3">Permohonan</th>
                  <th scope="col" class="px-4 py-3">Tarikh</th>
                  <th scope="col" class="px-4 py-3 text-right">Jumlah</th>
                  <th scope="col" class="px-4 py-3">Kemajuan</th>
                  <th scope="col" class="px-4 py-3">Status</th>
                  <th scope="col" class="w-10 px-3 py-3"><span class="sr-only">Buka</span></th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr
                  v-for="r in senarai"
                  :key="r.key"
                  class="cursor-pointer transition-colors hover:bg-slate-50/80"
                  @click="$router.push(`/pemohon/permohonan/${r.routeId}`)"
                >
                  <td class="px-5 py-4">
                    <div class="flex items-center gap-3">
                      <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl" :class="r.contoh ? 'bg-slate-100 text-slate-500' : 'bg-brand-50 text-brand-600'">
                        <Store class="h-5 w-5" aria-hidden="true" />
                      </span>
                      <div class="min-w-0">
                        <router-link :to="`/pemohon/permohonan/${r.routeId}`" class="font-semibold text-ink-900 hover:text-brand-700" @click.stop>
                          {{ r.produk }}
                        </router-link>
                        <p class="flex items-center gap-1.5 font-mono text-xs text-slate-500">
                          {{ r.noRujukan }}
                          <span v-if="r.contoh" class="rounded bg-slate-100 px-1.5 font-sans text-[10px] font-medium uppercase tracking-wide text-slate-500">contoh</span>
                        </p>
                      </div>
                    </div>
                  </td>
                  <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ r.tarikhLabel }}</td>
                  <td class="whitespace-nowrap px-4 py-4 text-right font-semibold tabular-nums text-ink-900">{{ formatRinggit(r.jumlah) }}</td>
                  <td class="w-56 px-4 py-4"><StageTracker :stage="r.stage" :outcome="r.outcome" size="sm" /></td>
                  <td class="px-4 py-4">
                    <span v-if="r.memuat" class="inline-block h-5 w-24 animate-pulse rounded-full bg-slate-200/70" />
                    <PBadge v-else :tone="r.tone" dot>{{ r.statusLabel }}</PBadge>
                  </td>
                  <td class="px-3 py-4 text-slate-300"><ChevronRight class="h-4 w-4" aria-hidden="true" /></td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Mobile cards -->
          <ul class="space-y-3 md:hidden">
            <li v-for="r in senarai" :key="r.key">
              <router-link
                :to="`/pemohon/permohonan/${r.routeId}`"
                class="block rounded-2xl border border-slate-200/80 bg-white p-4 shadow-[0_1px_2px_rgba(16,24,40,0.04)] active:bg-slate-50"
              >
                <div class="flex items-start gap-3">
                  <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl" :class="r.contoh ? 'bg-slate-100 text-slate-500' : 'bg-brand-50 text-brand-600'">
                    <Store class="h-5 w-5" aria-hidden="true" />
                  </span>
                  <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-2">
                      <p class="truncate font-semibold text-ink-900">{{ r.produk }}</p>
                      <p class="shrink-0 font-semibold tabular-nums text-ink-900">{{ formatRinggit(r.jumlah).replace(".00", "") }}</p>
                    </div>
                    <p class="mt-0.5 flex items-center gap-1.5 font-mono text-xs text-slate-500">
                      {{ r.noRujukan }}
                      <span v-if="r.contoh" class="rounded bg-slate-100 px-1.5 font-sans text-[10px] font-medium uppercase tracking-wide text-slate-500">contoh</span>
                    </p>
                  </div>
                </div>
                <div class="mt-3.5"><StageTracker :stage="r.stage" :outcome="r.outcome" size="sm" /></div>
                <div class="mt-3 flex items-center justify-between">
                  <span v-if="r.memuat" class="inline-block h-5 w-24 animate-pulse rounded-full bg-slate-200/70" />
                  <PBadge v-else :tone="r.tone" dot>{{ r.statusLabel }}</PBadge>
                  <span class="text-xs text-slate-500">{{ r.tarikhLabel }}</span>
                </div>
              </router-link>
            </li>
          </ul>
        </template>
      </template>
    </div>
  </PemohonLayout>
</template>
