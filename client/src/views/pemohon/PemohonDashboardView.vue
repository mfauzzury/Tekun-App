<script setup lang="ts">
import { computed } from "vue";
import { AlertTriangle, ArrowRight, FileText, PlusCircle, ShieldCheck, Store, Wallet, XCircle } from "lucide-vue-next";
import PemohonHebahanSlider from "@/components/pemohon/PemohonHebahanSlider.vue";
import PemohonLayout from "@/layouts/PemohonLayout.vue";
import { usePemohonStore } from "@/stores/pemohon";
import { formatRM } from "@/data/pembiayaan-dummy";

const pemohon = usePemohonStore();

const greeting = computed(() => {
  const h = new Date().getHours();
  if (h < 12) return "Selamat Pagi";
  if (h < 15) return "Selamat Tengah Hari";
  if (h < 19) return "Selamat Petang";
  return "Selamat Malam";
});

const todayLabel = computed(() =>
  new Date().toLocaleDateString("ms-MY", { weekday: "long", day: "numeric", month: "long", year: "numeric" }),
);

const initials = computed(() =>
  (pemohon.profil.nama || "P")
    .split(" ")
    .map((n) => n[0])
    .join("")
    .toUpperCase()
    .slice(0, 2),
);

const totalCount = computed(() => pemohon.permohonanList.length);
const activeCount = computed(
  () => pemohon.permohonanList.filter((p) => p.statusKey === "dalam_semakan" || p.statusKey === "dokumen_tambahan").length,
);
const approvedCount = computed(() => pemohon.permohonanList.filter((p) => p.statusKey === "lulus").length);
const rejectedCount = computed(() => pemohon.permohonanList.filter((p) => p.statusKey === "tidak_lulus").length);

function shareOf(count: number): string {
  return totalCount.value > 0 ? `${Math.round((count / totalCount.value) * 100)}% daripada jumlah` : "Tiada data lagi";
}

const stats = computed(() => [
  {
    key: "total",
    label: "Jumlah Permohonan",
    value: totalCount.value,
    caption: "Sejak permohonan pertama",
    icon: FileText,
    cardBg: "bg-gradient-to-br from-blue-500 to-blue-600",
  },
  {
    key: "active",
    label: "Dalam Semakan",
    value: activeCount.value,
    caption: shareOf(activeCount.value),
    icon: Wallet,
    cardBg: "bg-gradient-to-br from-amber-500 to-orange-600",
  },
  {
    key: "approved",
    label: "Lulus",
    value: approvedCount.value,
    caption: shareOf(approvedCount.value),
    icon: ShieldCheck,
    cardBg: "bg-gradient-to-br from-emerald-500 to-emerald-600",
  },
  {
    key: "rejected",
    label: "Tidak Lulus",
    value: rejectedCount.value,
    caption: shareOf(rejectedCount.value),
    icon: XCircle,
    cardBg: "bg-gradient-to-br from-rose-500 to-rose-600",
  },
]);

const onboardingIncomplete = computed(
  () => !pemohon.onboarding.eligibilityChecked || pemohon.onboarding.ekycResult !== "lulus",
);

const activeFinancing = computed(() => pemohon.pembiayaanList.filter((a) => a.status !== "Selesai"));
const totalOutstanding = computed(() => activeFinancing.value.reduce((sum, a) => sum + a.bakiAkhir, 0));
const nextPayment = computed(() => {
  const withArrears = activeFinancing.value.find((a) => a.tunggakan > 0);
  return withArrears ?? activeFinancing.value[0];
});
</script>

<template>
  <PemohonLayout>
    <div class="mx-auto max-w-5xl space-y-5">
      <div class="pemohon-hero rounded-b-2xl p-6 shadow-sm sm:p-7">
        <div class="flex items-center justify-between gap-4">
          <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-200">{{ greeting }} 👋</p>
            <h1 class="mt-2 truncate text-2xl font-bold text-white sm:text-3xl">{{ pemohon.profil.nama }}</h1>
            <p class="mt-1.5 flex items-center gap-1.5 text-sm text-blue-100">
              <Store class="h-4 w-4 shrink-0" />
              <span class="truncate">{{ pemohon.profil.perniagaan }}</span>
            </p>
          </div>
          <img
            v-if="pemohon.profil.foto"
            :src="pemohon.profil.foto"
            alt="Foto profil"
            class="hidden h-16 w-16 shrink-0 rounded-2xl object-cover ring-1 ring-white/25 sm:block"
          />
          <div
            v-else
            class="hidden h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-white/15 text-xl font-bold text-white ring-1 ring-white/25 sm:flex"
          >
            {{ initials }}
          </div>
        </div>

        <div
          v-if="onboardingIncomplete"
          class="mt-4 flex items-start gap-3 rounded-lg border border-amber-300/30 bg-amber-400/15 p-3.5"
        >
          <AlertTriangle class="mt-0.5 h-5 w-5 shrink-0 text-amber-300" />
          <div class="min-w-0 flex-1">
            <p class="text-sm font-medium text-amber-100">Lengkapkan proses onboarding anda</p>
            <p class="mt-0.5 text-xs text-amber-200/80">Semak kelayakan dan sahkan identiti (eKYC) sebelum menghantar permohonan baharu.</p>
          </div>
          <router-link
            to="/pemohon/semakan-kelayakan"
            class="shrink-0 self-center rounded-md bg-amber-400 px-3 py-1.5 text-xs font-semibold text-amber-950 transition-colors hover:bg-amber-300"
          >
            Teruskan
          </router-link>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1 border-t border-white/15 pt-3 text-xs text-blue-100">
          <span class="capitalize">{{ todayLabel }}</span>
          <span v-if="activeFinancing.length" class="flex items-center gap-1">
            <Wallet class="h-3.5 w-3.5" />
            {{ activeFinancing.length }} pembiayaan aktif · {{ formatRM(totalOutstanding) }}
          </span>
        </div>
      </div>

      <PemohonHebahanSlider />

      <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div
          v-for="stat in stats"
          :key="stat.key"
          class="rounded-xl p-5 shadow-sm transition-shadow hover:shadow-md"
          :class="stat.cardBg"
        >
          <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/15">
            <component :is="stat.icon" class="h-5 w-5 text-white" />
          </div>
          <p class="mt-3 text-3xl font-bold text-white">{{ stat.value }}</p>
          <p class="mt-1 text-xs font-medium text-white/90">{{ stat.label }}</p>
          <p class="mt-2 text-[11px] text-white/70">{{ stat.caption }}</p>
        </div>
      </div>

      <router-link
        v-if="activeFinancing.length"
        to="/pemohon/pembiayaan"
        class="block rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md"
      >
        <div class="flex items-center justify-between">
          <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-900">
            <Wallet class="h-4 w-4 text-blue-600" />
            Pembiayaan Aktif
          </h2>
          <ArrowRight class="h-4 w-4 text-slate-400" />
        </div>
        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
          <div>
            <p class="text-xs text-slate-500">Akaun Aktif</p>
            <p class="text-xl font-bold text-slate-900">{{ activeFinancing.length }}</p>
          </div>
          <div>
            <p class="text-xs text-slate-500">Jumlah Baki Tertunggak</p>
            <p class="text-xl font-bold text-slate-900">{{ formatRM(totalOutstanding) }}</p>
          </div>
          <div v-if="nextPayment">
            <p class="text-xs text-slate-500">Bayaran Seterusnya</p>
            <p class="text-xl font-bold text-slate-900">{{ nextPayment.tarikhBayaranSeterusnya }}</p>
            <p class="text-[11px] text-slate-400">{{ formatRM(nextPayment.bayaranBulanan) }} · {{ nextPayment.produk }}</p>
          </div>
        </div>
      </router-link>

      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-900">Tindakan Pantas</h2>
        </div>
        <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
          <router-link
            to="/pemohon/permohonan/baru"
            class="flex items-center justify-between rounded-lg border border-blue-200 bg-blue-50 p-4 transition-colors hover:bg-blue-100"
          >
            <div class="flex items-center gap-3">
              <PlusCircle class="h-5 w-5 text-blue-600" />
              <span class="text-sm font-medium text-blue-900">Mohon Pembiayaan Baharu</span>
            </div>
            <ArrowRight class="h-4 w-4 text-blue-600" />
          </router-link>
          <router-link
            to="/pemohon/permohonan"
            class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 p-4 transition-colors hover:bg-slate-100"
          >
            <div class="flex items-center gap-3">
              <FileText class="h-5 w-5 text-slate-600" />
              <span class="text-sm font-medium text-slate-900">Semak Permohonan Saya</span>
            </div>
            <ArrowRight class="h-4 w-4 text-slate-500" />
          </router-link>
        </div>
      </div>
    </div>
  </PemohonLayout>
</template>
