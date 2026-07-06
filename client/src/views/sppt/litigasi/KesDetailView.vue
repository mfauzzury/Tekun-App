<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { computed, ref, onMounted } from "vue";
import { useRoute, RouterLink } from "vue-router";
import { ArrowLeft, FileText, Calendar } from "lucide-vue-next";
import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import LitigasiSubNav from "@/components/sppt/litigasi/LitigasiSubNav.vue";
import { fetchSpptDataset } from "@/api/sppt";
import type { KesLitigasi } from "@/data/litigasi-dummy";

const { t, tp } = useI18n();

const kesLitigasi = ref<KesLitigasi[]>([]);

onMounted(async () => {
  const res = await fetchSpptDataset("litigasi", "kes_litigasi");
  kesLitigasi.value = res.data as KesLitigasi[];
});

const route = useRoute();
const kesId = computed(() => route.params.id as string);

const kes = computed(() => kesLitigasi.value.find((k) => k.id === kesId.value));

function statusLabel(s: string) {
  const m: Record<string, string> = {
    "fail-dibuka": "Fail Dibuka",
    "nod-dihantar": "NOD Dihantar",
    "saman-dihantar": "Saman Dihantar",
    pendengaran: "Pendengaran",
    perbicaraan: "Dalam Perbicaraan",
    penghakiman: "Penghakiman",
    execution: "Execution",
    selesai: "Selesai",
    henti: "Henti",
  };
  return m[s] ?? s;
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        :title="kes ? `Kes ${kes.noKes}` : 'Detail Kes'"
        :breadcrumb="[
          { label: 'Undang-Undang', to: '/admin/undang-undang/litigasi' },
          { label: 'Litigasi', to: '/admin/undang-undang/litigasi' },
          { label: kes?.noKes ?? 'Detail' },
        ]"
      />

      <LitigasiSubNav />

      <div v-if="!kes" class="rounded-lg border border-slate-200 bg-white p-8 text-center text-slate-500">
        Kes tidak dijumpai. <RouterLink to="/admin/undang-undang/litigasi" class="text-slate-900 underline">Kembali ke senarai</RouterLink>
      </div>

      <template v-else>
        <div class="flex items-center gap-2">
          <RouterLink to="/admin/undang-undang/litigasi" class="flex items-center gap-1 rounded-lg px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-100">
            <ArrowLeft class="h-4 w-4" />
            Kembali
          </RouterLink>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
          <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-900">
              <FileText class="h-4 w-4" />
              Maklumat Kes (7.7)
            </h3>
            <dl class="space-y-2 text-sm">
              <div class="flex justify-between"><dt class="text-slate-500">No. Kes</dt><dd class="font-mono text-slate-900">{{ kes.noKes }}</dd></div>
              <div v-if="kes.noKesMahkamah" class="flex justify-between"><dt class="text-slate-500">No. Kes Mahkamah</dt><dd class="font-mono text-slate-900">{{ kes.noKesMahkamah }}</dd></div>
              <div v-if="kes.namaMahkamah" class="flex justify-between"><dt class="text-slate-500">Mahkamah</dt><dd class="text-slate-900">{{ kes.namaMahkamah }}</dd></div>
              <div class="flex justify-between"><dt class="text-slate-500">No. Akaun</dt><dd class="font-mono text-slate-900">{{ kes.noAkaun }}</dd></div>
              <div class="flex justify-between"><dt class="text-slate-500">Nama</dt><dd class="font-medium text-slate-900">{{ kes.nama }}</dd></div>
              <div class="flex justify-between"><dt class="text-slate-500">Cawangan</dt><dd class="text-slate-900">{{ kes.cawangan }}</dd></div>
              <div class="flex justify-between"><dt class="text-slate-500">Negeri</dt><dd class="text-slate-900">{{ kes.negeri }}</dd></div>
              <div class="flex justify-between"><dt class="text-slate-500">Produk</dt><dd class="text-slate-900">{{ kes.produk }}</dd></div>
              <div class="flex justify-between"><dt class="text-slate-500">Jumlah Tuntutan</dt><dd class="font-medium text-slate-900">{{ kes.jumlahTuntutan }}</dd></div>
              <div class="flex justify-between"><dt class="text-slate-500">Status</dt><dd><span class="rounded-full px-2.5 py-0.5 text-xs font-medium bg-slate-100 text-slate-700">{{ statusLabel(kes.status) }}</span></dd></div>
              <div v-if="kes.panelPeguam" class="flex justify-between"><dt class="text-slate-500">Panel Peguam</dt><dd class="text-slate-900">{{ kes.panelPeguam }}</dd></div>
            </dl>
          </article>

          <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-900">
              <Calendar class="h-4 w-4" />
              Tarikh Penting (7.6.5)
            </h3>
            <dl class="space-y-2 text-sm">
              <div class="flex justify-between"><dt class="text-slate-500">Tarikh Buka</dt><dd class="text-slate-900">{{ kes.tarikhBuka }}</dd></div>
              <div v-if="kes.tarikhPendengaran" class="flex justify-between"><dt class="text-slate-500">Tarikh Pendengaran</dt><dd class="text-slate-900">{{ kes.tarikhPendengaran }}</dd></div>
              <div v-if="kes.tarikhPerbicaraan" class="flex justify-between"><dt class="text-slate-500">Tarikh Perbicaraan</dt><dd class="text-slate-900">{{ kes.tarikhPerbicaraan }}</dd></div>
              <div v-if="kes.tarikhPenghakiman" class="flex justify-between"><dt class="text-slate-500">Tarikh Penghakiman</dt><dd class="text-slate-900">{{ kes.tarikhPenghakiman }}</dd></div>
              <div v-if="kes.jenisPenghakiman" class="flex justify-between"><dt class="text-slate-500">Jenis Penghakiman</dt><dd class="text-slate-900">{{ kes.jenisPenghakiman.replace(/-/g, " ") }}</dd></div>
              <div v-if="kes.keputusan" class="flex justify-between"><dt class="text-slate-500">Keputusan</dt><dd class="font-medium text-slate-900">{{ kes.keputusan }}</dd></div>
            </dl>
            <p class="mt-3 text-xs text-slate-500">Peringatan automatik 7 hari sebelum tarikh.</p>
          </article>
        </div>

        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <h3 class="mb-2 text-sm font-semibold text-slate-900">Dokumen (7.6.6, 7.8.3)</h3>
          <p class="text-sm text-slate-600">Notis dan dokumen disimpan dengan nombor rujukan unik, dihubungkan dengan akaun. Boleh diakses oleh pegawai litigasi dan panel peguam.</p>
          <div class="mt-3 flex gap-2">
            <button class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50">Surat Arahan</button>
            <button class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50">Salinan NOD</button>
            <button class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50">Saman</button>
            <button class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm text-slate-600 hover:bg-slate-50">Surat Keputusan</button>
          </div>
        </article>
      </template>
    </div>
  </AdminLayout>
</template>
