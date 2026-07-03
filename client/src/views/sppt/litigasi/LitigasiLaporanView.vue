<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref } from "vue";
import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import LitigasiSubNav from "@/components/sppt/litigasi/LitigasiSubNav.vue";
import { LAPORAN_KES_AKTIF, LAPORAN_KEPUTUSAN_BULANAN } from "@/data/litigasi-dummy";

const { t, tp } = useI18n();

const kesAktif = ref([...LAPORAN_KES_AKTIF]);
const keputusanBulanan = ref([...LAPORAN_KEPUTUSAN_BULANAN]);
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="Laporan Litigasi"
        :breadcrumb="[{ label: 'Undang-Undang', to: '/admin/undang-undang/litigasi' }, { label: 'Laporan' }]"
      />

      <LitigasiSubNav />

      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs font-medium text-slate-500">Tempoh Purata Kes</p>
          <p class="mt-1 text-2xl font-semibold text-slate-900">45 hari</p>
        </article>
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs font-medium text-slate-500">Jumlah Dikumpul (YTD)</p>
          <p class="mt-1 text-2xl font-semibold text-emerald-600">RM 125,400</p>
        </article>
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs font-medium text-slate-500">Kes Aktif</p>
          <p class="mt-1 text-2xl font-semibold text-slate-900">31</p>
        </article>
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs font-medium text-slate-500">Kes Selesai (Bulan Ini)</p>
          <p class="mt-1 text-2xl font-semibold text-slate-900">8</p>
        </article>
      </div>

      <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold text-slate-900">Kes Aktif mengikut Mahkamah (7.8.5)</h3>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold text-slate-500">Mahkamah</th>
                <th class="px-4 py-2 text-xs font-semibold text-slate-500">Bil. Kes</th>
                <th class="px-4 py-2 text-xs font-semibold text-slate-500">Nilai Tuntutan</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="row in kesAktif" :key="row.mahkamah" class="hover:bg-slate-50">
                <td class="px-4 py-2 font-medium text-slate-900">{{ row.mahkamah }}</td>
                <td class="px-4 py-2 text-slate-600">{{ row.kes }}</td>
                <td class="px-4 py-2 font-medium text-slate-900">{{ row.nilai }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>

      <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="mb-3 text-sm font-semibold text-slate-900">Keputusan Bulanan</h3>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold text-slate-500">Bulan</th>
                <th class="px-4 py-2 text-xs font-semibold text-slate-500">Menang</th>
                <th class="px-4 py-2 text-xs font-semibold text-slate-500">Kalah</th>
                <th class="px-4 py-2 text-xs font-semibold text-slate-500">Selesai</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="row in keputusanBulanan" :key="row.bulan" class="hover:bg-slate-50">
                <td class="px-4 py-2 font-medium text-slate-900">{{ row.bulan }}</td>
                <td class="px-4 py-2 text-emerald-600">{{ row.menang }}</td>
                <td class="px-4 py-2 text-rose-600">{{ row.kalah }}</td>
                <td class="px-4 py-2 text-slate-600">{{ row.selesai }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>

      <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="mb-2 text-sm font-semibold text-slate-900">Laporan Ringkasan & Terperinci (7.8.5)</h3>
        <p class="text-sm text-slate-600">Kes aktif mengikut mahkamah/peguam, tempoh purata kes diselesaikan, jumlah tuntutan berjaya dikutip, laporan tempoh penghakiman.</p>
      </article>
    </div>
  </AdminLayout>
</template>
