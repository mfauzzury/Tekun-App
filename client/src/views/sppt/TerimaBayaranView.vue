<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref, computed } from "vue";
import { CreditCard, Smartphone, RefreshCw, Banknote } from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import SpptPageHeader from "@/components/sppt/SpptPageHeader.vue";
import { PAYMENT_CHANNELS, BAYARAN_ITEMS } from "@/data/bayaran-pembiayaan-dummy";

const { t, tp } = useI18n();

const activeChannel = ref<"fpx" | "ewallet" | "auto-debit" | "kaunter">("fpx");

const channels = [
  { id: "fpx" as const, label: "FPX / TEKUNPay", icon: CreditCard },
  { id: "ewallet" as const, label: "e-Wallet", icon: Smartphone },
  { id: "auto-debit" as const, label: "Auto Debit", icon: RefreshCw },
  { id: "kaunter" as const, label: "Kaunter", icon: Banknote },
];

const fpxBank = ref("");
const ewalletOption = ref("");
const noAkaun = ref("");
const noIC = ref("");
const jumlah = ref("");
const rujukan = ref("");

const recentPayments = computed(() => BAYARAN_ITEMS.slice(0, 5));

const fpxBanks = PAYMENT_CHANNELS.find((c) => c.id === "fpx")?.banks ?? [];
const ewalletOptions = PAYMENT_CHANNELS.find((c) => c.id === "ewallet")?.options ?? [];

function submitPayment() {
  alert("Bayaran direkod (dummy). Tiada sambungan API.");
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-7xl space-y-4">
      <SpptPageHeader
        title="Terima Bayaran"
        :breadcrumb="[{ label: 'Pembayaran', to: '/admin/pembayaran' }, { label: 'Terima Bayaran' }]"
      />

      <div class="grid gap-4 lg:grid-cols-3">
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
            <!-- FPX / TEKUNPay -->
            <div v-if="activeChannel === 'fpx'" class="space-y-3">
              <p class="text-sm text-slate-600">Integrasi FPX melalui TEKUNPay. Pilih bank untuk redirect.</p>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Bank</label>
                <select
                  v-model="fpxBank"
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                >
                  <option value="">Pilih bank...</option>
                  <option v-for="b in fpxBanks" :key="b" :value="b">{{ b }}</option>
                </select>
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">No. Akaun Pembiayaan</label>
                <input
                  v-model="noAkaun"
                  type="text"
                  placeholder="A-001"
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Jumlah (RM)</label>
                <input
                  v-model="jumlah"
                  type="number"
                  placeholder="2500"
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                />
              </div>
            </div>

            <!-- e-Wallet -->
            <div v-else-if="activeChannel === 'ewallet'" class="space-y-3">
              <p class="text-sm text-slate-600">Bayaran melalui e-Wallet (TNG, ShopeePay, Boost, GrabPay, MAE) atau DuitNow QR.</p>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">e-Wallet</label>
                <select
                  v-model="ewalletOption"
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                >
                  <option value="">Pilih e-Wallet...</option>
                  <option v-for="o in ewalletOptions" :key="o" :value="o">{{ o }}</option>
                </select>
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">No. Akaun Pembiayaan</label>
                <input
                  v-model="noAkaun"
                  type="text"
                  placeholder="A-001"
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Jumlah (RM)</label>
                <input
                  v-model="jumlah"
                  type="number"
                  placeholder="2500"
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                />
              </div>
            </div>

            <!-- Auto Debit -->
            <div v-else-if="activeChannel === 'auto-debit'" class="space-y-3">
              <p class="text-sm text-slate-600">Tetapan Auto Debit (e-Mandate): tarikh potongan, notifikasi prabayaran, log status.</p>
              <div class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
                Status: Aktif. Potongan bulanan: 10 haribulan. Notifikasi: 3 hari sebelum potongan.
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">No. Akaun Pembiayaan</label>
                <input
                  v-model="noAkaun"
                  type="text"
                  placeholder="A-001"
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Semak status / Log</label>
                <p class="text-xs text-slate-500">Paparan log berjaya/gagal, auto-terminate selepas 3 kegagalan.</p>
              </div>
            </div>

            <!-- Kaunter -->
            <div v-else-if="activeChannel === 'kaunter'" class="space-y-3">
              <p class="text-sm text-slate-600">Bayaran di kaunter (tunai / kad debit) melalui MAC. Resit dijana automatik.</p>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">No. IC</label>
                <input
                  v-model="noIC"
                  type="text"
                  placeholder="850101011234"
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">No. Akaun Pembiayaan</label>
                <input
                  v-model="noAkaun"
                  type="text"
                  placeholder="A-001"
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">Jumlah (RM)</label>
                <input
                  v-model="jumlah"
                  type="number"
                  placeholder="2500"
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-600">No. Rujukan</label>
                <input
                  v-model="rujukan"
                  type="text"
                  placeholder="TUN-001"
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                />
              </div>
            </div>

            <button
              class="w-full rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-slate-800"
              @click="submitPayment"
            >
              Rekod Bayaran
            </button>
          </div>
        </article>

        <aside class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <h3 class="text-sm font-semibold text-slate-900">Bayaran Terkini</h3>
          <ul class="mt-3 space-y-2">
            <li v-for="p in recentPayments" :key="p.id" class="flex justify-between text-sm">
              <span class="text-slate-600">{{ p.nama }}</span>
              <span class="font-medium">RM {{ p.jumlah.toLocaleString() }}</span>
            </li>
          </ul>
        </aside>
      </div>
    </div>
  </AdminLayout>
</template>
