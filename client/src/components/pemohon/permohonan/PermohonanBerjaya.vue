<script setup lang="ts">
import { ref } from "vue";
import { ArrowRight, Check, Copy, Home, Info } from "lucide-vue-next";

import AiProgressTracker from "@/components/pemohon/AiProgressTracker.vue";
import PButton from "@/components/pemohon/ui/PButton.vue";
import PCard from "@/components/pemohon/ui/PCard.vue";
import { useToast } from "@/composables/useToast";
import { formatRinggit } from "@/utils/pemohon-ui";

const props = defineProps<{ id: number; token: string; noRujukan: string; produk: string; jumlah: number }>();

const toast = useToast();
const aiDone = ref(false);
const copied = ref(false);

async function copyRef() {
  try {
    await navigator.clipboard.writeText(props.noRujukan);
    copied.value = true;
    window.setTimeout(() => (copied.value = false), 2000);
  } catch {
    toast.info("No. rujukan", props.noRujukan);
  }
}
</script>

<template>
  <div class="mx-auto max-w-2xl space-y-5 py-6 sm:py-10">
    <div class="text-center">
      <div class="relative mx-auto flex h-20 w-20 items-center justify-center">
        <span class="absolute inset-0 animate-ping rounded-full bg-emerald-400/30 [animation-iteration-count:2]" />
        <span class="relative flex h-20 w-20 items-center justify-center rounded-full bg-emerald-500 text-white shadow-lg shadow-emerald-500/30">
          <Check class="h-10 w-10" stroke-width="3" />
        </span>
      </div>
      <h1 class="mt-6 text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">Permohonan dihantar</h1>
      <p class="mt-2 text-sm text-slate-500 sm:text-[15px]">
        Terima kasih! Permohonan {{ produk }} sebanyak <span class="font-semibold text-ink-800">{{ formatRinggit(jumlah) }}</span> telah diterima.
      </p>
      <button
        type="button"
        class="mt-4 inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm shadow-xs transition-colors hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-slate-200"
        :aria-label="`Salin no. rujukan ${noRujukan}`"
        @click="copyRef"
      >
        <span class="text-slate-500">No. rujukan</span>
        <span class="font-mono font-semibold tracking-wide text-ink-900">{{ noRujukan }}</span>
        <Check v-if="copied" class="h-4 w-4 text-emerald-600" />
        <Copy v-else class="h-4 w-4 text-slate-400" />
      </button>
    </div>

    <PCard>
      <AiProgressTracker :permohonan-id="id" :token="token" auto-start @done="aiDone = true" />
      <p class="mt-4 flex gap-2 rounded-xl bg-slate-50 px-3.5 py-3 text-sm text-slate-600">
        <Info class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
        Kami sedang menilai permohonan anda menggunakan AI. Keputusan akhir dibuat oleh pegawai TEKUN.
      </p>
    </PCard>

    <div class="flex flex-col gap-2 sm:flex-row sm:justify-center">
      <PButton :variant="aiDone ? 'primary' : 'ink'" size="lg" :to="{ name: 'pemohon-permohonan-detail', params: { id: String(id) } }">
        Lihat status permohonan <ArrowRight class="h-4 w-4" />
      </PButton>
      <PButton variant="outline" size="lg" to="/pemohon/dashboard"><Home class="h-4 w-4" /> Ke halaman utama</PButton>
    </div>
  </div>
</template>
