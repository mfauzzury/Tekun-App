<script setup lang="ts">
import { computed } from "vue";
import { RouterLink, useRoute } from "vue-router";

import { useI18n } from "@/composables/useI18n";

const navItems = [
  { to: "/admin/undang-undang/litigasi", label: "Litigasi" },
  { to: "/admin/undang-undang/panel-peguam", label: "Panel Peguam" },
  { to: "/admin/undang-undang/akaun-npf", label: "Akaun NPF" },
  { to: "/admin/undang-undang/nod", label: "NOD" },
  { to: "/admin/undang-undang/saman", label: "Saman" },
  { to: "/admin/undang-undang/penghakiman", label: "Penghakiman" },
  { to: "/admin/undang-undang/execution", label: "Execution" },
  { to: "/admin/undang-undang/wss", label: "WSS" },
  { to: "/admin/undang-undang/garnishee", label: "Garnishee" },
  { to: "/admin/undang-undang/jds", label: "JDS" },
  { to: "/admin/undang-undang/kebankrapan", label: "Kebankrapan" },
  { to: "/admin/undang-undang/winding-up", label: "Winding-Up" },
  { to: "/admin/undang-undang/audit", label: "Audit" },
  { to: "/admin/undang-undang/laporan", label: "Laporan" },
  { to: "/admin/undang-undang/insolvensi", label: "Integrasi Insolvensi" },
];

const route = useRoute();
const { tp } = useI18n();

const displayNavItems = computed(() => navItems.map((item) => ({ ...item, label: tp(item.label) })));

function isActive(to: string) {
  if (to === "/admin/undang-undang/litigasi") return route.path === to || route.path.startsWith("/admin/undang-undang/kes/");
  return route.path.startsWith(to);
}
</script>

<template>
  <nav class="flex flex-wrap gap-1 border-b border-slate-200 bg-slate-50/80 px-2 py-2">
    <RouterLink
      v-for="item in displayNavItems"
      :key="item.to"
      :to="item.to"
      class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
      :class="isActive(item.to) ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-200 hover:text-slate-900'"
    >
      {{ item.label }}
    </RouterLink>
  </nav>
</template>
