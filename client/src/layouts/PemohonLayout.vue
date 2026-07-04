<script setup lang="ts">
import { computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import { FileText, HelpCircle, LayoutGrid, LogOut, User } from "lucide-vue-next";

import AppToastRegion from "@/components/AppToastRegion.vue";
import { useToast } from "@/composables/useToast";
import { usePemohonStore } from "@/stores/pemohon";

const route = useRoute();
const router = useRouter();
const pemohon = usePemohonStore();
const toast = useToast();

const navItems = [
  { to: "/pemohon/dashboard", label: "Dashboard", icon: LayoutGrid },
  { to: "/pemohon/permohonan", label: "Permohonan Saya", icon: FileText },
  { to: "/pemohon/profil", label: "Profil Saya", icon: User },
  { to: "/pemohon/bantuan", label: "Bantuan", icon: HelpCircle },
];

function isActive(path: string) {
  return route.path === path || route.path.startsWith(`${path}/`);
}

const userInitials = computed(() => {
  const nama = pemohon.profil.nama || "P";
  return nama
    .split(" ")
    .map((n) => n[0])
    .join("")
    .toUpperCase()
    .slice(0, 2);
});

function logout() {
  pemohon.logout();
  toast.success("Berjaya log keluar", "Sesi anda telah ditamatkan.");
  router.push({ name: "pemohon-log-masuk" });
}
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white">
      <div class="mx-auto flex h-14 w-full max-w-6xl items-center justify-between px-4">
        <router-link to="/pemohon/dashboard" class="flex items-center gap-2">
          <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-violet-600 to-indigo-600">
            <span class="text-xs font-bold text-white">TN</span>
          </div>
          <span class="text-sm font-semibold text-slate-900">Portal Pemohon SPPT</span>
        </router-link>

        <nav class="hidden items-center gap-1 md:flex">
          <router-link
            v-for="item in navItems"
            :key="item.to"
            :to="item.to"
            class="flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
            :class="isActive(item.to) ? 'bg-violet-50 text-violet-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
          >
            <component :is="item.icon" class="h-4 w-4" />
            {{ item.label }}
          </router-link>
        </nav>

        <div class="flex items-center gap-3">
          <AppToastRegion />
          <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-violet-600 to-indigo-600 text-xs font-semibold text-white">
            {{ userInitials }}
          </div>
          <button
            type="button"
            class="flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-sm font-medium text-slate-500 transition-colors hover:bg-rose-50 hover:text-rose-600"
            @click="logout"
          >
            <LogOut class="h-4 w-4" />
            <span class="hidden sm:inline">Log Keluar</span>
          </button>
        </div>
      </div>

      <nav class="flex items-center gap-1 overflow-x-auto border-t border-slate-100 px-4 py-1.5 md:hidden">
        <router-link
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          class="flex shrink-0 items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium transition-colors"
          :class="isActive(item.to) ? 'bg-violet-50 text-violet-700' : 'text-slate-600'"
        >
          <component :is="item.icon" class="h-3.5 w-3.5" />
          {{ item.label }}
        </router-link>
      </nav>
    </header>

    <main class="mx-auto w-full max-w-6xl px-4 py-6">
      <slot />
    </main>
  </div>
</template>
