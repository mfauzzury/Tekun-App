<script setup lang="ts">
import { computed, nextTick, onMounted, ref } from "vue";
import { CalendarClock, FileText, GraduationCap, Plus, Wallet } from "lucide-vue-next";

export type TimelineEvent = {
  id: string;
  title: string;
  start: Date;
  end: Date;
  kind: "permohonan" | "ansuran" | "program";
  /** 0–1; shows the striped progress bar under the card. */
  progress?: number | null;
  to?: string;
  sample?: boolean;
};

const props = defineProps<{ events: TimelineEvent[] }>();

const DAY_MS = 86_400_000;
const COL = 148;
const LANE_H = 128;
const DAYS_BEFORE = 3;
const DAYS_AFTER = 10;

function startOfDay(d: Date) {
  const x = new Date(d);
  x.setHours(0, 0, 0, 0);
  return x;
}

const today = startOfDay(new Date());
const first = new Date(today.getTime() - DAYS_BEFORE * DAY_MS);
const days = Array.from({ length: DAYS_BEFORE + DAYS_AFTER + 1 }, (_, i) => {
  const d = new Date(first.getTime() + i * DAY_MS);
  return {
    key: d.toISOString(),
    date: d.toLocaleDateString("ms-MY", { day: "numeric", month: "short" }),
    weekday: d.toLocaleDateString("ms-MY", { weekday: "long" }),
    isToday: d.getTime() === today.getTime(),
  };
});
const width = days.length * COL;
const todayX = (DAYS_BEFORE + (Date.now() - today.getTime()) / DAY_MS) * COL;

const fmt = (d: Date) => d.toLocaleDateString("ms-MY", { day: "numeric", month: "short" });

/** Clip to the visible window and pack into lanes so cards never overlap. */
const placed = computed(() => {
  const lastEnd = first.getTime() + days.length * DAY_MS;
  const visible = props.events
    .map((e) => ({ ...e, s: Math.max(startOfDay(e.start).getTime(), first.getTime()), f: Math.min(startOfDay(e.end).getTime() + DAY_MS, lastEnd) }))
    .filter((e) => e.f > e.s)
    .sort((a, b) => a.s - b.s);

  const laneEnds: number[] = [];
  return visible.map((e) => {
    let lane = laneEnds.findIndex((end) => end <= e.s);
    if (lane === -1) lane = laneEnds.push(0) - 1;
    laneEnds[lane] = e.f;
    const left = ((e.s - first.getTime()) / DAY_MS) * COL + 10;
    const w = Math.max(((e.f - e.s) / DAY_MS) * COL - 20, 2.6 * COL - 20);
    return { ...e, lane, left, w, range: e.start.toDateString() === e.end.toDateString() ? fmt(e.start) : `${fmt(e.start)} – ${fmt(e.end)}` };
  });
});
const lanes = computed(() => Math.max(3, ...placed.value.map((p) => p.lane + 1)));

const ICON = { permohonan: FileText, ansuran: Wallet, program: GraduationCap };
const ICON_CLASS = {
  permohonan: "bg-brand-50 text-brand-600",
  ansuran: "bg-amber-50 text-amber-600",
  program: "bg-emerald-50 text-emerald-600",
};

const scroller = ref<HTMLElement | null>(null);
onMounted(async () => {
  await nextTick();
  if (scroller.value) scroller.value.scrollLeft = Math.max(0, todayX - scroller.value.clientWidth / 3);
});
</script>

<template>
  <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white" aria-labelledby="jadual-title">
    <header class="flex items-center justify-between gap-3 px-5 pt-5 sm:px-6">
      <div class="flex items-center gap-2.5">
        <CalendarClock class="h-5 w-5 text-brand-600" aria-hidden="true" />
        <h2 id="jadual-title" class="text-[17px] font-semibold text-ink-900">Jadual & aktiviti</h2>
      </div>
      <div class="hidden items-center gap-4 text-xs text-slate-500 sm:flex">
        <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-brand-500" /> Permohonan</span>
        <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-amber-500" /> Ansuran</span>
        <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-500" /> Program</span>
      </div>
    </header>

    <div class="relative mt-4">
      <div ref="scroller" class="overflow-x-auto overscroll-x-contain">
        <div class="relative" :style="{ width: `${width}px` }">
          <!-- Day header -->
          <div class="flex border-b border-slate-100 bg-white">
            <div
              v-for="d in days"
              :key="d.key"
              class="shrink-0 py-3 text-center"
              :style="{ width: `${COL}px` }"
              :class="d.isToday ? 'text-brand-600' : 'text-slate-500'"
            >
              <p class="text-sm font-semibold" :class="d.isToday ? 'text-brand-600' : 'text-ink-800'">{{ d.date }}</p>
              <p class="text-xs capitalize">{{ d.weekday }}</p>
            </div>
          </div>

          <!-- Grid + events -->
          <div class="relative bg-[#f3f6fc]" :style="{ height: `${lanes * LANE_H + 32}px` }">
            <div class="absolute inset-0 flex" aria-hidden="true">
              <div v-for="d in days" :key="d.key" class="shrink-0 border-r border-slate-200/70" :style="{ width: `${COL}px` }" />
            </div>

            <div class="absolute bottom-0 top-0 z-10 w-0.5 bg-brand-600" :style="{ left: `${todayX}px` }" aria-hidden="true">
              <span class="absolute -left-[7px] -top-px h-0 w-0 border-x-[8px] border-t-[10px] border-x-transparent border-t-brand-600" />
            </div>

            <component
              :is="ev.to ? 'router-link' : 'div'"
              v-for="ev in placed"
              :key="ev.id"
              :to="ev.to"
              class="absolute z-20 overflow-hidden rounded-2xl bg-white shadow-[0_1px_2px_rgba(16,24,40,0.05),0_8px_24px_-12px_rgba(16,24,40,0.18)] transition-shadow hover:shadow-[0_1px_2px_rgba(16,24,40,0.05),0_12px_28px_-10px_rgba(16,24,40,0.25)]"
              :style="{ left: `${ev.left}px`, width: `${ev.w}px`, top: `${16 + ev.lane * LANE_H}px` }"
            >
              <div class="flex items-start gap-3 px-4 pb-3 pt-3.5">
                <div class="min-w-0 flex-1">
                  <p class="truncate text-[15px] font-semibold text-ink-900">{{ ev.title }}</p>
                  <p class="mt-1 text-sm text-slate-500">
                    {{ ev.range }}
                    <span v-if="ev.sample" class="ml-1 rounded bg-slate-100 px-1.5 text-[10px] font-medium uppercase tracking-wide text-slate-500">contoh</span>
                  </p>
                </div>
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full" :class="ICON_CLASS[ev.kind]">
                  <component :is="ICON[ev.kind]" class="h-[18px] w-[18px]" aria-hidden="true" />
                </span>
              </div>
              <div v-if="ev.progress != null" class="h-5 bg-slate-200/70" role="progressbar" :aria-valuenow="Math.round(ev.progress * 100)" aria-valuemin="0" aria-valuemax="100">
                <div
                  class="h-full rounded-r-full"
                  :style="{
                    width: `${Math.max(6, ev.progress * 100)}%`,
                    backgroundImage: 'repeating-linear-gradient(-45deg, #82a9ff 0 8px, #b3cdff 8px 16px)',
                  }"
                />
              </div>
              <div v-else class="h-5 bg-slate-100" />
            </component>
          </div>
        </div>
      </div>

      <router-link
        to="/pemohon/permohonan/baru"
        class="absolute bottom-5 right-5 z-30 inline-flex h-12 items-center gap-2 rounded-2xl bg-ink-950 px-5 text-sm font-semibold text-white shadow-lg shadow-ink-950/25 transition-colors hover:bg-ink-900"
      >
        <Plus class="h-4 w-4" aria-hidden="true" /> Mohon pembiayaan
      </router-link>
    </div>
  </section>
</template>
