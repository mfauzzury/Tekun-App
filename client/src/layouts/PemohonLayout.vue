<script setup lang="ts">
import { computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import { Bell, FileText, HelpCircle, LayoutGrid, LogOut, User, Wallet } from "lucide-vue-next";

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
  { to: "/pemohon/pembiayaan", label: "Pembiayaan Saya", shortLabel: "Pembiayaan", icon: Wallet },
  { to: "/pemohon/profil", label: "Profil Saya", shortLabel: "Profil", icon: User },
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
    <header class="static md:sticky md:top-0 z-40 bg-white">
      <div class="mx-auto flex h-12 w-full max-w-5xl items-center justify-between px-4">
        <router-link to="/pemohon/dashboard" class="flex items-center gap-2.5">
          <img src="/logo_tekun.png" alt="TEKUN Nasional" class="h-7 w-auto" />
        </router-link>

        <div class="flex items-center gap-1">
          <AppToastRegion />
          <button
            type="button"
            class="group relative flex h-8 w-8 items-center justify-center rounded-full text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-900"
            aria-label="Notifikasi"
          >
            <Bell class="h-4 w-4" />
            <span class="absolute right-1 top-1 h-2 w-2 rounded-full bg-rose-500 ring-2 ring-white" />
            <span class="pointer-events-none absolute -bottom-9 left-1/2 z-50 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100">Notifikasi</span>
          </button>
          <router-link
            to="/pemohon/bantuan"
            class="group relative flex h-8 w-8 items-center justify-center rounded-full transition-colors"
            :class="isActive('/pemohon/bantuan') ? 'bg-blue-50 text-blue-700' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900'"
            aria-label="Bantuan"
          >
            <HelpCircle class="h-4 w-4" />
            <span class="pointer-events-none absolute -bottom-9 left-1/2 z-50 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100">Bantuan</span>
          </router-link>
          <img
            v-if="pemohon.profil.foto"
            :src="pemohon.profil.foto"
            alt="Foto profil"
            class="h-7 w-7 rounded-full object-cover ring-1 ring-slate-200"
          />
          <div
            v-else
            class="flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-indigo-600 text-[11px] font-semibold text-white"
          >
            {{ userInitials }}
          </div>
          <button
            type="button"
            class="group relative flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-sm font-medium text-slate-500 transition-colors hover:bg-rose-50 hover:text-rose-600"
            aria-label="Log Keluar"
            @click="logout"
          >
            <LogOut class="h-4 w-4" />
            <span class="hidden sm:inline">Log Keluar</span>
            <span class="pointer-events-none absolute -bottom-9 left-1/2 z-50 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100 sm:hidden">Log Keluar</span>
          </button>
        </div>
      </div>

      <div class="hidden border-t border-red-700 bg-red-600 md:block">
        <div class="mx-auto flex h-11 w-full max-w-5xl items-center justify-between px-4">
          <span class="text-sm font-semibold text-white">Portal Pemohon SPPT</span>

          <nav class="flex items-center gap-1">
            <router-link
              v-for="item in navItems"
              :key="item.to"
              :to="item.to"
              class="flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-sm font-medium transition-colors"
              :class="isActive(item.to) ? 'border-white text-white' : 'border-transparent text-red-100 hover:bg-red-500 hover:text-white'"
            >
              <component :is="item.icon" class="h-4 w-4" />
              {{ item.label }}
            </router-link>
          </nav>
        </div>
      </div>
    </header>

    <main class="mx-auto w-full max-w-5xl px-4 pb-24 pt-0 md:pb-6">
      <slot />
    </main>

    <nav
      class="fixed inset-x-4 bottom-4 z-40 flex items-stretch overflow-hidden rounded-full bg-red-600 shadow-lg shadow-red-900/20 md:hidden"
      style="bottom: max(1rem, env(safe-area-inset-bottom))"
    >
      <router-link
        v-for="item in navItems"
        :key="item.to"
        :to="item.to"
        class="flex flex-1 flex-col items-center justify-center gap-0.5 py-2.5 text-[11px] font-medium transition-colors"
        :class="isActive(item.to) ? 'text-white' : 'text-red-100'"
      >
        <component :is="item.icon" class="h-5 w-5" />
        {{ item.shortLabel }}
      </router-link>
    </nav>
  </div>
</template>
