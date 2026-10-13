<script setup lang="ts">
import { reactive, ref } from "vue";
import { Lightbulb, QrCode, Store, Wallet, Zap } from "lucide-vue-next";

import PCard from "@/components/pemohon/ui/PCard.vue";
import PField from "@/components/pemohon/ui/PField.vue";
import { extractPemohonPenyata } from "@/api/pemohonPermohonan";
import { useToast } from "@/composables/useToast";
import { SPPT_DOCUMENT_MAX_BYTES, SPPT_DOCUMENT_MAX_LABEL } from "@/config/sppt-upload";
import { pInputClass } from "@/utils/pemohon-ui";
import type { PpDataAlternatifJenis, PpEkstrakResult } from "@/types";
import DataAlternatifSection from "./DataAlternatifSection.vue";
import { errorMessage, mergeSection, sectionHasValue, type DataAltForm, type DataAltSectionKey } from "./permohonan-form";

/** `data` is the parent's reactive data-alternatif object — edited in place; the parent saves it. */
const props = defineProps<{ data: DataAltForm; ensureRecord: () => Promise<{ id: number; token: string } | null> }>();

const toast = useToast();

const PENYEDIA = ["Touch 'n Go eWallet", "GrabPay", "Boost", "ShopeePay", "MAE", "Lain-lain"];
const PLATFORM = ["Shopee", "Lazada", "TikTok Shop", "GrabFood", "foodpanda", "Facebook Marketplace"];
const TOGGLES = [
  { key: "ccrisTersedia", label: "Saya mempunyai rekod CCRIS" },
  { key: "penyataBankTersedia", label: "Saya boleh sediakan penyata bank" },
] as const;
const JENIS: Record<DataAltSectionKey, PpDataAlternatifJenis> = { ewallet: "ewallet", duitnowQr: "duitnow_qr", bilUtiliti: "bil_utiliti", platform: "platform" };

const open = reactive<Record<DataAltSectionKey, boolean>>({
  ewallet: sectionHasValue(props.data.ewallet),
  duitnowQr: sectionHasValue(props.data.duitnowQr),
  bilUtiliti: sectionHasValue(props.data.bilUtiliti),
  platform: sectionHasValue(props.data.platform),
});
const extracting = reactive<Record<DataAltSectionKey, boolean>>({ ewallet: false, duitnowQr: false, bilUtiliti: false, platform: false });
const results = ref<Partial<Record<DataAltSectionKey, PpEkstrakResult>>>({});

async function extract(key: DataAltSectionKey, file: File) {
  if (file.size > SPPT_DOCUMENT_MAX_BYTES) {
    toast.error(`Fail melebihi had ${SPPT_DOCUMENT_MAX_LABEL}`);
    return;
  }
  extracting[key] = true;
  try {
    const record = await props.ensureRecord();
    if (!record) return;
    const res = await extractPemohonPenyata(record.id, record.token, JENIS[key], file);
    results.value[key] = res.data;
    if (res.data.ok) {
      mergeSection(props.data[key] as Record<string, unknown>, res.data.data ?? null);
      open[key] = true;
      toast.success("Penyata dibaca", "Nilai purata bulanan telah diisi. Sila semak.");
    } else {
      toast.error("Penyata tidak dapat dibaca", res.data.message || "Cuba fail yang lebih jelas.");
    }
  } catch (e) {
    toast.error("Gagal membaca penyata", errorMessage(e));
  } finally {
    extracting[key] = false;
  }
}

const filled = (key: DataAltSectionKey) => sectionHasValue(props.data[key] as Record<string, unknown>);
</script>

<template>
  <PCard>
    <template #header>
      <div class="flex min-w-0 items-start gap-3">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600"><Lightbulb class="h-5 w-5" /></span>
        <div>
          <h2 class="text-[15px] font-semibold text-ink-900">Data Alternatif <span class="ml-1 text-xs font-medium text-slate-400">Pilihan</span></h2>
          <p class="mt-0.5 text-sm text-slate-500">Tiada penyata bank atau rekod CCRIS? Data jualan digital anda boleh membantu.</p>
        </div>
      </div>
    </template>

    <div class="space-y-3">
      <DataAlternatifSection
        id="ewallet"
        v-model:open="open.ewallet"
        title="e-Dompet"
        description="Touch 'n Go, GrabPay, Boost, ShopeePay, MAE"
        :icon="Wallet"
        :filled="filled('ewallet')"
        :extracting="extracting.ewallet"
        :ekstrak="results.ewallet ?? null"
        @upload="extract('ewallet', $event)"
      >
        <div class="grid gap-4 sm:grid-cols-2">
          <PField label="Penyedia" for="da-ew-penyedia">
            <select id="da-ew-penyedia" v-model="data.ewallet.penyedia" :class="pInputClass">
              <option :value="null">Pilih penyedia</option>
              <option v-for="p in PENYEDIA" :key="p" :value="p">{{ p }}</option>
            </select>
          </PField>
          <PField label="Purata wang masuk sebulan (RM)" for="da-ew-masuk">
            <input id="da-ew-masuk" v-model.number="data.ewallet.purataMasukBulanan" type="number" min="0" inputmode="decimal" :class="pInputClass" />
          </PField>
          <PField label="Transaksi sebulan" for="da-ew-tx">
            <input id="da-ew-tx" v-model.number="data.ewallet.bilanganTransaksiBulanan" type="number" min="0" inputmode="numeric" :class="pInputClass" />
          </PField>
          <PField label="Bulan aktif" for="da-ew-bulan">
            <input id="da-ew-bulan" v-model.number="data.ewallet.bulanAktif" type="number" min="0" max="120" inputmode="numeric" :class="pInputClass" />
          </PField>
        </div>
      </DataAlternatifSection>

      <DataAlternatifSection
        id="duitnow"
        v-model:open="open.duitnowQr"
        title="DuitNow QR"
        description="Jualan melalui kod QR kedai anda"
        :icon="QrCode"
        :filled="filled('duitnowQr')"
        :extracting="extracting.duitnowQr"
        :ekstrak="results.duitnowQr ?? null"
        @upload="extract('duitnowQr', $event)"
      >
        <div class="grid gap-4 sm:grid-cols-2">
          <PField label="Purata jualan sebulan (RM)" for="da-qr-jualan">
            <input id="da-qr-jualan" v-model.number="data.duitnowQr.purataJualanBulanan" type="number" min="0" inputmode="decimal" :class="pInputClass" />
          </PField>
          <PField label="Transaksi sebulan" for="da-qr-tx">
            <input id="da-qr-tx" v-model.number="data.duitnowQr.bilanganTransaksiBulanan" type="number" min="0" inputmode="numeric" :class="pInputClass" />
          </PField>
          <PField label="Bulan aktif" for="da-qr-bulan">
            <input id="da-qr-bulan" v-model.number="data.duitnowQr.bulanAktif" type="number" min="0" max="120" inputmode="numeric" :class="pInputClass" />
          </PField>
          <PField label="Trend pertumbuhan (%)" for="da-qr-trend" hint="Cth: 8 bermaksud jualan naik 8% sebulan.">
            <input id="da-qr-trend" v-model.number="data.duitnowQr.trendPertumbuhanPeratus" type="number" min="-100" max="1000" step="0.1" :class="pInputClass" />
          </PField>
        </div>
      </DataAlternatifSection>

      <DataAlternatifSection
        id="utiliti"
        v-model:open="open.bilUtiliti"
        title="Bil utiliti"
        description="Rekod bayaran bil elektrik, air atau telefon"
        :icon="Zap"
        :filled="filled('bilUtiliti')"
        :extracting="extracting.bilUtiliti"
        :ekstrak="results.bilUtiliti ?? null"
        @upload="extract('bilUtiliti', $event)"
      >
        <div class="grid gap-4 sm:grid-cols-2">
          <PField label="Bulan dibayar tepat pada masa" for="da-ut-tepat">
            <input id="da-ut-tepat" v-model.number="data.bilUtiliti.bulanDibayarTepat" type="number" min="0" max="120" inputmode="numeric" :class="pInputClass" />
          </PField>
          <PField label="Daripada (bulan dinilai)" for="da-ut-nilai">
            <input id="da-ut-nilai" v-model.number="data.bilUtiliti.bulanDinilai" type="number" min="0" max="120" inputmode="numeric" :class="pInputClass" />
          </PField>
        </div>
      </DataAlternatifSection>

      <DataAlternatifSection
        id="platform"
        v-model:open="open.platform"
        title="Platform jualan dalam talian"
        description="Shopee, Lazada, TikTok Shop, GrabFood dan lain-lain"
        :icon="Store"
        :filled="filled('platform')"
        :extracting="extracting.platform"
        :ekstrak="results.platform ?? null"
        @upload="extract('platform', $event)"
      >
        <div class="grid gap-4 sm:grid-cols-2">
          <PField label="Nama platform" for="da-pf-nama">
            <input id="da-pf-nama" v-model="data.platform.nama" type="text" list="da-pf-list" :class="pInputClass" placeholder="cth: Shopee" />
            <datalist id="da-pf-list"><option v-for="p in PLATFORM" :key="p" :value="p" /></datalist>
          </PField>
          <PField label="Purata jualan sebulan (RM)" for="da-pf-jualan">
            <input id="da-pf-jualan" v-model.number="data.platform.purataJualanBulanan" type="number" min="0" inputmode="decimal" :class="pInputClass" />
          </PField>
          <PField label="Penarafan kedai (0–5)" for="da-pf-rating">
            <input id="da-pf-rating" v-model.number="data.platform.penarafan" type="number" min="0" max="5" step="0.1" inputmode="decimal" :class="pInputClass" />
          </PField>
          <PField label="Bulan aktif" for="da-pf-bulan">
            <input id="da-pf-bulan" v-model.number="data.platform.bulanAktif" type="number" min="0" max="120" inputmode="numeric" :class="pInputClass" />
          </PField>
        </div>
      </DataAlternatifSection>

      <div class="grid gap-2 pt-2 sm:grid-cols-2">
        <label
          v-for="t in TOGGLES"
          :key="t.key"
          class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-slate-200 px-3.5 py-3 text-sm text-ink-800 hover:bg-slate-50"
        >
          {{ t.label }}
          <span class="relative inline-flex shrink-0">
            <input v-model="data[t.key]" type="checkbox" role="switch" class="peer sr-only" />
            <span class="h-6 w-11 rounded-full bg-slate-200 transition-colors peer-checked:bg-brand-600 peer-focus-visible:ring-4 peer-focus-visible:ring-brand-100" />
            <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform peer-checked:translate-x-5" />
          </span>
        </label>
      </div>
    </div>
  </PCard>
</template>
