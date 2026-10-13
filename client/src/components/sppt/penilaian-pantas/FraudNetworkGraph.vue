<script setup lang="ts">
import { computed, ref, type Component } from "vue";
import { Building2, CreditCard, Landmark, Mail, MapPin, Phone, User } from "lucide-vue-next";

import type { PpGraphEdge, PpGraphNode } from "@/types";

const props = defineProps<{ nodes?: PpGraphNode[] | null; edges?: PpGraphEdge[] | null }>();
const emit = defineEmits<{ (e: "select", permohonanId: number): void }>();

const W = 600;
const H = 420;
const CX = W / 2;
const CY = H / 2;
const R_INNER = 112;
const R_OUTER = 182;
const X_STRETCH = 1.32;

type Placed = PpGraphNode & { key: string; x: number; y: number; r: number };

const ATTR: Record<string, { icon: Component; fill: string; stroke: string; label: string }> = {
  telefon: { icon: Phone, fill: "#e0f2fe", stroke: "#0284c7", label: "Telefon" },
  alamat: { icon: MapPin, fill: "#ccfbf1", stroke: "#0d9488", label: "Alamat" },
  ic: { icon: CreditCard, fill: "#e0e7ff", stroke: "#4f46e5", label: "No. IC" },
  emel: { icon: Mail, fill: "#fae8ff", stroke: "#a21caf", label: "E-mel" },
  akaun_bank: { icon: Landmark, fill: "#cffafe", stroke: "#0891b2", label: "Akaun bank" },
};

function applicantColor(n: PpGraphNode) {
  if (n.utama) return { fill: "#7c3aed", stroke: "#5b21b6", text: "#ffffff" };
  if (n.risiko === "tinggi") return { fill: "#ffe4e6", stroke: "#e11d48", text: "#9f1239" };
  if (n.risiko === "sederhana") return { fill: "#fef3c7", stroke: "#d97706", text: "#92400e" };
  return { fill: "#f1f5f9", stroke: "#64748b", text: "#334155" };
}

const layout = computed(() => {
  const nodes = (props.nodes ?? []).filter((n) => n && n.id !== undefined && n.id !== null);
  const edges = (props.edges ?? []).filter((e) => e && e.dari !== undefined && e.ke !== undefined);
  const placed = new Map<string, Placed>();
  if (!nodes.length) return { placed: [] as Placed[], lines: [] as { a: Placed; b: Placed; label: string }[] };

  const main = nodes.find((n) => n.utama) ?? nodes.find((n) => n.jenis === "pemohon") ?? nodes[0];
  const attrs = nodes.filter((n) => n !== main && n.jenis !== "pemohon");
  const others = nodes.filter((n) => n !== main && n.jenis === "pemohon");

  placed.set(String(main.id), { ...main, key: String(main.id), x: CX, y: CY, r: 24 });

  const angleOf = new Map<string, number>();
  attrs.forEach((n, i) => {
    const a = -Math.PI / 2 + (i / Math.max(1, attrs.length)) * Math.PI * 2;
    angleOf.set(String(n.id), a);
    placed.set(String(n.id), { ...n, key: String(n.id), x: CX + Math.cos(a) * R_INNER * X_STRETCH, y: CY + Math.sin(a) * R_INNER, r: 15 });
  });

  const neighbours = (id: string) =>
    edges.flatMap((e) => (String(e.dari) === id ? [String(e.ke)] : String(e.ke) === id ? [String(e.dari)] : []));

  const wanted = others.map((n, i) => {
    const linked = neighbours(String(n.id)).map((k) => angleOf.get(k)).filter((a): a is number => a !== undefined);
    let a: number;
    if (linked.length) {
      const sx = linked.reduce((s, v) => s + Math.cos(v), 0);
      const sy = linked.reduce((s, v) => s + Math.sin(v), 0);
      a = Math.atan2(sy, sx);
    } else {
      a = -Math.PI / 2 + ((i + 0.5) / Math.max(1, others.length)) * Math.PI * 2;
    }
    return { n, a };
  });
  wanted.sort((p, q) => p.a - q.a);
  const minGap = Math.min(0.42, (Math.PI * 2) / Math.max(1, wanted.length));
  for (let i = 1; i < wanted.length; i++) {
    if (wanted[i].a - wanted[i - 1].a < minGap) wanted[i].a = wanted[i - 1].a + minGap;
  }
  wanted.forEach(({ n, a }) => {
    placed.set(String(n.id), { ...n, key: String(n.id), x: CX + Math.cos(a) * R_OUTER * X_STRETCH, y: CY + Math.sin(a) * R_OUTER, r: 18 });
  });

  const lines = edges
    .map((e) => ({ a: placed.get(String(e.dari)), b: placed.get(String(e.ke)), label: e.label ?? "" }))
    .filter((l): l is { a: Placed; b: Placed; label: string } => !!l.a && !!l.b);

  return { placed: [...placed.values()], lines };
});

const hovered = ref<Placed | null>(null);

function shortLabel(s?: string | null, max = 18) {
  const v = String(s ?? "");
  return v.length > max ? `${v.slice(0, max - 1)}…` : v;
}

function iconFor(n: PpGraphNode): Component {
  if (n.jenis === "pemohon") return n.utama ? User : Building2;
  return ATTR[n.jenis]?.icon ?? User;
}

function styleFor(n: PpGraphNode) {
  if (n.jenis === "pemohon") return applicantColor(n);
  const a = ATTR[n.jenis];
  return { fill: a?.fill ?? "#f1f5f9", stroke: a?.stroke ?? "#64748b", text: a?.stroke ?? "#334155" };
}

function onClick(n: Placed) {
  if (n.jenis === "pemohon" && !n.utama && typeof n.permohonanId === "number") emit("select", n.permohonanId);
}
</script>

<template>
  <div class="relative">
    <div v-if="!layout.placed.length" class="flex h-48 items-center justify-center rounded-lg bg-slate-50 text-xs text-slate-500">
      Tiada rangkaian entiti dikesan.
    </div>
    <template v-else>
      <svg :viewBox="`0 0 ${W} ${H}`" class="h-auto w-full rounded-lg bg-gradient-to-br from-slate-50 to-white" role="img" aria-label="Graf rangkaian penipuan">
        <circle :cx="CX" :cy="CY" :r="R_INNER" fill="none" stroke="#e2e8f0" stroke-dasharray="3 5" :transform="`translate(${CX} ${CY}) scale(${X_STRETCH} 1) translate(${-CX} ${-CY})`" />
        <circle :cx="CX" :cy="CY" :r="R_OUTER" fill="none" stroke="#f1f5f9" stroke-dasharray="3 5" :transform="`translate(${CX} ${CY}) scale(${X_STRETCH} 1) translate(${-CX} ${-CY})`" />
        <g>
          <g v-for="(l, i) in layout.lines" :key="`e${i}`">
            <line
              :x1="l.a.x" :y1="l.a.y" :x2="l.b.x" :y2="l.b.y"
              :stroke="(l.a.risiko === 'tinggi' || l.b.risiko === 'tinggi') ? '#fda4af' : '#cbd5e1'"
              stroke-width="1.5"
              :opacity="hovered && hovered.key !== l.a.key && hovered.key !== l.b.key ? 0.25 : 1"
            />
            <text
              v-if="l.label"
              :x="(l.a.x + l.b.x) / 2" :y="(l.a.y + l.b.y) / 2"
              text-anchor="middle" dominant-baseline="middle"
              class="fill-slate-500 text-[9px]" stroke="#ffffff" stroke-width="3" paint-order="stroke"
            >{{ shortLabel(l.label, 16) }}</text>
          </g>
        </g>
        <g
          v-for="n in layout.placed"
          :key="n.key"
          :class="n.jenis === 'pemohon' && !n.utama && n.permohonanId ? 'cursor-pointer' : 'cursor-default'"
          @mouseenter="hovered = n"
          @mouseleave="hovered = null"
          @click="onClick(n)"
        >
          <circle v-if="n.utama" :cx="n.x" :cy="n.y" :r="n.r + 7" fill="#7c3aed" opacity="0.12" />
          <circle :cx="n.x" :cy="n.y" :r="n.r" :fill="styleFor(n).fill" :stroke="styleFor(n).stroke" :stroke-width="n.utama ? 2.5 : 1.75" />
          <component :is="iconFor(n)" :x="n.x - n.r * 0.5" :y="n.y - n.r * 0.5" :size="n.r" :color="styleFor(n).text" :stroke-width="2.2" />
          <text
            :x="n.x" :y="n.y + n.r + 11" text-anchor="middle"
            class="text-[10px] font-medium" :fill="n.utama ? '#4c1d95' : '#334155'"
            stroke="#ffffff" stroke-width="3" paint-order="stroke"
          >{{ shortLabel(n.label) }}</text>
        </g>
      </svg>

      <div
        v-if="hovered"
        class="pointer-events-none absolute z-10 max-w-[240px] -translate-x-1/2 -translate-y-full rounded-md bg-slate-900 px-2.5 py-1.5 text-[11px] text-white shadow-lg"
        :style="{ left: `${(hovered.x / W) * 100}%`, top: `calc(${(hovered.y / H) * 100}% - ${hovered.r + 4}px)` }"
      >
        <p class="font-semibold">{{ hovered.label || hovered.id }}</p>
        <p class="text-slate-300">
          {{ hovered.jenis === "pemohon" ? (hovered.utama ? "Pemohon semasa" : "Pemohon lain") : ATTR[hovered.jenis]?.label ?? hovered.jenis }}
          <template v-if="hovered.status"> · {{ hovered.status }}</template>
          <template v-if="hovered.risiko"> · risiko {{ hovered.risiko }}</template>
        </p>
        <p v-if="hovered.jenis === 'pemohon' && !hovered.utama && hovered.permohonanId" class="text-violet-300">Klik untuk buka permohonan</p>
      </div>
    </template>

    <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-[11px] text-slate-500">
      <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-violet-600" /> Pemohon semasa</span>
      <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full border border-rose-600 bg-rose-100" /> Pemohon berisiko tinggi</span>
      <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full border border-amber-600 bg-amber-100" /> Risiko sederhana</span>
      <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full border border-slate-500 bg-slate-100" /> Pemohon lain</span>
      <span v-for="(a, k) in ATTR" :key="k" class="inline-flex items-center gap-1">
        <span class="h-2.5 w-2.5 rounded-full border" :style="{ background: a.fill, borderColor: a.stroke }" /> {{ a.label }}
      </span>
    </div>
  </div>
</template>
