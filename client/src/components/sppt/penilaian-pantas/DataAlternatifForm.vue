<script setup lang="ts">
import { computed, reactive, ref, watch } from "vue";
import { Database, Info, Loader2, Save, Sparkles } from "lucide-vue-next";

import { extractPenilaianPantasDataAlternatif, updatePenilaianPantasDataAlternatif } from "@/api/sppt";
import { useToast } from "@/composables/useToast";
import type { PpDataAlternatif, PpDataAlternatifJenis, PpEkstrakResult } from "@/types";
import PpCard from "./PpCard.vue";

const props = defineProps<{ permohonanId: number; initial: PpDataAlternatif | null }>();
const emit = defineEmits<{ (e: "saved", data: PpDataAlternatif): void }>();
const toast = useToast();

type Num = number | string | null;
type FormState = {
  ewallet: { penyedia: string; purataMasukBulanan: Num; bilanganTransaksiBulanan: Num; bulanAktif: Num };
  duitnowQr: { purataJualanBulanan: Num; bilanganTransaksiBulanan: Num; bulanAktif: Num; trendPertumbuhanPeratus: Num };
  bilUtiliti: { bulanDibayarTepat: Num; bulanDinilai: Num };
  platform: { nama: string; purataJualanBulanan: Num; penarafan: Num; bulanAktif: Num };
  ccrisTersedia: boolean;
  penyataBankTersedia: boolean;
};
type SectionKey = "ewallet" | "duitnowQr" | "bilUtiliti" | "platform";

const SECTION_JENIS: Record<SectionKey, PpDataAlternatifJenis> = {
  ewallet: "ewallet",
  duitnowQr: "duitnow_qr",
  bilUtiliti: "bil_utiliti",
  platform: "platform",
};

const SECTIONS: SectionKey[] = ["ewallet", "duitnowQr", "bilUtiliti", "platform"];
const SECTION_LABEL: Record<SectionKey, string> = {
  ewallet: "E-wallet",
  duitnowQr: "DuitNow QR",
  bilUtiliti: "Bil utiliti",
  platform: "Platform e-dagang / penghantaran",
};

const PENYEDIA = ["Touch 'n Go eWallet", "GrabPay", "Boost", "ShopeePay", "MAE", "Lain-lain"];
const PLATFORM = ["Shopee", "Lazada", "GrabFood", "Foodpanda", "TikTok Shop", "Lain-lain"];

function blank(): FormState {
  return {
    ewallet: { penyedia: "", purataMasukBulanan: null, bilanganTransaksiBulanan: null, bulanAktif: null },
    duitnowQr: { purataJualanBulanan: null, bilanganTransaksiBulanan: null, bulanAktif: null, trendPertumbuhanPeratus: null },
    bilUtiliti: { bulanDibayarTepat: null, bulanDinilai: null },
    platform: { nama: "", purataJualanBulanan: null, penarafan: null, bulanAktif: null },
    ccrisTersedia: false,
    penyataBankTersedia: false,
  };
}

const form = reactive<FormState>(blank());
const saving = ref(false);
const extracting = ref<SectionKey | null>(null);
const ekstrak = ref<Partial<Record<SectionKey, PpEkstrakResult>>>({});
const savedOnce = ref(false);
const pendingSection = ref<SectionKey | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

function mergeSection(key: SectionKey, src?: Record<string, unknown> | null) {
  if (!src) return;
  const target = form[key] as Record<string, unknown>;
  for (const k of Object.keys(target)) {
    const v = src[k];
    if (v !== undefined && v !== null && v !== "") target[k] = v;
  }
}

watch(
  () => props.initial,
  (init) => {
    Object.assign(form, blank());
    if (!init) return;
    mergeSection("ewallet", init.ewallet ?? null);
    mergeSection("duitnowQr", init.duitnowQr ?? null);
    mergeSection("bilUtiliti", init.bilUtiliti ?? null);
    mergeSection("platform", init.platform ?? null);
    form.ccrisTersedia = !!init.ccrisTersedia;
    form.penyataBankTersedia = !!init.penyataBankTersedia;
  },
  { immediate: true },
);

const sumberCount = computed(
  () =>
    [form.ewallet.purataMasukBulanan, form.duitnowQr.purataJualanBulanan, form.bilUtiliti.bulanDinilai, form.platform.purataJualanBulanan].filter(
      (v) => v !== null && v !== "",
    ).length,
);

function n(v: Num): number | null {
  if (v === null || v === "") return null;
  const x = Number(v);
  return Number.isFinite(x) ? x : null;
}
function i(v: Num): number | null {
  const x = n(v);
  return x === null ? null : Math.round(x);
}

function payload(): PpDataAlternatif {
  const ewallet = { penyedia: form.ewallet.penyedia || null, purataMasukBulanan: n(form.ewallet.purataMasukBulanan), bilanganTransaksiBulanan: i(form.ewallet.bilanganTransaksiBulanan), bulanAktif: i(form.ewallet.bulanAktif) };
  const duitnowQr = { purataJualanBulanan: n(form.duitnowQr.purataJualanBulanan), bilanganTransaksiBulanan: i(form.duitnowQr.bilanganTransaksiBulanan), bulanAktif: i(form.duitnowQr.bulanAktif), trendPertumbuhanPeratus: n(form.duitnowQr.trendPertumbuhanPeratus) };
  const bilUtiliti = { bulanDibayarTepat: i(form.bilUtiliti.bulanDibayarTepat), bulanDinilai: i(form.bilUtiliti.bulanDinilai) };
  const platform = { nama: form.platform.nama || null, purataJualanBulanan: n(form.platform.purataJualanBulanan), penarafan: n(form.platform.penarafan), bulanAktif: i(form.platform.bulanAktif) };
  const empty = (o: Record<string, unknown>) => Object.values(o).every((v) => v === null);
  return {
    ewallet: empty(ewallet) ? null : ewallet,
    duitnowQr: empty(duitnowQr) ? null : duitnowQr,
    bilUtiliti: empty(bilUtiliti) ? null : bilUtiliti,
    platform: empty(platform) ? null : platform,
    ccrisTersedia: form.ccrisTersedia,
    penyataBankTersedia: form.penyataBankTersedia,
  };
}

async function save() {
  saving.value = true;
  try {
    const res = await updatePenilaianPantasDataAlternatif(props.permohonanId, payload());
    savedOnce.value = true;
    toast.success("Data alternatif disimpan", "Jalankan semula penilaian untuk mengemas kini skor kredit.");
    emit("saved", res?.data ?? payload());
  } catch (e: unknown) {
    toast.error("Simpan gagal", e instanceof Error ? e.message : undefined);
  } finally {
    saving.value = false;
  }
}

function pick(key: SectionKey) {
  pendingSection.value = key;
  fileInput.value?.click();
}

async function onFile(ev: Event) {
  const input = ev.target as HTMLInputElement;
  const file = input.files?.[0];
  input.value = "";
  const key = pendingSection.value;
  if (!file || !key) return;
  extracting.value = key;
  try {
    const res = await extractPenilaianPantasDataAlternatif(props.permohonanId, file, SECTION_JENIS[key]);
    const r = res?.data ?? null;
    if (r) {
      ekstrak.value = { ...ekstrak.value, [key]: r };
      mergeSection(key, r.data ?? null);
      if (r.ok === false) toast.error("Ekstrak tidak lengkap", r.message ?? undefined);
      else toast.success("Data diekstrak", "Semak nilai yang diisi, kemudian simpan.");
    }
  } catch (e: unknown) {
    toast.error("Ekstrak gagal", e instanceof Error ? e.message : undefined);
  } finally {
    extracting.value = null;
  }
}

const inputClass =
  "w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200";
const labelClass = "mb-1 block text-[11px] font-medium text-slate-500";
</script>

<template>
  <PpCard
    title="Data alternatif pemohon"
    :subtitle="`${sumberCount}/4 sumber diisi · e-wallet, DuitNow QR, bil utiliti, platform e-dagang`"
    :icon="Database"
    icon-class="text-sky-600"
    :default-open="false"
  >
    <template #actions>
      <button
        type="button"
        class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-slate-800 disabled:opacity-50"
        :disabled="saving"
        @click="save"
      >
        <Loader2 v-if="saving" class="h-3.5 w-3.5 animate-spin" />
        <Save v-else class="h-3.5 w-3.5" />
        Simpan
      </button>
    </template>

    <input ref="fileInput" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf,.csv,.txt" class="hidden" @change="onFile" />
    <p class="mb-3 flex items-center gap-1.5 rounded-md px-3 py-2 text-xs" :class="savedOnce ? 'bg-violet-50 font-medium text-violet-800' : 'bg-slate-50 text-slate-500'">
      <Info class="h-3.5 w-3.5" /> Simpan, kemudian jalankan semula penilaian.
    </p>

    <div class="grid gap-3 lg:grid-cols-2">
      <template v-for="sec in SECTIONS" :key="sec">
        <section class="rounded-lg border border-slate-200 p-3">
          <div class="mb-2 flex items-center gap-2">
            <p class="text-sm font-semibold text-slate-800">{{ SECTION_LABEL[sec] }}</p>
            <button
              type="button"
              class="ml-auto inline-flex items-center gap-1 rounded-md border border-violet-200 bg-violet-50 px-2 py-1 text-[11px] font-medium text-violet-700 hover:bg-violet-100 disabled:opacity-50"
              :disabled="extracting !== null"
              @click="pick(sec)"
            >
              <Loader2 v-if="extracting === sec" class="h-3 w-3 animate-spin" />
              <Sparkles v-else class="h-3 w-3" />
              Ekstrak daripada penyata (AI)
            </button>
          </div>

          <div v-if="sec === 'ewallet'" class="grid grid-cols-2 gap-2">
            <label class="col-span-2"><span :class="labelClass">Penyedia</span>
              <select v-model="form.ewallet.penyedia" :class="inputClass">
                <option value="">— Pilih —</option>
                <option v-for="p in PENYEDIA" :key="p" :value="p">{{ p }}</option>
              </select>
            </label>
            <label><span :class="labelClass">Purata masuk bulanan (RM)</span><input v-model="form.ewallet.purataMasukBulanan" type="number" min="0" step="0.01" :class="inputClass" /></label>
            <label><span :class="labelClass">Transaksi / bulan</span><input v-model="form.ewallet.bilanganTransaksiBulanan" type="number" min="0" :class="inputClass" /></label>
            <label><span :class="labelClass">Bulan aktif</span><input v-model="form.ewallet.bulanAktif" type="number" min="0" max="120" :class="inputClass" /></label>
          </div>

          <div v-else-if="sec === 'duitnowQr'" class="grid grid-cols-2 gap-2">
            <label><span :class="labelClass">Purata jualan bulanan (RM)</span><input v-model="form.duitnowQr.purataJualanBulanan" type="number" min="0" step="0.01" :class="inputClass" /></label>
            <label><span :class="labelClass">Transaksi / bulan</span><input v-model="form.duitnowQr.bilanganTransaksiBulanan" type="number" min="0" :class="inputClass" /></label>
            <label><span :class="labelClass">Bulan aktif</span><input v-model="form.duitnowQr.bulanAktif" type="number" min="0" max="120" :class="inputClass" /></label>
            <label><span :class="labelClass">Trend pertumbuhan (%)</span><input v-model="form.duitnowQr.trendPertumbuhanPeratus" type="number" min="-100" max="1000" step="0.1" :class="inputClass" /></label>
          </div>

          <div v-else-if="sec === 'bilUtiliti'" class="grid grid-cols-2 gap-2">
            <label><span :class="labelClass">Bulan dibayar tepat masa</span><input v-model="form.bilUtiliti.bulanDibayarTepat" type="number" min="0" max="120" :class="inputClass" /></label>
            <label><span :class="labelClass">Bulan dinilai</span><input v-model="form.bilUtiliti.bulanDinilai" type="number" min="0" max="120" :class="inputClass" /></label>
          </div>

          <div v-else class="grid grid-cols-2 gap-2">
            <label class="col-span-2"><span :class="labelClass">Nama platform</span>
              <input v-model="form.platform.nama" list="pp-platform-list" placeholder="cth. Shopee" :class="inputClass" />
            </label>
            <label><span :class="labelClass">Purata jualan bulanan (RM)</span><input v-model="form.platform.purataJualanBulanan" type="number" min="0" step="0.01" :class="inputClass" /></label>
            <label><span :class="labelClass">Penarafan (0–5)</span><input v-model="form.platform.penarafan" type="number" min="0" max="5" step="0.1" :class="inputClass" /></label>
            <label><span :class="labelClass">Bulan aktif</span><input v-model="form.platform.bulanAktif" type="number" min="0" max="120" :class="inputClass" /></label>
          </div>

          <div v-if="ekstrak[sec]" class="mt-2 rounded-md bg-violet-50/70 px-2.5 py-1.5 text-[11px] text-violet-900">
            <p v-if="ekstrak[sec]?.bulanDikesan?.length"><span class="font-semibold">Bulan dikesan:</span> {{ ekstrak[sec]?.bulanDikesan?.join(", ") }}</p>
            <p v-if="ekstrak[sec]?.catatan || ekstrak[sec]?.message">{{ ekstrak[sec]?.catatan || ekstrak[sec]?.message }}</p>
          </div>
        </section>
      </template>
    </div>
    <datalist id="pp-platform-list">
      <option v-for="p in PLATFORM" :key="p" :value="p" />
    </datalist>

    <div class="mt-3 flex flex-wrap gap-4 text-sm text-slate-700">
      <label class="inline-flex items-center gap-2"><input v-model="form.ccrisTersedia" type="checkbox" class="h-4 w-4 rounded border-slate-300" /> Rekod CCRIS tersedia</label>
      <label class="inline-flex items-center gap-2"><input v-model="form.penyataBankTersedia" type="checkbox" class="h-4 w-4 rounded border-slate-300" /> Penyata bank tersedia</label>
    </div>
  </PpCard>
</template>
