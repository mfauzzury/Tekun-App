<script setup lang="ts">
import { calculatePembiayaanFormula, getPembiayaanFormulaDefaults } from "@/api/sppt";
import type { PembiayaanFormulaComparison, PembiayaanFormulaMethodResult } from "@/types";
import { Calculator, CheckCircle2, Loader2, RefreshCw } from "lucide-vue-next";
import { computed, onMounted, ref, watch } from "vue";

const props = withDefaults(
  defineProps<{
    jumlahPokok?: number;
    kaedahPengiraan?: "flat_rate" | "reducing_balance";
    kadarKeuntungan?: number;
    tempohBulan?: number;
    readonly?: boolean;
  }>(),
  {
    readonly: false,
  },
);

const emit = defineEmits<{
  "update:kaedahPengiraan": [value: "flat_rate" | "reducing_balance"];
  "update:kadarKeuntungan": [value: number];
  "update:tempohBulan": [value: number];
  applyMonthlyInstallment: [amount: number, method: string];
}>();

const loading = ref(false);
const calculating = ref(false);
const error = ref("");
const result = ref<PembiayaanFormulaComparison | null>(null);

const jumlahPokok = ref(50000);
const kadarKeuntungan = ref(4);
const tempohBulan = ref(36);
const tempohOptions = ref<number[]>([12, 24, 36, 48, 60, 84, 120]);
const selectedMethod = ref<"flat_rate" | "reducing_balance">("reducing_balance");

const activeSchedule = computed((): PembiayaanFormulaMethodResult | null => {
  if (!result.value) return null;
  return selectedMethod.value === "flat_rate" ? result.value.flatRate : result.value.reducingBalance;
});

const selectedMethodResult = computed(() => activeSchedule.value);

function formatRm(value: number): string {
  return `RM ${value.toLocaleString("en-MY", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function selectMethod(method: "flat_rate" | "reducing_balance") {
  if (props.readonly) return;
  selectedMethod.value = method;
  emit("update:kaedahPengiraan", method);
}

async function loadDefaults() {
  loading.value = true;
  try {
    const res = await getPembiayaanFormulaDefaults();
    if (props.kadarKeuntungan === undefined) {
      kadarKeuntungan.value = res.data.defaultKadarKeuntunganTahunan;
    }
    if (props.tempohBulan === undefined) {
      tempohBulan.value = res.data.defaultTempohBulan;
    }
    tempohOptions.value = res.data.tempohBulanOptions;
  } catch {
    // Keep local defaults when API unavailable.
  } finally {
    loading.value = false;
  }
}

async function runCalculation() {
  if (jumlahPokok.value < 1000) {
    error.value = "Jumlah pokok minimum RM 1,000.";
    result.value = null;
    return;
  }

  calculating.value = true;
  error.value = "";
  try {
    const res = await calculatePembiayaanFormula({
      jumlahPokok: jumlahPokok.value,
      kadarKeuntunganTahunan: kadarKeuntungan.value,
      tempohBulan: tempohBulan.value,
    });
    result.value = res.data;
    if (!props.readonly) {
      emit("update:kadarKeuntungan", kadarKeuntungan.value);
      emit("update:tempohBulan", tempohBulan.value);
    }
  } catch (err) {
    result.value = null;
    error.value = err instanceof Error ? err.message : "Gagal mengira formula pembiayaan.";
  } finally {
    calculating.value = false;
  }
}

watch(kadarKeuntungan, (value) => {
  if (!props.readonly) emit("update:kadarKeuntungan", value);
});
watch(tempohBulan, (value) => {
  if (!props.readonly) emit("update:tempohBulan", value);
});

watch(
  () => props.jumlahPokok,
  (value) => {
    if (typeof value === "number" && value >= 1000) {
      jumlahPokok.value = value;
      if (!calculating.value) {
        void runCalculation();
      }
    }
  },
  { immediate: true },
);

watch(
  () => props.kaedahPengiraan,
  (value) => {
    if (value === "flat_rate" || value === "reducing_balance") {
      selectedMethod.value = value;
    }
  },
  { immediate: true },
);

watch(
  () => props.kadarKeuntungan,
  (value) => {
    if (typeof value === "number" && Number.isFinite(value)) {
      kadarKeuntungan.value = value;
    }
  },
  { immediate: true },
);

watch(
  () => props.tempohBulan,
  (value) => {
    if (typeof value === "number" && Number.isFinite(value)) {
      tempohBulan.value = value;
    }
  },
  { immediate: true },
);

onMounted(async () => {
  await loadDefaults();
  if (jumlahPokok.value >= 1000 && !result.value) {
    await runCalculation();
  }
});
</script>

<template>
  <section class="rounded-lg border border-sky-200 bg-sky-50/50 shadow-sm">
    <div class="flex items-center gap-2 border-b border-sky-200/50 px-4 py-3">
      <Calculator class="h-4 w-4 text-sky-600" />
      <h4 class="text-sm font-semibold text-sky-900">Fleksibiliti Formula Pengiraan</h4>
      <span class="rounded bg-sky-100 px-1.5 py-0.5 text-[10px] font-medium text-sky-700">Kadar Rata & Baki Menurun</span>
      <span
        v-if="readonly"
        class="rounded bg-slate-200 px-1.5 py-0.5 text-[10px] font-medium text-slate-700"
      >
        Paparan Sahaja
      </span>
    </div>

    <div class="space-y-4 p-4">
      <p class="text-xs text-sky-800/90">
        {{
          readonly
            ? "Butiran formula yang disimpan oleh penyemak semasa penilaian. Paparan ini tidak boleh diubah."
            : "Pilih kaedah pengiraan untuk disimpan ke penilaian, surat tawaran, dan pembukaan akaun pembiayaan."
        }}
      </p>

      <div v-if="readonly" class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-lg border border-slate-200 bg-white/80 px-3 py-2">
          <p class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Jumlah Pokok</p>
          <p class="mt-0.5 text-sm font-semibold text-slate-900">{{ formatRm(jumlahPokok) }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white/80 px-3 py-2">
          <p class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Kadar Keuntungan</p>
          <p class="mt-0.5 text-sm font-semibold text-slate-900">{{ kadarKeuntungan }}%/tahun</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white/80 px-3 py-2">
          <p class="text-[10px] font-medium uppercase tracking-wide text-slate-500">Tempoh</p>
          <p class="mt-0.5 text-sm font-semibold text-slate-900">{{ tempohBulan }} bulan</p>
        </div>
      </div>

      <div v-else class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div>
          <label class="mb-1 block text-xs font-medium text-slate-600">Jumlah Pokok (RM)</label>
          <input
            v-model.number="jumlahPokok"
            type="number"
            min="1000"
            step="1000"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100"
          />
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-slate-600">Kadar Keuntungan (%/tahun)</label>
          <input
            v-model.number="kadarKeuntungan"
            type="number"
            min="0"
            max="30"
            step="0.1"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100"
          />
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-slate-600">Tempoh (bulan)</label>
          <select
            v-model.number="tempohBulan"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-sky-400 focus:outline-none focus:ring-2 focus:ring-sky-100"
          >
            <option v-for="opt in tempohOptions" :key="opt" :value="opt">{{ opt }} bulan</option>
          </select>
        </div>
      </div>

      <div v-if="readonly && calculating" class="flex items-center gap-2 text-xs text-sky-800">
        <Loader2 class="h-4 w-4 animate-spin" />
        Memuatkan perbandingan formula...
      </div>

      <div v-else-if="!readonly" class="flex flex-wrap items-center gap-2">
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-lg bg-sky-700 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-sky-800 disabled:opacity-60"
          :disabled="calculating || loading"
          @click="runCalculation"
        >
          <Loader2 v-if="calculating" class="h-4 w-4 animate-spin" />
          <RefreshCw v-else class="h-4 w-4" />
          {{ calculating ? "Mengira..." : "Kira Semula" }}
        </button>
        <p v-if="selectedMethodResult" class="text-xs text-sky-800">
          Kaedah dipilih: <span class="font-semibold">{{ selectedMethodResult.methodLabel }}</span>
        </p>
      </div>

      <p v-else-if="selectedMethodResult" class="text-xs text-sky-800">
        Kaedah dipilih oleh penyemak:
        <span class="font-semibold">{{ selectedMethodResult.methodLabel }}</span>
      </p>

      <p v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700">{{ error }}</p>

      <template v-if="result">
        <div class="rounded-lg border border-sky-100 bg-white/80 px-3 py-2 text-xs text-slate-700">
          {{ result.perbandingan.ringkasan }}
        </div>

        <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
          <article
            v-for="method in [result.flatRate, result.reducingBalance]"
            :key="method.method"
            class="rounded-xl border bg-white p-4 shadow-sm transition-colors"
            :class="[
              selectedMethod === method.method ? 'border-sky-500 ring-2 ring-sky-100' : 'border-slate-200',
              readonly ? '' : 'cursor-pointer hover:border-sky-200',
            ]"
            @click="!readonly && selectMethod(method.method as 'flat_rate' | 'reducing_balance')"
          >
            <div class="flex items-start justify-between gap-2">
              <h5 class="text-sm font-semibold text-slate-900">{{ method.methodLabel }}</h5>
              <CheckCircle2
                v-if="selectedMethod === method.method"
                class="h-4 w-4 shrink-0 text-sky-600"
              />
            </div>
            <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
              <div>
                <dt class="text-slate-500">Ansuran Bulanan</dt>
                <dd class="text-base font-bold text-slate-900">{{ formatRm(method.ansuranBulanan) }}</dd>
              </div>
              <div>
                <dt class="text-slate-500">Jumlah Keuntungan</dt>
                <dd class="font-semibold text-slate-900">{{ formatRm(method.jumlahKeuntungan) }}</dd>
              </div>
              <div>
                <dt class="text-slate-500">Jumlah Bayaran Balik</dt>
                <dd class="font-semibold text-slate-900">{{ formatRm(method.jumlahBayaranBalik) }}</dd>
              </div>
              <div>
                <dt class="text-slate-500">Keuntungan Efektif</dt>
                <dd class="font-semibold text-slate-900">{{ method.keuntunganEfektifPct.toFixed(2) }}%/tahun</dd>
              </div>
            </dl>
            <button
              v-if="!readonly"
              type="button"
              class="mt-3 text-xs font-medium text-sky-700 hover:text-sky-800"
              @click.stop="emit('applyMonthlyInstallment', method.ansuranBulanan, method.methodLabel)"
            >
              Tambah ke catatan →
            </button>
          </article>
        </div>

        <div v-if="activeSchedule">
          <h5 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-600">
            Jadual Amortisasi — {{ activeSchedule.methodLabel }}
          </h5>
          <div class="max-h-80 overflow-auto rounded-lg border border-slate-200 bg-white">
            <table class="min-w-full text-xs">
              <thead class="sticky top-0 bg-slate-50 text-slate-600">
                <tr>
                  <th class="px-3 py-2 text-left font-semibold">#</th>
                  <th class="px-3 py-2 text-right font-semibold">Pokok</th>
                  <th class="px-3 py-2 text-right font-semibold">Keuntungan</th>
                  <th class="px-3 py-2 text-right font-semibold">Ansuran</th>
                  <th class="px-3 py-2 text-right font-semibold">Baki Pokok</th>
                  <th class="px-3 py-2 text-right font-semibold">Baki Bayaran</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in activeSchedule.jadual" :key="row.no" class="border-t border-slate-100">
                  <td class="px-3 py-1.5 text-slate-700">{{ row.no }}</td>
                  <td class="px-3 py-1.5 text-right text-slate-900">{{ formatRm(row.pokok) }}</td>
                  <td class="px-3 py-1.5 text-right text-slate-900">{{ formatRm(row.keuntungan) }}</td>
                  <td class="px-3 py-1.5 text-right font-medium text-slate-900">{{ formatRm(row.ansuran) }}</td>
                  <td class="px-3 py-1.5 text-right text-slate-600">{{ formatRm(row.bakiPokok) }}</td>
                  <td class="px-3 py-1.5 text-right text-slate-600">{{ formatRm(row.bakiBayaranBalik) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </template>
    </div>
  </section>
</template>
