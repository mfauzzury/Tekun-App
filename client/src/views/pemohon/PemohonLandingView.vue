<script setup lang="ts">
import { ArrowRight, CheckCircle2, FileText, Mail, MapPin, Phone, ShieldCheck, Wallet } from "lucide-vue-next";
import { FAQ_DUMMY, HUBUNGI_TEKUN, PRODUK_OPTIONS } from "@/data/portal-dummy";

const features = [
  {
    icon: FileText,
    title: "Permohonan Dalam Talian",
    desc: "Isi dan hantar permohonan pembiayaan bila-bila masa, di mana sahaja.",
    iconBg: "bg-blue-50",
    iconColor: "text-blue-600",
  },
  {
    icon: ShieldCheck,
    title: "Pengesahan Identiti Pantas",
    desc: "Proses eKYC digital untuk pengesahan identiti yang selamat dan cepat.",
    iconBg: "bg-red-50",
    iconColor: "text-red-600",
  },
  {
    icon: CheckCircle2,
    title: "Semakan Kelayakan Segera",
    desc: "Ketahui status kelayakan awal anda sebelum menghantar permohonan penuh.",
    iconBg: "bg-blue-50",
    iconColor: "text-blue-600",
  },
  {
    icon: Wallet,
    title: "Penjejakan Status Masa Nyata",
    desc: "Pantau status permohonan anda dari peringkat semakan hingga kelulusan.",
    iconBg: "bg-red-50",
    iconColor: "text-red-600",
  },
];
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <header class="border-b border-slate-200 bg-white">
      <div class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-4">
        <div class="flex items-center gap-2.5">
          <img src="/logo_tekun.png" alt="TEKUN Nasional" class="h-8 w-auto" />
          <span class="hidden text-base font-semibold text-slate-900 sm:inline">Portal Pemohon SPPT</span>
        </div>
        <div class="flex items-center gap-2">
          <router-link
            to="/pemohon/log-masuk"
            class="rounded-md px-4 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900"
          >
            Log Masuk
          </router-link>
          <router-link
            to="/pemohon/daftar"
            class="flex items-center gap-1.5 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
          >
            Daftar Akaun
            <ArrowRight class="h-4 w-4" />
          </router-link>
        </div>
      </div>
    </header>

    <main>
      <section class="mx-auto w-full max-w-6xl px-4 py-16 text-center">
        <span class="mb-3 inline-flex items-center rounded-full bg-red-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-red-600">
          Sistem Pengurusan Pembiayaan TEKUN
        </span>
        <h1 class="mx-auto max-w-3xl text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">
          Permohonan Pembiayaan Perniagaan Yang Mudah &amp; Pantas
        </h1>
        <p class="mx-auto mt-4 max-w-2xl text-base text-slate-600">
          Daftar, semak kelayakan, dan hantar permohonan pembiayaan anda secara dalam talian melalui Portal Pemohon SPPT TEKUN Nasional.
        </p>
        <div class="mt-8 flex items-center justify-center gap-3">
          <router-link
            to="/pemohon/daftar"
            class="flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-700"
          >
            Mohon Sekarang
            <ArrowRight class="h-4 w-4" />
          </router-link>
          <router-link
            to="/pemohon/bantuan"
            class="rounded-lg border border-red-200 bg-white px-6 py-3 text-sm font-semibold text-red-700 transition-colors hover:border-red-300 hover:bg-red-50"
          >
            Soalan Lazim
          </router-link>
        </div>
      </section>

      <section class="mx-auto w-full max-w-6xl px-4 pb-16">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div v-for="feature in features" :key="feature.title" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg" :class="feature.iconBg">
              <component :is="feature.icon" class="h-5 w-5" :class="feature.iconColor" />
            </div>
            <h3 class="text-sm font-semibold text-slate-900">{{ feature.title }}</h3>
            <p class="mt-1 text-sm text-slate-500">{{ feature.desc }}</p>
          </div>
        </div>
      </section>

      <section class="border-y border-slate-200 bg-white py-16">
        <div class="mx-auto w-full max-w-6xl px-4">
          <h2 class="mb-6 text-center text-2xl font-bold text-slate-900">Skim Pembiayaan Tersedia</h2>
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div
              v-for="produk in PRODUK_OPTIONS"
              :key="produk.value"
              class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-center transition-colors hover:border-blue-300 hover:bg-blue-50"
            >
              <p class="text-sm font-semibold text-slate-900">{{ produk.label }}</p>
            </div>
          </div>
        </div>
      </section>

      <section class="mx-auto w-full max-w-3xl px-4 py-16">
        <h2 class="mb-6 text-center text-2xl font-bold text-slate-900">Soalan Lazim</h2>
        <div class="space-y-3">
          <details v-for="item in FAQ_DUMMY.slice(0, 3)" :key="item.q" class="group rounded-lg border border-slate-200 bg-white p-4">
            <summary class="cursor-pointer text-sm font-medium text-slate-900">{{ item.q }}</summary>
            <p class="mt-2 text-sm text-slate-600">{{ item.a }}</p>
          </details>
        </div>
        <div class="mt-4 text-center">
          <router-link to="/pemohon/bantuan" class="text-sm font-medium text-blue-600 hover:text-blue-700">
            Lihat semua soalan lazim →
          </router-link>
        </div>
      </section>
    </main>

    <footer class="border-t border-slate-200 bg-white">
      <div class="mx-auto w-full max-w-6xl px-4 py-8">
        <div class="grid grid-cols-1 gap-4 text-sm text-slate-600 sm:grid-cols-3">
          <div class="flex items-start gap-2.5">
            <MapPin class="mt-0.5 h-4 w-4 shrink-0 text-red-600" />
            <span>{{ HUBUNGI_TEKUN.alamat }}</span>
          </div>
          <div class="flex items-center gap-2.5">
            <Phone class="h-4 w-4 shrink-0 text-red-600" />
            <a :href="`tel:${HUBUNGI_TEKUN.telefon.replace(/[^0-9+]/g, '')}`" class="hover:text-blue-700">{{ HUBUNGI_TEKUN.telefon }}</a>
          </div>
          <div class="flex items-center gap-2.5">
            <Mail class="h-4 w-4 shrink-0 text-red-600" />
            <a :href="`mailto:${HUBUNGI_TEKUN.emel}`" class="hover:text-blue-700">{{ HUBUNGI_TEKUN.emel }}</a>
          </div>
        </div>
        <div class="mt-6 border-t border-slate-100 pt-4 text-center text-sm text-slate-500">
          &copy; {{ new Date().getFullYear() }} TEKUN Nasional — Sistem Pengurusan Pembiayaan TEKUN (SPPT)
        </div>
      </div>
    </footer>
  </div>
</template>
