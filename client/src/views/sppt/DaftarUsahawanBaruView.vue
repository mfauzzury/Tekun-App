<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref } from "vue";
import { useRouter } from "vue-router";
import { ArrowLeft, Save } from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import { JENIS_PERNIAGAAN_OPTIONS, NEGERI_OPTIONS } from "@/config/sppt-options";

const { t, tp } = useI18n();

const router = useRouter();
const saving = ref(false);
const saved = ref(false);

const form = ref({
  nama: "Kamarul bin Ismail",
  noIc: "880315-08-5678",
  alamat: "No. 45, Jalan Sultan, 80000 Johor Bahru",
  negeri: "Johor",
  noTelefon: "019-8765432",
  email: "kamarul.ismail@email.com",
  jenisPerniagaan: "Perkhidmatan",
  tempohPerniagaan: "2 tahun",
  status: "Aktif",
});

function simpan() {
  saving.value = true;
  setTimeout(() => {
    saving.value = false;
    saved.value = true;
  }, 800);
}

function batal() {
  router.push("/admin/permohonan/usahawan");
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-3xl space-y-4">
      <SpptPageHeader
        title="Daftar Usahawan Baru"
        :breadcrumb="[
          { label: 'Permohonan', to: '/admin/permohonan' },
          { label: 'Rekod Usahawan', to: '/admin/permohonan/usahawan' },
          { label: 'Daftar Baru' },
        ]"
      />

      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-3">
          <h2 class="text-sm font-semibold text-slate-900">Maklumat Usahawan</h2>
          <p class="mt-0.5 text-xs text-slate-500">Isi maklumat usahawan untuk didaftarkan dalam Rekod Usahawan.</p>
        </div>

        <form class="space-y-6 p-4" @submit.prevent="simpan">
          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">Nama Penuh</label>
              <input
                v-model="form.nama"
                type="text"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                placeholder="Nama penuh"
              />
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">No. Kad Pengenalan</label>
              <input
                v-model="form.noIc"
                type="text"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                placeholder="YYMMDD-NN-GGGG"
              />
            </div>
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">No. Telefon</label>
              <input
                v-model="form.noTelefon"
                type="text"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                placeholder="01X-XXXXXXX"
              />
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">E-mel</label>
              <input
                v-model="form.email"
                type="email"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                placeholder="email@contoh.com"
              />
            </div>
          </div>

          <div>
            <label class="mb-1 block text-xs font-medium text-slate-600">Alamat</label>
            <textarea
              v-model="form.alamat"
              rows="2"
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
              placeholder="Alamat penuh"
            />
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">Negeri</label>
              <select
                v-model="form.negeri"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
              >
                <option value="">-- Pilih Negeri --</option>
                <option v-for="n in NEGERI_OPTIONS" :key="n" :value="n">{{ n }}</option>
              </select>
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">Status</label>
              <select
                v-model="form.status"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
              >
                <option value="Aktif">Aktif</option>
                <option value="Tidak Aktif">Tidak Aktif</option>
              </select>
            </div>
          </div>

          <hr class="border-slate-200" />

          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">Jenis Perniagaan</label>
              <select
                v-model="form.jenisPerniagaan"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
              >
                <option value="">-- Pilih Jenis Perniagaan --</option>
                <option v-for="j in JENIS_PERNIAGAAN_OPTIONS" :key="j" :value="j">{{ j }}</option>
              </select>
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">Tempoh Perniagaan</label>
              <input
                v-model="form.tempohPerniagaan"
                type="text"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                placeholder="Contoh: 2 tahun"
              />
            </div>
          </div>

          <div v-if="saved" class="rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
            Usahawan berjaya didaftarkan. (Dummy – tiada sambungan ke jadual.)
          </div>

          <div class="flex flex-wrap items-center gap-3 pt-2">
            <button
              type="submit"
              :disabled="saving"
              class="flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800 disabled:opacity-60"
            >
              <Save class="h-4 w-4" />
              {{ saving ? "Menyimpan..." : "Simpan" }}
            </button>
            <button
              type="button"
              class="flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 shadow-sm transition-colors hover:bg-slate-50"
              @click="batal"
            >
              <ArrowLeft class="h-4 w-4" />
              Batal
            </button>
          </div>
        </form>
      </article>
    </div>
  </AdminLayout>
</template>
