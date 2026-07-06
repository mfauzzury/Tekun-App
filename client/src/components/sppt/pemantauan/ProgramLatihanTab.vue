<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, onMounted } from "vue";
import { Calendar, Users, Award } from "lucide-vue-next";
import type { ProgramLatihan, UsahawanItem, KehadiranLatihan } from "@/data/pemantauan-usahawan-dummy";
import { fetchSpptDataset } from "@/api/sppt";

const { t, tp } = useI18n();

const programLatihanList = ref<ProgramLatihan[]>([]);
const kehadiranLatihanList = ref<KehadiranLatihan[]>([]);
const usahawanList = ref<UsahawanItem[]>([]);

onMounted(async () => {
  const [programRes, kehadiranRes, usahawanRes] = await Promise.all([
    fetchSpptDataset("pemantauan", "program_latihan"),
    fetchSpptDataset("pemantauan", "kehadiran_latihan"),
    fetchSpptDataset("pemantauan", "usahawan_list"),
  ]);
  programLatihanList.value = programRes.data as ProgramLatihan[];
  kehadiranLatihanList.value = kehadiranRes.data as typeof kehadiranLatihanList.value;
  usahawanList.value = usahawanRes.data as UsahawanItem[];
});

const activeSub = ref<"takwim" | "kehadiran" | "laporan">("takwim");

function getUsahawanName(id: string) {
  return usahawanList.value.find((u) => u.id === id)?.nama ?? id;
}

function getKehadiranForProgram(programId: string) {
  return kehadiranLatihanList.value.filter((k) => k.programId === programId);
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex flex-wrap gap-2">
      <button
        v-for="t in [
          { id: 'takwim' as const, label: 'Takwim Program', icon: Calendar },
          { id: 'kehadiran' as const, label: 'Kehadiran & e-Cert', icon: Users },
          { id: 'laporan' as const, label: 'Laporan Impak', icon: Award },
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

    <!-- Takwim Program -->
    <article v-if="activeSub === 'takwim'" class="rounded-lg border border-slate-200 bg-white shadow-sm">
      <div class="border-b border-slate-100 px-4 py-3">
        <h2 class="text-sm font-semibold text-slate-900">Program Pembangunan Usahawan</h2>
        <p class="mt-1 text-xs text-slate-500">Jemputan digital (SMS, email, WhatsApp), RSVP, tema & peserta sasaran.</p>
      </div>
      <div class="divide-y divide-slate-100">
        <div
          v-for="p in programLatihanList"
          :key="p.id"
          class="flex flex-wrap items-center justify-between gap-4 px-4 py-4"
        >
          <div>
            <h3 class="font-medium text-slate-900">{{ p.tajuk }}</h3>
            <p class="mt-1 text-xs text-slate-500">Tema: {{ p.tema }} · {{ p.platform }}</p>
            <p class="mt-1 text-xs text-slate-600">{{ p.tarikh }} · {{ p.masa }} · {{ p.lokasi }}</p>
            <p class="mt-1 text-xs text-slate-500">Penyampai: {{ p.penyampai }} · Sasaran: {{ p.pesertaSasaran }}</p>
          </div>
          <div class="flex items-center gap-2">
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
              {{ p.pesertaHadir }}/{{ p.pesertaJemput }} hadir
            </span>
            <button class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50">
              Jemput
            </button>
          </div>
        </div>
      </div>
    </article>

    <!-- Kehadiran & e-Cert -->
    <article v-if="activeSub === 'kehadiran'" class="rounded-lg border border-slate-200 bg-white shadow-sm">
      <div class="border-b border-slate-100 px-4 py-3">
        <h2 class="text-sm font-semibold text-slate-900">Rekod Kehadiran & Sijil Digital</h2>
        <p class="mt-1 text-xs text-slate-500">Manual / imbasan QR, e-Cert disimpan di dashboard usahawan & admin.</p>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-slate-100 text-left">
              <th class="px-4 py-2 text-xs font-semibold uppercase text-slate-500">Usahawan</th>
              <th class="px-4 py-2 text-xs font-semibold uppercase text-slate-500">Program</th>
              <th class="px-4 py-2 text-xs font-semibold uppercase text-slate-500">{{ t("common.status") }}</th>
              <th class="px-4 py-2 text-xs font-semibold uppercase text-slate-500">e-Cert</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="k in kehadiranLatihanList" :key="k.id" class="hover:bg-slate-50">
              <td class="px-4 py-2 font-medium">{{ getUsahawanName(k.usahawanId) }}</td>
              <td class="px-4 py-2">{{ programLatihanList.find((x) => x.id === k.programId)?.tajuk ?? k.programId }}</td>
              <td class="px-4 py-2">
                <span
                  class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                  :class="
                    k.status === 'Hadir'
                      ? 'bg-emerald-100 text-emerald-700'
                      : k.status === 'RSVP'
                        ? 'bg-blue-100 text-blue-700'
                        : 'bg-slate-100 text-slate-600'
                  "
                >
                  {{ k.status }}
                </span>
              </td>
              <td class="px-4 py-2">
                <a v-if="k.sijilUrl" href="#" class="text-blue-600 hover:underline">Muat turun</a>
                <span v-else class="text-slate-400">–</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </article>

    <!-- Laporan Impak -->
    <article v-if="activeSub === 'laporan'" class="rounded-lg border border-slate-200 bg-white shadow-sm">
      <div class="border-b border-slate-100 px-4 py-3">
        <h2 class="text-sm font-semibold text-slate-900">Laporan Impak Program</h2>
        <p class="mt-1 text-xs text-slate-500">Analisis penyertaan mengikut negeri, produk, impak prestasi bayaran.</p>
      </div>
      <div class="grid gap-4 p-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-slate-200 p-4">
          <p class="text-xs font-medium text-slate-500">Jumlah Program Bulan Ini</p>
          <p class="mt-1 text-2xl font-semibold text-slate-900">3</p>
        </div>
        <div class="rounded-lg border border-slate-200 p-4">
          <p class="text-xs font-medium text-slate-500">Jumlah Penyertaan</p>
          <p class="mt-1 text-2xl font-semibold text-slate-900">65</p>
        </div>
        <div class="rounded-lg border border-slate-200 p-4">
          <p class="text-xs font-medium text-slate-500">Kadar Kehadiran</p>
          <p class="mt-1 text-2xl font-semibold text-slate-900">78%</p>
        </div>
        <div class="rounded-lg border border-slate-200 p-4">
          <p class="text-xs font-medium text-slate-500">Peningkatan Jualan (Sesi TikTok)</p>
          <p class="mt-1 text-2xl font-semibold text-emerald-600">+15%</p>
        </div>
      </div>
      <div class="border-t border-slate-100 px-4 py-3">
        <button class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
          Jana Laporan Penuh
        </button>
      </div>
    </article>
  </div>
</template>
