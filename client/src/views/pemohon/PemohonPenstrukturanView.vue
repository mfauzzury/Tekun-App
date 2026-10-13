<script setup lang="ts">
import { computed, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { AlertTriangle, ArrowRight, Check, RefreshCw, Wallet } from "lucide-vue-next";

import PemohonLayout from "@/layouts/PemohonLayout.vue";
import PButton from "@/components/pemohon/ui/PButton.vue";
import PCard from "@/components/pemohon/ui/PCard.vue";
import PEmptyState from "@/components/pemohon/ui/PEmptyState.vue";
import PField from "@/components/pemohon/ui/PField.vue";
import PPageHeader from "@/components/pemohon/ui/PPageHeader.vue";
import { useToast } from "@/composables/useToast";
import { usePemohonStore } from "@/stores/pemohon";
import { RESTRUCTURE_TEMPOH_OPTIONS, computeRestructure, formatRM } from "@/data/pembiayaan-dummy";
import { pInputClass } from "@/utils/pemohon-ui";

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
    <div v-if="!akaun" class="mx-auto max-w-2xl pt-6">
      <div class="rounded-2xl border border-slate-200/80 bg-white">
        <PEmptyState :icon="Wallet" title="Akaun pembiayaan tidak dijumpai" description="Akaun ini mungkin telah ditutup atau pautan tidak sah.">
          <PButton variant="outline" to="/pemohon/pembiayaan">Kembali ke Pembiayaan Saya</PButton>
        </PEmptyState>
      </div>
    </div>

    <div v-else class="mx-auto max-w-2xl space-y-5">
      <PPageHeader
        eyebrow="Penstrukturan semula"
        title="Ubah tempoh pembiayaan"
        :subtitle="`${akaun.produk} · ${akaun.id}`"
        :back="`/pemohon/pembiayaan/${akaun.id}`"
        back-label="Kembali ke akaun"
      />

      <!-- Summary -->
      <PCard tone="muted">
        <dl class="grid grid-cols-3 gap-3 text-center sm:text-left">
          <div>
            <dt class="text-xs text-slate-500">Baki semasa</dt>
            <dd class="mt-0.5 text-sm font-semibold text-ink-900 sm:text-base">{{ formatRM(akaun.bakiAkhir) }}</dd>
          </div>
          <div>
            <dt class="text-xs text-slate-500">Ansuran semasa</dt>
            <dd class="mt-0.5 text-sm font-semibold text-ink-900 sm:text-base">{{ formatRM(akaun.bayaranBulanan) }}</dd>
          </div>
          <div>
            <dt class="text-xs text-slate-500">Tempoh asal</dt>
            <dd class="mt-0.5 text-sm font-semibold text-ink-900 sm:text-base">{{ akaun.tempohBulan }} bulan</dd>
          </div>
        </dl>
      </PCard>

      <!-- Tenure options -->
      <PCard title="Pilih tempoh baharu" subtitle="Ansuran bulanan dikira semula berdasarkan baki semasa.">
        <div role="radiogroup" aria-label="Tempoh baharu" class="grid grid-cols-2 gap-2.5 sm:grid-cols-3">
          <button
            v-for="opt in RESTRUCTURE_TEMPOH_OPTIONS"
            :key="opt"
            type="button"
            role="radio"
            :aria-checked="tempohBaharu === opt"
            class="relative rounded-xl border bg-white p-3.5 text-left transition-all focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-100"
            :class="tempohBaharu === opt ? 'border-brand-500 ring-2 ring-brand-500/20' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'"
            @click="tempohBaharu = opt"
          >
            <span
              class="absolute right-3 top-3 flex h-5 w-5 items-center justify-center rounded-full border transition-colors"
              :class="tempohBaharu === opt ? 'border-brand-600 bg-brand-600 text-white' : 'border-slate-300'"
            >
              <Check v-if="tempohBaharu === opt" class="h-3 w-3" stroke-width="3" />
            </span>
            <span class="block text-base font-bold text-ink-900">{{ opt }} <span class="text-sm font-medium text-slate-500">bulan</span></span>
            <span class="mt-1 block text-xs text-slate-500">
              <span class="font-semibold" :class="tempohBaharu === opt ? 'text-brand-700' : 'text-ink-800'">{{ formatRM(computeRestructure(akaun, opt).bayaranBaharu) }}</span> / bulan
            </span>
          </button>
        </div>

        <div class="mt-5 grid grid-cols-[1fr_auto_1fr] items-center gap-2 sm:gap-4">
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 sm:p-4">
            <p class="text-xs text-slate-500">Ansuran semasa</p>
            <p class="mt-1 text-base font-bold text-ink-900 sm:text-lg">{{ formatRM(akaun.bayaranBulanan) }}</p>
            <p class="mt-0.5 text-xs text-slate-400">/ bulan</p>
          </div>
          <ArrowRight class="h-5 w-5 text-slate-300" aria-hidden="true" />
          <div class="rounded-xl border border-brand-200 bg-brand-50 p-3 sm:p-4">
            <p class="text-xs text-brand-700">Ansuran baharu</p>
            <p class="mt-1 text-base font-bold text-brand-700 sm:text-lg">{{ formatRM(quote?.bayaranBaharu ?? 0) }}</p>
            <p class="mt-0.5 text-xs text-brand-600/80">/ bulan × {{ tempohBaharu }} bulan</p>
          </div>
        </div>
      </PCard>

      <PCard title="Sebab permohonan">
        <PField for="sebab-penstrukturan" hint="Nyatakan secara ringkas punca anda memerlukan penstrukturan semula.">
          <textarea
            id="sebab-penstrukturan"
            v-model="sebab"
            rows="3"
            placeholder="cth: Pengurangan pendapatan perniagaan sementara"
            :class="pInputClass"
          />
        </PField>
      </PCard>

      <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
        <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0 text-amber-600" />
        <p>Penstrukturan sebenar tertakluk kepada kelulusan TEKUN Nasional. Paparan ini adalah simulasi untuk tujuan demonstrasi.</p>
      </div>

      <PButton block size="lg" :loading="submitting" @click="submit">
        <RefreshCw v-if="!submitting" class="h-4 w-4" />
        {{ submitting ? "Menghantar..." : "Hantar permohonan penstrukturan" }}
      </PButton>
    </div>
  </PemohonLayout>
</template>
