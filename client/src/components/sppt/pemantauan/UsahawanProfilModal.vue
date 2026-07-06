<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, onMounted } from "vue";
import { FileText, MapPin, Award, BookOpen } from "lucide-vue-next";
import type { UsahawanItem, DokumenSokongan } from "@/data/pemantauan-usahawan-dummy";
import {
  STATUS_LABELS,
  SKOR_LABELS,
  KATEGORI_COLORS,
} from "@/data/pemantauan-usahawan-dummy";
import { fetchSpptDataset } from "@/api/sppt";

defineProps<{
  usahawan: UsahawanItem | null;
  show: boolean;
}>();

const { t, tp } = useI18n();

const dokumenByUsahawan = ref<Record<string, DokumenSokongan[]>>({});

onMounted(async () => {
  const res = await fetchSpptDataset("pemantauan", "dokumen_by_usahawan");
  dokumenByUsahawan.value = res.data as Record<string, DokumenSokongan[]>;
});

const emit = defineEmits<{ close: [] }>();

function fmtRm(n: number) {
  return `RM ${n.toLocaleString("en-MY")}`;
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
      @click.self="emit('close')"
    >
      <article
        v-if="usahawan"
        class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-xl"
      >
        <div class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-100 bg-white px-4 py-3">
          <h2 class="text-lg font-semibold text-slate-900">Profil Usahawan – {{ usahawan.nama }}</h2>
          <button
            type="button"
            class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700"
            @click="emit('close')"
          >
            ✕
          </button>
        </div>

        <div class="space-y-6 p-4">
          <!-- Maklumat Asas -->
          <section>
            <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-700">
              <FileText class="h-4 w-4" />
              Maklumat Asas
            </h3>
            <div class="grid gap-2 text-sm sm:grid-cols-2">
              <div><span class="text-slate-500">No. Usahawan:</span> {{ usahawan.id }}</div>
              <div><span class="text-slate-500">IC:</span> {{ usahawan.ic }}</div>
              <div><span class="text-slate-500">Perniagaan:</span> {{ usahawan.perniagaan }}</div>
              <div><span class="text-slate-500">Negeri:</span> {{ usahawan.negeri }}</div>
              <div><span class="text-slate-500">Cawangan:</span> {{ usahawan.cawangan }}</div>
              <div><span class="text-slate-500">Tarikh Daftar:</span> {{ usahawan.tarikhDaftar }}</div>
            </div>
          </section>

          <!-- PMKS & Status -->
          <section>
            <h3 class="mb-3 text-sm font-semibold text-slate-700">PMKS & Status Perniagaan</h3>
            <div class="flex flex-wrap gap-2">
              <span class="rounded-full bg-slate-100 px-4 py-1 text-sm font-medium text-slate-700">
                PMKS: {{ usahawan.pmks }}
              </span>
              <span class="rounded-full bg-slate-100 px-4 py-1 text-sm font-medium text-slate-700">
                {{ STATUS_LABELS[usahawan.statusPerniagaan] }}
              </span>
              <span
                class="rounded-full px-4 py-1 text-sm font-medium"
                :class="KATEGORI_COLORS[usahawan.kategoriRisiko]"
              >
                {{ usahawan.kategoriRisiko }} (Skor {{ usahawan.skorPrestasi }})
              </span>
              <span class="rounded-full bg-amber-100 px-4 py-1 text-sm font-medium text-amber-800" v-if="usahawan.risikoTambahan">
                {{ usahawan.risikoTambahan }}
              </span>
            </div>
            <p v-if="usahawan.faktorLuaran" class="mt-2 text-xs text-slate-600">
              Faktor luaran: {{ usahawan.faktorLuaran }}
            </p>
          </section>

          <!-- Prestasi Kewangan -->
          <section>
            <h3 class="mb-3 text-sm font-semibold text-slate-700">Prestasi Kewangan</h3>
            <div class="grid gap-2 text-sm sm:grid-cols-2">
              <div><span class="text-slate-500">Jualan Bulanan:</span> {{ fmtRm(usahawan.jualanBulanan) }}</div>
              <div><span class="text-slate-500">Jualan Tahunan:</span> {{ fmtRm(usahawan.jualanTahunan) }}</div>
              <div><span class="text-slate-500">Keuntungan Kasar:</span> {{ fmtRm(usahawan.keuntunganKasar) }}</div>
              <div><span class="text-slate-500">Keuntungan Bersih:</span> {{ fmtRm(usahawan.keuntunganBersih) }}</div>
              <div><span class="text-slate-500">Bil. Pelanggan:</span> {{ usahawan.bilPelanggan }}</div>
              <div><span class="text-slate-500">Bil. Pekerja:</span> {{ usahawan.bilPekerja }}</div>
              <div><span class="text-slate-500">Bil. Cawangan:</span> {{ usahawan.bilCawangan }}</div>
              <div><span class="text-slate-500">Keluasan Kedai:</span> {{ usahawan.keluasanKedai }} sq ft</div>
            </div>
          </section>

          <!-- Early Warning -->
          <section v-if="usahawan.earlyWarning" class="rounded-lg border border-amber-200 bg-amber-50 p-4">
            <h3 class="mb-2 text-sm font-semibold text-amber-800">⚠️ Early Warning</h3>
            <p class="text-sm text-amber-900">{{ usahawan.earlyWarningMsg }}</p>
          </section>

          <!-- Lokasi -->
          <section v-if="usahawan.parlimen || usahawan.dun">
            <h3 class="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-700">
              <MapPin class="h-4 w-4" />
              Lokasi
            </h3>
            <p class="text-sm text-slate-600">{{ usahawan.parlimen }} · {{ usahawan.dun }}</p>
          </section>

          <!-- Pensijilan & Kursus -->
          <section>
            <h3 class="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-700">
              <Award class="h-4 w-4" />
              Pensijilan
            </h3>
            <div class="flex flex-wrap gap-2">
              <span
                v-for="p in usahawan.pensijilan"
                :key="p"
                class="rounded-full bg-emerald-100 px-3 py-0.5 text-xs font-medium text-emerald-700"
              >
                {{ p }}
              </span>
              <span v-if="usahawan.pensijilan.length === 0" class="text-sm text-slate-400">Tiada</span>
            </div>
          </section>

          <section>
            <h3 class="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-700">
              <BookOpen class="h-4 w-4" />
              Kursus Diikuti
            </h3>
            <ul class="space-y-1 text-sm text-slate-600">
              <li v-for="(k, i) in usahawan.kursusDiikuti" :key="i">
                {{ k.nama }} – {{ k.penganjur }} ({{ k.tarikh }})
              </li>
              <li v-if="usahawan.kursusDiikuti.length === 0" class="text-slate-400">Tiada</li>
            </ul>
          </section>

          <!-- Dokumen Sokongan -->
          <section>
            <h3 class="mb-2 text-sm font-semibold text-slate-700">Dokumen Sokongan</h3>
            <ul class="space-y-1 text-sm">
              <li
                v-for="d in (dokumenByUsahawan[usahawan.id] ?? [])"
                :key="d.id"
                class="flex items-center justify-between rounded border border-slate-100 px-3 py-2"
              >
                <span>{{ d.jenis }} – {{ d.nama }}</span>
                <span class="text-slate-400">{{ d.tarikh }}</span>
              </li>
              <li v-if="!(dokumenByUsahawan[usahawan.id] ?? []).length" class="text-slate-400">Tiada dokumen</li>
            </ul>
          </section>
        </div>
      </article>
    </div>
  </Teleport>
</template>
