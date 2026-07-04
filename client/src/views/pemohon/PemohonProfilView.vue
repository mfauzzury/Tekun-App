<script setup lang="ts">
import { reactive } from "vue";
import { Save, UserCircle } from "lucide-vue-next";
import PemohonLayout from "@/layouts/PemohonLayout.vue";
import { useToast } from "@/composables/useToast";
import { usePemohonStore } from "@/stores/pemohon";

const pemohon = usePemohonStore();
const toast = useToast();
const inputClass = "w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 shadow-sm transition-colors focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-200";

const form = reactive({ ...pemohon.profil });

function save() {
  pemohon.profil = { ...form };
  toast.success("Profil Dikemaskini", "Maklumat profil anda telah disimpan.");
}
</script>

<template>
  <PemohonLayout>
    <div class="mx-auto max-w-5xl space-y-5">
      <div class="rounded-2xl border border-slate-200 bg-gradient-to-r from-slate-50 to-white p-6 shadow-sm">
        <div class="flex items-center gap-2">
          <UserCircle class="h-5 w-5 text-violet-600" />
          <h1 class="text-xl font-semibold text-slate-900">Profil Saya</h1>
        </div>
        <p class="mt-1 text-sm text-slate-500">Maklumat peribadi dan perniagaan anda.</p>
      </div>

      <form class="mx-auto max-w-2xl space-y-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm" @submit.prevent="save">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Nama Penuh</label>
            <input v-model="form.nama" type="text" :class="inputClass" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">No. Kad Pengenalan</label>
            <input v-model="form.noKp" type="text" :class="inputClass" />
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Emel</label>
            <input v-model="form.email" type="email" :class="inputClass" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">No. Telefon</label>
            <input v-model="form.telefon" type="text" :class="inputClass" />
          </div>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Alamat</label>
          <textarea v-model="form.alamat" rows="2" :class="inputClass" />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Nama Perniagaan</label>
            <input v-model="form.perniagaan" type="text" :class="inputClass" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">No. Pendaftaran SSM</label>
            <input v-model="form.noSsm" type="text" :class="inputClass" />
          </div>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Sektor Perniagaan</label>
          <input v-model="form.sektor" type="text" :class="inputClass" />
        </div>

        <div class="flex justify-end border-t border-slate-100 pt-4">
          <button
            type="submit"
            class="flex items-center gap-1.5 rounded-md bg-violet-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-violet-700"
          >
            <Save class="h-4 w-4" />
            Simpan Perubahan
          </button>
        </div>
      </form>
    </div>
  </PemohonLayout>
</template>
