<script setup lang="ts">
import { useSpptStatus } from "@/composables/useSpptStatus";
import { useToast } from "@/composables/useToast";
import { computed, onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { ArrowLeft, FileText } from "lucide-vue-next";

import { getPermohonan, isApprovedPermohonanStatus, openPermohonanDocument, openPermohonanOfferLetter } from "@/api/sppt";
import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import { documentClassLabel } from "@/config/sppt-document-classes";
import type { Permohonan, PermohonanAttachment } from "@/types";
import {
  AGAMA_OPTIONS,
  JANTINA_OPTIONS,
  PERKESO_PAKEJ,
  STATUS_PEKERJAAN_OPTIONS,
  TAKAFUL_KEMALANGAN_PAKEJ,
} from "@/config/sppt-options";

function normalizeStatusPekerjaan(value: unknown): "bekerja" | "tidak_bekerja" | "" {
  const normalized = String(value ?? "").trim().toLowerCase();
  if (normalized === "bekerja") return "bekerja";
  if (normalized === "tidak_bekerja") return "tidak_bekerja";
  return "";
}

const toast = useToast();
const { statusLabel, statusClass } = useSpptStatus();

const route = useRoute();
const router = useRouter();
const permohonanId = computed(() => Number(route.params.id) || 0);

const loading = ref(true);
const downloadingOfferLetter = ref(false);
const permohonan = ref<Permohonan | null>(null);
const form = ref<Record<string, unknown>>({});

const displayTitle = computed(() => permohonan.value?.noRujukan ?? `Permohonan #${permohonanId.value}`);

const canDownloadOfferLetter = computed(
  () => Boolean(permohonan.value?.status && isApprovedPermohonanStatus(permohonan.value.status)),
);

const attachments = computed(() => {
  const saved = form.value.attachments;
  if (!Array.isArray(saved)) return [] as PermohonanAttachment[];
  return saved.filter(
    (item): item is PermohonanAttachment =>
      typeof item === "object" && item !== null && "id" in item && "name" in item,
  );
});

const BULAN_OPTIONS = [
  "Januari", "Februari", "Mac", "April", "Mei", "Jun",
  "Julai", "Ogos", "September", "Oktober", "November", "Disember",
];

async function loadPermohonan() {
  if (!permohonanId.value) {
    router.push("/admin/permohonan");
    return;
  }

  loading.value = true;
  try {
    const res = await getPermohonan(permohonanId.value);
    permohonan.value = res.data;

    const details = (res.data.details ?? {}) as Record<string, unknown>;
    const { adaPasangan, currentStep, attachmentNames, attachments: savedAttachments, ...formFields } = details;

    form.value = { ...formFields };
    if (adaPasangan !== undefined) form.value.adaPasangan = adaPasangan;
    if (Array.isArray(savedAttachments)) form.value.attachments = savedAttachments;

    if (res.data.nama) form.value.nama = res.data.nama;
    if (res.data.kategoriPembiayaan) form.value.kategoriPembiayaan = res.data.kategoriPembiayaan;
    if (res.data.jumlahPermohonan != null) {
      form.value.jumlahPermohonan = String(res.data.jumlahPermohonan);
    }

    const normalizedStatus = normalizeStatusPekerjaan(form.value.statusPekerjaan);
    if (normalizedStatus) form.value.statusPekerjaan = normalizedStatus;
  } catch {
    toast.error("Gagal memuatkan butiran permohonan.");
    router.push("/admin/permohonan");
  } finally {
    loading.value = false;
  }
}

onMounted(loadPermohonan);

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

function getStatusPekerjaanLabel(v: string) {
  return STATUS_PEKERJAAN_OPTIONS.find((o) => o.value === v)?.label ?? v;
}

const isBekerja = computed(
  () => form.value.statusPekerjaan === "bekerja" || (!form.value.statusPekerjaan && Boolean(form.value.pekerjaanSekarang)),
);

async function viewAttachment(item: PermohonanAttachment) {
  try {
    await openPermohonanDocument(permohonanId.value, item.id);
  } catch {
    toast.error("Gagal membuka fail.");
  }
}

async function downloadOfferLetter() {
  if (!permohonanId.value) return;
  downloadingOfferLetter.value = true;
  try {
    await openPermohonanOfferLetter(permohonanId.value);
  } catch (err) {
    toast.error(err instanceof Error ? err.message : "Gagal menjana surat tawaran.");
  } finally {
    downloadingOfferLetter.value = false;
  }
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
        :title="displayTitle"
        :breadcrumb="[
          { label: 'Permohonan', to: '/admin/permohonan' },
          { label: 'Pendaftaran Permohonan', to: '/admin/permohonan' },
          { label: displayTitle },
        ]"
      />

      <div v-if="loading" class="rounded-lg border border-slate-200 bg-white p-8 text-center text-sm text-slate-500">
        Memuatkan butiran permohonan...
      </div>

      <div v-else class="space-y-6">
        <div v-if="permohonan?.status" class="flex flex-wrap items-center gap-3">
          <span class="text-xs font-medium text-slate-500">Status:</span>
          <span
            class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
            :class="statusClass(permohonan.status)"
          >
            {{ statusLabel(permohonan.status) }}
          </span>
          <button
            v-if="canDownloadOfferLetter"
            type="button"
            class="ml-auto flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 transition-colors hover:bg-blue-100 disabled:opacity-60"
            :disabled="downloadingOfferLetter"
            @click="downloadOfferLetter"
          >
            <FileText class="h-3.5 w-3.5" />
            {{ downloadingOfferLetter ? "Menjana PDF..." : "Surat Tawaran PDF" }}
          </button>
        </div>

        <div
          v-if="permohonan?.negeri || permohonan?.cawangan"
          class="flex flex-wrap items-center gap-x-6 gap-y-1 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700"
        >
          <span class="font-medium text-slate-900">Pejabat Pendaftaran:</span>
          <span v-if="permohonan?.negeri"><span class="text-slate-500">Negeri:</span> {{ permohonan.negeri }}</span>
          <span v-if="permohonan?.cawangan"><span class="text-slate-500">Cawangan:</span> {{ permohonan.cawangan }}</span>
        </div>

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
                <div :class="valueClass">
                  {{ form.bangsa === "Lain-lain" && form.kaum ? form.kaum : (form.bangsa || "—") }}
                </div>
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
            <div>
              <label :class="labelClass">Status Pekerjaan</label>
              <div :class="valueClass">
                {{ getStatusPekerjaanLabel(String(form.statusPekerjaan || (form.pekerjaanSekarang ? "bekerja" : "tidak_bekerja"))) }}
              </div>
            </div>
            <template v-if="isBekerja">
              <div class="grid gap-4 sm:grid-cols-2">
                <div>
                  <label :class="labelClass">Sektor Pekerjaan</label>
                  <div :class="valueClass">{{ form.sektorPekerjaan || "—" }}</div>
                </div>
                <div>
                  <label :class="labelClass">Status Jawatan</label>
                  <div :class="valueClass">{{ form.statusJawatan || "—" }}</div>
                </div>
              </div>
              <div class="grid gap-4 sm:grid-cols-2">
                <div>
                  <label :class="labelClass">Jawatan</label>
                  <div :class="valueClass">{{ form.jawatan || form.pekerjaanSekarang || "—" }}</div>
                </div>
                <div>
                  <label :class="labelClass">Nama Majikan</label>
                  <div :class="valueClass">{{ form.namaMajikan || "—" }}</div>
                </div>
              </div>
              <div class="grid gap-4 sm:grid-cols-2">
                <div>
                  <label :class="labelClass">Pendapatan (RM/bulan)</label>
                  <div :class="valueClass">{{ form.pendapatan || "0" }} / {{ form.pendapatanBulan || "1" }}</div>
                </div>
              </div>
            </template>
          </div>
        </article>

        <!-- Maklumat Pasangan -->
        <article v-if="form.adaPasangan && form.namaPasangan" class="rounded-lg border border-slate-200 bg-white shadow-sm">
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

        <!-- Dokumen Lampiran -->
        <article v-if="attachments.length > 0" class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-100 bg-slate-50/80 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-700">Dokumen Lampiran</h2>
            <p class="mt-0.5 text-xs text-slate-500">{{ attachments.length }} fail dilampirkan.</p>
          </div>
          <div class="divide-y divide-slate-100 p-4">
            <div
              v-for="item in attachments"
              :key="item.id"
              class="flex items-center justify-between gap-3 py-2 first:pt-0 last:pb-0"
            >
              <div class="flex min-w-0 items-center gap-2">
                <FileText class="h-4 w-4 shrink-0 text-slate-400" />
                <div class="min-w-0">
                  <span class="truncate text-sm text-slate-700">{{ item.name }}</span>
                  <p class="text-xs text-violet-700">
                    {{ documentClassLabel(item.documentClass, item.documentClassOther) }}
                  </p>
                </div>
              </div>
              <button
                type="button"
                class="shrink-0 text-xs font-medium text-blue-600 hover:text-blue-800"
                @click="viewAttachment(item)"
              >
                Lihat fail
              </button>
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
