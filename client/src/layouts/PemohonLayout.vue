<script setup lang="ts">
import { computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import { Bell, FileText, HelpCircle, LayoutGrid, LogOut, User } from "lucide-vue-next";

import AppToastRegion from "@/components/AppToastRegion.vue";
import { useToast } from "@/composables/useToast";
import { usePemohonStore } from "@/stores/pemohon";

const route = useRoute();
const router = useRouter();
const pemohon = usePemohonStore();
const toast = useToast();

const navItems = [
  { to: "/pemohon/dashboard", label: "Dashboard", shortLabel: "Dashboard", icon: LayoutGrid },
  { to: "/pemohon/permohonan", label: "Permohonan Saya", shortLabel: "Permohonan", icon: FileText },
  { to: "/pemohon/profil", label: "Profil Saya", shortLabel: "Profil", icon: User },
  { to: "/pemohon/bantuan", label: "Bantuan", shortLabel: "Bantuan", icon: HelpCircle },
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
    <header class="static md:sticky md:top-0 z-40 border-b border-slate-200 bg-white">
      <div class="mx-auto flex h-12 w-full max-w-6xl items-center justify-between px-4">
        <router-link to="/pemohon/dashboard" class="flex items-center gap-2.5">
          <img src="/logo_tekun.png" alt="TEKUN Nasional" class="h-7 w-auto" />
        </router-link>

        <div class="flex items-center gap-3">
          <AppToastRegion />
          <button
            type="button"
            class="relative flex h-8 w-8 items-center justify-center rounded-full text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-900"
            aria-label="Notifikasi"
          >
            <Bell class="h-4 w-4" />
            <span class="absolute right-1 top-1 h-2 w-2 rounded-full bg-rose-500 ring-2 ring-white" />
          </button>
          <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-indigo-600 text-xs font-semibold text-white">
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

      <div class="hidden border-t border-slate-100 md:block">
        <div class="mx-auto flex h-11 w-full max-w-6xl items-center justify-between px-4">
          <span class="text-sm font-semibold text-slate-900">Portal Pemohon SPPT</span>

          <nav class="flex items-center gap-1">
            <router-link
              v-for="item in navItems"
              :key="item.to"
              :to="item.to"
              class="flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-sm font-medium transition-colors"
              :class="isActive(item.to) ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-600 hover:bg-slate-100 hover:text-slate-900'"
            >
              <component :is="item.icon" class="h-4 w-4" />
              {{ item.label }}
            </router-link>
          </nav>
        </div>
      </div>
    </header>

    <main class="mx-auto w-full max-w-6xl px-4 py-6 pb-24 md:pb-6">
      <slot />
    </main>

    <nav
      class="fixed inset-x-0 bottom-0 z-40 flex items-stretch border-t border-slate-200 bg-white pb-[env(safe-area-inset-bottom)] md:hidden"
    >
      <router-link
        v-for="item in navItems"
        :key="item.to"
        :to="item.to"
        class="flex flex-1 flex-col items-center justify-center gap-0.5 py-2 text-[11px] font-medium transition-colors"
        :class="isActive(item.to) ? 'text-blue-700' : 'text-slate-500'"
      >
        <component :is="item.icon" class="h-5 w-5" />
        {{ item.shortLabel }}
      </router-link>
    </nav>
  </div>
</template>
