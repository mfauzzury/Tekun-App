<script setup lang="ts">
import { computed } from "vue";

const props = withDefaults(
  defineProps<{
    score: number;
    category?: string;
    bandColor?: "green" | "amber" | "red" | string;
    size?: number;
  }>(),
  {
    category: "",
    bandColor: "amber",
    size: 220,
  },
);

const clampedScore = computed(() => Math.max(0, Math.min(100, Math.round(props.score))));

/** Semicircle: 0 = left (180°), 100 = right (0°), arc opens upward. */
const needleRotation = computed(() => -90 + (clampedScore.value / 100) * 180);

const needleStroke = computed(() => {
  if (props.bandColor === "green") return "#059669";
  if (props.bandColor === "red") return "#dc2626";
  return "#d97706";
});

const categoryClass = computed(() => {
  if (props.bandColor === "green") return "text-emerald-700";
  if (props.bandColor === "red") return "text-rose-700";
  return "text-amber-700";
});

/** Map score 0→left, 50→top, 100→right on the upper semicircle (matches needle). */
function pointForScore(score: number, cx = 100, cy = 100, r = 72) {
  const rad = ((180 - (score / 100) * 180) * Math.PI) / 180;
  return {
    x: cx + r * Math.cos(rad),
    y: cy - r * Math.sin(rad),
  };
}

function arcSegment(startScore: number, endScore: number, color: string, width = 14) {
  const r = 72;
  const p1 = pointForScore(startScore);
  const p2 = pointForScore(endScore);

  return {
    d: `M ${p1.x} ${p1.y} A ${r} ${r} 0 0 1 ${p2.x} ${p2.y}`,
    color,
    width,
  };
}

const segments = [
  arcSegment(0, 59, "#f87171"),
  arcSegment(59, 79, "#fbbf24"),
  arcSegment(79, 100, "#34d399"),
];
</script>

<template>
  <div class="flex flex-col items-center" :style="{ width: `${size}px` }">
    <svg
      :width="size"
      :height="size * 0.62"
      viewBox="0 0 200 125"
      role="img"
      :aria-label="`Skor kredit ${clampedScore} daripada 100`"
    >
      <!-- Track -->
      <path
        d="M 28 100 A 72 72 0 0 1 172 100"
        fill="none"
        stroke="#e2e8f0"
        stroke-width="16"
        stroke-linecap="round"
      />

      <!-- Colored zones: high risk (red) → medium (amber) → low (green) -->
      <path
        v-for="(seg, idx) in segments"
        :key="idx"
        :d="seg.d"
        fill="none"
        :stroke="seg.color"
        :stroke-width="seg.width"
        stroke-linecap="butt"
      />

      <!-- Zone labels -->
      <text x="22" y="118" class="fill-rose-500 text-[8px] font-semibold">Tinggi</text>
      <text x="92" y="22" class="fill-amber-500 text-[8px] font-semibold">Sederhana</text>
      <text x="158" y="118" class="fill-emerald-500 text-[8px] font-semibold">Rendah</text>

      <!-- Needle -->
      <g transform="translate(100, 100)">
        <line
          x1="0"
          y1="0"
          x2="0"
          y2="-58"
          :stroke="needleStroke"
          stroke-width="3"
          stroke-linecap="round"
          :transform="`rotate(${needleRotation})`"
        />
        <circle r="6" fill="#1e293b" />
        <circle r="3" fill="#f8fafc" />
      </g>

      <!-- Score readout -->
      <text x="100" y="92" text-anchor="middle" class="fill-slate-900 text-[22px] font-bold">
        {{ clampedScore }}
      </text>
      <text x="100" y="106" text-anchor="middle" class="fill-slate-400 text-[9px] font-medium">/ 100</text>
    </svg>

    <p v-if="category" class="-mt-1 text-center text-xs font-semibold" :class="categoryClass">
      {{ category }}
    </p>
  </div>
</template>
