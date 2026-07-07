<script setup lang="ts">
import { computed, reactive, ref } from "vue";
import { Building2, Camera, Save, Trash2, User, UserCircle } from "lucide-vue-next";
import PemohonLayout from "@/layouts/PemohonLayout.vue";
import { useToast } from "@/composables/useToast";
import { usePemohonStore } from "@/stores/pemohon";

const pemohon = usePemohonStore();
const toast = useToast();
const inputClass = "w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 transition-colors focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200";

const form = reactive({ ...pemohon.profil });

const tabs = [
  { id: "individu", label: "Individu", icon: User },
  { id: "syarikat", label: "Syarikat", icon: Building2 },
];
const activeTab = ref("individu");

const initials = computed(() =>
  (form.nama || "P")
    .split(" ")
    .map((n) => n[0])
    .join("")
    .toUpperCase()
    .slice(0, 2),
);

function onPhotoSelected(event: Event) {
  const input = event.target as HTMLInputElement;
  const file = input.files?.[0];
  input.value = "";
  if (!file) return;

  if (!file.type.startsWith("image/")) {
    toast.error("Format tidak sah", "Sila pilih fail imej (JPG atau PNG).");
    return;
  }
  if (file.size > 2 * 1024 * 1024) {
    toast.error("Fail terlalu besar", "Saiz foto maksimum ialah 2MB.");
    return;
  }

  const reader = new FileReader();
  reader.onload = () => {
    form.foto = String(reader.result);
  };
  reader.readAsDataURL(file);
}

function removePhoto() {
  form.foto = "";
}

function save() {
  pemohon.profil = { ...form };
  toast.success("Profil Dikemaskini", "Maklumat profil anda telah disimpan.");
}
</script>

<template>
  <PemohonLayout>
    <div class="mx-auto max-w-5xl space-y-5">
      <div class="pemohon-hero rounded-b-2xl p-6 shadow-sm">
        <div class="flex items-center gap-2">
          <UserCircle class="h-5 w-5 text-blue-200" />
          <h1 class="text-xl font-semibold text-white">Profil Saya</h1>
        </div>
        <p class="mt-1 text-sm text-blue-100">Maklumat peribadi dan perniagaan anda.</p>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex border-b border-slate-200">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            type="button"
            class="flex flex-1 items-center justify-center gap-1.5 border-b-2 px-4 py-3 text-sm font-medium transition-colors"
            :class="activeTab === tab.id ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-500 hover:text-slate-800'"
            @click="activeTab = tab.id"
          >
            <component :is="tab.icon" class="h-4 w-4" />
            {{ tab.label }}
          </button>
        </div>

        <form @submit.prevent="save">
          <!-- Individu -->
          <div v-if="activeTab === 'individu'" class="space-y-4 p-5">
            <div class="flex items-center gap-4 border-b border-slate-100 pb-5">
              <img
                v-if="form.foto"
                :src="form.foto"
                alt="Foto profil"
                class="h-20 w-20 shrink-0 rounded-full object-cover ring-2 ring-slate-200"
              />
              <div
                v-else
                class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-blue-800 text-2xl font-bold text-white"
              >
                {{ initials }}
              </div>
              <div>
                <div class="flex flex-wrap items-center gap-2">
                  <label
                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-md border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50"
                  >
                    <Camera class="h-4 w-4" />
                    {{ form.foto ? "Tukar Foto" : "Muat Naik Foto" }}
                    <input type="file" accept="image/*" class="hidden" @change="onPhotoSelected" />
                  </label>
                  <button
                    v-if="form.foto"
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-sm font-medium text-rose-600 transition-colors hover:bg-rose-50"
                    @click="removePhoto"
                  >
                    <Trash2 class="h-4 w-4" />
                    Buang
                  </button>
                </div>
                <p class="mt-1.5 text-xs text-slate-400">JPG atau PNG, maksimum 2MB.</p>
              </div>
            </div>

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
          </div>

          <!-- Syarikat -->
          <div v-else class="space-y-4 p-5">
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
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Sektor Perniagaan</label>
                <input v-model="form.sektor" type="text" :class="inputClass" />
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Status Syariah</label>
                <input v-model="form.statusSyariah" type="text" :class="inputClass" />
              </div>
            </div>
          </div>

          <div class="flex justify-end border-t border-slate-100 p-5">
            <button
              type="submit"
              class="flex items-center gap-1.5 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
            >
              <Save class="h-4 w-4" />
              Simpan Perubahan
            </button>
          </div>
        </form>
      </div>
    </div>
  </PemohonLayout>
</template>
