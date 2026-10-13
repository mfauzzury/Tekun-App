<script setup lang="ts">
import { computed, ref } from "vue";
import { ArrowRight, ArrowUpDown, ChevronRight, FileText, MoreVertical, PenLine, Plus, RefreshCw, Search, SearchX, Store } from "lucide-vue-next";

import StageTracker from "@/components/pemohon/status/StageTracker.vue";
import { deriveView, formatTarikh, useApplicationStatuses } from "@/components/pemohon/status/useApplicationStatus";
import PBadge from "@/components/pemohon/ui/PBadge.vue";
import PButton from "@/components/pemohon/ui/PButton.vue";
import PEmptyState from "@/components/pemohon/ui/PEmptyState.vue";
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
const todayLabel = new Date().toLocaleDateString("ms-MY", { weekday: "long", day: "numeric", month: "long", year: "numeric" });
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
      <header class="flex flex-col gap-3 pt-6 sm:flex-row sm:items-end sm:justify-between lg:pt-2">
        <div class="min-w-0">
          <p class="text-sm capitalize text-slate-500">{{ todayLabel }}</p>
          <h1 class="mt-1 text-[28px] font-bold tracking-tight text-ink-950 sm:text-[32px]">Permohonan saya</h1>
          <p class="mt-1 text-sm text-slate-500">Jejak setiap permohonan pembiayaan anda — dari penghantaran hingga keputusan.</p>
        </div>
        <router-link
          to="/pemohon/permohonan/baru"
          class="inline-flex items-center gap-2 self-start rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-ink-800 transition-colors hover:border-brand-300 hover:bg-brand-50 sm:self-auto"
        >
          <component :is="pemohon.draftPermohonan ? PenLine : Plus" class="h-4 w-4 text-brand-600" aria-hidden="true" />
          {{ pemohon.draftPermohonan ? "Sambung draf" : "Mohon pembiayaan" }}
        </router-link>
      </header>

      <div v-if="!rows.length" class="rounded-3xl border border-slate-200 bg-white">
        <PEmptyState :icon="FileText" title="Belum ada permohonan" description="Mulakan permohonan pembiayaan pertama anda. Ia hanya mengambil masa beberapa minit.">
          <PButton to="/pemohon/permohonan/baru"><Plus class="h-4 w-4" aria-hidden="true" /> Mohon pembiayaan</PButton>
        </PEmptyState>
      </div>

      <template v-else>
        <!-- Bento: 2×2 stats + latest application -->
        <div class="grid grid-cols-2 gap-4 xl:grid-cols-[1fr_1fr_2.4fr]">
          <div class="relative rounded-3xl bg-brand-600 p-5 text-white shadow-lg shadow-brand-600/25">
            <MoreVertical class="absolute right-4 top-5 h-5 w-5 text-white/70" aria-hidden="true" />
            <p class="text-[34px] font-bold leading-none">{{ stats.jumlah }}</p>
            <p class="mt-3 text-[15px] text-white/90">Jumlah permohonan</p>
            <p class="mt-2 text-xs text-white/75">{{ pemohon.permohonanSaya.length }} dihantar dalam talian</p>
          </div>
          <div class="relative rounded-3xl border border-slate-200 bg-white p-5">
            <MoreVertical class="absolute right-4 top-5 h-5 w-5 text-slate-300" aria-hidden="true" />
            <p class="text-[34px] font-bold leading-none text-ink-950">{{ stats.proses }}</p>
            <p class="mt-3 text-[15px] text-ink-800">Dalam proses</p>
            <p class="mt-2 text-xs text-slate-500">Sedang dinilai</p>
          </div>
          <div class="relative col-start-1 rounded-3xl border border-slate-200 bg-white p-5 xl:row-start-2">
            <MoreVertical class="absolute right-4 top-5 h-5 w-5 text-slate-300" aria-hidden="true" />
            <p class="text-[34px] font-bold leading-none text-ink-950">{{ stats.lulus }}</p>
            <p class="mt-3 text-[15px] text-ink-800">Diluluskan</p>
            <p class="mt-2 text-xs text-slate-500">Pembiayaan diterima</p>
          </div>
          <div class="relative rounded-3xl bg-brand-50 p-5 xl:col-start-2 xl:row-start-2">
            <MoreVertical class="absolute right-4 top-5 h-5 w-5 text-brand-300" aria-hidden="true" />
            <p class="text-[26px] font-bold leading-none text-brand-800">{{ formatRinggit(stats.nilai).replace(".00", "") }}</p>
            <p class="mt-3 text-[15px] text-brand-800">Jumlah dipohon</p>
            <p class="mt-2 text-xs text-brand-800/70">Semua permohonan</p>
          </div>

          <router-link
            v-if="terkini"
            :to="`/pemohon/permohonan/${terkini.routeId}`"
            class="group col-span-2 flex flex-col justify-between rounded-3xl bg-[#e2edff] p-6 transition-transform hover:-translate-y-0.5 sm:p-7 xl:col-span-1 xl:col-start-3 xl:row-span-2 xl:row-start-1"
          >
            <div>
              <div class="flex items-center justify-between gap-3">
                <p class="text-xs font-bold uppercase tracking-wider text-brand-700">Permohonan terkini</p>
                <PBadge :tone="terkini.tone" dot size="md">{{ terkini.memuat ? "Memuatkan…" : terkini.statusLabel }}</PBadge>
              </div>
              <h2 class="mt-3 text-[26px] font-bold leading-tight tracking-tight text-ink-950 sm:text-[30px]">
                {{ terkini.produk }} · <span class="text-brand-600">{{ formatRinggit(terkini.jumlah).replace(".00", "") }}</span>
              </h2>
              <p class="mt-1 font-mono text-xs text-ink-700">
                {{ terkini.noRujukan }} · Dihantar {{ terkini.tarikhLabel }}
                <span v-if="terkini.contoh" class="ml-1 rounded bg-white/70 px-1.5 font-sans text-[10px] font-medium uppercase tracking-wide text-slate-500">contoh</span>
              </p>
            </div>
            <div class="mt-6 rounded-2xl bg-white/70 p-4">
              <StageTracker :stage="terkini.stage" :outcome="terkini.outcome" />
              <p v-if="terkini.mesej" class="mt-3 text-sm text-ink-700">{{ terkini.mesej }}</p>
            </div>
            <p class="mt-5 inline-flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-ink-950 transition-all group-hover:gap-3">
              Lihat butiran <ArrowRight class="h-5 w-5" aria-hidden="true" />
            </p>
          </router-link>
        </div>

        <section class="rounded-3xl border border-slate-200 bg-white p-4 sm:p-5" aria-label="Senarai permohonan">
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

        <div v-if="!senarai.length" class="mt-4">
          <PEmptyState :icon="SearchX" title="Tiada padanan" description="Tiada permohonan sepadan dengan carian atau penapis anda.">
            <PButton variant="outline" size="sm" @click="setSemula">Set semula penapis</PButton>
          </PEmptyState>
        </div>

        <template v-else>
          <!-- Desktop table -->
          <div class="mt-4 hidden overflow-hidden rounded-2xl border border-slate-100 md:block">
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
                        <p class="flex items-center gap-1.5 whitespace-nowrap font-mono text-xs text-slate-500">
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
          <ul class="mt-4 space-y-3 md:hidden">
            <li v-for="r in senarai" :key="r.key">
              <router-link
                :to="`/pemohon/permohonan/${r.routeId}`"
                class="block rounded-2xl bg-[#f3f6fc] p-4 active:bg-brand-50"
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
        </section>
      </template>
    </div>
  </PemohonLayout>
</template>
