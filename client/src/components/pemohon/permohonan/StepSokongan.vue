<script setup lang="ts">
import { computed } from "vue";
import { HeartHandshake, ScrollText, ShieldCheck, Users } from "lucide-vue-next";

import SpptDateInput from "@/components/sppt/SpptDateInput.vue";
import PBadge from "@/components/pemohon/ui/PBadge.vue";
import PCard from "@/components/pemohon/ui/PCard.vue";
import PField from "@/components/pemohon/ui/PField.vue";
import { PERKESO_PAKEJ, TAKAFUL_KEMALANGAN_PAKEJ } from "@/config/sppt-options";
import { pInputClass } from "@/utils/pemohon-ui";
import type { PermohonanForm } from "./permohonan-form";

const props = defineProps<{ form: PermohonanForm }>();

const checkbox = "mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-200";
const optRow = "flex cursor-pointer items-start gap-2.5 rounded-xl border border-slate-200 px-3.5 py-3 text-sm text-ink-800 hover:bg-slate-50";

const sokonganRequired = computed(() => props.form.kategoriPembiayaan === "teman_tekun");
const takafulKemalanganPakejSuggestion = computed(() => {
  const jumlah = props.form.jumlahPermohonan;
  return TAKAFUL_KEMALANGAN_PAKEJ.find((p) => jumlah >= p.min && jumlah <= p.max)?.value ?? "";
});
const PERUJUK = ([1, 2] as const).map((n) => ({
  n,
  nama: `perujuk${n}Nama` as const,
  noKp: `perujuk${n}NoKp` as const,
  hubungan: `perujuk${n}Hubungan` as const,
  noTel: `perujuk${n}NoTel` as const,
  alamat: `perujuk${n}Alamat` as const,
}));
</script>

<template>
  <div class="space-y-5">
    <PCard>
      <template #header>
        <div class="flex min-w-0 items-start gap-3">
          <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600"><HeartHandshake class="h-5 w-5" /></span>
          <div>
            <h2 class="text-[15px] font-semibold text-ink-900">Sokongan kumpulan</h2>
            <p class="mt-0.5 text-sm text-slate-500">
              {{ sokonganRequired ? "Diperlukan untuk produk Teman TEKUN." : "Hanya diperlukan untuk produk Teman TEKUN — boleh dilangkau." }}
            </p>
          </div>
        </div>
      </template>
      <template #actions><PBadge :tone="sokonganRequired ? 'brand' : 'neutral'">{{ sokonganRequired ? "Wajib" : "Pilihan" }}</PBadge></template>
      <div class="grid gap-4 sm:grid-cols-2">
        <PField label="Nama penyokong" for="s-nama" :required="sokonganRequired">
          <input id="s-nama" v-model="form.sokonganNama" type="text" :class="pInputClass" />
        </PField>
        <PField label="No. KP penyokong" for="s-kp" :required="sokonganRequired">
          <input id="s-kp" v-model="form.sokonganNoKp" type="text" inputmode="numeric" :class="pInputClass" />
        </PField>
        <PField label="Tempoh perkenalan" for="s-kenal">
          <input id="s-kenal" v-model="form.sokonganTempohPerkenalan" type="text" :class="pInputClass" placeholder="cth: 3 tahun" />
        </PField>
        <PField label="Tarikh perbincangan" for="s-tarikh">
          <SpptDateInput id="s-tarikh" v-model="form.sokonganTarikhPerbincangan" :input-class="pInputClass" />
        </PField>
        <PField class="sm:col-span-2" label="Jumlah pembiayaan disokong (RM)" for="s-jumlah" :required="sokonganRequired">
          <input id="s-jumlah" v-model="form.sokonganJumlah" type="number" min="0" :class="pInputClass" />
        </PField>
      </div>

      <h3 class="mb-3 mt-6 text-sm font-semibold text-ink-900">Perakuan</h3>
      <div class="grid gap-4 sm:grid-cols-2">
        <PField label="Nama" for="pk-nama"><input id="pk-nama" v-model="form.perakuanNama" type="text" :class="pInputClass" /></PField>
        <PField label="Jawatan" for="pk-jawatan"><input id="pk-jawatan" v-model="form.perakuanJawatan" type="text" :class="pInputClass" /></PField>
        <PField class="sm:col-span-2" label="Alamat" for="pk-alamat"><textarea id="pk-alamat" v-model="form.perakuanAlamat" rows="2" :class="pInputClass" /></PField>
        <PField label="No. telefon" for="pk-tel"><input id="pk-tel" v-model="form.perakuanNoTel" type="tel" :class="pInputClass" /></PField>
      </div>
    </PCard>

    <PCard>
      <template #header>
        <div class="flex min-w-0 items-start gap-3">
          <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600"><Users class="h-5 w-5" /></span>
          <div>
            <h2 class="text-[15px] font-semibold text-ink-900">Perujuk</h2>
            <p class="mt-0.5 text-sm text-slate-500">Dua orang yang mengenali anda (bukan ahli keluarga serumah).</p>
          </div>
        </div>
      </template>
      <div class="grid gap-5 lg:grid-cols-2">
        <fieldset v-for="{ n, nama, noKp, hubungan, noTel, alamat } in PERUJUK" :key="n" class="space-y-3 rounded-xl border border-slate-200 p-4">
          <legend class="px-1 text-sm font-semibold text-ink-800">Perujuk {{ n }}</legend>
          <div class="grid gap-3 sm:grid-cols-2">
            <PField label="Nama" :for="`r${n}-nama`"><input :id="`r${n}-nama`" v-model="form[nama]" type="text" :class="pInputClass" /></PField>
            <PField label="No. KP" :for="`r${n}-kp`"><input :id="`r${n}-kp`" v-model="form[noKp]" type="text" :class="pInputClass" /></PField>
            <PField label="Hubungan" :for="`r${n}-hub`"><input :id="`r${n}-hub`" v-model="form[hubungan]" type="text" :class="pInputClass" /></PField>
            <PField label="No. telefon" :for="`r${n}-tel`"><input :id="`r${n}-tel`" v-model="form[noTel]" type="tel" :class="pInputClass" /></PField>
            <PField class="sm:col-span-2" label="Alamat" :for="`r${n}-alamat`">
              <textarea :id="`r${n}-alamat`" v-model="form[alamat]" rows="2" :class="pInputClass" />
            </PField>
          </div>
        </fieldset>
      </div>
    </PCard>

    <PCard>
      <template #header>
        <div class="flex min-w-0 items-start gap-3">
          <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"><ShieldCheck class="h-5 w-5" /></span>
          <div>
            <h2 class="text-[15px] font-semibold text-ink-900">Perlindungan</h2>
            <p class="mt-0.5 text-sm text-slate-500">Lindungi diri dan perniagaan anda.</p>
          </div>
        </div>
      </template>
      <div class="space-y-3">
        <label class="flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50/70 px-3.5 py-3 text-sm text-emerald-900">
          <input v-model="form.takafulPembiayaan" type="checkbox" disabled :class="checkbox" />
          <span><span class="font-semibold">Takaful Pembiayaan Berkelompok</span> — wajib bagi semua permohonan.</span>
        </label>

        <label :class="optRow"><input v-model="form.takafulKemalangan" type="checkbox" :class="checkbox" /> Sertai Takaful Kemalangan Diri</label>
        <PField v-if="form.takafulKemalangan" class="pl-1" label="Pakej Takaful Kemalangan" for="t-pakej">
          <select id="t-pakej" v-model="form.takafulKemalanganPakej" :class="pInputClass">
            <option value="">Pilih ({{ TAKAFUL_KEMALANGAN_PAKEJ.find((p) => p.value === takafulKemalanganPakejSuggestion)?.label ?? "cadangan berdasarkan jumlah" }})</option>
            <option v-for="opt in TAKAFUL_KEMALANGAN_PAKEJ" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
        </PField>

        <label :class="optRow"><input v-model="form.perkeso" type="checkbox" :class="checkbox" /> Sertai PERKESO Pekerjaan Sendiri</label>
        <PField v-if="form.perkeso" class="pl-1" label="Pakej PERKESO" for="t-perkeso">
          <select id="t-perkeso" v-model="form.perkesoPakej" :class="pInputClass">
            <option value="">Pilih</option>
            <option v-for="opt in PERKESO_PAKEJ" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
        </PField>
      </div>
    </PCard>

    <PCard>
      <template #header>
        <div class="flex min-w-0 items-start gap-3">
          <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600"><ScrollText class="h-5 w-5" /></span>
          <div>
            <h2 class="text-[15px] font-semibold text-ink-900">Wasiat</h2>
            <p class="mt-0.5 text-sm text-slate-500">Pilihan — daftar wasiat bersama permohonan ini.</p>
          </div>
        </div>
      </template>
      <div class="space-y-3">
        <label :class="optRow"><input v-model="form.wasiat" type="checkbox" :class="checkbox" /> Saya ingin mendaftar Wasiat bersama permohonan ini</label>
        <PField v-if="form.wasiat" label="Jumlah wasiat (RM)" for="w-jumlah">
          <input id="w-jumlah" v-model="form.wasiatJumlah" type="number" min="0" :class="pInputClass" />
        </PField>
      </div>
    </PCard>
  </div>
</template>
