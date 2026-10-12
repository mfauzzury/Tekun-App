<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { Check, Clock, RefreshCw, Shield, X } from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import { useToast } from "@/composables/useToast";
import {
  approvePenjadualan,
  createPenjadualan,
  listAkaun,
  listPenjadualan,
} from "@/api/sppt";
import type { AkaunPembiayaan, PermohonanPenjadualan } from "@/types";

const toast = useToast();

const akaunList = ref<AkaunPembiayaan[]>([]);
const permohonanList = ref<PermohonanPenjadualan[]>([]);
const loading = ref(true);

const akaunId = ref<number | "">("");
const jenis = ref<"moratorium" | "penjadualan_semula" | "penstrukturan">("moratorium");
const tempohBulan = ref(6);
const tarikhKuatKuasa = ref(new Date().toISOString().slice(0, 10));
const sebab = ref("");
const submitting = ref(false);

const jenisOptions = [
  { value: "moratorium", label: "Moratorium", icon: Shield },
  { value: "penjadualan_semula", label: "Penjadualan Semula", icon: RefreshCw },
  { value: "penstrukturan", label: "Penstrukturan", icon: Clock },
];

const pending = computed(() => permohonanList.value.filter((p) => p.status === "menunggu"));

function fmtRm(n: number) {
  return `RM ${n.toLocaleString("ms-MY", { minimumFractionDigits: 2 })}`;
}

function jenisLabel(v: string) {
  return jenisOptions.find((o) => o.value === v)?.label ?? v;
}

async function load() {
  loading.value = true;
  try {
    const [akaunRes, penjadualanRes] = await Promise.all([
      listAkaun({ limit: 100 }),
      listPenjadualan({ limit: 50 }),
    ]);
    akaunList.value = akaunRes.data;
    permohonanList.value = penjadualanRes.data;
  } finally {
    loading.value = false;
  }
}

onMounted(load);

async function submitPermohonan() {
  if (!akaunId.value) {
    toast.error("Ralat", "Sila pilih akaun pembiayaan.");
    return;
  }
  submitting.value = true;
  try {
    await createPenjadualan({
      akaunId: Number(akaunId.value),
      jenis: jenis.value,
      tempohBulan: tempohBulan.value,
      tarikhKuatKuasa: tarikhKuatKuasa.value,
      sebab: sebab.value.trim() || undefined,
    });
    toast.success("Permohonan Dihantar", "Menunggu kelulusan pegawai.");
    sebab.value = "";
    await load();
  } catch (e) {
    toast.error("Gagal", e instanceof Error ? e.message : undefined);
  } finally {
    submitting.value = false;
  }
}

async function handleApprove(item: PermohonanPenjadualan, tindakan: "lulus" | "tolak") {
  if (!item._dbId) return;
  try {
    await approvePenjadualan(item._dbId, tindakan);
    toast.success(tindakan === "lulus" ? "Diluluskan" : "Ditolak", item.rujukan);
    await load();
  } catch (e) {
    toast.error("Gagal", e instanceof Error ? e.message : undefined);
  }
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="Penjadualan Semula & Penstrukturan"
        :breadcrumb="[
          { label: 'Pembiayaan', to: '/admin/pembiayaan/akaun' },
          { label: 'Penjadualan Semula' },
        ]"
      />

      <div class="grid gap-4 lg:grid-cols-2">
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <h2 class="text-sm font-semibold text-slate-900">Permohonan Baharu</h2>
          <p class="mt-1 text-xs text-slate-500">Moratorium, penjadualan semula, atau penstrukturan pembiayaan.</p>

          <div class="mt-4 space-y-3">
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">Akaun Pembiayaan</label>
              <select
                v-model="akaunId"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
              >
                <option value="">Pilih akaun...</option>
                <option v-for="a in akaunList" :key="a._dbId" :value="a._dbId">
                  {{ a.id }} — {{ a.nama }} ({{ a.status }})
                </option>
              </select>
            </div>

            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">Jenis</label>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="opt in jenisOptions"
                  :key="opt.value"
                  type="button"
                  class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium"
                  :class="jenis === opt.value ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600'"
                  @click="jenis = opt.value as typeof jenis"
                >
                  <component :is="opt.icon" class="h-3.5 w-3.5" />
                  {{ opt.label }}
                </button>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Tempoh (bulan)</label>
                <input v-model.number="tempohBulan" type="number" min="1" max="120" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Tarikh Kuat Kuasa</label>
                <input v-model="tarikhKuatKuasa" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
              </div>
            </div>

            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">Sebab / Justifikasi</label>
              <textarea
                v-model="sebab"
                rows="3"
                class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                placeholder="Contoh: Bencana banjir, sakit kronik, force majeure..."
              />
            </div>

            <button
              type="button"
              class="w-full rounded-lg bg-blue-700 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 disabled:opacity-50"
              :disabled="submitting"
              @click="submitPermohonan"
            >
              Hantar Permohonan
            </button>
          </div>
        </article>

        <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="border-b border-slate-100 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-900">Menunggu Kelulusan</h2>
            <p class="text-xs text-slate-500">{{ pending.length }} permohonan</p>
          </div>
          <div v-if="loading" class="p-6 text-center text-sm text-slate-400">Memuatkan...</div>
          <ul v-else class="divide-y divide-slate-100">
            <li v-for="item in pending" :key="item.rujukan" class="space-y-2 px-4 py-3">
              <div class="flex items-start justify-between gap-2">
                <div>
                  <p class="font-mono text-xs text-slate-500">{{ item.rujukan }}</p>
                  <p class="text-sm font-medium text-slate-900">{{ item.nama }}</p>
                  <p class="text-xs text-slate-500">{{ item.noAkaun }} · {{ jenisLabel(item.jenis) }}</p>
                  <p v-if="item.sebab" class="mt-1 text-xs text-slate-600">{{ item.sebab }}</p>
                </div>
                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">Menunggu</span>
              </div>
              <div class="flex gap-2">
                <button
                  type="button"
                  class="flex flex-1 items-center justify-center gap-1 rounded-lg bg-emerald-600 px-2 py-1.5 text-xs font-medium text-white hover:bg-emerald-700"
                  @click="handleApprove(item, 'lulus')"
                >
                  <Check class="h-3.5 w-3.5" /> Lulus
                </button>
                <button
                  type="button"
                  class="flex flex-1 items-center justify-center gap-1 rounded-lg border border-rose-200 px-2 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50"
                  @click="handleApprove(item, 'tolak')"
                >
                  <X class="h-3.5 w-3.5" /> Tolak
                </button>
              </div>
            </li>
            <li v-if="pending.length === 0" class="px-4 py-8 text-center text-sm text-slate-400">
              Tiada permohonan menunggu kelulusan.
            </li>
          </ul>
        </article>
      </div>

      <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-3">
          <h2 class="text-sm font-semibold text-slate-900">Sejarah Permohonan</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-slate-100 text-left">
                <th class="px-4 py-2 text-xs font-semibold uppercase text-slate-500">Rujukan</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase text-slate-500">Akaun</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase text-slate-500">Jenis</th>
                <th class="px-4 py-2 text-xs font-semibold uppercase text-slate-500">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="item in permohonanList" :key="item.rujukan">
                <td class="px-4 py-2 font-mono text-xs">{{ item.rujukan }}</td>
                <td class="px-4 py-2">{{ item.noAkaun }} — {{ item.nama }}</td>
                <td class="px-4 py-2">{{ jenisLabel(item.jenis) }}</td>
                <td class="px-4 py-2">
                  <span
                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                    :class="item.status === 'diluluskan' ? 'bg-emerald-100 text-emerald-700' : item.status === 'ditolak' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700'"
                  >
                    {{ item.status }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </article>
    </div>
  </AdminLayout>
</template>
