<script setup lang="ts">
import { computed } from "vue";
import { Heart } from "lucide-vue-next";

import SpptDateInput from "@/components/sppt/SpptDateInput.vue";
import PCard from "@/components/pemohon/ui/PCard.vue";
import PField from "@/components/pemohon/ui/PField.vue";
import {
  AGAMA_OPTIONS,
  BANGSA_OPTIONS,
  JANTINA_OPTIONS,
  NEGERI_OPTIONS,
  STATUS_KEDIAMAN_OPTIONS,
  TARAF_PENDIDIKAN_OPTIONS,
  TARAF_PERKAHWINAN_OPTIONS,
} from "@/config/sppt-options";
import { ageFromIsoDate, birthPartsFromIso, isoFromBirthParts } from "@/utils/spptDate";
import { pInputClass, pInputErrorClass } from "@/utils/pemohon-ui";
import { applyIcBirthDate, type FieldErrors, type PermohonanForm } from "./permohonan-form";

const props = defineProps<{ form: PermohonanForm; errors: FieldErrors }>();
const adaPasangan = defineModel<boolean>("adaPasangan", { required: true });

const ro = `${pInputClass} bg-slate-50 text-slate-500`;
const checkbox = "h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-200";

function onTarafPerkahwinanChange() {
  const taraf = props.form.tarafPerkahwinan.toLowerCase();
  adaPasangan.value = ["berkahwin", "duda", "janda"].some((s) => taraf.includes(s));
}

const tarikhLahir = computed({
  get: () => isoFromBirthParts(props.form.tarikhLahirHari, props.form.tarikhLahirBulan, props.form.tarikhLahirTahun),
  set: (iso: string) => {
    const f = props.form;
    const parts = birthPartsFromIso(iso);
    if (!parts) {
      f.tarikhLahirHari = "";
      f.tarikhLahirBulan = "";
      f.tarikhLahirTahun = "";
      f.umur = "";
      return;
    }
    f.tarikhLahirHari = parts.day;
    f.tarikhLahirBulan = parts.month;
    f.tarikhLahirTahun = parts.year;
    const age = ageFromIsoDate(iso);
    if (age !== null) f.umur = String(age);
  },
});
</script>

<template>
  <div class="space-y-5">
    <PCard title="Maklumat pemohon" subtitle="Pastikan maklumat sama seperti dalam MyKad anda.">
      <div class="grid gap-4 sm:grid-cols-2">
        <PField class="sm:col-span-2" label="Nama penuh" for="d-nama" required :error="errors.nama">
          <input id="d-nama" v-model="form.nama" type="text" autocomplete="name" :class="[pInputClass, errors.nama && pInputErrorClass]" />
        </PField>
        <PField label="No. Kad Pengenalan (baru)" for="d-ic" required :error="errors.noIcBaru" hint="Tarikh lahir & umur diisi secara automatik.">
          <input
            id="d-ic"
            v-model="form.noIcBaru"
            type="text"
            inputmode="numeric"
            placeholder="YYMMDD-NN-GGGG"
            :class="[pInputClass, 'font-mono tracking-wide', errors.noIcBaru && pInputErrorClass]"
            @input="applyIcBirthDate(form)"
          />
        </PField>
        <PField label="No. Kad Pengenalan (lama)" for="d-iclama">
          <input id="d-iclama" v-model="form.noIcLama" type="text" :class="pInputClass" />
        </PField>
        <PField label="Tarikh lahir" for="d-lahir">
          <SpptDateInput id="d-lahir" v-model="tarikhLahir" :input-class="pInputClass" />
        </PField>
        <PField label="Umur" for="d-umur">
          <input id="d-umur" :value="form.umur ? `${form.umur} tahun` : ''" type="text" readonly :class="ro" />
        </PField>
        <PField label="Jantina" for="d-jantina" required>
          <select id="d-jantina" v-model="form.jantina" :class="pInputClass">
            <option value="" disabled>Pilih</option>
            <option v-for="opt in JANTINA_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
        </PField>
        <PField label="Agama" for="d-agama" required>
          <select id="d-agama" v-model="form.agama" :class="pInputClass">
            <option value="" disabled>Pilih</option>
            <option v-for="opt in AGAMA_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
        </PField>
        <PField label="Bangsa" for="d-bangsa" required>
          <select id="d-bangsa" v-model="form.bangsa" :class="pInputClass">
            <option value="" disabled>Pilih</option>
            <option v-for="opt in BANGSA_OPTIONS" :key="opt" :value="opt">{{ opt }}</option>
          </select>
        </PField>
        <PField v-if="form.bangsa === 'Lain-lain'" label="Nyatakan bangsa" for="d-kaum" required>
          <input id="d-kaum" v-model="form.kaum" type="text" :class="pInputClass" />
        </PField>
        <PField label="Taraf perkahwinan" for="d-kahwin" required>
          <select id="d-kahwin" v-model="form.tarafPerkahwinan" :class="pInputClass" @change="onTarafPerkahwinanChange">
            <option value="" disabled>Pilih</option>
            <option v-for="opt in TARAF_PERKAHWINAN_OPTIONS" :key="opt" :value="opt">{{ opt }}</option>
          </select>
        </PField>
        <PField label="Bilangan tanggungan" for="d-tanggungan">
          <input id="d-tanggungan" v-model="form.bilanganTanggungan" type="number" min="0" inputmode="numeric" :class="pInputClass" />
        </PField>
        <PField class="sm:col-span-2" label="Taraf pendidikan" for="d-pendidikan" required>
          <select id="d-pendidikan" v-model="form.tarafPendidikan" :class="pInputClass">
            <option value="" disabled>Pilih</option>
            <option v-for="opt in TARAF_PENDIDIKAN_OPTIONS" :key="opt" :value="opt">{{ opt }}</option>
          </select>
        </PField>
      </div>
      <fieldset class="mt-5 grid gap-2 sm:grid-cols-3">
        <legend class="sr-only">Status tambahan</legend>
        <label class="flex items-center gap-2.5 rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-ink-800 hover:bg-slate-50">
          <input v-model="form.oku" type="checkbox" :class="checkbox" /> Orang Kurang Upaya (OKU)
        </label>
        <label class="flex items-center gap-2.5 rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-ink-800 hover:bg-slate-50">
          <input v-model="form.diberhentikanPandemik" type="checkbox" :class="checkbox" /> Diberhentikan kerja akibat pandemik
        </label>
        <label class="flex items-center gap-2.5 rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-ink-800 hover:bg-slate-50">
          <input v-model="form.asnafBerdaftar" type="checkbox" :class="checkbox" /> Asnaf berdaftar
        </label>
      </fieldset>
    </PCard>

    <PCard title="Alamat & hubungan">
      <div class="grid gap-4 sm:grid-cols-2">
        <PField class="sm:col-span-2" label="Alamat kediaman" for="d-alamat" required>
          <textarea id="d-alamat" v-model="form.alamat" rows="2" autocomplete="street-address" :class="pInputClass" />
        </PField>
        <PField label="Poskod" for="d-poskod" required>
          <input id="d-poskod" v-model="form.poskod" type="text" inputmode="numeric" maxlength="5" autocomplete="postal-code" :class="pInputClass" />
        </PField>
        <PField label="Negeri" for="d-negeri" required>
          <select id="d-negeri" v-model="form.negeri" :class="pInputClass">
            <option value="" disabled>Pilih</option>
            <option v-for="opt in NEGERI_OPTIONS" :key="opt" :value="opt">{{ opt }}</option>
          </select>
        </PField>
        <PField label="No. telefon bimbit" for="d-telefon" required :error="errors.noTelefonBimbit">
          <input id="d-telefon" v-model="form.noTelefonBimbit" type="tel" autocomplete="tel" :class="[pInputClass, errors.noTelefonBimbit && pInputErrorClass]" />
        </PField>
        <PField label="No. telefon rumah" for="d-telrumah">
          <input id="d-telrumah" v-model="form.noTelefonRumah" type="tel" :class="pInputClass" />
        </PField>
        <PField class="sm:col-span-2" label="Emel" for="d-emel" required>
          <input id="d-emel" v-model="form.email" type="email" autocomplete="email" :class="pInputClass" />
        </PField>
        <PField label="Facebook" for="d-fb">
          <input id="d-fb" v-model="form.facebook" type="text" :class="pInputClass" placeholder="Nama halaman / profil" />
        </PField>
        <PField label="Instagram" for="d-ig">
          <input id="d-ig" v-model="form.instagram" type="text" :class="pInputClass" placeholder="@akaun" />
        </PField>
        <PField class="sm:col-span-2" label="Status kediaman" for="d-kediaman">
          <select id="d-kediaman" v-model="form.statusKediaman" :class="pInputClass">
            <option value="">Pilih</option>
            <option v-for="opt in STATUS_KEDIAMAN_OPTIONS" :key="opt" :value="opt">{{ opt }}</option>
          </select>
        </PField>
      </div>
    </PCard>

    <PCard>
      <template #header>
        <label class="flex w-full cursor-pointer items-start gap-3">
          <input v-model="adaPasangan" type="checkbox" :class="[checkbox, 'mt-0.5']" />
          <span>
            <span class="flex items-center gap-1.5 text-[15px] font-semibold text-ink-900"><Heart class="h-4 w-4 text-slate-400" /> Maklumat pasangan</span>
            <span class="mt-0.5 block text-sm text-slate-500">Saya mempunyai pasangan dan ingin mengisi maklumat pasangan.</span>
          </span>
        </label>
      </template>
      <div v-if="adaPasangan" class="grid gap-4 sm:grid-cols-2">
        <PField class="sm:col-span-2" label="Nama pasangan" for="p-nama" required>
          <input id="p-nama" v-model="form.namaPasangan" type="text" :class="pInputClass" />
        </PField>
        <PField label="No. Kad Pengenalan pasangan" for="p-ic" required>
          <input id="p-ic" v-model="form.noIcPasangan" type="text" inputmode="numeric" :class="[pInputClass, 'font-mono']" />
        </PField>
        <PField label="No. pasport pasangan" for="p-pasport">
          <input id="p-pasport" v-model="form.noPassportPasangan" type="text" :class="pInputClass" />
        </PField>
        <PField label="Pekerjaan pasangan" for="p-kerja">
          <input id="p-kerja" v-model="form.pekerjaanPasangan" type="text" :class="pInputClass" />
        </PField>
        <PField label="Pendapatan pasangan (RM)" for="p-pendapatan">
          <input id="p-pendapatan" v-model="form.pendapatanPasangan" type="number" min="0" inputmode="decimal" :class="pInputClass" />
        </PField>
        <PField class="sm:col-span-2" label="Alamat majikan pasangan" for="p-alamat">
          <textarea id="p-alamat" v-model="form.alamatMajikanPasangan" rows="2" :class="pInputClass" />
        </PField>
        <PField label="Poskod majikan pasangan" for="p-poskod">
          <input id="p-poskod" v-model="form.poskodMajikanPasangan" type="text" inputmode="numeric" :class="pInputClass" />
        </PField>
        <PField label="No. telefon majikan pasangan" for="p-telmajikan">
          <input id="p-telmajikan" v-model="form.noTelefonMajikanPasangan" type="tel" :class="pInputClass" />
        </PField>
        <PField label="No. telefon bimbit pasangan" for="p-telbimbit">
          <input id="p-telbimbit" v-model="form.noTelefonBimbitPasangan" type="tel" :class="pInputClass" />
        </PField>
      </div>
      <p v-else class="text-sm text-slate-400">Tandakan kotak di atas jika ingin menambah maklumat pasangan.</p>
    </PCard>
  </div>
</template>
