<script setup lang="ts">
import { ArrowRight, CheckCircle2, FileText, ShieldCheck, Wallet } from "lucide-vue-next";
import { FAQ_DUMMY, PRODUK_OPTIONS } from "@/data/portal-dummy";

const features = [
  { icon: FileText, title: "Permohonan Dalam Talian", desc: "Isi dan hantar permohonan pembiayaan bila-bila masa, di mana sahaja." },
  { icon: ShieldCheck, title: "Pengesahan Identiti Pantas", desc: "Proses eKYC digital untuk pengesahan identiti yang selamat dan cepat." },
  { icon: CheckCircle2, title: "Semakan Kelayakan Segera", desc: "Ketahui status kelayakan awal anda sebelum menghantar permohonan penuh." },
  { icon: Wallet, title: "Penjejakan Status Masa Nyata", desc: "Pantau status permohonan anda dari peringkat semakan hingga kelulusan." },
];
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <header class="border-b border-slate-200 bg-white">
      <div class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-4">
        <div class="flex items-center gap-2">
          <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-violet-600 to-indigo-600">
            <span class="text-xs font-bold text-white">TN</span>
          </div>
          <span class="text-base font-semibold text-slate-900">Portal Pemohon SPPT</span>
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
            class="flex items-center gap-1.5 rounded-md bg-violet-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-violet-700"
          >
            Daftar Akaun
            <ArrowRight class="h-4 w-4" />
          </router-link>
        </div>
      </div>
    </header>

    <main>
      <section class="mx-auto w-full max-w-6xl px-4 py-16 text-center">
        <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-violet-600">Sistem Pengurusan Pembiayaan TEKUN</p>
        <h1 class="mx-auto max-w-3xl text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">
          Permohonan Pembiayaan Perniagaan Yang Mudah &amp; Pantas
        </h1>
        <p class="mx-auto mt-4 max-w-2xl text-base text-slate-600">
          Daftar, semak kelayakan, dan hantar permohonan pembiayaan anda secara dalam talian melalui Portal Pemohon SPPT TEKUN Nasional.
        </p>
        <div class="mt-8 flex items-center justify-center gap-3">
          <router-link
            to="/pemohon/daftar"
            class="flex items-center gap-2 rounded-lg bg-violet-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-violet-700"
          >
            Mohon Sekarang
            <ArrowRight class="h-4 w-4" />
          </router-link>
          <router-link
            to="/pemohon/bantuan"
            class="rounded-lg border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition-colors hover:bg-slate-50"
          >
            Soalan Lazim
          </router-link>
        </div>
      </section>

      <section class="mx-auto w-full max-w-6xl px-4 pb-16">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div v-for="feature in features" :key="feature.title" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-violet-50">
              <component :is="feature.icon" class="h-5 w-5 text-violet-600" />
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
              class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-center transition-colors hover:border-violet-300 hover:bg-violet-50"
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
          <router-link to="/pemohon/bantuan" class="text-sm font-medium text-violet-600 hover:text-violet-700">
            Lihat semua soalan lazim →
          </router-link>
        </div>
      </section>
    </main>

    <footer class="border-t border-slate-200 bg-white">
      <div class="mx-auto w-full max-w-6xl px-4 py-4 text-center text-sm text-slate-500">
        &copy; {{ new Date().getFullYear() }} TEKUN Nasional — Sistem Pengurusan Pembiayaan TEKUN (SPPT)
      </div>
    </footer>
  </div>
</template>
