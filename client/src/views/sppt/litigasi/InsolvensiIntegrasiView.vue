<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref } from "vue";
import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import LitigasiSubNav from "@/components/sppt/litigasi/LitigasiSubNav.vue";

const { t, tp } = useI18n();

const uploadStatus = ref<"idle" | "uploading" | "done">("idle");
const taggedCount = ref(3);

function doUpload() {
  uploadStatus.value = "uploading";
  setTimeout(() => {
    uploadStatus.value = "done";
  }, 1500);
}

const sampleRecords = [
  { noKes: "PK-2022-089", nama: "Syarikat ABC Sdn Bhd", noInsolvensi: "B-28-1234-2023", status: "Auto tagged", tarikh: "20 Nov 2023" },
  { noKes: "PK-2023-045", nama: "Mohd Fadzil bin Ibrahim", noInsolvensi: "B-28-2345-2024", status: "Auto tagged", tarikh: "10 Mac 2024" },
  { noKes: "PK-2023-078", nama: "Aminah binti Hassan", noInsolvensi: "B-28-2346-2024", status: "Manual tagged", tarikh: "12 Mac 2024" },
];
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="Integrasi Insolvensi"
        :breadcrumb="[{ label: 'Undang-Undang', to: '/admin/undang-undang/litigasi' }, { label: 'Integrasi Insolvensi' }]"
      />

      <LitigasiSubNav />

      <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="mb-2 text-sm font-semibold text-slate-900">Insolvensi Notification System (7.2.2)</h3>
        <p class="mb-4 text-sm text-slate-600">Integrasi dengan sistem Insolvensi untuk kenalpasti senarai yang telah bankrap. Proses auto tagging selepas upload file.</p>
        <div class="flex items-center gap-4">
          <button
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            :disabled="uploadStatus === 'uploading'"
            @click="doUpload"
          >
            {{ uploadStatus === "uploading" ? "Memuat naik..." : uploadStatus === "done" ? "Upload Berjaya" : "Upload Fail Insolvensi" }}
          </button>
          <span v-if="uploadStatus === 'done'" class="text-sm text-emerald-600">Auto tagging selesai. {{ taggedCount }} rekod dikemaskini.</span>
        </div>
      </article>

      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-3">
          <h2 class="text-sm font-semibold text-slate-900">Rekod Tagging Insolvensi</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No. Kes</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.name") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">No. Kes Insolvensi</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.status") }}</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t("common.date") }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="row in sampleRecords" :key="row.noKes" class="hover:bg-slate-50">
                <td class="px-4 py-2 font-mono text-slate-600">{{ row.noKes }}</td>
                <td class="px-4 py-2 font-medium text-slate-900">{{ row.nama }}</td>
                <td class="px-4 py-2 font-mono text-slate-600">{{ row.noInsolvensi }}</td>
                <td class="px-4 py-2"><span class="rounded-full px-2.5 py-0.5 text-xs font-medium bg-emerald-100 text-emerald-700">{{ row.status }}</span></td>
                <td class="px-4 py-2 text-slate-600">{{ row.tarikh }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>

      <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="mb-2 text-sm font-semibold text-slate-900">POD ke JIM (7.6.1)</h3>
        <p class="text-sm text-slate-600">Notifikasi BSK ke JUU, tagging di sistem, hantar POD ke Jabatan Insolvensi Malaysia (JIM). JUU boleh upload surat luar dan tagging.</p>
      </article>
    </div>
  </AdminLayout>
</template>
