<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import { ArrowLeft } from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import {
  AGAMA_OPTIONS,
  JANTINA_OPTIONS,
  PERKESO_PAKEJ,
  TAKAFUL_KEMALANGAN_PAKEJ,
} from "@/config/sppt-options";

const { t, tp } = useI18n();

const route = useRoute();
const router = useRouter();
const id = computed(() => (route.params.id as string) || "");

const BULAN_OPTIONS = [
  "Januari", "Februari", "Mac", "April", "Mei", "Jun",
  "Julai", "Ogos", "September", "Oktober", "November", "Disember",
];

// Dummy data - map list item ids to full form data
const DUMMY_DATA: Record<string, Record<string, unknown>> = {
  "P-2024-001": {
    kategoriPembiayaan: "TEKUN Niaga",
    statusPerniagaan: "sedang_berniaga",
    sektorPerniagaan: "Peruncitan",
    kaedahPerniagaan: "offline",
    namaBank: "Maybank",
    noAkaunBank: "1234567890",
    noUsahawan: "U-001",
    nama: "Ahmad bin Abdullah",
    noIcBaru: "850312-10-1234",
    noIcLama: "",
    jantina: "L",
    agama: "islam",
    tarikhLahirHari: "12",
    tarikhLahirBulan: "3",
    tarikhLahirTahun: "1985",
    bangsa: "Melayu",
    kaum: "",
    umur: "39",
    tarafPerkahwinan: "Berkahwin",
    bilanganTanggungan: "3",
    oku: false,
    diberhentikanPandemik: false,
    asnafBerdaftar: false,
    tarafPendidikan: "SPM",
    alamat: "No. 12, Jalan Merdeka, 50000 Kuala Lumpur",
    poskod: "50000",
    negeri: "Wilayah Persekutuan Kuala Lumpur",
    noTelefonRumah: "",
    noTelefonBimbit: "012-3456789",
    email: "ahmad@email.com",
    facebook: "",
    instagram: "",
    statusKediaman: "Sendiri",
    pekerjaanSekarang: "Usahawan",
    pendapatan: "3500",
    pendapatanBulan: "1",
    namaMajikan: "",
    alamatMajikan: "",
    noTelefonMajikan: "",
    namaPasangan: "Siti binti Abdullah",
    noIcPasangan: "880315-08-5678",
    pekerjaanPasangan: "Suri rumah",
    pendapatanPasangan: "0",
    pendapatanPasanganBulan: "1",
    jumlahPermohonan: "50000",
    tempohPembiayaan: "60",
    kekerapanBayaran: "Bulanan",
    tujuan: "Modal pusingan dan pembelian stok",
    namaPerniagaan: "Kedai Runcit Ahmad",
    noSsm: "",
    tempohBerniaga: "5",
    alamatPremis: "No. 12, Jalan Merdeka",
    poskodPremis: "50000",
    anggaranPendapatan: "5000",
    statusPremis: "Sewa",
    pemilikanPerniagaan: "Pemilikan Tunggal",
    takafulPembiayaan: true,
    takafulKemalangan: false,
    perkeso: false,
    wasiat: false,
    kebenaranKredit: true,
  },
  "P-2024-002": {
    kategoriPembiayaan: "TEKUN Niaga",
    statusPerniagaan: "sedang_berniaga",
    sektorPerniagaan: "Perkhidmatan",
    kaedahPerniagaan: "online",
    namaBank: "CIMB",
    noAkaunBank: "9876543210",
    noUsahawan: "U-002",
    nama: "Siti Nurhaliza binti Omar",
    noIcBaru: "920515-14-5678",
    noIcLama: "",
    jantina: "P",
    agama: "islam",
    tarikhLahirHari: "15",
    tarikhLahirBulan: "5",
    tarikhLahirTahun: "1992",
    bangsa: "Melayu",
    kaum: "",
    umur: "32",
    tarafPerkahwinan: "Berkahwin",
    bilanganTanggungan: "2",
    oku: false,
    diberhentikanPandemik: false,
    asnafBerdaftar: false,
    tarafPendidikan: "Diploma",
    alamat: "Lot 5, Taman Desa, 43000 Kajang",
    poskod: "43000",
    negeri: "Selangor",
    noTelefonRumah: "",
    noTelefonBimbit: "019-8765432",
    email: "siti@email.com",
    facebook: "",
    instagram: "",
    statusKediaman: "Sewa",
    pekerjaanSekarang: "Usahawan",
    pendapatan: "2800",
    pendapatanBulan: "1",
    namaPasangan: "Omar bin Hassan",
    noIcPasangan: "900101-01-1234",
    pekerjaanPasangan: "Pegawai Kerajaan",
    pendapatanPasangan: "4500",
    pendapatanPasanganBulan: "1",
    jumlahPermohonan: "30000",
    tempohPembiayaan: "48",
    kekerapanBayaran: "Bulanan",
    tujuan: "Pembelian peralatan dan stok",
    namaPerniagaan: "Siti Catering",
    tempohBerniaga: "2",
    statusPremis: "Sewa",
    pemilikanPerniagaan: "Pemilikan Tunggal",
    takafulPembiayaan: true,
    takafulKemalangan: true,
    perkeso: false,
    wasiat: false,
    kebenaranKredit: true,
  },
  "P-2024-003": {
    kategoriPembiayaan: "TEKUN Niaga",
    statusPerniagaan: "sedang_berniaga",
    sektorPerniagaan: "Pembuatan",
    kaedahPerniagaan: "offline",
    namaBank: "Bank Rakyat",
    noAkaunBank: "5555666677",
    noUsahawan: "U-003",
    nama: "Mohd Rizal bin Hassan",
    noIcBaru: "780820-10-9999",
    noIcLama: "",
    jantina: "L",
    agama: "islam",
    tarikhLahirHari: "20",
    tarikhLahirBulan: "8",
    tarikhLahirTahun: "1978",
    bangsa: "Melayu",
    kaum: "",
    umur: "46",
    tarafPerkahwinan: "Berkahwin",
    bilanganTanggungan: "4",
    oku: false,
    diberhentikanPandemik: false,
    asnafBerdaftar: false,
    tarafPendidikan: "SPM",
    alamat: "Kampung Baru, 15100 Kota Bharu",
    poskod: "15100",
    negeri: "Kelantan",
    noTelefonBimbit: "013-1112233",
    email: "rizal@email.com",
    statusKediaman: "Sendiri",
    pekerjaanSekarang: "Usahawan",
    pendapatan: "4200",
    pendapatanBulan: "1",
    namaPasangan: "Fatimah binti Yusof",
    noIcPasangan: "820505-06-4321",
    pekerjaanPasangan: "Suri rumah",
    pendapatanPasangan: "0",
    pendapatanPasanganBulan: "1",
    jumlahPermohonan: "75000",
    tempohPembiayaan: "72",
    kekerapanBayaran: "Bulanan",
    tujuan: "Pengembangan kilang kecil dan mesin",
    namaPerniagaan: "Rizal Woodcraft",
    tempohBerniaga: "8",
    statusPremis: "Sendiri",
    pemilikanPerniagaan: "Pemilikan Tunggal",
    takafulPembiayaan: true,
    takafulKemalangan: true,
    perkeso: true,
    wasiat: false,
    kebenaranKredit: true,
  },
  "P-2024-004": {
    kategoriPembiayaan: "TEKUN Niaga",
    statusPerniagaan: "memulakan",
    sektorPerniagaan: "Peruncitan",
    kaedahPerniagaan: "offline",
    namaBank: "RHB",
    noAkaunBank: "1122334455",
    noUsahawan: "U-004",
    nama: "Fatimah binti Ibrahim",
    noIcBaru: "950210-08-1234",
    noIcLama: "",
    jantina: "P",
    agama: "islam",
    tarikhLahirHari: "10",
    tarikhLahirBulan: "2",
    tarikhLahirTahun: "1995",
    bangsa: "Melayu",
    kaum: "",
    umur: "29",
    tarafPerkahwinan: "Bujang",
    bilanganTanggungan: "0",
    oku: false,
    diberhentikanPandemik: false,
    asnafBerdaftar: false,
    tarafPendidikan: "STPM",
    alamat: "No. 8, Jalan Damansara, 50490 Kuala Lumpur",
    poskod: "50490",
    negeri: "Wilayah Persekutuan Kuala Lumpur",
    noTelefonBimbit: "018-5556677",
    email: "fatimah@email.com",
    statusKediaman: "Sewa",
    pekerjaanSekarang: "Memulakan Perniagaan",
    pendapatan: "0",
    pendapatanBulan: "1",
    namaPasangan: "",
    noIcPasangan: "",
    pekerjaanPasangan: "",
    pendapatanPasangan: "0",
    pendapatanPasanganBulan: "1",
    jumlahPermohonan: "25000",
    tempohPembiayaan: "36",
    kekerapanBayaran: "Bulanan",
    tujuan: "Modal permulaan kedai runcit",
    namaPerniagaan: "Kedai Fatimah",
    tempohBerniaga: "0",
    statusPremis: "Sewa",
    pemilikanPerniagaan: "Pemilikan Tunggal",
    takafulPembiayaan: true,
    takafulKemalangan: false,
    perkeso: false,
    wasiat: false,
    kebenaranKredit: true,
  },
};

const form = computed(() => {
  const data = DUMMY_DATA[id.value] || DUMMY_DATA["P-2024-001"];
  return { ...DUMMY_DATA["P-2024-001"], ...data };
});

function getStatusLabel(v: string) {
  if (v === "sedang_berniaga") return "Sedang Berniaga";
  if (v === "memulakan") return "Memulakan Perniagaan";
  return v;
}

function getJantinaLabel(v: string) {
  return JANTINA_OPTIONS.find((j) => j.value === v)?.label ?? v;
}

function getAgamaLabel(v: string) {
  return AGAMA_OPTIONS.find((a) => a.value === v)?.label ?? v;
}

function getBulanLabel(v: string) {
  const i = parseInt(v, 10);
  return BULAN_OPTIONS[i - 1] || v;
}

function getTakafulPakejLabel(v: string) {
  return TAKAFUL_KEMALANGAN_PAKEJ.find((p) => p.value === v)?.label ?? v;
}

function getPerkesoPakejLabel(v: string) {
  return PERKESO_PAKEJ.find((p) => p.value === v)?.label ?? v;
}

function batal() {
  router.push("/admin/permohonan");
}

const labelClass = "mb-1 block text-xs font-medium text-slate-600";
const valueClass = "rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700";
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-4xl space-y-4">
      <SpptPageHeader
        :title="`Permohonan ${id}`"
        :breadcrumb="[
          { label: 'Permohonan', to: '/admin/permohonan' },
          { label: 'Pendaftaran Permohonan', to: '/admin/permohonan' },
          { label: id || 'Butiran' },
        ]"
      />

      <div class="space-y-6">
        <!-- Maklumat Asas -->
        <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-100 bg-slate-50/80 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-700">Maklumat Asas</h2>
            <p class="mt-0.5 text-xs text-slate-500">Status perniagaan, sektor dan maklumat bank operasi.</p>
          </div>
          <div class="space-y-4 p-4">
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label :class="labelClass">Status Perniagaan</label>
                <div :class="valueClass">{{ getStatusLabel(String(form.statusPerniagaan)) }}</div>
              </div>
              <div>
                <label :class="labelClass">Sektor Perniagaan</label>
                <div :class="valueClass">{{ form.sektorPerniagaan }}</div>
              </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label :class="labelClass">Kaedah Perniagaan</label>
                <div :class="valueClass">{{ form.kaedahPerniagaan === "online" ? "Online" : "Offline" }}</div>
              </div>
              <div>
                <label :class="labelClass">Kategori Pembiayaan</label>
                <div :class="valueClass">{{ form.kategoriPembiayaan }}</div>
              </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label :class="labelClass">Nama Bank Operasi Perniagaan</label>
                <div :class="valueClass">{{ form.namaBank || "—" }}</div>
              </div>
              <div>
                <label :class="labelClass">No. Akaun Bank</label>
                <div :class="valueClass">{{ form.noAkaunBank || "—" }}</div>
              </div>
            </div>
          </div>
        </article>

        <!-- Maklumat Pemohon -->
        <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-100 bg-slate-50/80 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-700">Maklumat Pemohon</h2>
            <p class="mt-0.5 text-xs text-slate-500">Nama, No. KP, jantina, agama, tarikh lahir dan maklumat peribadi.</p>
          </div>
          <div class="space-y-4 p-4">
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label :class="labelClass">No. Usahawan</label>
                <div :class="valueClass">{{ form.noUsahawan || "—" }}</div>
              </div>
              <div>
                <label :class="labelClass">Nama Pemohon</label>
                <div :class="valueClass">{{ form.nama }}</div>
              </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label :class="labelClass">No. Kad Pengenalan (Baru)</label>
                <div :class="valueClass">{{ form.noIcBaru || form.noIcLama || "—" }}</div>
              </div>
              <div>
                <label :class="labelClass">Jantina</label>
                <div :class="valueClass">{{ getJantinaLabel(String(form.jantina)) }}</div>
              </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
              <div>
                <label :class="labelClass">Agama</label>
                <div :class="valueClass">{{ getAgamaLabel(String(form.agama)) }}</div>
              </div>
              <div>
                <label :class="labelClass">Taraf Perkahwinan</label>
                <div :class="valueClass">{{ form.tarafPerkahwinan }}</div>
              </div>
              <div>
                <label :class="labelClass">Bilangan Tanggungan</label>
                <div :class="valueClass">{{ form.bilanganTanggungan }}</div>
              </div>
              <div>
                <label :class="labelClass">Tarikh Lahir</label>
                <div :class="valueClass">{{ form.tarikhLahirHari }}/{{ getBulanLabel(String(form.tarikhLahirBulan)) }}/{{ form.tarikhLahirTahun }}</div>
              </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label :class="labelClass">Bangsa</label>
                <div :class="valueClass">{{ form.bangsa || "—" }}</div>
              </div>
              <div>
                <label :class="labelClass">Taraf Pendidikan</label>
                <div :class="valueClass">{{ form.tarafPendidikan }}</div>
              </div>
            </div>
            <div class="flex flex-wrap gap-4 text-sm text-slate-600">
              <span v-if="form.oku">OKU</span>
              <span v-if="form.diberhentikanPandemik">Diberhentikan semasa pandemik</span>
              <span v-if="form.asnafBerdaftar">Asnaf berdaftar</span>
            </div>
          </div>
        </article>

        <!-- Alamat Kediaman -->
        <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-100 bg-slate-50/80 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-700">Alamat Kediaman</h2>
            <p class="mt-0.5 text-xs text-slate-500">Alamat penuh, poskod, telefon dan media sosial.</p>
          </div>
          <div class="space-y-4 p-4">
            <div>
              <label :class="labelClass">Alamat Kediaman</label>
              <div :class="valueClass">{{ form.alamat }}</div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
              <div>
                <label :class="labelClass">Poskod</label>
                <div :class="valueClass">{{ form.poskod }}</div>
              </div>
              <div>
                <label :class="labelClass">Negeri</label>
                <div :class="valueClass">{{ form.negeri }}</div>
              </div>
              <div>
                <label :class="labelClass">No. Telefon Bimbit</label>
                <div :class="valueClass">{{ form.noTelefonBimbit || "—" }}</div>
              </div>
              <div>
                <label :class="labelClass">E-mel</label>
                <div :class="valueClass">{{ form.email }}</div>
              </div>
            </div>
            <div>
              <label :class="labelClass">Status Kediaman</label>
              <div :class="valueClass">{{ form.statusKediaman }}</div>
            </div>
          </div>
        </article>

        <!-- Pekerjaan Sekarang -->
        <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-100 bg-slate-50/80 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-700">Pekerjaan Sekarang</h2>
            <p class="mt-0.5 text-xs text-slate-500">Pendapatan dan maklumat majikan.</p>
          </div>
          <div class="space-y-4 p-4">
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label :class="labelClass">Pekerjaan Sekarang</label>
                <div :class="valueClass">{{ form.pekerjaanSekarang }}</div>
              </div>
              <div>
                <label :class="labelClass">Pendapatan (RM/bulan)</label>
                <div :class="valueClass">{{ form.pendapatan }} / {{ form.pendapatanBulan }}</div>
              </div>
            </div>
          </div>
        </article>

        <!-- Maklumat Pasangan -->
        <article v-if="form.namaPasangan" class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-100 bg-slate-50/80 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-700">Maklumat Pasangan Pemohon</h2>
          </div>
          <div class="space-y-4 p-4">
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label :class="labelClass">Nama Suami / Isteri</label>
                <div :class="valueClass">{{ form.namaPasangan }}</div>
              </div>
              <div>
                <label :class="labelClass">No. Kad Pengenalan</label>
                <div :class="valueClass">{{ form.noIcPasangan }}</div>
              </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label :class="labelClass">Pekerjaan</label>
                <div :class="valueClass">{{ form.pekerjaanPasangan || "—" }}</div>
              </div>
              <div>
                <label :class="labelClass">Pendapatan (RM/bulan)</label>
                <div :class="valueClass">{{ form.pendapatanPasangan }} / {{ form.pendapatanPasanganBulan }}</div>
              </div>
            </div>
          </div>
        </article>

        <!-- Maklumat Perniagaan -->
        <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-100 bg-slate-50/80 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-700">F. Maklumat Perniagaan</h2>
            <p class="mt-0.5 text-xs text-slate-500">Nama perniagaan, premis, pemilikan.</p>
          </div>
          <div class="space-y-4 p-4">
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label :class="labelClass">Nama Perniagaan</label>
                <div :class="valueClass">{{ form.namaPerniagaan || "—" }}</div>
              </div>
              <div>
                <label :class="labelClass">No. SSM (ROB/ROE)</label>
                <div :class="valueClass">{{ form.noSsm || "—" }}</div>
              </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label :class="labelClass">Tempoh Berniaga (tahun)</label>
                <div :class="valueClass">{{ form.tempohBerniaga || "—" }}</div>
              </div>
              <div>
                <label :class="labelClass">Anggaran Pendapatan Kasar (RM/sebulan)</label>
                <div :class="valueClass">{{ form.anggaranPendapatan || "—" }}</div>
              </div>
            </div>
            <div>
              <label :class="labelClass">Alamat Perniagaan / Premis</label>
              <div :class="valueClass">{{ form.alamatPremis || "—" }}</div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label :class="labelClass">Status Premis</label>
                <div :class="valueClass">{{ form.statusPremis || "—" }}</div>
              </div>
              <div>
                <label :class="labelClass">Pemilikan Perniagaan</label>
                <div :class="valueClass">{{ form.pemilikanPerniagaan || "—" }}</div>
              </div>
            </div>
          </div>
        </article>

        <!-- Keterangan Pembiayaan -->
        <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-100 bg-slate-50/80 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-700">Keterangan Mengenai Pembiayaan Yang Dipohon</h2>
            <p class="mt-0.5 text-xs text-slate-500">Jumlah, tempoh dan tujuan pembiayaan.</p>
          </div>
          <div class="space-y-4 p-4">
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label :class="labelClass">Jumlah Permohonan (RM)</label>
                <div :class="valueClass">{{ form.jumlahPermohonan }}</div>
              </div>
              <div>
                <label :class="labelClass">Tempoh Pembiayaan (bulan)</label>
                <div :class="valueClass">{{ form.tempohPembiayaan }}</div>
              </div>
            </div>
            <div>
              <label :class="labelClass">Kekerapan Bayaran</label>
              <div :class="valueClass">{{ form.kekerapanBayaran }}</div>
            </div>
            <div>
              <label :class="labelClass">Tujuan Pembiayaan</label>
              <div :class="valueClass">{{ form.tujuan || "—" }}</div>
            </div>
          </div>
        </article>

        <!-- Takaful & PERKESO -->
        <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-100 bg-slate-50/80 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-700">H. Perlindungan Takaful dan PERKESO</h2>
          </div>
          <div class="space-y-4 p-4">
            <div class="flex flex-wrap gap-4 text-sm text-slate-700">
              <span>Takaful Pembiayaan: {{ form.takafulPembiayaan ? "Ya" : "Tidak" }}</span>
              <span>Takaful Kemalangan: {{ form.takafulKemalangan ? "Ya" : "Tidak" }}</span>
              <span v-if="form.takafulKemalangan">({{ getTakafulPakejLabel(String(form.takafulKemalanganPakej)) }})</span>
              <span>PERKESO: {{ form.perkeso ? "Ya" : "Tidak" }}</span>
              <span v-if="form.perkeso">({{ getPerkesoPakejLabel(String(form.perkesoPakej)) }})</span>
            </div>
          </div>
        </article>

        <!-- Kebenaran -->
        <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-100 bg-slate-50/80 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-700">J. Kebenaran Penzahiran Maklumat Kredit</h2>
          </div>
          <div class="p-4">
            <div :class="valueClass">{{ form.kebenaranKredit ? "Ya – Pemohon telah memberi kebenaran" : "Tidak" }}</div>
          </div>
        </article>

        <div class="flex justify-start">
          <button
            type="button"
            class="flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition-colors hover:bg-slate-50"
            @click="batal"
          >
            <ArrowLeft class="h-4 w-4" />
            Kembali ke Senarai
          </button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
