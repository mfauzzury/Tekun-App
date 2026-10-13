<script setup lang="ts">
import SpptDateInput from "@/components/sppt/SpptDateInput.vue";
import SpptTimeInput from "@/components/sppt/SpptTimeInput.vue";
import PCard from "@/components/pemohon/ui/PCard.vue";
import PField from "@/components/pemohon/ui/PField.vue";
import {
  AGENSI_KURSUS_OPTIONS,
  INSTITUSI_PEMBIAYAAN_OPTIONS,
  PEMILIKAN_PERNIAGAAN_OPTIONS,
  SEKTOR_PEKERJAAN_OPTIONS,
  STATUS_JAWATAN_OPTIONS,
  STATUS_PEKERJAAN_OPTIONS,
  STATUS_PREMIS_OPTIONS,
} from "@/config/sppt-options";
import { pInputClass } from "@/utils/pemohon-ui";
import DataAlternatifCard from "./DataAlternatifCard.vue";
import type { DataAltForm, PermohonanForm } from "./permohonan-form";

defineProps<{ form: PermohonanForm; dataAlt: DataAltForm; ensureRecord: () => Promise<{ id: number; token: string } | null> }>();

const checkbox = "mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-200";
const toggleRow = "flex cursor-pointer items-start gap-2.5 rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-ink-800 hover:bg-slate-50";
</script>

<template>
  <div class="space-y-5">
    <PCard title="Perniagaan" subtitle="Maklumat perniagaan yang akan dibiayai.">
      <div class="grid gap-4 sm:grid-cols-2">
        <PField class="sm:col-span-2" label="Nama perniagaan" for="b-nama" required>
          <input id="b-nama" v-model="form.namaPerniagaan" type="text" :class="pInputClass" />
        </PField>
        <PField label="No. pendaftaran SSM" for="b-ssm">
          <input id="b-ssm" v-model="form.noSsm" type="text" :class="pInputClass" />
        </PField>
        <PField label="Tempoh berniaga (tahun)" for="b-tempoh">
          <input id="b-tempoh" v-model="form.tempohBerniaga" type="text" inputmode="decimal" :class="pInputClass" />
        </PField>
        <PField label="Anggaran pendapatan bulanan (RM)" for="b-pendapatan">
          <input id="b-pendapatan" v-model="form.anggaranPendapatan" type="number" min="0" inputmode="decimal" :class="pInputClass" />
        </PField>
        <PField label="Nilai aset perniagaan (RM)" for="b-aset">
          <input id="b-aset" v-model="form.nilaiAset" type="number" min="0" inputmode="decimal" :class="pInputClass" />
        </PField>
        <PField class="sm:col-span-2" label="Alamat premis perniagaan" for="b-alamat">
          <textarea id="b-alamat" v-model="form.alamatPremis" rows="2" :class="pInputClass" />
        </PField>
        <PField label="Poskod premis" for="b-poskod">
          <input id="b-poskod" v-model="form.poskodPremis" type="text" inputmode="numeric" maxlength="5" :class="pInputClass" />
        </PField>
        <PField label="Status premis" for="b-premis">
          <select id="b-premis" v-model="form.statusPremis" :class="pInputClass">
            <option v-for="opt in STATUS_PREMIS_OPTIONS" :key="opt" :value="opt">{{ opt }}</option>
          </select>
        </PField>
        <PField label="No. telefon premis" for="b-tel">
          <input id="b-tel" v-model="form.noTelPremis" type="tel" :class="pInputClass" />
        </PField>
        <PField label="No. telefon bimbit premis" for="b-telbimbit">
          <input id="b-telbimbit" v-model="form.noTelBimbitPremis" type="tel" :class="pInputClass" />
        </PField>
        <PField label="Pemilikan perniagaan" for="b-milik">
          <select id="b-milik" v-model="form.pemilikanPerniagaan" :class="pInputClass">
            <option v-for="opt in PEMILIKAN_PERNIAGAAN_OPTIONS" :key="opt" :value="opt">{{ opt }}</option>
          </select>
        </PField>
        <PField v-if="form.pemilikanPerniagaan === 'Sendirian Berhad'" label="Modal berbayar (RM)" for="b-modal">
          <input id="b-modal" v-model="form.modalBerbayar" type="number" min="0" :class="pInputClass" />
        </PField>
        <PField label="Sumber modal" for="b-sumber">
          <input id="b-sumber" v-model="form.sumberModal" type="text" :class="pInputClass" />
        </PField>
        <PField label="Tarikh daftar perniagaan" for="b-daftar">
          <SpptDateInput id="b-daftar" v-model="form.tarikhDaftar" :input-class="pInputClass" />
        </PField>
        <PField label="Tarikh tamat lesen" for="b-lesen">
          <SpptDateInput id="b-lesen" v-model="form.tarikhTamatLesen" :input-class="pInputClass" />
        </PField>
        <div class="grid grid-cols-2 gap-3 sm:col-span-2">
          <PField label="Masa berniaga dari" for="b-dari">
            <SpptTimeInput id="b-dari" v-model="form.masaBerniagaDari" :input-class="pInputClass" />
          </PField>
          <PField label="Hingga" for="b-hingga">
            <SpptTimeInput id="b-hingga" v-model="form.masaBerniagaHingga" :input-class="pInputClass" />
          </PField>
        </div>
        <PField label="Agensi kursus dihadiri" for="b-kursus">
          <select id="b-kursus" v-model="form.kursusAgensi" :class="pInputClass">
            <option value="">Pilih</option>
            <option v-for="opt in AGENSI_KURSUS_OPTIONS" :key="opt" :value="opt">{{ opt }}</option>
          </select>
        </PField>
        <PField v-if="form.kursusAgensi === 'Lain-lain'" label="Nyatakan kursus" for="b-kursuslain">
          <input id="b-kursuslain" v-model="form.kursusLain" type="text" :class="pInputClass" />
        </PField>
        <PField class="sm:col-span-2" label="Perniagaan terdahulu (jika ada)" for="b-terdahulu">
          <input id="b-terdahulu" v-model="form.perniagaanTerdahulu" type="text" :class="pInputClass" />
        </PField>
      </div>

      <div class="mt-5 space-y-3">
        <label :class="toggleRow"><input v-model="form.keahlianPersatuan" type="checkbox" :class="checkbox" /> Ahli persatuan / koperasi perniagaan</label>
        <input v-if="form.keahlianPersatuan" v-model="form.keahlianPersatuanNyata" type="text" aria-label="Nama persatuan" :class="pInputClass" placeholder="Nyatakan nama persatuan" />

        <label :class="toggleRow"><input v-model="form.pengiktirafan" type="checkbox" :class="checkbox" /> Perniagaan pernah menerima pengiktirafan / anugerah</label>
        <input v-if="form.pengiktirafan" v-model="form.pengiktirafanNyata" type="text" aria-label="Pengiktirafan" :class="pInputClass" placeholder="Nyatakan pengiktirafan" />

        <label :class="toggleRow"><input v-model="form.pembiayaanSediaAda" type="checkbox" :class="checkbox" /> Mempunyai pembiayaan / pinjaman perniagaan sedia ada</label>
        <div v-if="form.pembiayaanSediaAda" class="grid gap-4 rounded-xl bg-slate-50 p-4 sm:grid-cols-2">
          <PField class="sm:col-span-2" label="Institusi pembiayaan" for="b-institusi">
            <select id="b-institusi" v-model="form.institusiPembiayaan" :class="pInputClass">
              <option value="">Pilih</option>
              <option v-for="opt in INSTITUSI_PEMBIAYAAN_OPTIONS" :key="opt" :value="opt">{{ opt }}</option>
            </select>
          </PField>
          <PField label="Jumlah pembiayaan sedia ada (RM)" for="b-jumlahsedia">
            <input id="b-jumlahsedia" v-model="form.jumlahPembiayaanSediaAda" type="number" min="0" :class="pInputClass" />
          </PField>
          <PField label="Baki pembiayaan (RM)" for="b-baki">
            <input id="b-baki" v-model="form.bakiPembiayaan" type="number" min="0" :class="pInputClass" />
          </PField>
        </div>
      </div>
    </PCard>

    <DataAlternatifCard :data="dataAlt" :ensure-record="ensureRecord" />

    <PCard title="Pekerjaan" subtitle="Jika anda juga bekerja makan gaji selain berniaga.">
      <div class="grid gap-4 sm:grid-cols-2">
        <PField class="sm:col-span-2" label="Status pekerjaan" for="k-status" required>
          <select id="k-status" v-model="form.statusPekerjaan" :class="pInputClass">
            <option value="" disabled>Pilih</option>
            <option v-for="opt in STATUS_PEKERJAAN_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
        </PField>
        <template v-if="form.statusPekerjaan === 'bekerja'">
          <PField label="Sektor pekerjaan" for="k-sektor" required>
            <select id="k-sektor" v-model="form.sektorPekerjaan" :class="pInputClass">
              <option value="" disabled>Pilih</option>
              <option v-for="opt in SEKTOR_PEKERJAAN_OPTIONS" :key="opt" :value="opt">{{ opt }}</option>
            </select>
          </PField>
          <PField label="Jawatan" for="k-jawatan">
            <input id="k-jawatan" v-model="form.jawatan" type="text" :class="pInputClass" />
          </PField>
          <PField label="Status jawatan" for="k-statusjawatan">
            <select id="k-statusjawatan" v-model="form.statusJawatan" :class="pInputClass">
              <option value="">Pilih</option>
              <option v-for="opt in STATUS_JAWATAN_OPTIONS" :key="opt" :value="opt">{{ opt }}</option>
            </select>
          </PField>
          <PField label="Pendapatan bulanan (RM)" for="k-pendapatan" required>
            <input id="k-pendapatan" v-model="form.pendapatan" type="number" min="0" inputmode="decimal" :class="pInputClass" />
          </PField>
          <PField class="sm:col-span-2" label="Nama majikan" for="k-majikan">
            <input id="k-majikan" v-model="form.namaMajikan" type="text" :class="pInputClass" />
          </PField>
          <PField class="sm:col-span-2" label="Alamat majikan" for="k-alamat">
            <textarea id="k-alamat" v-model="form.alamatMajikan" rows="2" :class="pInputClass" />
          </PField>
          <PField label="No. telefon majikan" for="k-tel">
            <input id="k-tel" v-model="form.noTelefonMajikan" type="tel" :class="pInputClass" />
          </PField>
        </template>
      </div>
    </PCard>
  </div>
</template>
