<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { AlertTriangle, Building2, CreditCard, Gauge, Layers, Loader2, MousePointerClick, Scale, Sparkles } from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import {
  getPenilaianPantasPermohonan,
  getPenilaianPantasRun,
  listPenilaianPantas,
  runPenilaianPantasStep,
  startPenilaianPantas,
} from "@/api/sppt";
import { useConfirmDialog } from "@/composables/useConfirmDialog";
import { useToast } from "@/composables/useToast";
import type {
  PpDataAlternatif,
  PpEkycHasil,
  PpFaceResult,
  PpFraudResult,
  PpKeputusanResult,
  PpLangkahKod,
  PpPermohonanDetail,
  PpPermohonanRow,
  PpRun,
  PpSkorKreditHasil,
} from "@/types";
import AltCreditScorePanel from "@/components/sppt/penilaian-pantas/AltCreditScorePanel.vue";
import ApplicationList from "@/components/sppt/penilaian-pantas/ApplicationList.vue";
import ArchitectureDiagram from "@/components/sppt/penilaian-pantas/ArchitectureDiagram.vue";
import DataAlternatifForm from "@/components/sppt/penilaian-pantas/DataAlternatifForm.vue";
import EkycPanel from "@/components/sppt/penilaian-pantas/EkycPanel.vue";
import FairnessPanel from "@/components/sppt/penilaian-pantas/FairnessPanel.vue";
import FraudPanel from "@/components/sppt/penilaian-pantas/FraudPanel.vue";
import LiveTimerCard from "@/components/sppt/penilaian-pantas/LiveTimerCard.vue";
import PipelineTimeline from "@/components/sppt/penilaian-pantas/PipelineTimeline.vue";
import RecommendationPanel from "@/components/sppt/penilaian-pantas/RecommendationPanel.vue";

const route = useRoute();
const router = useRouter();
const toast = useToast();
const confirmDialog = useConfirmDialog();

/** Step order. `kod` is the URL/value (snake_case); `key` is the camelCased object key inside run.langkah. */
const STEPS: { kod: PpLangkahKod; key: string }[] = [
  { kod: "ekyc", key: "ekyc" },
  { kod: "peraturan", key: "peraturan" },
  { kod: "penipuan", key: "penipuan" },
  { kod: "skor_kredit", key: "skorKredit" },
  { kod: "cadangan", key: "cadangan" },
];

function stepOf(r: PpRun | null | undefined, s: { kod: string; key: string }) {
  return r?.langkah?.[s.key] ?? r?.langkah?.[s.kod] ?? null;
}
function hasilOf<T>(kod: PpLangkahKod): T | null {
  const s = STEPS.find((x) => x.kod === kod)!;
  const step = stepOf(run.value, s);
  return step?.status === "selesai" ? ((step.hasil ?? null) as T | null) : null;
}

// ─── Tabs ────────────────────────────────────────────────────────────────────
type Tab = "penilaian" | "keadilan" | "senibina";
const tab = ref<Tab>("penilaian");
const TABS: { id: Tab; label: string; icon: typeof Gauge }[] = [
  { id: "penilaian", label: "Penilaian", icon: Gauge },
  { id: "keadilan", label: "Kawalan Bias", icon: Scale },
  { id: "senibina", label: "Seni Bina", icon: Layers },
];

// ─── Application list ────────────────────────────────────────────────────────
const rows = ref<PpPermohonanRow[]>([]);
const total = ref<number | null>(null);
const listLoading = ref(true);
const listRefreshing = ref(false);
const listError = ref("");
const q = ref("");
let qTimer: number | null = null;
let listTimer: number | null = null;

async function loadList(silent = false) {
  if (silent) listRefreshing.value = true;
  else listLoading.value = true;
  try {
    const res = await listPenilaianPantas({ page: 1, limit: 50, q: q.value.trim() || undefined });
    rows.value = Array.isArray(res?.data) ? res.data : [];
    const t = Number((res?.meta as Record<string, unknown> | undefined)?.total);
    total.value = Number.isFinite(t) ? t : rows.value.length;
    listError.value = "";
  } catch (e: unknown) {
    listError.value = e instanceof Error ? e.message : "Gagal memuatkan senarai permohonan";
  } finally {
    listLoading.value = false;
    listRefreshing.value = false;
  }
}

watch(q, () => {
  if (qTimer !== null) window.clearTimeout(qTimer);
  qTimer = window.setTimeout(() => void loadList(true), 300);
});

// ─── Selected application ────────────────────────────────────────────────────
const selectedId = ref<number | null>(null);
const detail = ref<PpPermohonanDetail | null>(null);
const detailLoading = ref(false);
const detailError = ref("");
const run = ref<PpRun | null>(null);

async function loadDetail(id: number, silent = false) {
  if (!silent) {
    detailLoading.value = true;
    detailError.value = "";
  }
  try {
    const res = await getPenilaianPantasPermohonan(id);
    if (selectedId.value !== id) return;
    detail.value = res?.data ?? null;
    run.value = res?.data?.penilaian ?? null;
    detailError.value = "";
  } catch (e: unknown) {
    if (selectedId.value !== id) return;
    if (!silent) {
      detail.value = null;
      run.value = null;
    }
    detailError.value = e instanceof Error ? e.message : "Gagal memuatkan permohonan";
  } finally {
    if (selectedId.value === id) detailLoading.value = false;
  }
}

function select(id: number) {
  if (!Number.isFinite(id)) return;
  if (String(route.query.id ?? "") !== String(id)) {
    void router.replace({ query: { ...route.query, id: String(id) } });
  }
  if (selectedId.value === id && detail.value) return;
  selectedId.value = id;
  detail.value = null;
  run.value = null;
  tab.value = "penilaian";
  void loadDetail(id);
  window.scrollTo({ top: 0, behavior: "smooth" });
}

watch(
  () => route.query.id,
  (v) => {
    const id = Number(v);
    if (v && Number.isFinite(id) && id !== selectedId.value) select(id);
  },
);

// ─── Pipeline driver ─────────────────────────────────────────────────────────
const driving = ref<{ runId: number; permohonanId: number; kod: PpLangkahKod; since: number } | null>(null);
const starting = ref(false);

const isDrivingThis = computed(() => !!driving.value && driving.value.runId === run.value?.id);

function applyRun(r: PpRun | null | undefined) {
  if (r && r.permohonanId === selectedId.value) run.value = r;
}

function fmtDuration(ms?: number | null) {
  if (typeof ms !== "number") return "";
  const s = ms / 1000;
  return s >= 60 ? `${Math.floor(s / 60)} min ${Math.round(s % 60)} s` : `${s.toFixed(1)} s`;
}

async function drive(start: PpRun) {
  let current = start;
  for (const s of STEPS) {
    if (stepOf(current, s)?.status === "selesai") continue;
    driving.value = { runId: current.id, permohonanId: current.permohonanId, kod: s.kod, since: Date.now() };
    try {
      const res = await runPenilaianPantasStep(current.id, s.kod);
      if (!res?.data) break;
      current = res.data;
      applyRun(current);
      const st = stepOf(current, s);
      if (st?.status === "ralat") {
        toast.error("Langkah gagal", st.ringkasan || "Cuba “Teruskan” untuk menjalankan semula langkah ini.", 6000);
        break;
      }
    } catch (e: unknown) {
      toast.error("Penilaian terhenti", e instanceof Error ? e.message : undefined, 6000);
      break;
    }
  }
  driving.value = null;
  if (current.status === "selesai") {
    toast.success("Penilaian selesai", `Cadangan sedia dalam ${fmtDuration(current.jumlahMs)} masa pemprosesan AI.`, 5000);
  }
  void loadList(true);
}

async function startRun() {
  const id = selectedId.value;
  if (!id || starting.value || driving.value) return;
  if (run.value?.keputusanPegawai) {
    const ok = await confirmDialog.confirm({
      title: "Nilai semula permohonan ini?",
      message: "Keputusan pegawai telah direkodkan untuk penilaian terdahulu. Penilaian baharu akan dicipta untuk rujukan.",
      confirmText: "Nilai semula",
      cancelText: "Batal",
    });
    if (!ok) return;
  }
  starting.value = true;
  try {
    const res = await startPenilaianPantas(id);
    const r = res?.data;
    if (!r) throw new Error("Respons pelayan tidak lengkap");
    applyRun(r);
    starting.value = false;
    await drive(r);
  } catch (e: unknown) {
    toast.error("Gagal memulakan penilaian", e instanceof Error ? e.message : undefined);
  } finally {
    starting.value = false;
  }
}

function resume() {
  if (run.value && !driving.value) void drive(run.value);
}

// ─── Polling for applicant-portal runs ───────────────────────────────────────
const isPemohonRunInProgress = computed(
  () => run.value?.status === "berjalan" && run.value?.sumber === "pemohon" && !isDrivingThis.value,
);
let pollTimer: number | null = null;
let pollBusy = false;

async function pollOnce() {
  const r = run.value;
  if (!r || pollBusy || !isPemohonRunInProgress.value) return;
  pollBusy = true;
  try {
    const res = await getPenilaianPantasRun(r.id);
    applyRun(res?.data);
    if (res?.data?.status === "selesai") void loadList(true);
  } catch {
    /* transient — try again next tick */
  } finally {
    pollBusy = false;
  }
}

watch(
  isPemohonRunInProgress,
  (v) => {
    if (v && pollTimer === null) pollTimer = window.setInterval(() => void pollOnce(), 2000);
    if (!v && pollTimer !== null) {
      window.clearInterval(pollTimer);
      pollTimer = null;
    }
  },
  { immediate: true },
);

const firstPendingKod = computed<string | null>(() => {
  const s = STEPS.find((x) => stepOf(run.value, x)?.status !== "selesai");
  return s?.kod ?? null;
});

const activeKod = computed(() => {
  if (isDrivingThis.value) return driving.value!.kod;
  if (isPemohonRunInProgress.value) return firstPendingKod.value;
  return null;
});

const canResume = computed(() => {
  const r = run.value;
  if (!r || r.status !== "berjalan" || isDrivingThis.value || driving.value) return false;
  if (r.sumber !== "pemohon") return true;
  const hasError = STEPS.some((s) => stepOf(r, s)?.status === "ralat");
  const updated = Date.parse(r.updatedAt ?? r.mulaPada ?? "");
  const stale = Number.isFinite(updated) && Date.now() - updated > 90000;
  return hasError || stale;
});

// ─── Detail panel data ───────────────────────────────────────────────────────
const ekycHasil = computed(() => hasilOf<PpEkycHasil>("ekyc"));
const fraudHasil = computed(() => hasilOf<PpFraudResult>("penipuan"));
const kreditHasil = computed(() => hasilOf<PpSkorKreditHasil>("skor_kredit"));
const ekycKeputusan = computed(() => {
  const st = stepOf(run.value, STEPS[0]);
  return st?.status === "selesai" ? (st.keputusan ?? null) : null;
});
const showRecommendation = computed(() => run.value?.status === "selesai" && !!run.value?.cadangan);

function onFaceVerified(r: PpFaceResult) {
  if (detail.value) detail.value = { ...detail.value, ekycWajah: r };
}
function onDataSaved(d: PpDataAlternatif) {
  if (detail.value) detail.value = { ...detail.value, dataAlternatif: d };
}
function onDecided(res: PpKeputusanResult) {
  applyRun(res?.penilaian);
  void loadList(true);
}

// ─── Summary helpers ─────────────────────────────────────────────────────────
const p = computed(() => detail.value?.permohonan ?? null);
const rm = new Intl.NumberFormat("ms-MY", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
function fmtRM(v?: number | string | null) {
  const n = Number(v);
  return v !== null && v !== undefined && v !== "" && Number.isFinite(n) ? `RM ${rm.format(n)}` : "—";
}
function maskIc(v?: string | null) {
  const d = String(v ?? "").replace(/\D/g, "");
  return d.length >= 8 ? `${d.slice(0, 6)}-**-**${d.slice(-2)}` : v || "—";
}
function fmtDate(iso?: string | null) {
  if (!iso) return "—";
  const d = new Date(iso);
  return Number.isNaN(d.getTime()) ? "—" : d.toLocaleString("ms-MY", { dateStyle: "medium", timeStyle: "medium" });
}

// ─── Lifecycle ───────────────────────────────────────────────────────────────
function tickList() {
  if (document.visibilityState === "visible" && tab.value === "penilaian" && !listRefreshing.value) void loadList(true);
}

onMounted(async () => {
  const qid = Number(route.query.id);
  if (route.query.id && Number.isFinite(qid)) select(qid);
  await loadList();
  listTimer = window.setInterval(tickList, 10000);
});

onBeforeUnmount(() => {
  if (listTimer !== null) window.clearInterval(listTimer);
  if (pollTimer !== null) window.clearInterval(pollTimer);
  if (qTimer !== null) window.clearTimeout(qTimer);
});
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-[1600px] space-y-4">
      <!-- Header -->
      <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
          <h1 class="page-title flex items-center gap-2">Enjin Penilaian Pantas AI</h1>
          <p class="text-sm text-slate-500">
            POC 1 · Hantar → cadangan keputusan &lt; 7 minit · Keputusan akhir oleh pegawai (human-in-the-loop)
          </p>
        </div>
        <nav class="inline-flex rounded-lg border border-slate-200 bg-white p-1 shadow-sm">
          <button
            v-for="t in TABS"
            :key="t.id"
            type="button"
            class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
            :class="tab === t.id ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'"
            @click="tab = t.id"
          >
            <component :is="t.icon" class="h-4 w-4" /> {{ t.label }}
          </button>
        </nav>
      </div>

      <!-- Penilaian -->
      <div v-show="tab === 'penilaian'" class="grid items-start gap-4 lg:grid-cols-[300px_minmax(0,1fr)]">
        <ApplicationList
          v-model:q="q"
          class="max-h-[420px] lg:sticky lg:top-4 lg:max-h-[calc(100vh-7rem)]"
          :rows="rows"
          :total="total"
          :loading="listLoading"
          :refreshing="listRefreshing"
          :error="listError"
          :selected-id="selectedId"
          @select="select"
          @refresh="loadList(true)"
        />

        <div class="min-w-0 space-y-4">
          <div
            v-if="!selectedId"
            class="flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-slate-300 bg-white px-6 py-20 text-center"
          >
            <MousePointerClick class="h-8 w-8 text-slate-300" />
            <p class="text-sm font-semibold text-slate-700">Pilih permohonan untuk dinilai</p>
            <p class="max-w-md text-xs text-slate-500">
              Permohonan yang dihantar melalui portal pemohon akan muncul secara langsung di senarai kiri. Enjin AI menjalankan e-KYC,
              semakan kelayakan, analisis rangkaian penipuan dan skor kredit data alternatif, kemudian mencadangkan laluan keputusan.
            </p>
          </div>

          <div v-else-if="detailLoading && !detail" class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white p-6 text-sm text-slate-500">
            <Loader2 class="h-4 w-4 animate-spin" /> Memuatkan permohonan…
          </div>

          <p v-else-if="detailError && !detail" class="flex items-center gap-2 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
            <AlertTriangle class="h-4 w-4" /> {{ detailError }}
            <button type="button" class="ml-auto font-semibold underline" @click="selectedId && loadDetail(selectedId)">Cuba semula</button>
          </p>

          <template v-else-if="detail && p">
            <!-- Summary strip -->
            <article class="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm">
              <div class="flex flex-wrap items-start gap-x-6 gap-y-2">
                <div class="min-w-0">
                  <p class="font-mono text-xs text-slate-500">{{ p.noRujukan || `#${p.id}` }}</p>
                  <p class="truncate text-lg font-bold text-slate-900">{{ p.nama || "—" }}</p>
                  <p v-if="p.namaPerniagaan" class="flex items-center gap-1 truncate text-xs text-slate-500">
                    <Building2 class="h-3 w-3" /> {{ p.namaPerniagaan }}
                  </p>
                </div>
                <dl class="ml-auto grid grid-cols-2 gap-x-6 gap-y-1 text-xs sm:grid-cols-4">
                  <div>
                    <dt class="text-slate-400">No. MyKad</dt>
                    <dd class="flex items-center gap-1 font-mono font-medium text-slate-800">
                      <CreditCard class="h-3 w-3 text-slate-400" /> {{ maskIc(p.noIc) }}
                    </dd>
                  </div>
                  <div>
                    <dt class="text-slate-400">Jumlah dipohon</dt>
                    <dd class="font-semibold text-slate-900">{{ fmtRM(p.jumlahPermohonan) }}<span v-if="p.tempohPembiayaan" class="font-normal text-slate-500"> · {{ p.tempohPembiayaan }} bln</span></dd>
                  </div>
                  <div>
                    <dt class="text-slate-400">Cawangan</dt>
                    <dd class="font-medium text-slate-800">{{ p.cawangan || "—" }}</dd>
                  </div>
                  <div>
                    <dt class="text-slate-400">Dihantar pada</dt>
                    <dd class="font-medium text-slate-800">{{ fmtDate(p.dihantarPada) }}</dd>
                  </div>
                </dl>
              </div>
            </article>

            <LiveTimerCard :run="run" :dihantar-pada="p.dihantarPada" :sasaran-ms="detail.sasaranMs ?? 420000" />

            <PipelineTimeline
              :run="run"
              :active-kod="activeKod"
              :active-since="isDrivingThis ? driving?.since ?? null : null"
              :busy="starting || !!driving"
              :can-resume="canResume"
              :polling="isPemohonRunInProgress"
              @start="startRun"
              @resume="resume"
            />

            <RecommendationPanel v-if="showRecommendation && run" :key="`rec-${run.id}`" :run="run" @decided="onDecided" @refresh="loadDetail(p.id, true)" />

            <EkycPanel
              :key="`ekyc-${p.id}`"
              :permohonan-id="p.id"
              :hasil="ekycHasil"
              :ekyc-wajah="detail.ekycWajah"
              :ada-mykad="p.adaMykad"
              :keputusan="ekycKeputusan"
              @face-verified="onFaceVerified"
            />

            <FraudPanel :key="`fraud-${p.id}`" :permohonan-id="p.id" :hasil="fraudHasil" @select="select" />

            <DataAlternatifForm :key="`alt-${p.id}`" :permohonan-id="p.id" :initial="detail.dataAlternatif" @saved="onDataSaved" />

            <AltCreditScorePanel :hasil="kreditHasil" />

            <p class="flex items-center justify-center gap-1.5 pb-2 text-[11px] text-slate-400">
              <Sparkles class="h-3 w-3" /> Cadangan dijana AI untuk membantu pegawai. Setiap keputusan direkodkan dalam log audit.
            </p>
          </template>
        </div>
      </div>

      <FairnessPanel v-if="tab === 'keadilan'" />
      <ArchitectureDiagram v-if="tab === 'senibina'" :run="run" />
    </div>
  </AdminLayout>
</template>
