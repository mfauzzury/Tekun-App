<script setup lang="ts">
import { computed, ref, watch } from "vue";
import {
  AlertTriangle,
  BadgeCheck,
  CheckCircle2,
  FileWarning,
  Gavel,
  Landmark,
  Loader2,
  Route,
  ShieldAlert,
  UserCheck,
  XCircle,
  Zap,
} from "lucide-vue-next";

import { decidePenilaianPantas } from "@/api/sppt";
import { useConfirmDialog } from "@/composables/useConfirmDialog";
import { useToast } from "@/composables/useToast";
import { useAuthStore } from "@/stores/auth";
import type { PpDisbursementCheck, PpKeputusanInput, PpKeputusanPegawai, PpKeputusanResult, PpRun } from "@/types";
import DisbursementAlertModal from "./DisbursementAlertModal.vue";
import PpBadge from "./PpBadge.vue";

const props = defineProps<{ run: PpRun }>();
const emit = defineEmits<{ (e: "decided", result: PpKeputusanResult): void; (e: "refresh"): void }>();

const toast = useToast();
const confirmDialog = useConfirmDialog();
const auth = useAuthStore();

const rm = new Intl.NumberFormat("ms-MY", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
function fmtRM(v?: number | null) {
  return typeof v === "number" && Number.isFinite(v) ? `RM ${rm.format(v)}` : "—";
}
function fmtDate(iso?: string | null) {
  if (!iso) return "—";
  const d = new Date(iso);
  return Number.isNaN(d.getTime()) ? "—" : d.toLocaleString("ms-MY", { dateStyle: "medium", timeStyle: "short" });
}

const c = computed(() => props.run.cadangan ?? null);
const laluan = computed(() => c.value?.laluan ?? props.run.laluan ?? null);
const decided = computed(() => !!props.run.keputusanPegawai);
const canDecide = computed(() => {
  const perms = auth.user?.permissions;
  return !Array.isArray(perms) || perms.includes("sppt.kelulusan") || auth.user?.role === "admin";
});

const BANNER = {
  pantas: { cls: "border-emerald-300 bg-emerald-50 text-emerald-900", icon: Zap, iconCls: "bg-emerald-600", title: "Laluan pantas — sedia untuk kelulusan satu klik" },
  semakan_pegawai: { cls: "border-amber-300 bg-amber-50 text-amber-900", icon: UserCheck, iconCls: "bg-amber-500", title: "Semakan pegawai diperlukan" },
  cadang_tolak: { cls: "border-rose-300 bg-rose-50 text-rose-900", icon: XCircle, iconCls: "bg-rose-600", title: "Dicadang tolak — pengesahan pegawai diperlukan" },
} as const;
const banner = computed(() => BANNER[(laluan.value ?? "semakan_pegawai") as keyof typeof BANNER] ?? BANNER.semakan_pegawai);

const jumlah = ref<number | string>("");
const catatan = ref("");
const catatanError = ref("");
const submitting = ref<PpKeputusanPegawai | null>(null);
const akaun = ref<Record<string, unknown> | null>(null);
const alertOpen = ref(false);
const alertCheck = ref<PpDisbursementCheck | null>(null);
const alertError = ref("");

watch(
  () => props.run.id,
  () => {
    jumlah.value = c.value?.jumlahDicadang ?? c.value?.jumlahDipohon ?? "";
    catatan.value = "";
    catatanError.value = "";
    akaun.value = null;
  },
  { immediate: true },
);

const needsCatatanForApprove = computed(() => laluan.value !== "pantas");

type ApiErr = Error & { code?: string; details?: unknown };

async function submit(input: PpKeputusanInput, fromModal = false) {
  submitting.value = input.keputusan;
  catatanError.value = "";
  alertError.value = "";
  try {
    const res = await decidePenilaianPantas(props.run.id, input);
    const data = res?.data;
    akaun.value = (data?.akaun as Record<string, unknown> | null) ?? null;
    alertOpen.value = false;
    const msg = { lulus: "Permohonan diluluskan", tolak: "Permohonan ditolak", rujuk: "Dirujuk untuk semakan penuh" }[input.keputusan];
    toast.success(msg, data?.akaun ? "Akaun pembiayaan telah dibuka." : undefined);
    if (data) emit("decided", data);
  } catch (e: unknown) {
    const err = e as ApiErr;
    if (err?.code === "DISBURSEMENT_ALERT") {
      alertCheck.value = (err.details as PpDisbursementCheck | null) ?? null;
      alertOpen.value = true;
    } else if (err?.code === "VALIDATION_ERROR") {
      const d = err.details as Record<string, string[] | string> | null;
      const first = d?.catatan ?? d?.jumlahDiluluskan ?? d?.jumlah_diluluskan;
      const text = (Array.isArray(first) ? first[0] : first) || err.message;
      if (fromModal) alertError.value = text;
      else catatanError.value = text;
    } else if (err?.code === "CONFLICT") {
      toast.error("Tidak dapat direkodkan", err.message);
      emit("refresh");
    } else if (fromModal) {
      alertError.value = err?.message || "Permintaan gagal";
    } else {
      toast.error("Keputusan gagal direkodkan", err?.message);
    }
  } finally {
    submitting.value = null;
  }
}

function jumlahNum(): number | undefined {
  const n = Number(jumlah.value);
  return jumlah.value !== "" && Number.isFinite(n) && n >= 0 ? n : undefined;
}

function approve() {
  if (needsCatatanForApprove.value && !catatan.value.trim()) {
    catatanError.value = "Catatan diperlukan untuk kelulusan di luar laluan pantas.";
    return;
  }
  void submit({ keputusan: "lulus", jumlahDiluluskan: jumlahNum(), catatan: catatan.value.trim() || undefined });
}

function refer() {
  if (!catatan.value.trim()) {
    catatanError.value = "Catatan diperlukan untuk merujuk semakan penuh.";
    return;
  }
  void submit({ keputusan: "rujuk", catatan: catatan.value.trim() });
}

async function reject() {
  if (!catatan.value.trim()) {
    catatanError.value = "Catatan sebab penolakan diperlukan.";
    return;
  }
  const ok = await confirmDialog.confirm({
    title: "Tolak permohonan ini?",
    message: "Keputusan tolak akan direkodkan dalam log audit dan pemohon akan dimaklumkan.",
    confirmText: "Tolak",
    cancelText: "Batal",
    destructive: true,
  });
  if (ok) void submit({ keputusan: "tolak", catatan: catatan.value.trim() });
}

function confirmAlert(justifikasi: string) {
  void submit({ keputusan: "lulus", jumlahDiluluskan: jumlahNum(), catatan: justifikasi, akuiAmaran: true }, true);
}

const keputusanLabel = computed(
  () => ({ lulus: "Diluluskan", tolak: "Ditolak", rujuk: "Dirujuk semakan penuh" })[props.run.keputusanPegawai ?? "rujuk"],
);
const noAkaun = computed(() => {
  const a = akaun.value;
  return (a?.noAkaun ?? a?.nomborAkaun ?? a?.noAkaunPembiayaan ?? a?.id ?? null) as string | number | null;
});
</script>

<template>
  <article class="overflow-hidden rounded-lg border-2 bg-white shadow-sm" :class="banner.cls.split(' ')[0]">
    <!-- Banner -->
    <div class="flex items-start gap-3 border-b px-4 py-3" :class="banner.cls">
      <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-white" :class="banner.iconCls">
        <component :is="banner.icon" class="h-5 w-5" />
      </div>
      <div class="min-w-0 flex-1">
        <p class="text-[10px] font-semibold uppercase tracking-wider opacity-70">Cadangan AI · Langkah 5</p>
        <p class="text-base font-bold">{{ banner.title }}</p>
        <p v-if="c?.label" class="text-xs opacity-80">{{ c.label }}</p>
      </div>
      <PpBadge v-if="c?.amaranPenipuan" tone="rose" size="sm"><ShieldAlert class="h-3.5 w-3.5" /> Amaran penipuan</PpBadge>
    </div>

    <div class="space-y-4 p-4">
      <div class="grid grid-cols-2 gap-2 md:grid-cols-4">
        <div class="rounded-lg bg-slate-50 px-3 py-2">
          <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Skor kredit</p>
          <p class="text-lg font-bold text-slate-900">{{ c?.skorKredit ?? run.skorKredit ?? "—" }}<span class="text-xs font-medium text-slate-400"> · Gred {{ c?.gred ?? "—" }}</span></p>
        </div>
        <div class="rounded-lg bg-slate-50 px-3 py-2">
          <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Tahap risiko</p>
          <p class="text-lg font-bold capitalize text-slate-900">{{ c?.tahapRisiko ?? run.tahapRisiko ?? "—" }}</p>
        </div>
        <div class="rounded-lg bg-slate-50 px-3 py-2">
          <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Dipohon → dicadang</p>
          <p class="text-sm font-bold text-slate-900">{{ fmtRM(c?.jumlahDipohon) }}</p>
          <p class="text-sm font-bold text-emerald-700">→ {{ fmtRM(c?.jumlahDicadang) }}</p>
        </div>
        <div class="rounded-lg bg-slate-50 px-3 py-2">
          <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">Ansuran · tempoh</p>
          <p class="text-sm font-bold text-slate-900">{{ fmtRM(c?.ansuranDicadang) }}<span class="text-xs font-medium text-slate-400"> / bulan</span></p>
          <p class="text-xs text-slate-500">{{ c?.tempohBulan ?? "—" }} bulan</p>
        </div>
      </div>

      <div class="grid gap-3 md:grid-cols-2">
        <section v-if="c?.sebab?.length">
          <p class="mb-1 flex items-center gap-1 text-xs font-semibold text-slate-700"><Route class="h-3.5 w-3.5" /> Sebab penghalaan</p>
          <ul class="space-y-1 text-xs text-slate-600">
            <li v-for="(s, i) in c.sebab" :key="i" class="flex gap-1.5"><CheckCircle2 class="mt-0.5 h-3.5 w-3.5 shrink-0 text-slate-400" /> {{ s }}</li>
          </ul>
        </section>
        <section v-if="c?.syarat?.length">
          <p class="mb-1 flex items-center gap-1 text-xs font-semibold text-slate-700"><FileWarning class="h-3.5 w-3.5" /> Syarat kelulusan</p>
          <ul class="space-y-1 text-xs text-slate-600">
            <li v-for="(s, i) in c.syarat" :key="i" class="flex gap-1.5"><span class="text-slate-400">{{ i + 1 }}.</span> {{ s }}</li>
          </ul>
        </section>
      </div>

      <!-- Decision recorded -->
      <section v-if="decided" class="space-y-3">
        <div
          class="flex items-start gap-3 rounded-lg border px-4 py-3"
          :class="run.keputusanPegawai === 'lulus' ? 'border-emerald-200 bg-emerald-50' : run.keputusanPegawai === 'tolak' ? 'border-rose-200 bg-rose-50' : 'border-amber-200 bg-amber-50'"
        >
          <Gavel class="mt-0.5 h-5 w-5 shrink-0 text-slate-700" />
          <div class="min-w-0">
            <p class="text-sm font-bold text-slate-900">Keputusan pegawai: {{ keputusanLabel }}</p>
            <p class="text-xs text-slate-600">Direkodkan pada {{ fmtDate(run.keputusanPada) }}</p>
            <p v-if="run.catatanPegawai" class="mt-1 text-xs text-slate-700">“{{ run.catatanPegawai }}”</p>
          </div>
        </div>
        <div v-if="akaun" class="flex items-start gap-3 rounded-lg border border-emerald-300 bg-white px-4 py-3 shadow-sm">
          <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white"><Landmark class="h-5 w-5" /></div>
          <div>
            <p class="text-sm font-bold text-emerald-800">Akaun pembiayaan dibuka</p>
            <p v-if="noAkaun" class="font-mono text-sm text-slate-800">No. akaun: {{ noAkaun }}</p>
            <p class="text-xs text-slate-500">Dana sedia untuk disalurkan selepas semakan pengeluaran.</p>
          </div>
        </div>
      </section>

      <!-- Officer actions -->
      <section v-else class="space-y-3 rounded-lg border border-slate-200 bg-slate-50/60 p-3">
        <div class="flex items-center gap-2">
          <BadgeCheck class="h-4 w-4 text-violet-600" />
          <p class="text-sm font-semibold text-slate-800">Keputusan pegawai (human-in-the-loop)</p>
        </div>
        <p v-if="!canDecide" class="flex items-center gap-1.5 rounded-md bg-amber-50 px-3 py-2 text-xs text-amber-800">
          <AlertTriangle class="h-3.5 w-3.5" /> Anda tiada kebenaran <span class="font-mono">sppt.kelulusan</span> untuk merekod keputusan.
        </p>
        <div class="grid gap-3 md:grid-cols-[12rem_minmax(0,1fr)]">
          <label class="block">
            <span class="mb-1 block text-[11px] font-medium text-slate-500">Jumlah diluluskan (RM)</span>
            <input
              v-model="jumlah"
              type="number"
              min="0"
              step="100"
              class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
            />
          </label>
          <label class="block">
            <span class="mb-1 block text-[11px] font-medium text-slate-500">
              Catatan pegawai
              <span v-if="needsCatatanForApprove" class="text-rose-600">* (wajib di luar laluan pantas)</span>
              <span v-else class="text-slate-400">(wajib untuk rujuk / tolak)</span>
            </span>
            <textarea
              v-model="catatan"
              rows="2"
              class="w-full rounded-lg border bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2"
              :class="catatanError ? 'border-rose-400 focus:ring-rose-100' : 'border-slate-300 focus:border-slate-400 focus:ring-slate-200'"
              placeholder="Justifikasi keputusan…"
              @input="catatanError = ''"
            />
            <span v-if="catatanError" class="mt-1 block text-xs font-medium text-rose-600">{{ catatanError }}</span>
          </label>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-emerald-700 disabled:opacity-50"
            :disabled="!!submitting || !canDecide"
            @click="approve"
          >
            <Loader2 v-if="submitting === 'lulus'" class="h-4 w-4 animate-spin" />
            <Zap v-else-if="laluan === 'pantas'" class="h-4 w-4" />
            <CheckCircle2 v-else class="h-4 w-4" />
            {{ laluan === "pantas" ? "Luluskan (satu klik)" : "Luluskan" }}
          </button>
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-amber-300 bg-white px-4 py-2.5 text-sm font-semibold text-amber-800 shadow-sm hover:bg-amber-50 disabled:opacity-50"
            :disabled="!!submitting || !canDecide"
            @click="refer"
          >
            <Loader2 v-if="submitting === 'rujuk'" class="h-4 w-4 animate-spin" />
            <UserCheck v-else class="h-4 w-4" />
            Rujuk semakan penuh
          </button>
          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border border-rose-300 bg-white px-4 py-2.5 text-sm font-semibold text-rose-700 shadow-sm hover:bg-rose-50 disabled:opacity-50"
            :disabled="!!submitting || !canDecide"
            @click="reject"
          >
            <Loader2 v-if="submitting === 'tolak'" class="h-4 w-4 animate-spin" />
            <XCircle v-else class="h-4 w-4" />
            Tolak
          </button>
        </div>
      </section>

      <p v-if="c?.notaHitl" class="text-[11px] italic text-slate-400">{{ c.notaHitl }}</p>
    </div>

    <DisbursementAlertModal
      :open="alertOpen"
      :check="alertCheck"
      :submitting="submitting === 'lulus'"
      :error="alertError"
      @close="alertOpen = false"
      @confirm="confirmAlert"
    />
  </article>
</template>
