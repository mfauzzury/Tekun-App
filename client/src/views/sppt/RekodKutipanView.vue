<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref } from "vue";
import { useRouter } from "vue-router";
import { MapPin, Camera, FileSignature } from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import { KUTIPAN_ITEMS, STATUS_KUTIPAN } from "@/data/kutipan-dummy";

const { t, tp } = useI18n();

const router = useRouter();

const usahawanId = ref("");
const tarikhLawatan = ref("");
const masaLawatan = ref("");
const lokasiGps = ref("");
const hasilKutipan = ref("");
const janjiBayar = ref("");
const status = ref("");
const catatan = ref("");
const buktiGambar = ref<File | null>(null);
const buktiTandatangan = ref(false);

const usahawanOptions = KUTIPAN_ITEMS.map((k) => ({ value: k.id, label: `${k.nama} (${k.noPembiayaan})` }));

function onFileChange(e: Event) {
  const input = e.target as HTMLInputElement;
  buktiGambar.value = input.files?.[0] ?? null;
}

function submit() {
  alert(
    "Rekod kutipan disimpan (dummy).\n\n" +
      `Usahawan: ${usahawanId.value}\n` +
      `Tarikh: ${tarikhLawatan.value} ${masaLawatan.value}\n` +
      `Lokasi: ${lokasiGps.value}\n` +
      `Hasil: ${hasilKutipan.value}\n` +
      `Status: ${status.value}`
  );
  router.push("/admin/pemantauan/kutipan");
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-2xl space-y-4">
      <SpptPageHeader
        title="Rekod Kutipan"
        :breadcrumb="[
          { label: 'Pemantauan', to: '/admin/pemantauan/usahawan' },
          { label: 'Kutipan', to: '/admin/pemantauan/kutipan' },
          { label: 'Rekod Kutipan' },
        ]"
      />

      <form class="space-y-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm" @submit.prevent="submit">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Pilih Usahawan</label>
          <select
            v-model="usahawanId"
            required
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
          >
            <option value="">Pilih usahawan...</option>
            <option v-for="opt in usahawanOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
          </select>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Tarikh Lawatan</label>
            <input
              v-model="tarikhLawatan"
              type="date"
              required
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
            />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Masa Lawatan</label>
            <input
              v-model="masaLawatan"
              type="time"
              required
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
            />
          </div>
        </div>

        <div>
          <label class="mb-1 flex items-center gap-2 text-sm font-medium text-slate-700">
            <MapPin class="h-4 w-4" />
            Lokasi (GPS)
          </label>
          <input
            v-model="lokasiGps"
            type="text"
            placeholder="Contoh: 3.1390, 101.6869 atau alamat premis"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
          />
          <button
            type="button"
            class="mt-1 text-xs text-slate-500 underline hover:text-slate-700"
            @click="lokasiGps = '3.1390, 101.6869'"
          >
            Dapatkan lokasi semasa (dummy)
          </button>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Hasil Kutipan (RM)</label>
            <input
              v-model="hasilKutipan"
              type="text"
              placeholder="0.00"
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
            />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Janji Bayar (tarikh)</label>
            <input
              v-model="janjiBayar"
              type="date"
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
            />
          </div>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Status Kutipan</label>
          <select
            v-model="status"
            required
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
          >
            <option value="">Pilih status...</option>
            <option v-for="s in STATUS_KUTIPAN" :key="s.value" :value="s.label">{{ s.label }}</option>
          </select>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Catatan</label>
          <textarea
            v-model="catatan"
            rows="3"
            placeholder="Maklum balas penerima biaya, catatan lawatan..."
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
          />
        </div>

        <div class="space-y-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
          <h3 class="text-sm font-semibold text-slate-800">Bukti Lawatan</h3>
          <div>
            <label class="mb-1 flex items-center gap-2 text-sm font-medium text-slate-700">
              <Camera class="h-4 w-4" />
              Gambar / Foto Premis
            </label>
            <input
              type="file"
              accept="image/*"
              class="w-full text-sm text-slate-600 file:mr-2 file:rounded-lg file:border-0 file:bg-slate-200 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-300"
              @change="onFileChange"
            />
            <p v-if="buktiGambar" class="mt-1 text-xs text-slate-500">{{ buktiGambar.name }}</p>
          </div>
          <div class="flex items-center gap-2">
            <input
              v-model="buktiTandatangan"
              type="checkbox"
              id="tt"
              class="h-4 w-4 rounded border-slate-300 text-slate-600 focus:ring-slate-200"
            />
            <label for="tt" class="flex items-center gap-2 text-sm text-slate-700">
              <FileSignature class="h-4 w-4" />
              Tandatangan usahawan diperoleh
            </label>
          </div>
        </div>

        <div class="flex gap-3">
          <button
            type="submit"
            class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800"
          >
            Simpan Rekod
          </button>
          <button
            type="button"
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition-colors hover:bg-slate-50"
            @click="router.push('/admin/pemantauan/kutipan')"
          >
            Batal
          </button>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>
