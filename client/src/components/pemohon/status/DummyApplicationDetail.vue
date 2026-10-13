<script setup lang="ts">
import { computed } from "vue";
import { CalendarClock, CircleCheck, CircleDashed, Clock, MapPin } from "lucide-vue-next";

import PemohonStatusTimeline from "@/components/pemohon/PemohonStatusTimeline.vue";
import PBadge from "@/components/pemohon/ui/PBadge.vue";
import PCard from "@/components/pemohon/ui/PCard.vue";
import PPageHeader from "@/components/pemohon/ui/PPageHeader.vue";
import { DOKUMEN_CHECKLIST_DUMMY, STATUS_TIMELINE, TEMUDUGA_DUMMY } from "@/data/portal-dummy";
import type { PermohonanItem } from "@/stores/pemohon";
import type { PTone } from "@/utils/pemohon-status";

/** Sample (dummy) application history — original behaviour, new look. */
const props = defineProps<{ item: PermohonanItem }>();

const TONE: Record<string, PTone> = { lulus: "success", tidak_lulus: "danger", dokumen_tambahan: "warning" };

const milestones = computed(() => {
  const list = STATUS_TIMELINE[props.item.statusKey] ?? [];
  const isFinal = props.item.statusKey === "lulus" || props.item.statusKey === "tidak_lulus";
  return list.map((m, i) => ({
    ...m,
    tone: isFinal && i === list.length - 1 ? (props.item.statusKey === "lulus" ? ("success" as const) : ("danger" as const)) : undefined,
  }));
});
const temuduga = computed(() => TEMUDUGA_DUMMY.find((t) => t.permohonanId === props.item.id));

const DOC: Record<string, { label: string; tone: PTone }> = {
  lengkap: { label: "Lengkap", tone: "success" },
  menunggu_semakan: { label: "Menunggu semakan", tone: "warning" },
  diperlukan: { label: "Diperlukan", tone: "neutral" },
};
</script>

<template>
  <div class="space-y-6">
    <PPageHeader
      :eyebrow="item.id"
      :title="item.produk"
      :subtitle="`${item.jumlah} · Dimohon pada ${item.tarikh}`"
      back="/pemohon/permohonan"
      back-label="Permohonan saya"
    >
      <template #actions>
        <div class="flex flex-wrap items-center gap-2">
          <PBadge :tone="TONE[item.statusKey] ?? 'info'" dot size="md">{{ item.status }}</PBadge>
          <PBadge tone="neutral" size="md">Contoh rekod</PBadge>
        </div>
      </template>
    </PPageHeader>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
      <PCard title="Status permohonan" class="lg:col-span-3">
        <PemohonStatusTimeline :milestones="milestones" />
      </PCard>

      <div class="space-y-6 lg:col-span-2">
        <PCard title="Senarai semak dokumen">
          <ul class="space-y-3">
            <li v-for="doc in DOKUMEN_CHECKLIST_DUMMY" :key="doc.id" class="flex items-center gap-3">
              <CircleCheck v-if="doc.status === 'lengkap'" class="h-5 w-5 shrink-0 text-emerald-500" aria-hidden="true" />
              <Clock v-else-if="doc.status === 'menunggu_semakan'" class="h-5 w-5 shrink-0 text-amber-500" aria-hidden="true" />
              <CircleDashed v-else class="h-5 w-5 shrink-0 text-slate-300" aria-hidden="true" />
              <span class="min-w-0 flex-1 text-sm text-ink-800">{{ doc.nama }}</span>
              <PBadge :tone="DOC[doc.status]?.tone ?? 'neutral'">{{ DOC[doc.status]?.label ?? doc.status }}</PBadge>
            </li>
          </ul>
        </PCard>

        <PCard v-if="temuduga">
          <template #header>
            <div class="flex items-center gap-2.5">
              <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                <CalendarClock class="h-[18px] w-[18px]" aria-hidden="true" />
              </span>
              <h2 class="text-[15px] font-semibold text-ink-900">Temuduga</h2>
            </div>
          </template>
          <template #actions>
            <PBadge tone="info">{{ temuduga.status }}</PBadge>
          </template>
          <p class="text-lg font-bold tracking-tight text-ink-900">{{ temuduga.tarikh }}</p>
          <p class="text-sm text-slate-500">{{ temuduga.masa }} · {{ temuduga.jenis }}</p>
          <p class="mt-3 flex items-start gap-1.5 text-sm text-ink-700">
            <MapPin class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
            {{ temuduga.lokasi }}
          </p>
        </PCard>
      </div>
    </div>
  </div>
</template>
