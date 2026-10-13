<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { Bot, Cloud, Loader2, Server, ShieldCheck, Sparkles } from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import ProviderPanel from "@/components/ai-provider/ProviderPanel.vue";
import { getAiProvider } from "@/api/cms";
import type { AiProviderKod, AiProviderStatus } from "@/types";

const TABS: { kod: AiProviderKod; label: string; icon: typeof Bot }[] = [
  { kod: "anthropic", label: "Anthropic Claude", icon: Sparkles },
  { kod: "openai", label: "OpenAI ChatGPT", icon: Bot },
  { kod: "deepseek", label: "DeepSeek", icon: Cloud },
  { kod: "onprem", label: "On-premise", icon: Server },
];

const status = ref<AiProviderStatus | null>(null);
const loading = ref(true);
const error = ref("");
const tab = ref<AiProviderKod>("anthropic");

const active = computed(() => (status.value ? status.value.penyedia[status.value.aktif] : null));

async function load() {
  loading.value = true;
  error.value = "";
  try {
    const res = await getAiProvider();
    status.value = res.data;
    tab.value = res.data.aktif;
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : "Gagal memuatkan tetapan penyedia AI";
  } finally {
    loading.value = false;
  }
}

function dotClass(kod: AiProviderKod) {
  const info = status.value?.penyedia[kod];
  if (info?.aktif) return "bg-emerald-500";
  return info?.dikonfigurasi ? "bg-blue-500" : "bg-slate-300";
}

onMounted(load);
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-5xl space-y-4">
      <div>
        <h1 class="page-title">Penyedia AI</h1>
        <p class="text-sm text-slate-500">Pilih dan konfigurasi model bahasa (LLM) yang digunakan oleh ciri AI SPPT — awan atau on-premise.</p>
      </div>

      <p v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
      <div v-if="loading" class="flex items-center gap-2 text-sm text-slate-500"><Loader2 class="h-4 w-4 animate-spin" /> Memuatkan…</div>

      <template v-else-if="status && active">
        <!-- Active provider -->
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
          <div class="flex flex-wrap items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
              <ShieldCheck class="h-5 w-5" />
            </span>
            <div class="min-w-0 flex-1">
              <p class="text-xs text-slate-400">Penyedia aktif</p>
              <p class="text-base font-semibold text-slate-900">
                {{ active.label }} <span class="font-mono text-sm font-normal text-slate-500">· {{ active.model || "—" }}</span>
              </p>
            </div>
            <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="active.onPremise ? 'bg-emerald-50 text-emerald-700' : 'bg-blue-50 text-blue-700'">
              {{ active.onPremise ? "On-premise — data kekal dalam TEKUN" : "Awan" }}
            </span>
            <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="active.vision ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'">
              {{ active.vision ? "Imej disokong" : "Teks sahaja" }}
            </span>
          </div>
          <p class="mt-3 text-xs text-slate-500">
            Digunakan oleh <span class="font-medium text-slate-700">Penilaian Pantas AI</span> (e-KYC MyKad &amp; wajah, skor kredit data alternatif,
            semakan dokumen jualan) dan e-KYC portal pemohon. OCR borang penuh, skor risiko lama dan pembantu chat AINA masih menggunakan Anthropic Claude.
          </p>
        </article>

        <!-- Tabs -->
        <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="flex gap-1 overflow-x-auto border-b border-slate-100 px-2 pt-2" role="tablist" aria-label="Penyedia AI">
            <button
              v-for="t in TABS"
              :key="t.kod"
              type="button"
              role="tab"
              :aria-selected="tab === t.kod"
              class="relative flex shrink-0 items-center gap-2 rounded-t-lg px-4 py-2.5 text-sm font-medium transition-colors"
              :class="tab === t.kod ? 'bg-white text-slate-900' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800'"
              @click="tab = t.kod"
            >
              <component :is="t.icon" class="h-4 w-4" />
              {{ t.label }}
              <span class="h-2 w-2 rounded-full" :class="dotClass(t.kod)" />
              <span v-if="tab === t.kod" class="absolute inset-x-3 -bottom-px h-0.5 rounded-full bg-slate-900" />
            </button>
          </div>
          <div class="p-4" role="tabpanel">
            <ProviderPanel :key="tab" :info="status.penyedia[tab]" :timeout="status.timeout" @updated="(s) => (status = s)" />
          </div>
          <div class="flex flex-wrap gap-4 border-t border-slate-100 px-4 py-2.5 text-xs text-slate-500">
            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-500" /> Aktif</span>
            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-blue-500" /> Dikonfigurasi</span>
            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-slate-300" /> Belum dikonfigurasi</span>
          </div>
        </article>

        <p class="flex items-start gap-2 text-xs text-slate-500">
          <ShieldCheck class="mt-0.5 h-3.5 w-3.5 shrink-0 text-emerald-600" />
          Setiap perubahan dan pertukaran penyedia direkodkan dalam log audit (tanpa nilai key). Hanya pengguna dengan kebenaran
          <code class="rounded bg-slate-100 px-1 py-0.5">settings.edit</code> boleh mengubah, menguji atau mengaktifkan penyedia.
        </p>
      </template>
    </div>
  </AdminLayout>
</template>
