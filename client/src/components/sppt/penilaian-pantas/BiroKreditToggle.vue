<script setup lang="ts">
import { onMounted, ref } from "vue";
import { Loader2, Unplug, Plug } from "lucide-vue-next";

import { getBiroKredit, setBiroKredit } from "@/api/sppt";
import { useToast } from "@/composables/useToast";

/** K4: lets the panel cut the (simulated) credit bureau API and watch the pipeline fall back to alternative data. */
const toast = useToast();
const tersambung = ref<boolean | null>(null);
const busy = ref(false);

onMounted(async () => {
  try {
    tersambung.value = (await getBiroKredit()).data.tersambung;
  } catch {
    tersambung.value = null;
  }
});

async function toggle() {
  if (tersambung.value === null) return;
  busy.value = true;
  try {
    tersambung.value = (await setBiroKredit(!tersambung.value)).data.tersambung;
    toast.info(
      tersambung.value ? "Biro kredit disambung semula" : "Biro kredit diputuskan",
      tersambung.value ? "Penilaian seterusnya akan menyemak CCRIS / CTOS." : "Penilaian seterusnya diteruskan dengan data alternatif sahaja.",
    );
  } catch (e: unknown) {
    toast.error("Tidak dapat menukar status biro kredit", e instanceof Error ? e.message : undefined);
  } finally {
    busy.value = false;
  }
}
</script>

<template>
  <button
    v-if="tersambung !== null"
    type="button"
    class="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium shadow-sm transition-colors disabled:opacity-60"
    :class="tersambung ? 'border-emerald-200 bg-emerald-50 text-emerald-800 hover:bg-emerald-100' : 'border-rose-200 bg-rose-50 text-rose-800 hover:bg-rose-100'"
    :disabled="busy"
    :title="tersambung ? 'Klik untuk memutuskan sambungan API biro kredit (simulasi K4)' : 'Klik untuk menyambung semula API biro kredit'"
    @click="toggle"
  >
    <Loader2 v-if="busy" class="h-4 w-4 animate-spin" />
    <Plug v-else-if="tersambung" class="h-4 w-4" />
    <Unplug v-else class="h-4 w-4" />
    Biro kredit: {{ tersambung ? "Tersambung" : "Terputus" }}
    <span class="h-2 w-2 rounded-full" :class="tersambung ? 'bg-emerald-500' : 'bg-rose-500 animate-pulse'" />
  </button>
</template>
