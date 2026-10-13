<script setup lang="ts">
import { computed } from "vue";
import { Calculator, Info } from "lucide-vue-next";

import PCard from "@/components/pemohon/ui/PCard.vue";
import PField from "@/components/pemohon/ui/PField.vue";
import { PRODUK_OPTIONS } from "@/data/portal-dummy";
import { KEEKERAPAN_BAYARAN_OPTIONS } from "@/config/sppt-options";
import { formatRinggit, pInputClass, pInputErrorClass } from "@/utils/pemohon-ui";
import { anggaranAnsuranBulanan, KADAR_ANGGARAN, type FieldErrors, type PermohonanForm } from "./permohonan-form";

/** `form` is the parent's reactive object — fields are edited in place (existing pattern). */
const props = defineProps<{ form: PermohonanForm; errors: FieldErrors }>();

const JUMLAH_PANTAS = [5000, 10000, 20000, 50000];
const TEMPOH_PANTAS = [12, 24, 36, 48, 60];

const bulan = computed(() => Number(props.form.tempohPembiayaan) || 0);
const ansuran = computed(() => anggaranAnsuranBulanan(Number(props.form.jumlahPermohonan) || 0, bulan.value));
const jumlahBayaran = computed(() => (ansuran.value ? ansuran.value * bulan.value : null));
const mingguan = computed(() => (ansuran.value ? (ansuran.value * 12) / 52 : null));
</script>

<template>
  <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_320px]">
    <PCard title="Pembiayaan yang dipohon" subtitle="Pilih produk dan jumlah yang sesuai dengan keperluan perniagaan anda.">
      <div class="space-y-5">
        <PField label="Kategori pembiayaan" for="pb-kategori" required :error="errors.kategoriPembiayaan">
          <select id="pb-kategori" v-model="form.kategoriPembiayaan" :class="[pInputClass, errors.kategoriPembiayaan && pInputErrorClass]">
            <option value="" disabled>Pilih kategori</option>
            <option v-for="p in PRODUK_OPTIONS" :key="p.value" :value="p.value">{{ p.label }}</option>
          </select>
        </PField>

        <PField label="Jumlah permohonan (RM)" for="pb-jumlah" required :error="errors.jumlahPermohonan">
          <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-sm font-semibold text-slate-400">RM</span>
            <input
              id="pb-jumlah"
              v-model.number="form.jumlahPermohonan"
              type="number"
              min="1000"
              step="500"
              inputmode="numeric"
              :class="[pInputClass, 'pl-11 text-base font-semibold', errors.jumlahPermohonan && pInputErrorClass]"
            />
          </div>
          <div class="flex flex-wrap gap-2 pt-1">
            <button
              v-for="j in JUMLAH_PANTAS"
              :key="j"
              type="button"
              class="rounded-full border px-3 py-1 text-xs font-semibold transition-colors focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-100"
              :class="form.jumlahPermohonan === j ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'"
              @click="form.jumlahPermohonan = j"
            >
              RM {{ j.toLocaleString("ms-MY") }}
            </button>
          </div>
        </PField>

        <PField label="Tujuan pembiayaan" for="pb-tujuan" hint="Cth: modal pusingan, membeli peralatan, membuka cawangan baharu.">
          <textarea id="pb-tujuan" v-model="form.tujuanPembiayaan" rows="3" :class="pInputClass" placeholder="Terangkan secara ringkas kegunaan pembiayaan" />
        </PField>

        <div class="grid gap-5 sm:grid-cols-2">
          <PField label="Tempoh pembiayaan (bulan)" for="pb-tempoh">
            <input id="pb-tempoh" v-model="form.tempohPembiayaan" type="number" min="1" max="120" inputmode="numeric" :class="pInputClass" placeholder="cth: 36" />
            <div class="flex flex-wrap gap-1.5 pt-1">
              <button
                v-for="t in TEMPOH_PANTAS"
                :key="t"
                type="button"
                class="rounded-full border px-2.5 py-0.5 text-xs font-medium transition-colors focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-100"
                :class="bulan === t ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'"
                @click="form.tempohPembiayaan = String(t)"
              >
                {{ t }} bln
              </button>
            </div>
          </PField>
          <PField label="Kekerapan bayaran" for="pb-kekerapan">
            <select id="pb-kekerapan" v-model="form.kekerapanBayaran" :class="pInputClass">
              <option v-for="opt in KEEKERAPAN_BAYARAN_OPTIONS" :key="opt" :value="opt">{{ opt }}</option>
            </select>
          </PField>
        </div>
      </div>
    </PCard>

    <aside class="space-y-4">
      <div class="overflow-hidden rounded-2xl bg-ink-900 p-5 text-white shadow-lg shadow-ink-900/20">
        <div class="flex items-center gap-2 text-ink-200">
          <Calculator class="h-4 w-4" />
          <p class="text-xs font-semibold uppercase tracking-wider">Anggaran ansuran</p>
        </div>
        <template v-if="ansuran">
          <p class="mt-3 text-3xl font-bold tracking-tight">~{{ formatRinggit(ansuran) }}</p>
          <p class="text-sm text-ink-200">sebulan selama {{ bulan }} bulan</p>
          <dl class="mt-5 space-y-2 border-t border-white/10 pt-4 text-sm">
            <div class="flex justify-between gap-3">
              <dt class="text-ink-300">Jumlah pembiayaan</dt>
              <dd class="font-medium tabular-nums">{{ formatRinggit(form.jumlahPermohonan) }}</dd>
            </div>
            <div class="flex justify-between gap-3">
              <dt class="text-ink-300">Anggaran jumlah bayaran</dt>
              <dd class="font-medium tabular-nums">{{ formatRinggit(jumlahBayaran) }}</dd>
            </div>
            <div v-if="form.kekerapanBayaran === 'Mingguan' && mingguan" class="flex justify-between gap-3">
              <dt class="text-ink-300">Anggaran mingguan</dt>
              <dd class="font-medium tabular-nums">~{{ formatRinggit(mingguan) }}</dd>
            </div>
          </dl>
        </template>
        <p v-else class="mt-3 text-sm text-ink-200">Masukkan jumlah dan tempoh pembiayaan untuk melihat anggaran ansuran bulanan.</p>
      </div>
      <p class="flex gap-2 px-1 text-xs leading-relaxed text-slate-500">
        <Info class="mt-0.5 h-3.5 w-3.5 shrink-0" />
        Anggaran sahaja berdasarkan kadar keuntungan rata {{ KADAR_ANGGARAN * 100 }}% setahun. Kadar sebenar ditentukan semasa kelulusan.
      </p>
    </aside>
  </div>
</template>
