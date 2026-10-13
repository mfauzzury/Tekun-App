<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { ChevronDown, FileText, HelpCircle, Home, LogOut, Plus, User, Wallet } from "lucide-vue-next";

import AppToastRegion from "@/components/AppToastRegion.vue";
import { useToast } from "@/composables/useToast";
import { usePemohonStore } from "@/stores/pemohon";
import { initials } from "@/utils/pemohon-ui";

const route = useRoute();
const router = useRouter();
const pemohon = usePemohonStore();
const toast = useToast();

const desktopNav = [
  { to: "/pemohon/dashboard", label: "Utama", icon: Home },
  { to: "/pemohon/permohonan", label: "Permohonan", icon: FileText },
  { to: "/pemohon/pembiayaan", label: "Pembiayaan", icon: Wallet },
  { to: "/pemohon/bantuan", label: "Bantuan", icon: HelpCircle },
];

const mobileNav = [
  { to: "/pemohon/dashboard", label: "Utama", icon: Home },
  { to: "/pemohon/permohonan", label: "Permohonan", icon: FileText },
  { to: "/pemohon/permohonan/baru", label: "Mohon", icon: Plus, primary: true },
  { to: "/pemohon/pembiayaan", label: "Pembiayaan", icon: Wallet },
  { to: "/pemohon/profil", label: "Profil", icon: User },
];

function isActive(path: string) {
  if (path === "/pemohon/permohonan") {
    return route.path === path || (route.path.startsWith(`${path}/`) && route.path !== "/pemohon/permohonan/baru");
  }
  return route.path === path || route.path.startsWith(`${path}/`);
}

const userInitials = computed(() => initials(pemohon.profil.nama));
const firstName = computed(() => (pemohon.profil.nama || "Pemohon").split(" ")[0]);

const menuOpen = ref(false);
const menuRef = ref<HTMLElement | null>(null);

function onDocumentClick(e: MouseEvent) {
  if (menuRef.value && !menuRef.value.contains(e.target as Node)) menuOpen.value = false;
}
onMounted(() => document.addEventListener("click", onDocumentClick));
onBeforeUnmount(() => document.removeEventListener("click", onDocumentClick));
watch(() => route.fullPath, () => (menuOpen.value = false));

function logout() {
  pemohon.logout();
  toast.success("Berjaya log keluar", "Sesi anda telah ditamatkan.");
  router.push({ name: "pemohon-log-masuk" });
}
</script>

<template>
  <div class="min-h-screen bg-[#f6f7fb]">
    <header class="sticky top-0 z-40 border-b border-slate-200/70 bg-white/90 backdrop-blur supports-[backdrop-filter]:bg-white/75">
      <div class="mx-auto flex h-16 w-full max-w-6xl items-center gap-6 px-4 sm:px-6">
        <router-link to="/pemohon/dashboard" class="flex shrink-0 items-center gap-3">
          <img src="/logo_tekun.png" alt="TEKUN Nasional" class="h-8 w-auto" />
          <span class="hidden h-6 w-px bg-slate-200 sm:block" />
          <span class="hidden text-sm font-semibold text-ink-700 sm:block">Portal Usahawan</span>
        </router-link>

        <div class="ml-auto flex items-center gap-2">
          <AppToastRegion />

          <div ref="menuRef" class="relative">
            <button
              type="button"
              class="flex items-center gap-2 rounded-xl p-1 pr-2 transition-colors hover:bg-slate-100"
              :aria-expanded="menuOpen"
              aria-haspopup="menu"
              @click="menuOpen = !menuOpen"
            >
              <img v-if="pemohon.profil.foto" :src="pemohon.profil.foto" alt="" class="h-8 w-8 rounded-full object-cover ring-2 ring-white" />
              <span v-else class="flex h-8 w-8 items-center justify-center rounded-full bg-ink-900 text-xs font-semibold text-white">
                {{ userInitials }}
              </span>
              <span class="hidden text-sm font-medium text-ink-800 sm:block">{{ firstName }}</span>
              <ChevronDown class="hidden h-4 w-4 text-slate-400 sm:block" />
            </button>

            <transition
              enter-active-class="transition duration-100 ease-out"
              enter-from-class="opacity-0 -translate-y-1"
              leave-active-class="transition duration-75 ease-in"
              leave-to-class="opacity-0 -translate-y-1"
            >
              <div
                v-if="menuOpen"
                role="menu"
                class="absolute right-0 mt-2 w-60 overflow-hidden rounded-2xl border border-slate-200 bg-white p-1.5 shadow-xl shadow-slate-900/10"
              >
                <div class="px-3 py-2.5">
                  <p class="truncate text-sm font-semibold text-ink-900">{{ pemohon.profil.nama || "Pemohon" }}</p>
                  <p class="truncate text-xs text-slate-500">{{ pemohon.account?.email || pemohon.profil.email }}</p>
                </div>
                <div class="my-1 h-px bg-slate-100" />
                <router-link to="/pemohon/profil" role="menuitem" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm text-ink-700 hover:bg-slate-50">
                  <User class="h-4 w-4 text-slate-400" /> Profil saya
                </router-link>
                <router-link to="/pemohon/bantuan" role="menuitem" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm text-ink-700 hover:bg-slate-50">
                  <HelpCircle class="h-4 w-4 text-slate-400" /> Bantuan & soalan lazim
                </router-link>
                <div class="my-1 h-px bg-slate-100" />
                <button
                  type="button"
                  role="menuitem"
                  class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-sm text-rose-600 hover:bg-rose-50"
                  @click="logout"
                >
                  <LogOut class="h-4 w-4" /> Log keluar
                </button>
              </div>
            </transition>
          </div>
        </div>
      </div>

      <!-- Desktop menu bar, below the top bar -->
      <div class="hidden border-t border-slate-200/70 md:block">
        <nav class="mx-auto flex h-12 w-full max-w-6xl items-center gap-1 px-4 sm:px-6" aria-label="Navigasi utama">
          <router-link
            v-for="item in desktopNav"
            :key="item.to"
            :to="item.to"
            class="relative flex h-12 items-center gap-2 px-3 text-sm font-medium transition-colors"
            :class="isActive(item.to) ? 'text-brand-700' : 'text-slate-500 hover:text-ink-900'"
          >
            <component :is="item.icon" class="h-4 w-4" />
            {{ item.label }}
            <span v-if="isActive(item.to)" class="absolute inset-x-3 bottom-0 h-0.5 rounded-full bg-brand-600" />
          </router-link>
        </nav>
      </div>
    </header>

    <main class="mx-auto w-full max-w-6xl px-4 pb-32 sm:px-6 md:pb-12">
      <slot />
    </main>

    <!-- Mobile bottom tab bar -->
    <nav
      class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 backdrop-blur md:hidden"
      style="padding-bottom: env(safe-area-inset-bottom)"
      aria-label="Navigasi utama"
    >
      <div class="mx-auto grid max-w-md grid-cols-5 items-end px-2">
        <router-link
          v-for="item in mobileNav"
          :key="item.to"
          :to="item.to"
          class="flex flex-col items-center gap-1 pb-2 pt-2 text-[11px] font-medium"
          :class="item.primary ? 'text-brand-700' : isActive(item.to) ? 'text-brand-600' : 'text-slate-500'"
        >
          <span
            v-if="item.primary"
            class="-mt-6 flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-600 text-white shadow-lg shadow-brand-600/30 ring-4 ring-white"
          >
            <component :is="item.icon" class="h-6 w-6" />
          </span>
          <component :is="item.icon" v-else class="h-5 w-5" />
          {{ item.label }}
        </router-link>
      </div>
    </nav>
  </div>
</template>
