<script setup lang="ts">
import { computed, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { ArrowLeft, CheckCircle2, Loader2, Sparkles } from "lucide-vue-next";

import PemohonLayout from "@/layouts/PemohonLayout.vue";
import { useToast } from "@/composables/useToast";
import { usePemohonStore } from "@/stores/pemohon";
import { PAYMENT_CHANNELS, computeSettlement, formatRM } from "@/data/pembiayaan-dummy";

const route = useRoute();
const router = useRouter();
const pemohon = usePemohonStore();
const toast = useToast();

const akaun = computed(() => pemohon.getPembiayaan(String(route.params.id)));
const quote = computed(() => (akaun.value ? computeSettlement(akaun.value) : null));

const channel = ref("fpx");
const processing = ref(false);
const done = ref<{ rujukan: string; tarikh: string; amaunBersih: number; kaedah: string } | null>(null);

const channelLabel = computed(() => PAYMENT_CHANNELS.find((c) => c.id === channel.value)?.label ?? "");

async function settle() {
  if (!akaun.value) return;
  processing.value = true;
  await new Promise((resolve) => setTimeout(resolve, 1500));
  try {
    const result = pemohon.settleEarly(akaun.value.id, channelLabel.value);
    done.value = { rujukan: result.rujukan, tarikh: result.tarikh, amaunBersih: result.amaunBersih, kaedah: result.kaedah };
    toast.success("Penyelesaian Berjaya", "Pembiayaan anda kini selesai sepenuhnya.");
  } catch (e) {
    toast.error("Gagal", e instanceof Error ? e.message : undefined);
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

      <!-- Success -->
      <div v-if="done" class="rounded-xl border border-emerald-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col items-center text-center">
          <div class="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100">
            <CheckCircle2 class="h-8 w-8 text-emerald-600" />
          </div>
          <h1 class="mt-4 text-lg font-semibold text-slate-900">Pembiayaan Selesai</h1>
          <p class="mt-1 text-sm text-slate-500">Tahniah! Penyelesaian awal anda telah berjaya.</p>
        </div>
        <dl class="mt-5 space-y-2.5 border-t border-slate-100 pt-5 text-sm">
          <div class="flex justify-between"><dt class="text-slate-500">No. Rujukan</dt><dd class="font-medium text-slate-900">{{ done.rujukan }}</dd></div>
          <div class="flex justify-between"><dt class="text-slate-500">Tarikh</dt><dd class="font-medium text-slate-900">{{ done.tarikh }}</dd></div>
          <div class="flex justify-between"><dt class="text-slate-500">Kaedah</dt><dd class="font-medium text-slate-900">{{ done.kaedah }}</dd></div>
          <div class="flex justify-between"><dt class="text-slate-500">Amaun Diselesaikan</dt><dd class="font-semibold text-emerald-600">{{ formatRM(done.amaunBersih) }}</dd></div>
        </dl>
        <button
          type="button"
          class="mt-6 flex w-full items-center justify-center gap-2 rounded-md bg-blue-600 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-blue-700"
          @click="router.push(`/pemohon/pembiayaan/${akaun.id}`)"
        >
          Selesai
        </button>
      </div>

      <!-- Quote -->
      <template v-else-if="quote">
        <div class="rounded-2xl bg-gradient-to-br from-blue-700 to-blue-900 p-6 shadow-sm">
          <h1 class="text-xl font-semibold text-white">Penyelesaian Awal</h1>
          <p class="mt-1 text-sm text-blue-100">{{ akaun.produk }} · {{ akaun.id }}</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="mb-4 text-sm font-semibold text-slate-900">Pengiraan Penyelesaian</h2>
          <dl class="space-y-3 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">Baki Pinjaman</dt><dd class="font-medium text-slate-900">{{ formatRM(quote.bakiPinjaman) }}</dd></div>
            <div class="flex items-center justify-between text-emerald-600">
              <dt class="flex items-center gap-1"><Sparkles class="h-3.5 w-3.5" /> Rebat (2 bulan keuntungan)</dt>
              <dd class="font-medium">− {{ formatRM(quote.rebatDuaBulan) }}</dd>
            </div>
            <div class="flex justify-between border-t border-slate-200 pt-3">
              <dt class="font-semibold text-slate-900">Amaun Perlu Dibayar</dt>
              <dd class="text-lg font-bold text-blue-700">{{ formatRM(quote.amaunBersih) }}</dd>
            </div>
          </dl>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
          <label class="mb-2 block text-sm font-medium text-slate-700">Kaedah Pembayaran</label>
          <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <button
              v-for="c in PAYMENT_CHANNELS"
              :key="c.id"
              type="button"
              class="rounded-md border px-3 py-2.5 text-left text-sm font-medium transition-colors"
              :class="channel === c.id ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-slate-200 text-slate-600 hover:bg-slate-50'"
              @click="channel = c.id"
            >
              {{ c.label }}
            </button>
          </div>
        </div>

        <button
          type="button"
          class="flex w-full items-center justify-center gap-2 rounded-md bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-700 disabled:opacity-60"
          :disabled="processing"
          @click="settle"
        >
          <Loader2 v-if="processing" class="h-4 w-4 animate-spin" />
          <CheckCircle2 v-else class="h-4 w-4" />
          {{ processing ? "Memproses..." : `Bayar & Selesaikan (${formatRM(quote.amaunBersih)})` }}
        </button>
        <p class="text-center text-xs text-slate-400">Pembayaran adalah simulasi untuk tujuan demonstrasi.</p>
      </template>
    </div>
  </PemohonLayout>
</template>
