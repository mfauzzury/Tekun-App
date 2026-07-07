<script setup lang="ts">
import { computed, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { ArrowLeft, CheckCircle2, CreditCard, Loader2 } from "lucide-vue-next";

import PemohonLayout from "@/layouts/PemohonLayout.vue";
import { useToast } from "@/composables/useToast";
import { usePemohonStore } from "@/stores/pemohon";
import { PAYMENT_CHANNELS, formatRM } from "@/data/pembiayaan-dummy";

const route = useRoute();
const router = useRouter();
const pemohon = usePemohonStore();
const toast = useToast();

const akaun = computed(() => pemohon.getPembiayaan(String(route.params.id)));

const amount = ref(0);
const channel = ref("fpx");
const subOption = ref("");
const processing = ref(false);
const receipt = ref<{ rujukan: string; tarikh: string; jumlah: number; kaedah: string; bakiBaru: number } | null>(null);

// default amount = arrears if any, else monthly installment
if (akaun.value) {
  amount.value = akaun.value.tunggakan > 0 ? akaun.value.tunggakan : akaun.value.bayaranBulanan;
}

const selectedChannel = computed(() => PAYMENT_CHANNELS.find((c) => c.id === channel.value));
const subOptions = computed(() => {
  const c = selectedChannel.value as { banks?: string[]; options?: string[] } | undefined;
  return c?.banks ?? c?.options ?? [];
});

const kaedahLabel = computed(() => {
  const base = selectedChannel.value?.label ?? "";
  return subOption.value ? `${base} (${subOption.value})` : base;
});

const canPay = computed(() => amount.value > 0 && !processing.value && !!akaun.value);

async function pay() {
  if (!akaun.value || amount.value <= 0) return;
  processing.value = true;
  await new Promise((resolve) => setTimeout(resolve, 1500));
  try {
    receipt.value = pemohon.makePayment(akaun.value.id, amount.value, kaedahLabel.value);
    toast.success("Bayaran Berjaya", `${formatRM(amount.value)} telah diterima.`);
  } catch (e) {
    toast.error("Bayaran Gagal", e instanceof Error ? e.message : undefined);
  } finally {
    processing.value = false;
  }
}
</script>

<template>
  <PemohonLayout>
    <div v-if="!akaun" class="mx-auto max-w-2xl rounded-xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500 shadow-sm">
      Akaun pembiayaan tidak dijumpai.
    </div>

    <div v-else class="mx-auto max-w-2xl space-y-5">
      <router-link :to="`/pemohon/pembiayaan/${akaun.id}`" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition-colors hover:text-slate-900">
        <ArrowLeft class="h-4 w-4" />
        Kembali ke Akaun
      </router-link>

      <!-- Success receipt -->
      <div v-if="receipt" class="rounded-xl border border-emerald-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col items-center text-center">
          <div class="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100">
            <CheckCircle2 class="h-8 w-8 text-emerald-600" />
          </div>
          <h1 class="mt-4 text-lg font-semibold text-slate-900">Bayaran Berjaya</h1>
          <p class="mt-1 text-sm text-slate-500">Resit bayaran anda</p>
        </div>
        <dl class="mt-5 space-y-2.5 border-t border-slate-100 pt-5 text-sm">
          <div class="flex justify-between"><dt class="text-slate-500">No. Rujukan</dt><dd class="font-medium text-slate-900">{{ receipt.rujukan }}</dd></div>
          <div class="flex justify-between"><dt class="text-slate-500">Tarikh</dt><dd class="font-medium text-slate-900">{{ receipt.tarikh }}</dd></div>
          <div class="flex justify-between"><dt class="text-slate-500">Kaedah</dt><dd class="font-medium text-slate-900">{{ receipt.kaedah }}</dd></div>
          <div class="flex justify-between"><dt class="text-slate-500">Jumlah Dibayar</dt><dd class="font-semibold text-emerald-600">{{ formatRM(receipt.jumlah) }}</dd></div>
          <div class="flex justify-between border-t border-slate-100 pt-2.5"><dt class="text-slate-500">Baki Baharu</dt><dd class="font-semibold text-slate-900">{{ formatRM(receipt.bakiBaru) }}</dd></div>
        </dl>
        <button
          type="button"
          class="mt-6 flex w-full items-center justify-center gap-2 rounded-md bg-blue-600 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-blue-700"
          @click="router.push(`/pemohon/pembiayaan/${akaun.id}`)"
        >
          Selesai
        </button>
      </div>

      <!-- Payment form -->
      <template v-else>
        <div class="rounded-2xl bg-gradient-to-br from-blue-700 to-blue-900 p-6 shadow-sm">
          <h1 class="text-xl font-semibold text-white">Buat Bayaran</h1>
          <p class="mt-1 text-sm text-blue-100">{{ akaun.produk }} · {{ akaun.id }}</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Jumlah Bayaran (RM)</label>
          <input
            v-model.number="amount"
            type="number"
            min="1"
            class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
          />
          <div class="mt-2 flex flex-wrap gap-2">
            <button
              type="button"
              class="rounded-full border border-slate-200 px-3 py-1 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-50"
              @click="amount = akaun.bayaranBulanan"
            >
              Ansuran Bulanan ({{ formatRM(akaun.bayaranBulanan) }})
            </button>
            <button
              v-if="akaun.tunggakan > 0"
              type="button"
              class="rounded-full border border-rose-200 px-3 py-1 text-xs font-medium text-rose-600 transition-colors hover:bg-rose-50"
              @click="amount = akaun.tunggakan"
            >
              Jelaskan Tunggakan ({{ formatRM(akaun.tunggakan) }})
            </button>
          </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
          <label class="mb-2 block text-sm font-medium text-slate-700">Kaedah Pembayaran</label>
          <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <button
              v-for="c in PAYMENT_CHANNELS"
              :key="c.id"
              type="button"
              class="flex items-center gap-2 rounded-md border px-3 py-2.5 text-left text-sm font-medium transition-colors"
              :class="channel === c.id ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
              @click="channel = c.id; subOption = ''"
            >
              <CreditCard class="h-4 w-4 shrink-0" />
              {{ c.label }}
            </button>
          </div>

          <div v-if="subOptions.length" class="mt-3">
            <label class="mb-1.5 block text-xs font-medium text-slate-500">Pilih Bank / e-Wallet</label>
            <select
              v-model="subOption"
              class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
            >
              <option value="">Pilih...</option>
              <option v-for="opt in subOptions" :key="opt" :value="opt">{{ opt }}</option>
            </select>
          </div>
        </div>

        <button
          type="button"
          class="flex w-full items-center justify-center gap-2 rounded-md bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-700 disabled:opacity-60"
          :disabled="!canPay"
          @click="pay"
        >
          <Loader2 v-if="processing" class="h-4 w-4 animate-spin" />
          <CreditCard v-else class="h-4 w-4" />
          {{ processing ? "Memproses bayaran..." : `Bayar ${formatRM(amount || 0)}` }}
        </button>
        <p class="text-center text-xs text-slate-400">Pembayaran adalah simulasi untuk tujuan demonstrasi.</p>
      </template>
    </div>
  </PemohonLayout>
</template>
