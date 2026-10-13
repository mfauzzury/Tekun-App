import { computed, ref, toValue, watch, type MaybeRefOrGetter } from "vue";

import { getPemohonPenilaianPantas, getPemohonPermohonan } from "@/api/pemohonPermohonan";
import type { PermohonanSaya } from "@/stores/pemohon";
import type { PemohonAiProgress, PemohonPermohonan } from "@/types";
import { statusStage, statusTone, type PTone } from "@/utils/pemohon-status";

/** Live state of one real (backend) application: the record plus its AI assessment progress. */
export interface ApplicationSnapshot {
  record: PemohonPermohonan | null;
  progress: PemohonAiProgress | null;
  /** The record itself could not be fetched — show stored info only. */
  failed: boolean;
}

export type Keputusan = "lulus" | "tolak" | null;
export type AiState = PemohonAiProgress["status"] | null;

export interface ApplicationView {
  statusLabel: string;
  tone: PTone;
  /** Index into TIMELINE_STAGES the application has reached. */
  stage: number;
  keputusan: Keputusan;
  aiState: AiState;
  mesej: string | null;
}

export async function fetchApplicationStatus(id: number, token: string): Promise<ApplicationSnapshot> {
  const [rec, prog] = await Promise.allSettled([getPemohonPermohonan(id, token), getPemohonPenilaianPantas(id, token)]);
  return {
    record: rec.status === "fulfilled" ? rec.value.data : null,
    progress: prog.status === "fulfilled" ? prog.value.data : null,
    failed: rec.status === "rejected",
  };
}

export function deriveView(snap: ApplicationSnapshot | null | undefined): ApplicationView {
  const progress = snap?.progress ?? null;
  const status = snap?.record?.status ?? progress?.statusPermohonan ?? null;
  const keputusan: Keputusan =
    progress?.keputusan ?? (status === "Diluluskan" ? "lulus" : status === "Ditolak" ? "tolak" : null);
  const aiState: AiState = progress?.status ?? null;
  const mesej = progress?.mesej ?? null;

  if (keputusan === "lulus") return { statusLabel: "Diluluskan", tone: "success", stage: 3, keputusan, aiState, mesej };
  if (keputusan === "tolak") return { statusLabel: "Tidak berjaya", tone: "danger", stage: 3, keputusan, aiState, mesej };
  if (aiState === "berjalan") return { statusLabel: "Penilaian AI", tone: "info", stage: 1, keputusan, aiState, mesej };
  if (aiState === "selesai") {
    return { statusLabel: "Semakan pegawai", tone: "info", stage: Math.max(2, statusStage(status, true)), keputusan, aiState, mesej };
  }
  if (status) return { statusLabel: status, tone: statusTone(status), stage: statusStage(status, false), keputusan, aiState, mesej };
  return { statusLabel: "Dihantar", tone: "neutral", stage: 1, keputusan, aiState, mesej };
}

/** Single application — used by the dashboard and the detail page. */
export function useApplicationStatus(source: MaybeRefOrGetter<Pick<PermohonanSaya, "id" | "token"> | null | undefined>) {
  const snapshot = ref<ApplicationSnapshot | null>(null);
  const loading = ref(false);
  let seq = 0;

  async function refresh() {
    const target = toValue(source);
    const mine = ++seq;
    if (!target) {
      snapshot.value = null;
      return;
    }
    loading.value = true;
    try {
      const snap = await fetchApplicationStatus(target.id, target.token);
      if (mine === seq) snapshot.value = snap;
    } finally {
      if (mine === seq) loading.value = false;
    }
  }

  watch(
    () => {
      const t = toValue(source);
      return t ? `${t.id}:${t.token}` : "";
    },
    () => void refresh(),
    { immediate: true },
  );

  return {
    snapshot,
    loading,
    record: computed(() => snapshot.value?.record ?? null),
    progress: computed(() => snapshot.value?.progress ?? null),
    failed: computed(() => !!snapshot.value?.failed),
    view: computed(() => deriveView(snapshot.value)),
    refresh,
  };
}

/** Many applications in parallel — used by the list page. Keyed by application id. */
export function useApplicationStatuses(source: MaybeRefOrGetter<PermohonanSaya[]>) {
  const snapshots = ref<Record<number, ApplicationSnapshot>>({});
  const loading = ref(false);

  async function refresh() {
    const items = toValue(source);
    if (!items.length) return;
    loading.value = true;
    try {
      const results = await Promise.all(items.map((i) => fetchApplicationStatus(i.id, i.token)));
      snapshots.value = Object.fromEntries(items.map((i, idx) => [i.id, results[idx]]));
    } finally {
      loading.value = false;
    }
  }

  watch(() => toValue(source).map((i) => i.id).join(","), () => void refresh(), { immediate: true });

  return { snapshots, loading, refresh };
}

export function formatTarikh(iso: string | null | undefined, withTime = false): string {
  if (!iso) return "—";
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) return iso;
  const date = d.toLocaleDateString("ms-MY", { day: "numeric", month: "short", year: "numeric" });
  return withTime ? `${date}, ${d.toLocaleTimeString("ms-MY", { hour: "numeric", minute: "2-digit" })}` : date;
}

/** Read a typed value out of the free-form `details` bag. */
export function detail<T>(record: PemohonPermohonan | null | undefined, key: string): T | undefined {
  const value = (record?.details as Record<string, unknown> | undefined)?.[key];
  return value == null || value === "" ? undefined : (value as T);
}
