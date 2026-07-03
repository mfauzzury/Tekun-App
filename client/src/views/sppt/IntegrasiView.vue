<script setup lang="ts">
import { useI18n } from "@/composables/useI18n";
import { ref } from "vue";
import {
  ChevronRight,
  Plug,
  Building2,
  CreditCard,
  Landmark,
  Shield,
  Smartphone,
  FileCheck,
  MessageSquare,
  Server,
  Users,
  Wallet,
  Activity,
  MapPin,
  AlertCircle,
  CheckCircle2,
  XCircle,
  Save,
  RefreshCw,
} from "lucide-vue-next";
import { RouterLink } from "vue-router";
import AdminLayout from "@/layouts/AdminLayout.vue";

const { t, tp } = useI18n();

type IntegrationStatus = "active" | "disabled" | "pending" | "error";

interface Integration {
  id: string;
  name: string;
  description: string;
  specRef?: string;
  status: IntegrationStatus;
  endpoint?: string;
  lastSync?: string;
}

const externalIntegrations = ref<Integration[]>([
  { id: "jpn-mykad", name: "JPN / MyKad", description: "Pengesahan identiti pemohon (nama, IC, alamat, kewarganegaraan)", specRef: "10.1.1, 10.1.3", status: "pending" },
  { id: "ssm", name: "SSM", description: "Semakan pendaftaran syarikat, status aktif, pemilik/pengarah, jenis perniagaan", specRef: "10.1.4", status: "pending" },
  { id: "apk", name: "APK (EXPERIAN, CTOS, CCRIS)", description: "Rekod kredit pemohon: baki, tunggakan, NPF, skor kredit, litigasi", specRef: "10.1.5", status: "pending" },
  { id: "perkeso", name: "PERKESO", description: "Pengesahan status caruman dan perlindungan keselamatan sosial", specRef: "10.1.9", status: "pending" },
  { id: "fpx", name: "FPX", description: "Pembayaran melalui bank pilihan (CIMB, Maybank, Bank Islam, e-wallet)", specRef: "10.1.16", status: "pending" },
  { id: "portal-bank", name: "Portal Bank", description: "Bulk payment untuk pengeluaran dana", specRef: "10.1.17", status: "pending" },
  { id: "mygdx", name: "MyGDX (CIDB, PKK)", description: "Pertukaran data kerajaan; pengesahan kontraktor CIDB/PKK", specRef: "10.1.1, 10.1.11, 10.1.12", status: "pending" },
  { id: "bsas", name: "BSAS", description: "Tawarruq STP selepas kelulusan pembiayaan; sijil tawarruq", specRef: "10.1.6, 10.1.7", status: "pending" },
  { id: "takaful", name: "Takaful Malaysia", description: "Semakan status perlindungan takaful pemohon", specRef: "10.1.8", status: "pending" },
  { id: "wasiat", name: "Wasiat (MyAngkasa, Awaris)", description: "Pengesahan status pewarisan dan instrumen amanah", specRef: "10.1.10", status: "pending" },
  { id: "sme-corp", name: "SME Corp", description: "Pengesahan status PKS, pensijilan SME, kategori perusahaan", specRef: "10.1.13", status: "pending" },
  { id: "insolvensi", name: "Jabatan Insolvensi", description: "Pengesahan status kebankrapan pemohon", specRef: "10.1.14", status: "pending" },
  { id: "sms-whatsapp", name: "SMS / WhatsApp Blasting", description: "Penghantaran mesej pukal, laporan status penghantaran", specRef: "10.1.15", status: "pending" },
]);

const internalIntegrations = ref<Integration[]>([
  { id: "tekunpay", name: "TEKUNPay", description: "Maklumat bayaran real-time, penyata, surat NOD/selesai bayar/baki/saman", specRef: "10.2.2", status: "pending" },
  { id: "hrms", name: "HRMS", description: "Nama, jawatan, lokasi, staf ID, jadual kerja, cuti, kehadiran, GPK", specRef: "10.2.3", status: "pending" },
  { id: "grp", name: "GRP (Sistem Perakaunan)", description: "Entri automatik pengeluaran/bayaran balik, GL, jurnal pembetulan, audit trail", specRef: "10.2.4", status: "pending" },
  { id: "grp-snc", name: "GRP - Tagging SNC", description: "Pengasingan amaun tidak patuh syariah ke akaun SNC", specRef: "10.2.5", status: "pending" },
  { id: "grp-simpanan", name: "GRP - Simpanan Usahawan", description: "Pengasingan amaun simpanan ke akaun khas", specRef: "10.2.6", status: "pending" },
  { id: "pmgi", name: "PMGi", description: "Lawatan tapak, kutipan lapangan, pemantauan usahawan, KPI, GPS", specRef: "10.2.7–10.2.11", status: "pending" },
  { id: "emandate", name: "eMandate", description: "Direct Debit ke RHB, potongan ansuran, pendaftaran QR, 3 percubaan", specRef: "10.2.12", status: "pending" },
  { id: "gps-biometrik", name: "GPS / Biometrik", description: "Pengesahan lokasi lawatan pegawai lapangan", specRef: "10.2.13", status: "pending" },
  { id: "fems", name: "FeMS", description: "Sejarah interaksi, aduan, pertanyaan, tindakan susulan", specRef: "10.2.14", status: "pending" },
  { id: "spat", name: "SPAT", description: "Pertukaran data dua hala, status aduan teknikal TEKUN", specRef: "10.2.15", status: "pending" },
]);

const saving = ref(false);
const saved = ref(false);
const expandedId = ref<string | null>(null);

function statusClass(s: IntegrationStatus) {
  const map: Record<IntegrationStatus, string> = {
    active: "bg-emerald-100 text-emerald-800",
    disabled: "bg-slate-100 text-slate-600",
    pending: "bg-amber-100 text-amber-800",
    error: "bg-rose-100 text-rose-800",
  };
  return map[s] ?? "bg-slate-100 text-slate-600";
}

function statusLabel(s: IntegrationStatus) {
  const map: Record<IntegrationStatus, string> = {
    active: tp("Aktif"),
    disabled: tp("Dimatikan"),
    pending: tp("Belum dikonfigurasi"),
    error: tp("Ralat"),
  };
  return map[s] ?? s;
}

function statusIcon(s: IntegrationStatus) {
  if (s === "active") return CheckCircle2;
  if (s === "error") return XCircle;
  return AlertCircle;
}

function toggleExpand(id: string) {
  expandedId.value = expandedId.value === id ? null : id;
}

async function save() {
  saving.value = true;
  saved.value = false;
  // Simulate save - in production, call API
  await new Promise((r) => setTimeout(r, 600));
  saving.value = false;
  saved.value = true;
  setTimeout(() => (saved.value = false), 2000);
}

async function testConnection(id: string) {
  // Placeholder - in production, call API to test
  console.log("Test connection:", id);
}
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-4xl space-y-6">
      <!-- Breadcrumb -->
      <nav class="flex items-center gap-1 text-sm text-slate-500">
        <RouterLink to="/admin" class="hover:text-slate-700">{{ t("common.home") }}</RouterLink>
        <ChevronRight class="h-4 w-4 shrink-0 text-slate-300" />
        <RouterLink to="/admin/settings" class="hover:text-slate-700">{{ tp("Settings") }}</RouterLink>
        <ChevronRight class="h-4 w-4 shrink-0 text-slate-300" />
        <span class="text-slate-700">{{ tp("Integrasi") }}</span>
      </nav>

      <!-- Header -->
      <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 class="text-xl font-semibold text-slate-900">{{ tp("Integrasi Pihak Ketiga") }}</h1>
          <p class="mt-1 text-sm text-slate-600">{{ tp("Urus konfigurasi integrasi dengan sistem luaran dan dalaman SPPT.") }}</p>
        </div>
        <div class="flex items-center gap-2">
          <button
            class="flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50"
            @click="save"
            :disabled="saving"
          >
            <Save class="h-4 w-4" />
            {{ saving ? tp("Menyimpan...") : t("common.save") }}
          </button>
          <span v-if="saved" class="flex items-center gap-1.5 text-sm font-medium text-emerald-600">
            <CheckCircle2 class="h-4 w-4" />
            {{ tp("Disimpan") }}
          </span>
        </div>
      </div>

      <!-- External Systems -->
      <article class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3">
          <Building2 class="h-5 w-5 text-blue-600" />
          <h2 class="text-base font-semibold text-slate-900">Integrasi Sistem Luaran</h2>
        </div>
        <div class="divide-y divide-slate-100">
          <div
            v-for="int in externalIntegrations"
            :key="int.id"
            class="group"
          >
            <button
              class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition-colors hover:bg-slate-50"
              @click="toggleExpand(int.id)"
            >
              <div class="flex min-w-0 flex-1 items-start gap-3">
                <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100">
                  <Plug class="h-4 w-4 text-slate-600" />
                </div>
                <div class="min-w-0 flex-1">
                  <div class="flex flex-wrap items-center gap-2">
                    <span class="font-medium text-slate-900">{{ int.name }}</span>
                    <span
                      class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium"
                      :class="statusClass(int.status)"
                    >
                      <component :is="statusIcon(int.status)" class="h-3 w-3" />
                      {{ statusLabel(int.status) }}
                    </span>
                  </div>
                  <p class="mt-0.5 text-sm text-slate-600">{{ int.description }}</p>
                  <p v-if="int.specRef" class="mt-1 text-xs text-slate-400">Spesifikasi: {{ int.specRef }}</p>
                </div>
              </div>
              <ChevronRight
                class="h-5 w-5 shrink-0 text-slate-400 transition-transform"
                :class="{ 'rotate-90': expandedId === int.id }"
              />
            </button>
            <div v-show="expandedId === int.id" class="border-t border-slate-100 bg-slate-50/50 px-5 py-4">
              <div class="grid gap-3 sm:grid-cols-2">
                <div class="space-y-1.5">
                  <label class="text-xs font-medium text-slate-600">Endpoint / URL</label>
                  <input
                    v-model="int.endpoint"
                    type="text"
                    placeholder="https://api.example.com"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                  />
                </div>
                <div class="space-y-1.5">
                  <label class="text-xs font-medium text-slate-600">Status</label>
                  <select
                    v-model="int.status"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                  >
                    <option value="pending">Belum dikonfigurasi</option>
                    <option value="active">Aktif</option>
                    <option value="disabled">Dimatikan</option>
                    <option value="error">Ralat</option>
                  </select>
                </div>
                <div class="sm:col-span-2 flex items-center gap-2">
                  <button
                    class="flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"
                    @click.stop="testConnection(int.id)"
                  >
                    <RefreshCw class="h-4 w-4" />
                    Uji Sambungan
                  </button>
                  <span v-if="int.lastSync" class="text-xs text-slate-500">Sinkron terakhir: {{ int.lastSync }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </article>

      <!-- Internal Systems -->
      <article class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center gap-2 border-b border-slate-100 px-5 py-3">
          <Server class="h-5 w-5 text-violet-600" />
          <h2 class="text-base font-semibold text-slate-900">Integrasi Sistem Dalaman</h2>
        </div>
        <div class="divide-y divide-slate-100">
          <div
            v-for="int in internalIntegrations"
            :key="int.id"
            class="group"
          >
            <button
              class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition-colors hover:bg-slate-50"
              @click="toggleExpand(int.id)"
            >
              <div class="flex min-w-0 flex-1 items-start gap-3">
                <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-violet-50">
                  <Server class="h-4 w-4 text-violet-600" />
                </div>
                <div class="min-w-0 flex-1">
                  <div class="flex flex-wrap items-center gap-2">
                    <span class="font-medium text-slate-900">{{ int.name }}</span>
                    <span
                      class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium"
                      :class="statusClass(int.status)"
                    >
                      <component :is="statusIcon(int.status)" class="h-3 w-3" />
                      {{ statusLabel(int.status) }}
                    </span>
                  </div>
                  <p class="mt-0.5 text-sm text-slate-600">{{ int.description }}</p>
                  <p v-if="int.specRef" class="mt-1 text-xs text-slate-400">Spesifikasi: {{ int.specRef }}</p>
                </div>
              </div>
              <ChevronRight
                class="h-5 w-5 shrink-0 text-slate-400 transition-transform"
                :class="{ 'rotate-90': expandedId === int.id }"
              />
            </button>
            <div v-show="expandedId === int.id" class="border-t border-slate-100 bg-slate-50/50 px-5 py-4">
              <div class="grid gap-3 sm:grid-cols-2">
                <div class="space-y-1.5">
                  <label class="text-xs font-medium text-slate-600">Endpoint / URL</label>
                  <input
                    v-model="int.endpoint"
                    type="text"
                    placeholder="https://internal-api.tekun.gov.my/..."
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-200"
                  />
                </div>
                <div class="space-y-1.5">
                  <label class="text-xs font-medium text-slate-600">Status</label>
                  <select
                    v-model="int.status"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-200"
                  >
                    <option value="pending">Belum dikonfigurasi</option>
                    <option value="active">Aktif</option>
                    <option value="disabled">Dimatikan</option>
                    <option value="error">Ralat</option>
                  </select>
                </div>
                <div class="sm:col-span-2 flex items-center gap-2">
                  <button
                    class="flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100"
                    @click.stop="testConnection(int.id)"
                  >
                    <RefreshCw class="h-4 w-4" />
                    Uji Sambungan
                  </button>
                  <span v-if="int.lastSync" class="text-xs text-slate-500">Sinkron terakhir: {{ int.lastSync }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </article>

      <!-- Info note -->
      <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <p class="font-medium">Nota</p>
        <p class="mt-1 text-amber-700">
          Konfigurasi API keys, token dan credential sensitif hendaklah disimpan dalam environment variables atau vault.
          Pastikan endpoint dan sambungan diuji dalam persekitaran sandbox sebelum production.
        </p>
      </div>
    </div>
  </AdminLayout>
</template>
