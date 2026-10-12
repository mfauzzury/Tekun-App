<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, computed, onMounted } from "vue";
import { CreditCard, Smartphone, RefreshCw, Banknote, QrCode, Upload } from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import { useToast } from "@/composables/useToast";
import { createBatchBayaran, createBayaran, listAkaun, listBayaran, fetchSpptModuleDatasets, pickSpptModuleDataset } from "@/api/sppt";
import { PAYMENT_CHANNELS } from "@/data/bayaran-pembiayaan-dummy";
import type { TransaksiBayaran } from "@/types";

const { tp } = useI18n();
const toast = useToast();

const paymentChannels = ref<(typeof PAYMENT_CHANNELS)[number][]>([]);
const recentTx = ref<TransaksiBayaran[]>([]);
const mode = ref<"realtime" | "batch">("realtime");

type ChannelId = "fpx" | "jompay" | "ewallet" | "auto-debit" | "kaunter";

const activeChannel = ref<ChannelId>("fpx");

const channels = [
  { id: "fpx" as const, label: "FPX / TEKUNPay", icon: CreditCard, saluran: "fpx" },
  { id: "jompay" as const, label: "JomPAY", icon: QrCode, saluran: "jompay" },
  { id: "ewallet" as const, label: "e-Wallet", icon: Smartphone, saluran: "ewallet" },
  { id: "auto-debit" as const, label: "Auto Debit", icon: RefreshCw, saluran: "auto_debit" },
  { id: "kaunter" as const, label: "Kaunter", icon: Banknote, saluran: "kaunter" },
];

const fpxBank = ref("");
const ewalletOption = ref("");
const noAkaun = ref("");
const noIC = ref("");
const jumlah = ref("");
const rujukan = ref("");
const submitting = ref(false);

const batchSaluran = ref("jompay");
const batchCsv = ref("");
const batchResult = ref("");

const fpxBanks = computed(() => paymentChannels.value.find((c) => c.id === "fpx")?.banks ?? []);
const ewalletOptions = computed(() => paymentChannels.value.find((c) => c.id === "ewallet")?.options ?? []);
const activeSaluran = computed(() => channels.find((c) => c.id === activeChannel.value)?.saluran ?? "fpx");

async function loadRecent() {
  const res = await listBayaran({ limit: 5 });
  recentTx.value = res.data;
}

onMounted(async () => {
  const data = await fetchSpptModuleDatasets("pembayaran");
  paymentChannels.value = pickSpptModuleDataset(data, "payment_channels", [] as typeof paymentChannels.value);
  await loadRecent();
});

async function resolveAkaunDbId(): Promise<number | null> {
  const q = noAkaun.value.trim();
  if (!q) return null;
  const res = await listAkaun({ q, limit: 10 });
  const match = res.data.find((a) => String(a.id).toLowerCase() === q.toLowerCase()) ?? res.data[0];
  return match?._dbId ?? null;
}

async function submitPayment() {
  const dbId = await resolveAkaunDbId();
  const amount = parseFloat(jumlah.value);
  if (!dbId || !amount || amount <= 0) {
    toast.error("Ralat", "Sila isi no. akaun dan jumlah yang sah.");
    return;
  }

  submitting.value = true;
  try {
    await createBayaran({
      akaunId: dbId,
      jumlah: amount,
      saluran: activeSaluran.value as "fpx" | "jompay" | "kaunter" | "auto_debit" | "ewallet",
      noRujukanLuar: rujukan.value.trim() || (activeChannel.value === "fpx" ? `FPX-${Date.now()}` : undefined),
    });
    toast.success("Bayaran Direkod", "Akaun dikemaskini secara real-time.");
    jumlah.value = "";
    rujukan.value = "";
    await loadRecent();
  } catch (e) {
    toast.error("Gagal", e instanceof Error ? e.message : undefined);
  } finally {
    submitting.value = false;
  }
}

async function submitBatch() {
  const lines = batchCsv.value.trim().split("\n").filter(Boolean);
  const rows = lines.map((line) => {
    const [noAkaunVal, jumlahVal, ruj] = line.split(",").map((s) => s.trim());
    return { noAkaun: noAkaunVal, jumlah: parseFloat(jumlahVal), noRujukanLuar: ruj };
  }).filter((r) => r.noAkaun && r.jumlah > 0);

  if (rows.length === 0) {
    toast.error("Ralat", "Format CSV: no_akaun,jumlah,rujukan (satu baris setiap transaksi)");
    return;
  }

  submitting.value = true;
  try {
    const res = await createBatchBayaran(batchSaluran.value, rows);
    batchResult.value = `${res.data.rujukan}: ${res.data.jumlahBerjaya}/${res.data.jumlahRekod} berjaya — ${res.data.jumlahKeseluruhan.toLocaleString()} RM`;
    toast.success("Batch Diproses", batchResult.value);
    batchCsv.value = "";
    await loadRecent();
  } catch (e) {
    toast.error("Gagal", e instanceof Error ? e.message : undefined);
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="Terima Bayaran"
        :breadcrumb="[{ label: 'Pembayaran', to: '/admin/pembayaran' }, { label: 'Terima Bayaran' }]"
      />

      <div class="flex gap-2">
        <button
          type="button"
          class="rounded-lg px-3 py-1.5 text-sm font-medium"
          :class="mode === 'realtime' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600'"
          @click="mode = 'realtime'"
        >
          Real-time
        </button>
        <button
          type="button"
          class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium"
          :class="mode === 'batch' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600'"
          @click="mode = 'batch'"
        >
          <Upload class="h-4 w-4" /> Batch Processing
        </button>
      </div>

      <div v-if="mode === 'realtime'" class="grid gap-4 lg:grid-cols-3">
        <article class="rounded-lg border border-slate-200 bg-white shadow-sm lg:col-span-2">
          <div class="border-b border-slate-100 px-4 py-3">
            <div class="flex flex-wrap gap-2">
              <button
                v-for="ch in channels"
                :key="ch.id"
                type="button"
                class="flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-medium transition-colors"
                :class="activeChannel === ch.id ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                @click="activeChannel = ch.id"
              >
                <component :is="ch.icon" class="h-4 w-4" />
                {{ ch.label }}
              </button>
            </div>
          </div>
          <div class="space-y-4 p-4">
            <div v-if="activeChannel === 'fpx'" class="space-y-3">
              <p class="text-sm text-slate-600">Integrasi FPX melalui TEKUNPay — kemas kini baki real-time selepas pengesahan.</p>
              <select v-model="fpxBank" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Pilih bank...</option>
                <option v-for="b in fpxBanks" :key="b" :value="b">{{ b }}</option>
              </select>
            </div>
            <div v-else-if="activeChannel === 'jompay'" class="space-y-3">
              <p class="text-sm text-slate-600">Biller Code TEKUN — transaksi JomPAY direkod automatik melalui API.</p>
            </div>
            <div v-else-if="activeChannel === 'ewallet'" class="space-y-3">
              <select v-model="ewalletOption" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">Pilih e-Wallet...</option>
                <option v-for="o in ewalletOptions" :key="o" :value="o">{{ o }}</option>
              </select>
            </div>
            <div v-else-if="activeChannel === 'auto-debit'" class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
              e-Mandate: potongan automatik bulanan. Status direkod real-time.
            </div>
            <div v-else class="text-sm text-slate-600">Bayaran kaunter fizikal — resit dan kemaskini baki serta-merta.</div>

            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">No. Akaun Pembiayaan</label>
              <input v-model="noAkaun" type="text" placeholder="TEK-KL-2024-0001" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
            </div>
            <div v-if="activeChannel === 'kaunter'">
              <label class="mb-1 block text-xs font-medium text-slate-600">No. IC</label>
              <input v-model="noIC" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">Jumlah (RM)</label>
              <input v-model="jumlah" type="number" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">No. Rujukan Luar</label>
              <input v-model="rujukan" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
            </div>

            <button
              class="w-full rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 disabled:opacity-50"
              :disabled="submitting"
              @click="submitPayment"
            >
              Rekod Bayaran (Real-time)
            </button>
          </div>
        </article>

        <aside class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <h3 class="text-sm font-semibold text-slate-900">Bayaran Terkini (API)</h3>
          <ul class="mt-3 space-y-2">
            <li v-for="p in recentTx" :key="p.rujukan" class="text-sm">
              <div class="flex justify-between">
                <span class="text-slate-600">{{ p.nama ?? p.noAkaun }}</span>
                <span class="font-medium">RM {{ p.jumlah.toLocaleString() }}</span>
              </div>
              <p class="text-xs text-slate-400">{{ p.saluran }} · {{ p.mod }}</p>
            </li>
          </ul>
        </aside>
      </div>

      <article v-else class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <h2 class="text-sm font-semibold text-slate-900">Pemprosesan Batch</h2>
        <p class="mt-1 text-xs text-slate-500">Muat naik fail transaksi dari saluran luar (JomPAY, FPX, auto-debit).</p>
        <div class="mt-4 grid gap-4 lg:grid-cols-2">
          <div class="space-y-3">
            <label class="block text-xs font-medium text-slate-600">Saluran</label>
            <select v-model="batchSaluran" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
              <option value="jompay">JomPAY</option>
              <option value="fpx">FPX</option>
              <option value="auto_debit">Auto Debit</option>
              <option value="kaunter">Kaunter</option>
            </select>
            <label class="block text-xs font-medium text-slate-600">Data CSV (no_akaun,jumlah,rujukan)</label>
            <textarea
              v-model="batchCsv"
              rows="8"
              class="w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-xs"
              placeholder="TEK-KL-2024-0001,2500,JOMPAY001&#10;TEK-SHA-2023-0045,1500,JOMPAY002"
            />
            <button
              class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
              :disabled="submitting"
              @click="submitBatch"
            >
              Proses Batch
            </button>
          </div>
          <div v-if="batchResult" class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
            {{ batchResult }}
          </div>
        </div>
      </article>
    </div>
  </AdminLayout>
</template>
