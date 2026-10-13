<script setup lang="ts">
import { computed, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
  ArrowLeftRight,
  Check,
  CheckCircle2,
  CreditCard,
  FileText,
  Landmark,
  Lock,
  RefreshCw,
  Send,
  Smartphone,
  Store,
  Wallet,
} from "lucide-vue-next";
import type { Component } from "vue";

import PemohonLayout from "@/layouts/PemohonLayout.vue";
import PButton from "@/components/pemohon/ui/PButton.vue";
import PCard from "@/components/pemohon/ui/PCard.vue";
import PEmptyState from "@/components/pemohon/ui/PEmptyState.vue";
import PField from "@/components/pemohon/ui/PField.vue";
import PPageHeader from "@/components/pemohon/ui/PPageHeader.vue";
import { useToast } from "@/composables/useToast";
import { usePemohonStore } from "@/stores/pemohon";
import { PAYMENT_CHANNELS, formatRM } from "@/data/pembiayaan-dummy";
import { pInputClass } from "@/utils/pemohon-ui";

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

const CHANNEL_ICONS: Record<string, Component> = {
  fpx: Landmark,
  ewallet: Smartphone,
  "auto-debit": RefreshCw,
  kaunter: Store,
  eft: ArrowLeftRight,
  cek: FileText,
  kiriman: Send,
};

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
    <div v-if="!akaun" class="mx-auto max-w-2xl pt-6">
      <div class="rounded-2xl border border-slate-200/80 bg-white">
        <PEmptyState :icon="Wallet" title="Akaun pembiayaan tidak dijumpai" description="Akaun ini mungkin telah ditutup atau pautan tidak sah.">
          <PButton variant="outline" to="/pemohon/pembiayaan">Kembali ke Pembiayaan Saya</PButton>
        </PEmptyState>
      </div>
    </div>

    <div v-else class="mx-auto max-w-2xl space-y-5">
      <!-- Success receipt -->
      <div v-if="receipt" class="pt-6">
        <PCard>
          <div class="flex flex-col items-center pt-2 text-center">
            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 ring-8 ring-emerald-50/60">
              <CheckCircle2 class="h-9 w-9 text-emerald-600" />
            </span>
            <h1 class="mt-5 text-xl font-bold tracking-tight text-ink-900">Bayaran berjaya</h1>
            <p class="mt-1 text-sm text-slate-500">Terima kasih. Resit bayaran anda di bawah.</p>
            <p class="mt-4 text-[32px] font-bold tracking-tight text-emerald-600">{{ formatRM(receipt.jumlah) }}</p>
          </div>
          <dl class="mt-6 space-y-3 rounded-xl bg-slate-50 p-4 text-sm">
            <div class="flex justify-between gap-4"><dt class="text-slate-500">No. rujukan</dt><dd class="font-mono font-medium text-ink-900">{{ receipt.rujukan }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Tarikh</dt><dd class="font-medium text-ink-900">{{ receipt.tarikh }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Kaedah</dt><dd class="text-right font-medium text-ink-900">{{ receipt.kaedah }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Akaun</dt><dd class="font-medium text-ink-900">{{ akaun.id }}</dd></div>
            <div class="flex justify-between gap-4 border-t border-slate-200 pt-3"><dt class="font-medium text-ink-800">Baki baharu</dt><dd class="font-semibold text-ink-900">{{ formatRM(receipt.bakiBaru) }}</dd></div>
          </dl>
          <PButton class="mt-6" block size="lg" @click="router.push(`/pemohon/pembiayaan/${akaun.id}`)">Selesai</PButton>
        </PCard>
      </div>

      <!-- Payment form -->
      <template v-else>
        <PPageHeader
          eyebrow="Bayaran"
          title="Buat bayaran"
          :subtitle="`${akaun.produk} · ${akaun.id}`"
          :back="`/pemohon/pembiayaan/${akaun.id}`"
          back-label="Kembali ke akaun"
        />

        <!-- Summary -->
        <PCard tone="muted">
          <dl class="grid grid-cols-3 gap-3 text-center sm:text-left">
            <div>
              <dt class="text-xs text-slate-500">Ansuran bulanan</dt>
              <dd class="mt-0.5 text-sm font-semibold text-ink-900 sm:text-base">{{ formatRM(akaun.bayaranBulanan) }}</dd>
            </div>
            <div>
              <dt class="text-xs text-slate-500">Tunggakan</dt>
              <dd class="mt-0.5 text-sm font-semibold sm:text-base" :class="akaun.tunggakan > 0 ? 'text-rose-600' : 'text-ink-900'">{{ formatRM(akaun.tunggakan) }}</dd>
            </div>
            <div>
              <dt class="text-xs text-slate-500">Baki</dt>
              <dd class="mt-0.5 text-sm font-semibold text-ink-900 sm:text-base">{{ formatRM(akaun.bakiAkhir) }}</dd>
            </div>
          </dl>
        </PCard>

        <!-- Amount -->
        <PCard title="Jumlah bayaran">
          <label for="amaun-bayaran" class="sr-only">Jumlah bayaran (RM)</label>
          <div class="relative">
            <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-lg font-semibold text-slate-400">RM</span>
            <input
              id="amaun-bayaran"
              v-model.number="amount"
              type="number"
              min="1"
              inputmode="decimal"
              :class="[pInputClass, 'h-14 pl-14! text-2xl! font-bold tracking-tight']"
            />
          </div>
          <div class="mt-3 flex flex-wrap gap-2">
            <button
              type="button"
              class="rounded-full border px-3.5 py-1.5 text-xs font-medium transition-colors focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-100"
              :class="amount === akaun.bayaranBulanan ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50'"
              @click="amount = akaun.bayaranBulanan"
            >
              Ansuran bulanan · {{ formatRM(akaun.bayaranBulanan) }}
            </button>
            <button
              v-if="akaun.tunggakan > 0"
              type="button"
              class="rounded-full border px-3.5 py-1.5 text-xs font-medium transition-colors focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-rose-100"
              :class="amount === akaun.tunggakan ? 'border-rose-500 bg-rose-50 text-rose-700' : 'border-rose-200 text-rose-600 hover:bg-rose-50'"
              @click="amount = akaun.tunggakan"
            >
              Jelaskan tunggakan · {{ formatRM(akaun.tunggakan) }}
            </button>
          </div>
        </PCard>

        <!-- Channel -->
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
              @click="channel = c.id; subOption = ''"
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

          <PField v-if="subOptions.length" class="mt-4" label="Pilih bank / e-wallet" for="sub-option">
            <select id="sub-option" v-model="subOption" :class="pInputClass">
              <option value="">Pilih...</option>
              <option v-for="opt in subOptions" :key="opt" :value="opt">{{ opt }}</option>
            </select>
          </PField>
        </PCard>

        <!-- Total + CTA -->
        <PCard>
          <dl class="space-y-2.5 text-sm">
            <div class="flex justify-between gap-4"><dt class="text-slate-500">Kaedah</dt><dd class="text-right font-medium text-ink-900">{{ kaedahLabel }}</dd></div>
            <div class="flex items-baseline justify-between gap-4 border-t border-slate-100 pt-3">
              <dt class="font-semibold text-ink-900">Jumlah perlu dibayar</dt>
              <dd class="text-2xl font-bold tracking-tight text-ink-900">{{ formatRM(amount || 0) }}</dd>
            </div>
          </dl>
          <PButton class="mt-5" block size="lg" :loading="processing" :disabled="!canPay" @click="pay">
            <CreditCard v-if="!processing" class="h-4 w-4" />
            {{ processing ? "Memproses bayaran..." : `Bayar ${formatRM(amount || 0)}` }}
          </PButton>
          <p class="mt-3 flex items-center justify-center gap-1.5 text-center text-xs text-slate-400">
            <Lock class="h-3.5 w-3.5" />
            Pembayaran adalah simulasi untuk tujuan demonstrasi.
          </p>
        </PCard>
      </template>
    </div>
  </PemohonLayout>
</template>
