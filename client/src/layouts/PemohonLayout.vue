<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
  Bell,
  ChevronDown,
  Clock,
  FileText,
  HelpCircle,
  Home,
  LogOut,
  Mail,
  PenLine,
  Phone,
  Plus,
  ShieldCheck,
  User,
  Wallet,
} from "lucide-vue-next";

import AppToastRegion from "@/components/AppToastRegion.vue";
import { useToast } from "@/composables/useToast";
import { HUBUNGI_TEKUN } from "@/data/portal-dummy";
import { usePemohonStore } from "@/stores/pemohon";
import { initials } from "@/utils/pemohon-ui";

const route = useRoute();
const router = useRouter();
const pemohon = usePemohonStore();
const toast = useToast();

const sideNav = [
  { to: "/pemohon/dashboard", label: "Utama", icon: Home },
  { to: "/pemohon/permohonan", label: "Permohonan", icon: FileText },
  { to: "/pemohon/pembiayaan", label: "Pembiayaan", icon: Wallet },
  { to: "/pemohon/profil", label: "Profil", icon: User },
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
const email = computed(() => pemohon.account?.email || pemohon.profil.email);

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
  <div class="min-h-screen bg-[#e6ecf6] lg:p-4">
    <div class="lg:flex lg:h-[calc(100vh-2rem)] lg:overflow-hidden lg:rounded-[28px] lg:shadow-[0_24px_64px_-24px_rgba(0,16,124,0.35)]">
      <!-- Sidebar (desktop) -->
      <aside class="hidden w-[272px] shrink-0 flex-col bg-ink-950 px-5 pb-5 pt-6 text-white lg:flex" aria-label="Menu portal">
        <div class="flex items-center justify-between">
          <router-link to="/pemohon/dashboard" class="min-w-0 px-1" aria-label="Portal Usahawan — Utama">
            <span class="block text-[11px] font-semibold uppercase tracking-[0.14em] text-white/40">TEKUN Nasional</span>
            <span class="block text-lg font-bold leading-tight text-white">Portal Usahawan</span>
          </router-link>
          <router-link
            to="/pemohon/permohonan"
            class="relative flex h-11 w-11 items-center justify-center rounded-2xl bg-white/10 text-white transition-colors hover:bg-white/15"
            aria-label="Notifikasi permohonan"
          >
            <Bell class="h-5 w-5" />
            <span v-if="pemohon.permohonanSaya.length" class="absolute right-2.5 top-2.5 h-2 w-2 rounded-full bg-brand-300 ring-2 ring-ink-950" />
          </router-link>
        </div>

        <nav class="mt-10 space-y-1.5" aria-label="Navigasi utama">
          <router-link
            v-for="item in sideNav"
            :key="item.to"
            :to="item.to"
            class="flex h-12 items-center gap-3.5 rounded-2xl px-4 text-[15px] font-medium transition-colors"
            :class="isActive(item.to) ? 'bg-brand-50 font-semibold text-brand-800' : 'text-white/70 hover:bg-white/5 hover:text-white'"
          >
            <component :is="item.icon" class="h-5 w-5" />
            {{ item.label }}
          </router-link>
        </nav>

        <div class="mt-auto">
          <div class="rounded-2xl bg-white/[0.06] p-4">
            <div class="flex items-start gap-3">
              <img v-if="pemohon.profil.foto" :src="pemohon.profil.foto" alt="" class="h-11 w-11 shrink-0 rounded-full object-cover ring-2 ring-white/20" />
              <span v-else class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-600 text-sm font-semibold text-white">
                {{ userInitials }}
              </span>
              <div class="min-w-0 flex-1">
                <p class="truncate text-[15px] font-semibold">{{ pemohon.profil.nama || "Pemohon" }}</p>
                <span class="mt-1.5 inline-flex rounded-lg bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-800">Usahawan TEKUN</span>
              </div>
              <router-link to="/pemohon/profil" class="rounded-lg p-1 text-white/50 transition-colors hover:text-white" aria-label="Kemas kini profil">
                <PenLine class="h-4 w-4" />
              </router-link>
            </div>
          </div>
          <button
            type="button"
            class="mt-3 flex w-full items-center gap-3 rounded-2xl px-4 py-2.5 text-sm font-medium text-white/60 transition-colors hover:bg-white/5 hover:text-white"
            @click="logout"
          >
            <LogOut class="h-4 w-4" /> Log keluar
          </button>
        </div>
      </aside>

      <!-- Main panel -->
      <div class="flex min-w-0 flex-1 flex-col bg-white lg:overflow-y-auto">
        <!-- Mobile / tablet top bar -->
        <header class="sticky top-0 z-40 border-b border-slate-200/70 bg-white/90 backdrop-blur lg:hidden">
          <div class="flex h-16 items-center gap-3 px-4 sm:px-6">
            <router-link to="/pemohon/dashboard" class="flex shrink-0 items-center gap-3">
              <img src="/logo_tekun.png" alt="TEKUN Nasional" class="h-8 w-auto" />
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
                  <img v-if="pemohon.profil.foto" :src="pemohon.profil.foto" alt="" class="h-8 w-8 rounded-full object-cover" />
                  <span v-else class="flex h-8 w-8 items-center justify-center rounded-full bg-ink-900 text-xs font-semibold text-white">{{ userInitials }}</span>
                  <span class="hidden text-sm font-medium text-ink-800 sm:block">{{ firstName }}</span>
                  <ChevronDown class="hidden h-4 w-4 text-slate-400 sm:block" />
                </button>
                <div
                  v-if="menuOpen"
                  role="menu"
                  class="absolute right-0 mt-2 w-60 overflow-hidden rounded-2xl border border-slate-200 bg-white p-1.5 shadow-xl shadow-slate-900/10"
                >
                  <div class="px-3 py-2.5">
                    <p class="truncate text-sm font-semibold text-ink-900">{{ pemohon.profil.nama || "Pemohon" }}</p>
                    <p class="truncate text-xs text-slate-500">{{ email }}</p>
                  </div>
                  <div class="my-1 h-px bg-slate-100" />
                  <router-link to="/pemohon/bantuan" role="menuitem" class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm text-ink-700 hover:bg-slate-50">
                    <HelpCircle class="h-4 w-4 text-slate-400" /> Bantuan & soalan lazim
                  </router-link>
                  <button type="button" role="menuitem" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-sm text-rose-600 hover:bg-rose-50" @click="logout">
                    <LogOut class="h-4 w-4" /> Log keluar
                  </button>
                </div>
              </div>
            </div>
          </div>
        </header>

        <!-- Desktop toolbar -->
        <div class="hidden items-center justify-end gap-4 px-10 pt-6 lg:flex">
          <AppToastRegion />
          <router-link to="/pemohon/dashboard" aria-label="TEKUN Nasional — Utama">
            <img src="/logo_tekun.png" alt="TEKUN Nasional" class="h-10 w-auto" />
          </router-link>
        </div>

        <main class="mx-auto w-full max-w-6xl flex-1 px-4 pb-12 sm:px-6 lg:px-10">
          <slot />
        </main>

        <footer class="border-t border-brand-100 bg-brand-50 pb-28 lg:pb-0">
          <div class="mx-auto grid w-full max-w-6xl gap-8 px-4 py-10 sm:px-6 md:grid-cols-[1.4fr_1fr_1.2fr] lg:px-10">
            <div>
              <img src="/logo_tekun.png" alt="TEKUN Nasional" class="h-9 w-auto" />
              <p class="mt-4 max-w-sm text-sm leading-relaxed text-brand-800/80">
                Rakan pembangunan usahawan — pembiayaan mikro yang pantas, telus dan dibantu AI, dengan keputusan akhir oleh pegawai TEKUN.
              </p>
              <p class="mt-4 inline-flex items-center gap-1.5 rounded-full bg-white/70 px-3 py-1 text-xs font-medium text-brand-800 ring-1 ring-brand-100">
                <ShieldCheck class="h-3.5 w-3.5" /> Dilindungi Akta Perlindungan Data Peribadi 2010
              </p>
            </div>
            <nav aria-label="Pautan pantas">
              <h2 class="text-sm font-semibold text-brand-800">Pautan pantas</h2>
              <ul class="mt-3 space-y-2 text-sm">
                <li v-for="item in sideNav" :key="item.to">
                  <router-link :to="item.to" class="text-brand-800/75 transition-colors hover:text-brand-800">{{ item.label }}</router-link>
                </li>
              </ul>
            </nav>
            <div>
              <h2 class="text-sm font-semibold text-brand-800">Hubungi kami</h2>
              <ul class="mt-3 space-y-2.5 text-sm text-brand-800/75">
                <li class="flex items-center gap-2"><Phone class="h-4 w-4 shrink-0 text-brand-600" /> {{ HUBUNGI_TEKUN.telefon }}</li>
                <li class="flex items-center gap-2"><Mail class="h-4 w-4 shrink-0 text-brand-600" /> {{ HUBUNGI_TEKUN.emel }}</li>
                <li v-for="w in HUBUNGI_TEKUN.waktuOperasi" :key="w" class="flex items-center gap-2"><Clock class="h-4 w-4 shrink-0 text-brand-600" /> {{ w }}</li>
              </ul>
            </div>
          </div>
          <div class="border-t border-brand-100">
            <p class="mx-auto w-full max-w-6xl px-4 py-4 text-xs text-brand-800/60 sm:px-6 lg:px-10">
              © {{ new Date().getFullYear() }} Tabung Ekonomi Kumpulan Usaha Niaga (TEKUN Nasional). Hak cipta terpelihara.
            </p>
          </div>
        </footer>
      </div>
    </div>

    <!-- Mobile / tablet bottom tab bar -->
    <nav
      class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 backdrop-blur lg:hidden"
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
