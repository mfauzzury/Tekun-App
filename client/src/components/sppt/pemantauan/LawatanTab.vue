<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, computed, onMounted } from "vue";
import { Calendar, FileText, MapPin } from "lucide-vue-next";
import type { LawatanItem, UsahawanItem } from "@/data/pemantauan-usahawan-dummy";
import { fetchSpptDataset } from "@/api/sppt";

const { t, tp } = useI18n();

const lawatanList = ref<LawatanItem[]>([]);
const usahawanList = ref<UsahawanItem[]>([]);

onMounted(async () => {
  const [lawatanRes, usahawanRes] = await Promise.all([
    fetchSpptDataset("pemantauan", "lawatan_list"),
    fetchSpptDataset("pemantauan", "usahawan_list"),
  ]);
  lawatanList.value = lawatanRes.data as LawatanItem[];
  usahawanList.value = usahawanRes.data as UsahawanItem[];
});

const activeSub = ref<"kalendar" | "form" | "laporan">("kalendar");

// Dummy calendar data - March 2024
const daysInMonth = computed(() => {
  const year = 2024;
  const month = 2; // March
  const first = new Date(year, month, 1);
  const last = new Date(year, month + 1, 0);
  const days: { date: Date; lawatan: LawatanItem[] }[] = [];
  for (let d = 1; d <= last.getDate(); d++) {
    const date = new Date(year, month, d);
    const lawatan = lawatanList.value.filter(
      (l) => l.tarikh === date.toISOString().slice(0, 10)
    );
    days.push({ date, lawatan });
  }
  return days;
});

function getUsahawanName(id: string) {
  return usahawanList.value.find((u) => u.id === id)?.nama ?? id;
}

function getDayName(d: Date) {
  return ["Ahd", "Isn", "Sel", "Rab", "Kha", "Jum", "Sab"][d.getDay()];
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex flex-wrap gap-2">
      <button
        v-for="t in [
          { id: 'kalendar' as const, label: 'Kalendar Lawatan', icon: Calendar },
          { id: 'form' as const, label: 'Borang Lawatan', icon: FileText },
          { id: 'laporan' as const, label: 'Laporan Lawatan', icon: FileText },
        ]"
        :key="t.id"
        type="button"
        class="flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-medium transition-colors"
        :class="activeSub === t.id ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
        @click="activeSub = t.id"
      >
        <component :is="t.icon" class="h-4 w-4" />
        {{ t.label }}
      </button>
    </div>

    <!-- Kalendar -->
    <article v-if="activeSub === 'kalendar'" class="rounded-lg border border-slate-200 bg-white shadow-sm">
      <div class="border-b border-slate-100 px-4 py-3">
        <h2 class="text-sm font-semibold text-slate-900">Penjadualan Lawatan (Mac 2024)</h2>
        <p class="mt-1 text-xs text-slate-500">Kekerapan: 3 atau 6 bulan. Notifikasi SMS/Email/WhatsApp dihantar sebelum lawatan.</p>
      </div>
      <div class="flex flex-wrap gap-2 p-4">
        <div
          v-for="day in daysInMonth"
          :key="day.date.toISOString()"
          class="min-w-[80px] rounded-lg border p-2"
          :class="day.lawatan.length ? 'border-amber-300 bg-amber-50' : 'border-slate-100'"
        >
          <div class="text-xs font-medium text-slate-500">{{ getDayName(day.date) }}</div>
          <div class="text-lg font-semibold text-slate-900">{{ day.date.getDate() }}</div>
          <div v-for="l in day.lawatan" :key="l.id" class="mt-1 text-xs text-amber-800">
            {{ getUsahawanName(l.usahawanId) }}
          </div>
        </div>
      </div>
    </article>

    <!-- Borang Lawatan -->
    <article v-if="activeSub === 'form'" class="rounded-lg border border-slate-200 bg-white shadow-sm">
      <div class="border-b border-slate-100 px-4 py-3">
        <h2 class="text-sm font-semibold text-slate-900">Borang Digital Lawatan</h2>
        <p class="mt-1 text-xs text-slate-500">GPS auto-capture, status operasi, pengambilan gambar, penemuan & tindakan.</p>
      </div>
      <div class="space-y-4 p-4">
        <div>
          <label class="block text-xs font-medium text-slate-600">Usahawan</label>
          <select class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option v-for="u in usahawanList" :key="u.id" :value="u.id">{{ u.nama }} – {{ u.perniagaan }}</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600">Tarikh & Masa</label>
          <input type="datetime-local" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600">Status Operasi Premis</label>
          <select class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="Aktif">Aktif</option>
            <option value="Tidak Aktif">Tidak Aktif</option>
            <option value="Tutup Sementara">Tutup Sementara</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600">Geo-location (Parlimen, DUN)</label>
          <div class="mt-1 flex items-center gap-2 rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-500">
            <MapPin class="h-4 w-4" />
            Auto-capture dari peranti mudah alih
          </div>
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600">Gambar Premis / Produk</label>
          <div class="mt-1 flex items-center justify-center rounded-lg border-2 border-dashed border-slate-300 py-8 text-sm text-slate-400">
            Ambil gambar / muat naik
          </div>
        </div>
        <div>
          <label class="block text-xs font-medium text-slate-600">Penemuan & Tindakan Susulan</label>
          <textarea
            class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
            rows="5"
            placeholder="Sesi bimbingan, penjadualan semula bayaran, lawatan susulan lebih awal, pindah ke kutipan/litigasi..."
          />
        </div>
        <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
          Simpan & Jana Laporan
        </button>
      </div>
    </article>

    <!-- Laporan Lawatan -->
    <article v-if="activeSub === 'laporan'" class="rounded-lg border border-slate-200 bg-white shadow-sm">
      <div class="border-b border-slate-100 px-4 py-3">
        <h2 class="text-sm font-semibold text-slate-900">Rekod Lawatan</h2>
        <p class="mt-1 text-xs text-slate-500">Laporan auto-generate dengan lampiran, Excel/PDF.</p>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-slate-100 text-left">
              <th class="px-4 py-2 text-xs font-semibold uppercase text-slate-500">{{ t("common.date") }}</th>
              <th class="px-4 py-2 text-xs font-semibold uppercase text-slate-500">Usahawan</th>
              <th class="px-4 py-2 text-xs font-semibold uppercase text-slate-500">Pegawai</th>
              <th class="px-4 py-2 text-xs font-semibold uppercase text-slate-500">Status Operasi</th>
              <th class="px-4 py-2 text-xs font-semibold uppercase text-slate-500">Skor</th>
              <th class="px-4 py-2 text-xs font-semibold uppercase text-slate-500">{{ t("common.actions") }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="l in lawatanList" :key="l.id" class="hover:bg-slate-50">
              <td class="px-4 py-2">{{ l.tarikh }}</td>
              <td class="px-4 py-2 font-medium">{{ getUsahawanName(l.usahawanId) }}</td>
              <td class="px-4 py-2">{{ l.pegawai }}</td>
              <td class="px-4 py-2">{{ l.statusOperasi }}</td>
              <td class="px-4 py-2">{{ l.skorSelepas }}</td>
              <td class="px-4 py-2 text-slate-600">{{ l.tindakanSusulan }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="border-t border-slate-100 px-4 py-3">
        <button class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
          Export Excel
        </button>
        <button class="ml-2 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
          Export PDF
        </button>
      </div>
    </article>
  </div>
</template>
