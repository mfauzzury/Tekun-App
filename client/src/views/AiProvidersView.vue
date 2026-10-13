<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import {
  Bot,
  KeyRound,
  Cpu,
  Save,
  CheckCircle2,
  XCircle,
  AlertTriangle,
  Eye,
  EyeOff,
  Trash2,
  PlugZap,
  ShieldCheck,
  Loader2,
} from "lucide-vue-next";

import AdminLayout from "@/layouts/AdminLayout.vue";
import { getAiProvider, testAiProvider, updateAiProvider } from "@/api/cms";
import { useConfirmDialog } from "@/composables/useConfirmDialog";
import { useToast } from "@/composables/useToast";
import type { AiProviderStatus, AiProviderTestResult } from "@/types";

const toast = useToast();
const confirmDialog = useConfirmDialog();

const status = ref<AiProviderStatus | null>(null);
const loading = ref(true);
const saving = ref(false);
const testing = ref(false);
const error = ref("");
const showKey = ref(false);
const testResult = ref<AiProviderTestResult | null>(null);

const form = ref({
  apiKey: "",
  model: "",
  chatModel: "",
  timeout: 300,
});

const inputClass =
  "w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200";

const sourceLabel = computed(() => {
  switch (status.value?.keySource) {
    case "database":
      return "Disimpan di sistem (disulitkan)";
    case "env":
      return "Fail .env pelayan";
    default:
      return "Belum dikonfigurasi";
  }
});

function applyStatus(data: AiProviderStatus) {
  status.value = data;
  form.value = {
    apiKey: "",
    model: data.model,
    chatModel: data.chatModel,
    timeout: data.timeout,
  };
}

async function load() {
  loading.value = true;
  error.value = "";
  try {
    const res = await getAiProvider();
    applyStatus(res.data);
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : "Gagal memuatkan tetapan penyedia AI";
  } finally {
    loading.value = false;
  }
}

async function save() {
  saving.value = true;
  error.value = "";
  try {
    const res = await updateAiProvider({
      apiKey: form.value.apiKey.trim() || null,
      model: form.value.model,
      chatModel: form.value.chatModel,
      timeout: Number(form.value.timeout),
    });
    applyStatus(res.data);
    showKey.value = false;
    toast.success("Tetapan disimpan", "Konfigurasi penyedia AI telah dikemas kini.");
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : "Gagal menyimpan tetapan";
    toast.error("Simpan gagal", error.value);
  } finally {
    saving.value = false;
  }
}

async function clearKey() {
  const allowed = await confirmDialog.confirm({
    title: "Padam API key tersimpan?",
    message:
      "Sistem akan kembali menggunakan key daripada fail .env (jika ada). Ciri AI akan beralih ke kaedah heuristik jika tiada key.",
    confirmText: "Padam",
    destructive: true,
  });
  if (!allowed || !status.value) return;

  saving.value = true;
  try {
    const res = await updateAiProvider({
      clearApiKey: true,
      model: form.value.model,
      chatModel: form.value.chatModel,
      timeout: Number(form.value.timeout),
    });
    applyStatus(res.data);
    testResult.value = null;
    toast.info("API key dipadam");
  } catch (e: unknown) {
    toast.error("Gagal memadam key", e instanceof Error ? e.message : undefined);
  } finally {
    saving.value = false;
  }
}

async function runTest() {
  testing.value = true;
  testResult.value = null;
  try {
    const res = await testAiProvider({
      apiKey: form.value.apiKey.trim() || null,
      model: form.value.model,
    });
    testResult.value = res.data;
  } catch (e: unknown) {
    toast.error("Ujian sambungan gagal", e instanceof Error ? e.message : undefined);
  } finally {
    testing.value = false;
  }
}

onMounted(load);
</script>

<template>
  <AdminLayout>
    <div class="mx-auto max-w-5xl space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="page-title">Penyedia AI</h1>
          <p class="text-sm text-slate-500">Konfigurasi sambungan model bahasa (LLM) yang digunakan oleh ciri AI SPPT.</p>
        </div>
        <button
          class="flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-800 disabled:opacity-50"
          :disabled="loading || saving"
          @click="save"
        >
          <Loader2 v-if="saving" class="h-4 w-4 animate-spin" />
          <Save v-else class="h-4 w-4" />
          Simpan
        </button>
      </div>

      <p v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>

      <div v-if="loading" class="flex items-center gap-2 text-sm text-slate-500">
        <Loader2 class="h-4 w-4 animate-spin" /> Memuatkan…
      </div>

      <template v-else-if="status">
        <!-- ═══════ STATUS ═══════ -->
        <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5">
            <Bot class="h-4 w-4 text-violet-600" />
            <h2 class="text-sm font-semibold text-slate-900">{{ status.name }}</h2>
            <span
              class="ml-auto inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
              :class="status.keyConfigured ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
            >
              <CheckCircle2 v-if="status.keyConfigured" class="h-3.5 w-3.5" />
              <AlertTriangle v-else class="h-3.5 w-3.5" />
              {{ status.keyConfigured ? "Aktif" : "Tiada API key" }}
            </span>
          </div>
          <div class="grid gap-3 p-4 text-sm md:grid-cols-3">
            <div>
              <p class="text-xs text-slate-400">Sumber API key</p>
              <p class="font-medium text-slate-800">{{ sourceLabel }}</p>
            </div>
            <div>
              <p class="text-xs text-slate-400">API key</p>
              <p class="font-mono font-medium text-slate-800">{{ status.keyHint ?? "—" }}</p>
            </div>
            <div>
              <p class="text-xs text-slate-400">Model utama</p>
              <p class="font-mono font-medium text-slate-800">{{ status.model }}</p>
            </div>
          </div>
          <p
            v-if="status.keyUnreadable"
            class="mx-4 mb-4 flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800"
          >
            <AlertTriangle class="mt-0.5 h-3.5 w-3.5 shrink-0" />
            Key tersimpan tidak dapat dinyahsulit pada pelayan ini (APP_KEY berbeza). Masukkan semula API key dan simpan.
          </p>
          <p
            v-if="!status.keyConfigured"
            class="mx-4 mb-4 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600"
          >
            Tanpa API key, skor kredit dan OCR menggunakan kaedah heuristik, dan pembantu chat tidak tersedia.
          </p>
        </article>

        <!-- ═══════ API KEY ═══════ -->
        <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5">
            <KeyRound class="h-4 w-4 text-amber-600" />
            <h2 class="text-sm font-semibold text-slate-900">API key</h2>
          </div>
          <div class="space-y-3 p-4">
            <div class="space-y-1.5">
              <label class="text-sm font-medium text-slate-700" for="ai-api-key">
                {{ status.keySource === "database" ? "Gantikan API key" : "API key Anthropic" }}
              </label>
              <div class="flex gap-2">
                <div class="relative flex-1">
                  <input
                    id="ai-api-key"
                    v-model="form.apiKey"
                    :type="showKey ? 'text' : 'password'"
                    autocomplete="off"
                    spellcheck="false"
                    placeholder="sk-ant-…"
                    :class="[inputClass, 'pr-10 font-mono']"
                  />
                  <button
                    type="button"
                    class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600"
                    :aria-label="showKey ? 'Sembunyi key' : 'Papar key'"
                    @click="showKey = !showKey"
                  >
                    <EyeOff v-if="showKey" class="h-4 w-4" />
                    <Eye v-else class="h-4 w-4" />
                  </button>
                </div>
                <button
                  v-if="status.keySource === 'database'"
                  type="button"
                  class="flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-600 transition-colors hover:border-rose-300 hover:text-rose-600"
                  :disabled="saving"
                  @click="clearKey"
                >
                  <Trash2 class="h-4 w-4" />
                  Padam
                </button>
              </div>
              <p class="text-xs text-slate-400">
                Biarkan kosong untuk mengekalkan key sedia ada. Key disulitkan sebelum disimpan dan tidak akan dipaparkan semula.
              </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
              <button
                type="button"
                class="flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 disabled:opacity-50"
                :disabled="testing || (!form.apiKey.trim() && !status.keyConfigured)"
                @click="runTest"
              >
                <Loader2 v-if="testing" class="h-4 w-4 animate-spin" />
                <PlugZap v-else class="h-4 w-4" />
                Uji sambungan
              </button>
              <span class="text-xs text-slate-400">
                {{ form.apiKey.trim() ? "Menguji key yang dimasukkan (belum disimpan)." : "Menguji key yang aktif." }}
              </span>
            </div>

            <div
              v-if="testResult"
              class="flex items-start gap-2 rounded-lg border px-3 py-2 text-sm"
              :class="testResult.ok ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800'"
            >
              <CheckCircle2 v-if="testResult.ok" class="mt-0.5 h-4 w-4 shrink-0" />
              <XCircle v-else class="mt-0.5 h-4 w-4 shrink-0" />
              <div>
                <p class="font-medium">{{ testResult.message }}</p>
                <p class="text-xs opacity-80">
                  Model: <span class="font-mono">{{ testResult.displayName ?? testResult.model }}</span>
                  · Latensi: {{ testResult.latencyMs }} ms
                  <template v-if="testResult.status"> · HTTP {{ testResult.status }}</template>
                </p>
              </div>
            </div>
          </div>
        </article>

        <!-- ═══════ MODELS ═══════ -->
        <article class="rounded-lg border border-slate-200 bg-white shadow-sm">
          <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5">
            <Cpu class="h-4 w-4 text-blue-600" />
            <h2 class="text-sm font-semibold text-slate-900">Model</h2>
          </div>
          <div class="grid gap-3 p-4 md:grid-cols-3">
            <div class="space-y-1.5">
              <label class="text-sm font-medium text-slate-700" for="ai-model">Model utama</label>
              <select id="ai-model" v-model="form.model" :class="inputClass">
                <option v-for="m in status.models" :key="m.id" :value="m.id">{{ m.label }} — {{ m.tier }}</option>
              </select>
              <p class="text-xs text-slate-400">Penilaian kredit, OCR borang dan skor risiko.</p>
            </div>
            <div class="space-y-1.5">
              <label class="text-sm font-medium text-slate-700" for="ai-chat-model">Model chat</label>
              <select id="ai-chat-model" v-model="form.chatModel" :class="inputClass">
                <option v-for="m in status.models" :key="m.id" :value="m.id">{{ m.label }} — {{ m.tier }}</option>
              </select>
              <p class="text-xs text-slate-400">Pembantu AINA dan chat pemohon.</p>
            </div>
            <div class="space-y-1.5">
              <label class="text-sm font-medium text-slate-700" for="ai-timeout">Had masa (saat)</label>
              <input id="ai-timeout" v-model.number="form.timeout" type="number" min="10" max="600" :class="inputClass" />
              <p class="text-xs text-slate-400">10–600 saat bagi setiap panggilan.</p>
            </div>
          </div>
        </article>

        <p class="flex items-start gap-2 text-xs text-slate-500">
          <ShieldCheck class="mt-0.5 h-3.5 w-3.5 shrink-0 text-emerald-600" />
          Setiap perubahan direkodkan dalam log audit (tanpa nilai key). Hanya pengguna dengan kebenaran
          <code class="rounded bg-slate-100 px-1 py-0.5">settings.edit</code> boleh mengubah atau menguji tetapan ini.
        </p>
      </template>
    </div>
  </AdminLayout>
</template>
