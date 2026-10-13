<script setup lang="ts">
import { computed, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { ArrowLeftRight, Check, CheckCircle2, CreditCard, FileText, Landmark, Lock, RefreshCw, Send, Smartphone, Sparkles, Store, Wallet } from "lucide-vue-next";
import type { Component } from "vue";

import PemohonLayout from "@/layouts/PemohonLayout.vue";
import PButton from "@/components/pemohon/ui/PButton.vue";
import PCard from "@/components/pemohon/ui/PCard.vue";
import PEmptyState from "@/components/pemohon/ui/PEmptyState.vue";
import PPageHeader from "@/components/pemohon/ui/PPageHeader.vue";
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

const CHANNEL_ICONS: Record<string, Component> = {
  fpx: Landmark,
  ewallet: Smartphone,
  "auto-debit": RefreshCw,
  kaunter: Store,
  eft: ArrowLeftRight,
  cek: FileText,
  kiriman: Send,
};

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
    <div v-if="!akaun" class="mx-auto max-w-2xl pt-6">
      <div class="rounded-2xl border border-slate-200/80 bg-white">
        <PEmptyState :icon="Wallet" title="Akaun pembiayaan tidak dijumpai" description="Akaun ini mungkin telah ditutup atau pautan tidak sah.">
          <PButton variant="outline" to="/pemohon/pembiayaan">Kembali ke Pembiayaan Saya</PButton>
        </PEmptyState>
      </div>
    </div>

    <div v-else class="mx-auto max-w-2xl space-y-5">
      <!-- Success -->
      <div v-if="done" class="pt-6">
        <PCard>
          <div class="flex flex-col items-center pt-2 text-center">
            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 ring-8 ring-emerald-50/60">
              <CheckCircle2 class="h-9 w-9 text-emerald-600" />
            </span>
            <h1 class="mt-5 text-xl font-bold tracking-tight text-ink-900">Pembiayaan selesai</h1>
            <p class="mt-1 text-sm text-slate-500">Tahniah! Penyelesaian awal anda telah berjaya.</p>
            <p class="mt-4 text-[32px] font-bold tracking-tight text-emerald-600">{{ formatRM(done.amaunBersih) }}</p>
          </div>
          <dl class="mt-6 space-y-3 rounded-xl bg-slate-50 p-4 text-sm">
            <div class="flex justify-between gap-4"><dt class="text-slate-500">No. rujukan</dt><dd class="font-mono font-medium text-ink-900">{{ done.rujukan }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Tarikh</dt><dd class="font-medium text-ink-900">{{ done.tarikh }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Kaedah</dt><dd class="text-right font-medium text-ink-900">{{ done.kaedah }}</dd></div>
            <div class="flex justify-between gap-4 border-t border-slate-200 pt-3"><dt class="font-medium text-ink-800">Amaun diselesaikan</dt><dd class="font-semibold text-emerald-600">{{ formatRM(done.amaunBersih) }}</dd></div>
          </dl>
          <PButton class="mt-6" block size="lg" @click="router.push(`/pemohon/pembiayaan/${akaun.id}`)">Selesai</PButton>
        </PCard>
      </div>

      <!-- Quote -->
      <template v-else-if="quote">
        <PPageHeader
          eyebrow="Penyelesaian awal"
          title="Selesaikan pembiayaan"
          :subtitle="`${akaun.produk} · ${akaun.id}`"
          :back="`/pemohon/pembiayaan/${akaun.id}`"
          back-label="Kembali ke akaun"
        />

        <PCard title="Pengiraan penyelesaian" subtitle="Nikmati rebat apabila menyelesaikan lebih awal.">
          <dl class="space-y-3 text-sm">
            <div class="flex justify-between gap-4">
              <dt class="text-slate-500">Baki pinjaman</dt>
              <dd class="font-medium text-ink-900">{{ formatRM(quote.bakiPinjaman) }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4 rounded-xl bg-emerald-50 px-3 py-2.5 text-emerald-700">
              <dt class="flex items-center gap-1.5"><Sparkles class="h-4 w-4" /> Rebat (2 bulan keuntungan)</dt>
              <dd class="font-semibold">− {{ formatRM(quote.rebatDuaBulan) }}</dd>
            </div>
            <div class="flex items-baseline justify-between gap-4 border-t border-slate-100 pt-3">
              <dt class="font-semibold text-ink-900">Amaun perlu dibayar</dt>
              <dd class="text-2xl font-bold tracking-tight text-ink-900">{{ formatRM(quote.amaunBersih) }}</dd>
            </div>
          </dl>
        </PCard>

        <PCard title="Kaedah pembayaran">
          <div role="radiogroup" aria-label="Kaedah pembayaran" class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
            <button
              v-for="c in PAYMENT_CHANNELS"
              :key="c.id"
              type="button"
              role="radio"
              :aria-checked="channel === c.id"
              class="relative flex items-center gap-3 rounded-xl border bg-white px-3.5 py-3 text-left transition-all focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-100"
              :class="channel === c.id ? 'border-brand-500 ring-2 ring-brand-500/20' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'"
              @click="channel = c.id"
            >
              <span
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                :class="channel === c.id ? 'bg-brand-50 text-brand-600' : 'bg-slate-100 text-slate-500'"
              >
                <component :is="CHANNEL_ICONS[c.id] ?? CreditCard" class="h-[18px] w-[18px]" />
              </span>
              <span class="min-w-0 flex-1 text-sm font-medium" :class="channel === c.id ? 'text-ink-900' : 'text-ink-700'">{{ c.label }}</span>
              <span
                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border transition-colors"
                :class="channel === c.id ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-300'"
              >
                <Check v-if="channel === c.id" class="h-3 w-3" stroke-width="3" />
              </span>
            </button>
          </div>
        </PCard>

        <div>
          <PButton block size="lg" :loading="processing" @click="settle">
            <CheckCircle2 v-if="!processing" class="h-4 w-4" />
            {{ processing ? "Memproses..." : `Bayar & selesaikan (${formatRM(quote.amaunBersih)})` }}
          </PButton>
          <p class="mt-3 flex items-center justify-center gap-1.5 text-center text-xs text-slate-400">
            <Lock class="h-3.5 w-3.5" />
            Pembayaran adalah simulasi untuk tujuan demonstrasi.
          </p>
        </div>
      </template>
    </div>
  </PemohonLayout>
</template>
