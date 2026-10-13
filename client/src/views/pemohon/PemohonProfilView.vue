<script setup lang="ts">
import { computed, reactive, ref } from "vue";
import { Building2, Camera, Mail, Phone, Save, Trash2, User } from "lucide-vue-next";
import PemohonLayout from "@/layouts/PemohonLayout.vue";
import PButton from "@/components/pemohon/ui/PButton.vue";
import PCard from "@/components/pemohon/ui/PCard.vue";
import PField from "@/components/pemohon/ui/PField.vue";
import PPageHeader from "@/components/pemohon/ui/PPageHeader.vue";
import { useToast } from "@/composables/useToast";
import { usePemohonStore } from "@/stores/pemohon";
import { initials, pInputClass } from "@/utils/pemohon-ui";

const pemohon = usePemohonStore();
const toast = useToast();

const form = reactive({ ...pemohon.profil });

const tabs = [
  { id: "individu", label: "Individu", icon: User },
  { id: "syarikat", label: "Syarikat", icon: Building2 },
];
const activeTab = ref("individu");

const userInitials = computed(() => initials(form.nama));

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
    <div class="space-y-6">
      <PPageHeader eyebrow="Akaun saya" title="Profil Saya" subtitle="Maklumat peribadi dan perniagaan anda." />

      <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
        <!-- Avatar card -->
        <div class="lg:col-span-1">
          <PCard class="lg:sticky lg:top-24">
            <div class="flex flex-col items-center text-center">
              <div class="relative">
                <img
                  v-if="form.foto"
                  :src="form.foto"
                  alt="Foto profil"
                  class="h-24 w-24 rounded-full object-cover ring-4 ring-white shadow-md"
                />
                <div
                  v-else
                  class="flex h-24 w-24 items-center justify-center rounded-full bg-ink-900 text-3xl font-bold text-white ring-4 ring-white shadow-md"
                  aria-hidden="true"
                >
                  {{ userInitials }}
                </div>
                <label
                  class="absolute -bottom-1 -right-1 flex h-9 w-9 cursor-pointer items-center justify-center rounded-full bg-brand-600 text-white shadow-md ring-4 ring-white transition-colors hover:bg-brand-700 focus-within:ring-brand-200"
                  :title="form.foto ? 'Tukar foto' : 'Muat naik foto'"
                >
                  <Camera class="h-4 w-4" />
                  <span class="sr-only">{{ form.foto ? "Tukar foto" : "Muat naik foto" }}</span>
                  <input type="file" accept="image/*" class="sr-only" @change="onPhotoSelected" />
                </label>
              </div>

              <p class="mt-4 text-base font-semibold text-ink-900">{{ form.nama || "Pemohon" }}</p>
              <p v-if="form.perniagaan" class="mt-0.5 text-sm text-slate-500">{{ form.perniagaan }}</p>

              <div class="mt-4 w-full space-y-2 border-t border-slate-100 pt-4 text-left text-sm">
                <p v-if="form.email" class="flex items-center gap-2.5 truncate text-slate-600">
                  <Mail class="h-4 w-4 shrink-0 text-slate-400" />
                  <span class="truncate">{{ form.email }}</span>
                </p>
                <p v-if="form.telefon" class="flex items-center gap-2.5 text-slate-600">
                  <Phone class="h-4 w-4 shrink-0 text-slate-400" />
                  {{ form.telefon }}
                </p>
              </div>

              <div class="mt-4 flex w-full flex-wrap justify-center gap-2">
                <label
                  class="inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 text-sm font-semibold text-ink-800 transition-colors hover:bg-slate-50 focus-within:ring-4 focus-within:ring-slate-200"
                >
                  <Camera class="h-4 w-4" />
                  {{ form.foto ? "Tukar foto" : "Muat naik foto" }}
                  <input type="file" accept="image/*" class="sr-only" @change="onPhotoSelected" />
                </label>
                <PButton v-if="form.foto" size="sm" variant="ghost" class="text-rose-600! hover:bg-rose-50!" @click="removePhoto">
                  <Trash2 class="h-4 w-4" />
                  Buang
                </PButton>
              </div>
              <p class="mt-2 text-xs text-slate-400">JPG atau PNG, maksimum 2MB.</p>
            </div>
          </PCard>
        </div>

        <!-- Form -->
        <form class="space-y-5 lg:col-span-2" @submit.prevent="save">
          <div role="tablist" aria-label="Jenis maklumat" class="grid w-full grid-cols-2 gap-1 rounded-xl bg-slate-200/60 p-1 sm:inline-grid sm:w-auto">
            <button
              v-for="tab in tabs"
              :key="tab.id"
              type="button"
              role="tab"
              :aria-selected="activeTab === tab.id"
              class="flex items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-300 sm:px-6"
              :class="activeTab === tab.id ? 'bg-white text-ink-900 shadow-sm' : 'text-slate-500 hover:text-ink-900'"
              @click="activeTab = tab.id"
            >
              <component :is="tab.icon" class="h-4 w-4" />
              {{ tab.label }}
            </button>
          </div>

          <!-- Individu -->
          <template v-if="activeTab === 'individu'">
            <PCard title="Maklumat peribadi" subtitle="Pastikan maklumat sepadan dengan MyKad anda.">
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <PField label="Nama penuh" for="profil-nama">
                  <input id="profil-nama" v-model="form.nama" type="text" autocomplete="name" :class="pInputClass" />
                </PField>
                <PField label="No. kad pengenalan" for="profil-nokp">
                  <input id="profil-nokp" v-model="form.noKp" type="text" :class="pInputClass" />
                </PField>
              </div>
            </PCard>
            <PCard title="Maklumat perhubungan">
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <PField label="Emel" for="profil-email">
                  <input id="profil-email" v-model="form.email" type="email" autocomplete="email" :class="pInputClass" />
                </PField>
                <PField label="No. telefon" for="profil-telefon">
                  <input id="profil-telefon" v-model="form.telefon" type="text" autocomplete="tel" :class="pInputClass" />
                </PField>
                <PField label="Alamat" for="profil-alamat" class="sm:col-span-2">
                  <textarea id="profil-alamat" v-model="form.alamat" rows="3" autocomplete="street-address" :class="pInputClass" />
                </PField>
              </div>
            </PCard>
          </template>

          <!-- Syarikat -->
          <PCard v-else title="Maklumat perniagaan" subtitle="Seperti yang didaftarkan dengan SSM.">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <PField label="Nama perniagaan" for="profil-perniagaan">
                <input id="profil-perniagaan" v-model="form.perniagaan" type="text" :class="pInputClass" />
              </PField>
              <PField label="No. pendaftaran SSM" for="profil-ssm">
                <input id="profil-ssm" v-model="form.noSsm" type="text" :class="pInputClass" />
              </PField>
              <PField label="Sektor perniagaan" for="profil-sektor">
                <input id="profil-sektor" v-model="form.sektor" type="text" :class="pInputClass" />
              </PField>
              <PField label="Status syariah" for="profil-syariah">
                <input id="profil-syariah" v-model="form.statusSyariah" type="text" :class="pInputClass" />
              </PField>
            </div>
          </PCard>

          <!-- Sticky save bar -->
          <div class="sticky bottom-24 z-30 md:bottom-4">
            <div
              class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200/80 bg-white/95 p-3 pl-4 shadow-[0_8px_30px_-8px_rgba(16,24,40,0.18)] backdrop-blur"
            >
              <p class="hidden text-sm text-slate-500 sm:block">Semak maklumat sebelum menyimpan.</p>
              <PButton type="submit" class="w-full sm:w-auto">
                <Save class="h-4 w-4" />
                Simpan perubahan
              </PButton>
            </div>
          </div>
        </form>
      </div>
    </div>
  </PemohonLayout>
</template>
