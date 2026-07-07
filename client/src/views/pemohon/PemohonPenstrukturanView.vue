<script setup lang="ts">
import { computed, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { AlertTriangle, ArrowLeft, ArrowRight, Loader2, RefreshCw } from "lucide-vue-next";

import PemohonLayout from "@/layouts/PemohonLayout.vue";
import { useToast } from "@/composables/useToast";
import { usePemohonStore } from "@/stores/pemohon";
import { RESTRUCTURE_TEMPOH_OPTIONS, computeRestructure, formatRM } from "@/data/pembiayaan-dummy";

const route = useRoute();
const router = useRouter();
const pemohon = usePemohonStore();
const toast = useToast();

const akaun = computed(() => pemohon.getPembiayaan(String(route.params.id)));

const tempohBaharu = ref<number>(RESTRUCTURE_TEMPOH_OPTIONS[2]);
const sebab = ref("");
const submitting = ref(false);

const quote = computed(() => (akaun.value ? computeRestructure(akaun.value, tempohBaharu.value) : null));

async function submit() {
  if (!akaun.value) return;
  submitting.value = true;
  await new Promise((resolve) => setTimeout(resolve, 1000));
  try {
    pemohon.applyRestructure(akaun.value.id, tempohBaharu.value, sebab.value.trim());
    toast.success("Penstrukturan Diluluskan", `Ansuran bulanan baharu: ${formatRM(quote.value?.bayaranBaharu ?? 0)}.`);
    router.push(`/pemohon/pembiayaan/${akaun.value.id}`);
  } catch (e) {
    toast.error("Gagal", e instanceof Error ? e.message : undefined);
  } finally {
    submitting.value = false;
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

      <div class="rounded-2xl bg-gradient-to-br from-blue-700 to-blue-900 p-6 shadow-sm">
        <h1 class="text-xl font-semibold text-white">Penstrukturan Semula Pembiayaan</h1>
        <p class="mt-1 text-sm text-blue-100">{{ akaun.produk }} · {{ akaun.id }}</p>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <label class="mb-1.5 block text-sm font-medium text-slate-700">Tempoh Baharu</label>
        <select
          v-model.number="tempohBaharu"
          class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
        >
          <option v-for="opt in RESTRUCTURE_TEMPOH_OPTIONS" :key="opt" :value="opt">{{ opt }} bulan</option>
        </select>

        <div class="mt-4 grid grid-cols-2 gap-3">
          <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs text-slate-500">Ansuran Semasa</p>
            <p class="mt-1 text-lg font-bold text-slate-900">{{ formatRM(akaun.bayaranBulanan) }}</p>
            <p class="mt-0.5 text-xs text-slate-400">/ bulan</p>
          </div>
          <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
            <div class="flex items-center gap-1 text-xs text-blue-600">
              <ArrowRight class="h-3 w-3" /> Ansuran Baharu
            </div>
            <p class="mt-1 text-lg font-bold text-blue-700">{{ formatRM(quote?.bayaranBaharu ?? 0) }}</p>
            <p class="mt-0.5 text-xs text-blue-500">/ bulan × {{ tempohBaharu }} bulan</p>
          </div>
        </div>

        <div class="mt-4">
          <label class="mb-1.5 block text-sm font-medium text-slate-700">Sebab Permohonan</label>
          <textarea
            v-model="sebab"
            rows="3"
            placeholder="cth: Pengurangan pendapatan perniagaan sementara"
            class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
          />
        </div>
      </div>

      <div class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3.5 text-xs text-amber-800">
        <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0 text-amber-600" />
        Penstrukturan sebenar tertakluk kepada kelulusan TEKUN Nasional. Paparan ini adalah simulasi untuk tujuan demonstrasi.
      </div>

      <button
        type="button"
        class="flex w-full items-center justify-center gap-2 rounded-md bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-700 disabled:opacity-60"
        :disabled="submitting"
        @click="submit"
      >
        <Loader2 v-if="submitting" class="h-4 w-4 animate-spin" />
        <RefreshCw v-else class="h-4 w-4" />
        {{ submitting ? "Menghantar..." : "Hantar Permohonan Penstrukturan" }}
      </button>
    </div>
  </PemohonLayout>
</template>
