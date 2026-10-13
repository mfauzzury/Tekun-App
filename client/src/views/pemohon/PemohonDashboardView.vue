<script setup lang="ts">
import { computed } from "vue";
import { ArrowRight, MoreVertical, PenLine } from "lucide-vue-next";

import ActivityTimeline, { type TimelineEvent } from "@/components/pemohon/status/ActivityTimeline.vue";
import OnboardingChecklist from "@/components/pemohon/status/OnboardingChecklist.vue";
import { deriveView, useApplicationStatuses } from "@/components/pemohon/status/useApplicationStatus";
import PemohonLayout from "@/layouts/PemohonLayout.vue";
import { usePemohonStore } from "@/stores/pemohon";
import { formatRinggit } from "@/utils/pemohon-ui";

const pemohon = usePemohonStore();
const { snapshots } = useApplicationStatuses(() => pemohon.permohonanSaya);

const NAME_PREFIXES = /^(mohd|md|muhd|muhammad|mohamad|mohammad|mohamed)$/i;
const firstName = computed(() => {
  const words = (pemohon.profil.nama || "").trim().split(/\s+/).filter(Boolean);
  return words.find((w, i) => !NAME_PREFIXES.test(w) || i === words.length - 1) || "Usahawan";
});
const todayLabel = new Date().toLocaleDateString("ms-MY", { weekday: "long", day: "numeric", month: "long", year: "numeric" });
const hasDraft = computed(() => !!pemohon.draftPermohonan);

const DAY_MS = 86_400_000;
const weekAgo = Date.now() - 7 * DAY_MS;

// ---- Stat cards ----
const stats = computed(() => {
  const real = pemohon.permohonanSaya.map((p) => ({ k: deriveView(snapshots.value[p.id]).keputusan, at: new Date(p.dihantarPada).getTime() }));
  const sample = pemohon.permohonanList;
  const lulus = real.filter((r) => r.k === "lulus").length + sample.filter((p) => p.statusKey === "lulus").length;
  const tolak = real.filter((r) => r.k === "tolak").length + sample.filter((p) => p.statusKey === "tidak_lulus").length;
  const total = real.length + sample.length;
  return {
    total,
    totalMingguIni: real.filter((r) => r.at >= weekAgo).length,
    lulus,
    proses: total - lulus - tolak,
    tindakan: sample.filter((p) => p.statusKey === "dokumen_tambahan").length,
  };
});

/** Next due date for a monthly instalment that started on `iso`. */
function nextDue(iso: string): Date {
  const start = new Date(iso);
  const now = new Date();
  const due = new Date(now.getFullYear(), now.getMonth(), start.getDate());
  if (due.getTime() < new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime()) due.setMonth(due.getMonth() + 1);
  return due;
}

const aktif = computed(() => pemohon.pembiayaanList.filter((a) => a.statusKey !== "selesai"));
const ansuranSeterusnya = computed(() => {
  const list = aktif.value.map((a) => ({ a, due: nextDue(a.tarikhMulaIso) })).sort((x, y) => x.due.getTime() - y.due.getTime());
  return list[0] ?? null;
});

// ---- Ring: share of financing already repaid ----
const peratusDibayar = computed(() => {
  const acc = aktif.value[0];
  return acc ? Math.round((acc.ansuranDibayar / Math.max(1, acc.jumlahAnsuran)) * 100) : 0;
});
const RADIUS = 52;
const CIRC = 2 * Math.PI * RADIUS;
const ringDash = computed(() => `${(peratusDibayar.value / 100) * CIRC} ${CIRC}`);

// ---- Timeline ----
const events = computed<TimelineEvent[]>(() => {
  const out: TimelineEvent[] = [];
  const today = new Date();

  pemohon.permohonanSaya.slice(0, 3).forEach((p) => {
    const view = deriveView(snapshots.value[p.id]);
    const start = new Date(p.dihantarPada);
    out.push({
      id: `p-${p.id}`,
      title: `${p.produk || "Permohonan"} · ${p.noRujukan}`,
      start,
      end: new Date(start.getTime() + 3 * DAY_MS),
      kind: "permohonan",
      progress: view.keputusan ? 1 : (view.stage + 1) / 4,
      to: `/pemohon/permohonan/${p.id}`,
    });
  });

  aktif.value.forEach((a) => {
    const due = nextDue(a.tarikhMulaIso);
    out.push({
      id: `a-${a.id}`,
      title: `Ansuran ${a.produk} · ${formatRinggit(a.bayaranBulanan).replace(".00", "")}`,
      start: due,
      end: due,
      kind: "ansuran",
      to: `/pemohon/pembiayaan/${a.id}/bayar`,
    });
  });

  out.push(
    {
      id: "prog-1",
      title: "Bengkel Pemasaran Digital",
      start: new Date(today.getTime() + 2 * DAY_MS),
      end: new Date(today.getTime() + 4 * DAY_MS),
      kind: "program",
      progress: 0,
      sample: true,
    },
    {
      id: "prog-2",
      title: "Sesi bimbingan usahawan",
      start: new Date(today.getTime() - 2 * DAY_MS),
      end: today,
      kind: "program",
      progress: 0.7,
      sample: true,
    },
  );
  return out;
});
</script>

<template>
  <PemohonLayout>
    <div class="space-y-8 pt-6 lg:pt-2">
      <!-- Greeting -->
      <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
          <p class="text-sm capitalize text-slate-500">{{ todayLabel }}</p>
          <h1 class="mt-1 truncate text-[28px] font-bold tracking-tight text-ink-950 sm:text-[32px]">Selamat datang, {{ firstName }}</h1>
        </div>
        <router-link
          v-if="hasDraft"
          to="/pemohon/permohonan/baru"
          class="inline-flex items-center gap-2 self-start rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-ink-800 transition-colors hover:border-brand-300 hover:bg-brand-50 sm:self-auto"
        >
          <PenLine class="h-4 w-4 text-brand-600" aria-hidden="true" /> Sambung draf
        </router-link>
      </header>

      <OnboardingChecklist />

      <!-- Bento -->
      <div class="grid grid-cols-2 gap-4 xl:grid-cols-[1fr_1fr_1.75fr_1fr]">
        <!-- 2×2 stats -->
        <router-link to="/pemohon/permohonan" class="relative rounded-3xl bg-brand-600 p-5 text-white shadow-lg shadow-brand-600/25 transition-transform hover:-translate-y-0.5">
          <MoreVertical class="absolute right-4 top-5 h-5 w-5 text-white/70" aria-hidden="true" />
          <p class="text-[34px] font-bold leading-none">{{ stats.total }}</p>
          <p class="mt-3 text-[15px] text-white/90">Jumlah permohonan</p>
          <p class="mt-2 text-xs text-white/75">+{{ stats.totalMingguIni }} minggu ini</p>
        </router-link>
        <router-link to="/pemohon/permohonan" class="relative rounded-3xl border border-slate-200 bg-white p-5 transition-transform hover:-translate-y-0.5">
          <MoreVertical class="absolute right-4 top-5 h-5 w-5 text-slate-300" aria-hidden="true" />
          <p class="text-[34px] font-bold leading-none text-ink-950">{{ stats.lulus }}</p>
          <p class="mt-3 text-[15px] text-ink-800">Diluluskan</p>
          <p class="mt-2 text-xs text-slate-500">Pembiayaan diterima</p>
        </router-link>
        <router-link to="/pemohon/permohonan" class="relative col-start-1 rounded-3xl border border-slate-200 bg-white p-5 transition-transform hover:-translate-y-0.5 xl:row-start-2">
          <MoreVertical class="absolute right-4 top-5 h-5 w-5 text-slate-300" aria-hidden="true" />
          <p class="text-[34px] font-bold leading-none text-ink-950">{{ stats.proses }}</p>
          <p class="mt-3 text-[15px] text-ink-800">Dalam proses</p>
          <p class="mt-2 text-xs text-slate-500">{{ stats.tindakan }} perlu tindakan anda</p>
        </router-link>
        <router-link
          :to="ansuranSeterusnya ? `/pemohon/pembiayaan/${ansuranSeterusnya.a.id}` : '/pemohon/pembiayaan'"
          class="relative rounded-3xl bg-brand-50 p-5 transition-transform hover:-translate-y-0.5 xl:col-start-2 xl:row-start-2"
        >
          <MoreVertical class="absolute right-4 top-5 h-5 w-5 text-brand-300" aria-hidden="true" />
          <p class="text-[28px] font-bold leading-none text-brand-800">
            {{ ansuranSeterusnya ? formatRinggit(ansuranSeterusnya.a.bayaranBulanan).replace(".00", "") : "—" }}
          </p>
          <p class="mt-3 text-[15px] text-brand-800">Ansuran seterusnya</p>
          <p class="mt-2 text-xs text-brand-800/70">
            {{ ansuranSeterusnya ? ansuranSeterusnya.due.toLocaleDateString("ms-MY", { day: "numeric", month: "long" }) : "Tiada pembiayaan aktif" }}
          </p>
        </router-link>

        <!-- Promo -->
        <div class="col-span-2 flex flex-col justify-between rounded-3xl bg-[#e2edff] p-6 sm:p-8 xl:col-span-1 xl:col-start-3 xl:row-span-2 xl:row-start-1">
          <h2 class="text-[28px] font-bold leading-[1.15] tracking-tight text-ink-950 sm:text-[34px]">
            Kembangkan <span class="text-brand-600">perniagaan</span> anda bersama TEKUN
          </h2>
          <p class="mt-3 max-w-md text-sm text-ink-700">Mohon dalam talian — penilaian awal dibantu AI dalam beberapa minit, keputusan akhir oleh pegawai.</p>
          <router-link
            to="/pemohon/permohonan/baru"
            class="mt-6 inline-flex items-center gap-2 self-start text-sm font-bold uppercase tracking-wide text-ink-950 transition-all hover:gap-3"
          >
            Mohon sekarang <ArrowRight class="h-5 w-5" aria-hidden="true" />
          </router-link>
        </div>

        <!-- Ring -->
        <router-link
          to="/pemohon/pembiayaan"
          class="col-span-2 flex flex-col items-center justify-center rounded-3xl bg-[#eef3fb] p-6 text-center xl:col-span-1 xl:col-start-4 xl:row-span-2 xl:row-start-1"
        >
          <div class="relative h-36 w-36">
            <svg viewBox="0 0 120 120" class="h-36 w-36 -rotate-90" aria-hidden="true">
              <circle cx="60" cy="60" :r="RADIUS" fill="none" stroke-width="10" class="stroke-white" />
              <circle
                cx="60"
                cy="60"
                :r="RADIUS"
                fill="none"
                stroke-width="10"
                stroke-linecap="round"
                :stroke-dasharray="ringDash"
                class="stroke-brand-600 transition-[stroke-dasharray] duration-700"
              />
            </svg>
            <span class="absolute inset-0 flex items-center justify-center text-[30px] font-bold text-ink-950">{{ peratusDibayar }}%</span>
          </div>
          <p class="mt-4 text-[15px] leading-snug text-ink-800">daripada pembiayaan<br />telah dijelaskan</p>
        </router-link>
      </div>

      <!-- Timeline -->
      <ActivityTimeline :events="events" />
    </div>
  </PemohonLayout>
</template>
