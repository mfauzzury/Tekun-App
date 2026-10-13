<script setup lang="ts">
import { computed } from "vue";
import { CloudOff, RefreshCw } from "lucide-vue-next";

import AiProgressTracker from "@/components/pemohon/AiProgressTracker.vue";
import PemohonStatusTimeline, { type TimelineMilestone } from "@/components/pemohon/PemohonStatusTimeline.vue";
import PBadge from "@/components/pemohon/ui/PBadge.vue";
import PButton from "@/components/pemohon/ui/PButton.vue";
import PCard from "@/components/pemohon/ui/PCard.vue";
import PPageHeader from "@/components/pemohon/ui/PPageHeader.vue";
import PSkeleton from "@/components/pemohon/ui/PSkeleton.vue";
import { usePemohonStore, type PermohonanSaya } from "@/stores/pemohon";
import type { PemohonMyKadScan, PermohonanAttachment, PpDataAlternatif, PpFaceResult } from "@/types";
import { formatRinggit } from "@/utils/pemohon-ui";
import AltDataCard from "./AltDataCard.vue";
import AttachmentsCard from "./AttachmentsCard.vue";
import DecisionBanner from "./DecisionBanner.vue";
import IdentityCard from "./IdentityCard.vue";
import { detail, formatTarikh, useApplicationStatus } from "./useApplicationStatus";

const props = defineProps<{ item: PermohonanSaya }>();

const pemohon = usePemohonStore();
const { snapshot, record, progress, loading, failed, view, refresh } = useApplicationStatus(() => props.item);

const firstLoad = computed(() => loading.value && !snapshot.value);
const produk = computed(() => record.value?.kategoriPembiayaan || props.item.produk || "Pembiayaan");
const jumlah = computed(() => record.value?.jumlahPermohonan ?? props.item.jumlah);
const dihantarIso = computed(() => detail<string>(record.value, "dihantarPada") ?? props.item.dihantarPada ?? record.value?.createdAt);

const attachments = computed(() => detail<PermohonanAttachment[]>(record.value, "attachments") ?? []);
const wajah = computed(() => detail<PpFaceResult>(record.value, "ekycWajah") ?? null);
const mykad = computed(() => detail<PemohonMyKadScan>(record.value, "ekycMykad") ?? null);
const dataAlternatif = computed(() => detail<PpDataAlternatif>(record.value, "dataAlternatif") ?? null);

const butiran = computed(() => {
  const tempoh = detail<string | number>(record.value, "tempohPembiayaan");
  return [
    { label: "Produk", value: produk.value },
    { label: "Jumlah dipohon", value: formatRinggit(jumlah.value) },
    { label: "Tempoh", value: tempoh ? `${tempoh} bulan` : "—" },
    { label: "Kekerapan bayaran", value: detail<string>(record.value, "kekerapanBayaran") ?? "—" },
    { label: "Tujuan", value: detail<string>(record.value, "tujuanPembiayaan") ?? "—" },
    { label: "Tarikh dihantar", value: formatTarikh(dihantarIso.value, true) },
  ];
});

const showTracker = computed(() => view.value.aiState === "berjalan" || view.value.aiState === "selesai");

const milestones = computed<TimelineMilestone[]>(() => {
  const v = view.value;
  const aiDone = v.aiState === "selesai" || !!v.keputusan;
  return [
    {
      key: "dihantar",
      label: "Permohonan dihantar",
      done: true,
      description: "Permohonan dan dokumen anda telah diterima.",
      date: formatTarikh(dihantarIso.value, true),
    },
    {
      key: "ai",
      label: "Penilaian AI",
      done: aiDone,
      description: showTracker.value ? null : aiDone ? "Penilaian automatik selesai." : "Penilaian automatik akan bermula sebentar lagi.",
      date: progress.value?.siapPada ? formatTarikh(progress.value.siapPada, true) : null,
    },
    {
      key: "pegawai",
      label: "Semakan pegawai",
      done: !!v.keputusan,
      description: v.keputusan ? "Pegawai TEKUN telah menyemak permohonan anda." : "Pegawai TEKUN akan menyemak cadangan penilaian dan dokumen anda.",
    },
    {
      key: "keputusan",
      label: v.keputusan === "lulus" ? "Keputusan: Diluluskan" : v.keputusan === "tolak" ? "Keputusan: Tidak berjaya" : "Keputusan",
      done: !!v.keputusan,
      tone: v.keputusan === "lulus" ? "success" : v.keputusan === "tolak" ? "danger" : undefined,
      description: v.keputusan ? (v.mesej ?? null) : "Keputusan akan dipaparkan di sini sebaik sahaja dibuat.",
    },
  ];
});
</script>

<template>
  <div class="space-y-6">
    <PPageHeader
      :eyebrow="item.noRujukan"
      :title="produk"
      :subtitle="`${formatRinggit(jumlah)} · Dihantar ${formatTarikh(dihantarIso)}`"
      back="/pemohon/permohonan"
      back-label="Permohonan saya"
    >
      <template #actions>
        <div class="flex items-center gap-2">
          <span v-if="firstLoad" class="h-7 w-28 animate-pulse rounded-full bg-slate-200/70" aria-hidden="true" />
          <PBadge v-else :tone="view.tone" dot size="md">{{ view.statusLabel }}</PBadge>
          <PButton variant="outline" size="sm" :disabled="loading" aria-label="Muat semula status" @click="refresh">
            <RefreshCw class="h-4 w-4" :class="loading ? 'animate-spin' : ''" aria-hidden="true" />
            <span class="hidden sm:inline">Muat semula</span>
          </PButton>
        </div>
      </template>
    </PPageHeader>

    <div v-if="failed" class="flex items-center justify-between gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" role="status">
      <span class="flex items-center gap-2"><CloudOff class="h-4 w-4 shrink-0" aria-hidden="true" /> Status tidak dapat dikemas kini. Maklumat di bawah mungkin tidak terkini.</span>
      <button type="button" class="shrink-0 font-semibold hover:text-amber-950" @click="refresh">Cuba lagi</button>
    </div>

    <DecisionBanner v-if="view.keputusan" :keputusan="view.keputusan" :mesej="view.mesej" />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
      <div class="space-y-6 lg:col-span-3">
        <PCard title="Status permohonan" subtitle="Ikuti setiap peringkat permohonan anda">
          <PSkeleton v-if="firstLoad" :lines="6" />
          <PemohonStatusTimeline v-else :milestones="milestones">
            <template v-if="showTracker" #ai>
              <div class="rounded-2xl border border-slate-200/80 bg-slate-50/70 p-4">
                <AiProgressTracker :permohonan-id="item.id" :token="item.token" @done="refresh" />
              </div>
            </template>
          </PemohonStatusTimeline>
        </PCard>

        <PCard v-if="firstLoad" title="Dokumen dihantar"><PSkeleton :lines="4" /></PCard>
        <AttachmentsCard v-else :attachments="attachments" />
      </div>

      <div class="space-y-6 lg:col-span-2">
        <PCard title="Butiran pembiayaan">
          <dl class="divide-y divide-slate-100">
            <div v-for="b in butiran" :key="b.label" class="flex items-start justify-between gap-4 py-2.5 first:pt-0 last:pb-0">
              <dt class="shrink-0 text-sm text-slate-500">{{ b.label }}</dt>
              <dd class="min-w-0 text-right text-sm font-medium text-ink-900 [overflow-wrap:anywhere]">{{ b.value }}</dd>
            </div>
          </dl>
        </PCard>

        <template v-if="firstLoad">
          <PCard title="Pengesahan identiti"><PSkeleton :lines="3" /></PCard>
          <PCard title="Data alternatif"><PSkeleton :lines="3" /></PCard>
        </template>
        <template v-else>
          <IdentityCard :wajah="wajah" :mykad="mykad" :onboarding-verified="pemohon.onboarding.ekycVerified" />
          <AltDataCard :data="dataAlternatif" />
        </template>
      </div>
    </div>
  </div>
</template>
