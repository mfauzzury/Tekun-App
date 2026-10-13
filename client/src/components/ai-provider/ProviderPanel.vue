<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { CheckCircle2, Eye, EyeOff, Image as ImageIcon, Loader2, PlugZap, Save, Server, Trash2, Type, XCircle } from "lucide-vue-next";

import { setActiveAiProvider, testAiProvider, updateAiProvider } from "@/api/cms";
import { useConfirmDialog } from "@/composables/useConfirmDialog";
import { useToast } from "@/composables/useToast";
import type { AiProviderInfo, AiProviderStatus, AiProviderTestResult } from "@/types";

const props = defineProps<{ info: AiProviderInfo; timeout: number }>();
const emit = defineEmits<{ updated: [status: AiProviderStatus] }>();

const toast = useToast();
const confirmDialog = useConfirmDialog();

const inputClass =
  "w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm transition-colors focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200";

const form = ref({ apiKey: "", model: "", chatModel: "", visionModel: "", timeout: 300, baseUrl: "", vision: false });
const showKey = ref(false);
const saving = ref(false);
const testing = ref(false);
const activating = ref<"teks" | "imej" | null>(null);
const testResult = ref<AiProviderTestResult | null>(null);
const errors = ref<Record<string, string>>({});

function reset() {
  form.value = {
    apiKey: "",
    model: props.info.model ?? "",
    chatModel: props.info.chatModel ?? "",
    visionModel: props.info.visionModel ?? "",
    timeout: props.timeout,
    baseUrl: props.info.baseUrl ?? "",
    vision: props.info.vision,
  };
  errors.value = {};
}
watch(() => [props.info, props.timeout], reset, { immediate: true });

const isClaude = computed(() => props.info.kod === "anthropic");
const isOnprem = computed(() => props.info.kod === "onprem");
const modelSuggestions = computed(() => [...new Set([...(testResult.value?.modelTersedia ?? []), ...props.info.cadanganModel])]);
const sourceLabel = computed(
  () => ({ database: "Disimpan di sistem (disulitkan)", env: "Fail .env pelayan", none: "Belum dikonfigurasi" })[props.info.keySource],
);

function payload() {
  return {
    apiKey: form.value.apiKey.trim() || null,
    model: form.value.model.trim(),
    ...(isClaude.value ? { chatModel: form.value.chatModel, visionModel: form.value.visionModel, timeout: Number(form.value.timeout) } : {}),
    ...(isOnprem.value ? { baseUrl: form.value.baseUrl.trim(), vision: form.value.vision } : {}),
  };
}

function captureErrors(e: unknown) {
  const details = (e as { details?: Record<string, string[] | string> | null })?.details ?? null;
  errors.value = Object.fromEntries(Object.entries(details ?? {}).map(([k, v]) => [k, Array.isArray(v) ? v[0] : String(v)]));
}

async function save() {
  saving.value = true;
  errors.value = {};
  try {
    const res = await updateAiProvider(props.info.kod, payload());
    emit("updated", res.data);
    showKey.value = false;
    toast.success("Tetapan disimpan", `${props.info.label} telah dikemas kini.`);
  } catch (e: unknown) {
    captureErrors(e);
    toast.error("Simpan gagal", e instanceof Error ? e.message : undefined);
  } finally {
    saving.value = false;
  }
}

async function clearKey() {
  const ok = await confirmDialog.confirm({
    title: "Padam API key tersimpan?",
    message: "Sistem akan kembali menggunakan key daripada fail .env (jika ada).",
    confirmText: "Padam",
    destructive: true,
  });
  if (!ok) return;
  saving.value = true;
  try {
    const res = await updateAiProvider(props.info.kod, { ...payload(), apiKey: null, clearApiKey: true });
    emit("updated", res.data);
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
    const res = await testAiProvider(props.info.kod, {
      apiKey: form.value.apiKey.trim() || null,
      model: form.value.model.trim() || undefined,
      baseUrl: isOnprem.value ? form.value.baseUrl.trim() || undefined : undefined,
    });
    testResult.value = res.data;
  } catch (e: unknown) {
    captureErrors(e);
    toast.error("Ujian sambungan gagal", e instanceof Error ? e.message : undefined);
  } finally {
    testing.value = false;
  }
}

async function activate(untuk: "teks" | "imej") {
  activating.value = untuk;
  try {
    const res = await setActiveAiProvider(props.info.kod, untuk);
    emit("updated", res.data);
    toast.success(
      "Penyedia ditukar",
      untuk === "imej" ? `Analisis imej kini menggunakan ${props.info.label}.` : `Tugasan teks kini menggunakan ${props.info.label}.`,
    );
  } catch (e: unknown) {
    toast.error("Tidak dapat diaktifkan", e instanceof Error ? e.message : undefined);
  } finally {
    activating.value = null;
  }
}
</script>

<template>
  <div class="space-y-4">
    <p class="text-sm text-slate-600">{{ info.penerangan }}</p>

    <div v-if="isOnprem" class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900">
      <p class="flex items-center gap-2 font-medium"><Server class="h-4 w-4" /> Pelaksanaan on-premise</p>
      <p class="mt-1 text-xs text-emerald-800">
        Sambung ke pelayan model dalam pusat data TEKUN melalui API serasi OpenAI. Contoh alamat:
        <code class="rounded bg-white/70 px-1">http://pelayan:11434/v1</code> (Ollama),
        <code class="rounded bg-white/70 px-1">http://pelayan:8000/v1</code> (vLLM),
        <code class="rounded bg-white/70 px-1">http://pelayan:1234/v1</code> (LM Studio).
        Untuk OCR MyKad dan pengesahan wajah, gunakan model visi seperti Qwen2.5-VL atau Llama 3.2 Vision dan hidupkan "Model menyokong imej".
      </p>
    </div>

    <!-- Status -->
    <div class="grid gap-3 rounded-lg border border-slate-200 bg-slate-50/60 p-3 text-sm sm:grid-cols-3">
      <div>
        <p class="text-xs text-slate-400">Sumber API key</p>
        <p class="font-medium text-slate-800">{{ info.keyWajib || info.keyConfigured ? sourceLabel : "Tidak diperlukan" }}</p>
      </div>
      <div>
        <p class="text-xs text-slate-400">API key</p>
        <p class="font-mono font-medium text-slate-800">{{ info.keyHint ?? "—" }}</p>
      </div>
      <div>
        <p class="text-xs text-slate-400">Analisis imej (OCR / wajah)</p>
        <p class="font-medium" :class="info.vision ? 'text-emerald-700' : 'text-amber-700'">{{ info.vision ? "Disokong" : "Tidak disokong" }}</p>
      </div>
    </div>
    <p v-if="info.keyUnreadable" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
      Key tersimpan tidak dapat dinyahsulit pada pelayan ini (APP_KEY berbeza). Masukkan semula API key dan simpan.
    </p>

    <!-- Form -->
    <div class="grid gap-4 md:grid-cols-2">
      <div v-if="isOnprem" class="space-y-1.5 md:col-span-2">
        <label class="text-sm font-medium text-slate-700" :for="`url-${info.kod}`">Alamat pelayan (base URL)</label>
        <input :id="`url-${info.kod}`" v-model="form.baseUrl" :class="inputClass" class="font-mono" placeholder="http://10.0.0.5:11434/v1" />
        <p v-if="errors.baseUrl" class="text-xs text-rose-600">{{ errors.baseUrl }}</p>
      </div>
      <div v-else class="space-y-1.5 md:col-span-2">
        <p class="text-sm font-medium text-slate-700">Alamat API</p>
        <p class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 font-mono text-sm text-slate-600">{{ info.baseUrl }}</p>
      </div>

      <div class="space-y-1.5 md:col-span-2">
        <label class="text-sm font-medium text-slate-700" :for="`key-${info.kod}`">
          {{ info.keySource === "database" ? "Gantikan API key" : "API key" }}
          <span v-if="!info.keyWajib" class="font-normal text-slate-400">(pilihan)</span>
        </label>
        <div class="flex gap-2">
          <div class="relative flex-1">
            <input
              :id="`key-${info.kod}`"
              v-model="form.apiKey"
              :type="showKey ? 'text' : 'password'"
              autocomplete="off"
              spellcheck="false"
              :placeholder="isClaude ? 'sk-ant-…' : isOnprem ? 'Kosongkan jika pelayan tidak memerlukan key' : 'sk-…'"
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
            v-if="info.keySource === 'database'"
            type="button"
            class="flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-600 hover:border-rose-300 hover:text-rose-600"
            :disabled="saving"
            @click="clearKey"
          >
            <Trash2 class="h-4 w-4" /> Padam
          </button>
        </div>
        <p v-if="errors.apiKey" class="text-xs text-rose-600">{{ errors.apiKey }}</p>
        <p v-else class="text-xs text-slate-400">Biarkan kosong untuk mengekalkan key sedia ada. Key disulitkan dan tidak dipaparkan semula.</p>
      </div>

      <template v-if="isClaude">
        <div class="space-y-1.5">
          <label class="text-sm font-medium text-slate-700" for="claude-model">Model teks</label>
          <select id="claude-model" v-model="form.model" :class="inputClass">
            <option v-for="m in info.models" :key="m.id" :value="m.id">{{ m.label }} — {{ m.tier }}</option>
          </select>
          <p class="text-xs text-slate-400">Penjelasan skor kredit, penyata CSV dan skor risiko.</p>
        </div>
        <div class="space-y-1.5">
          <label class="text-sm font-medium text-slate-700" for="claude-vision-model">Model imej</label>
          <select id="claude-vision-model" v-model="form.visionModel" :class="inputClass">
            <option v-for="m in info.models" :key="m.id" :value="m.id">{{ m.label }} — {{ m.tier }}</option>
          </select>
          <p class="text-xs text-slate-400">OCR MyKad &amp; borang, pengesahan wajah, semakan dokumen. Disyorkan: Sonnet 5.5.</p>
        </div>
        <div class="space-y-1.5">
          <label class="text-sm font-medium text-slate-700" for="claude-chat-model">Model chat</label>
          <select id="claude-chat-model" v-model="form.chatModel" :class="inputClass">
            <option v-for="m in info.models" :key="m.id" :value="m.id">{{ m.label }} — {{ m.tier }}</option>
          </select>
          <p class="text-xs text-slate-400">Pembantu AINA dan chat pemohon.</p>
        </div>
        <div class="space-y-1.5">
          <label class="text-sm font-medium text-slate-700" for="claude-timeout">Had masa (saat)</label>
          <input id="claude-timeout" v-model.number="form.timeout" type="number" min="10" max="600" :class="inputClass" />
          <p v-if="errors.timeout" class="text-xs text-rose-600">{{ errors.timeout }}</p>
        </div>
      </template>
      <template v-else>
        <div class="space-y-1.5">
          <label class="text-sm font-medium text-slate-700" :for="`model-${info.kod}`">Model</label>
          <input :id="`model-${info.kod}`" v-model="form.model" :list="`models-${info.kod}`" :class="inputClass" class="font-mono" placeholder="cth. gpt-4o-mini" />
          <datalist :id="`models-${info.kod}`">
            <option v-for="m in modelSuggestions" :key="m" :value="m" />
          </datalist>
          <p v-if="errors.model" class="text-xs text-rose-600">{{ errors.model }}</p>
          <p v-else class="text-xs text-slate-400">Pilih daripada cadangan atau taip nama model.</p>
        </div>
        <label v-if="isOnprem" class="flex items-start gap-3 rounded-lg border border-slate-200 p-3 md:self-end">
          <input v-model="form.vision" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300" />
          <span>
            <span class="block text-sm font-medium text-slate-700">Model menyokong imej</span>
            <span class="block text-xs text-slate-500">Diperlukan untuk OCR MyKad, pengesahan wajah dan semakan dokumen.</span>
          </span>
        </label>
      </template>
    </div>

    <!-- Test result -->
    <div
      v-if="testResult"
      class="flex items-start gap-2 rounded-lg border px-3 py-2 text-sm"
      :class="testResult.ok ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800'"
    >
      <CheckCircle2 v-if="testResult.ok" class="mt-0.5 h-4 w-4 shrink-0" />
      <XCircle v-else class="mt-0.5 h-4 w-4 shrink-0" />
      <div class="min-w-0">
        <p class="font-medium">{{ testResult.message }}</p>
        <p class="text-xs opacity-80">
          Latensi: {{ testResult.latencyMs }} ms<template v-if="testResult.status"> · HTTP {{ testResult.status }}</template>
          <template v-if="testResult.modelTersedia.length"> · {{ testResult.modelTersedia.length }} model dikesan</template>
        </p>
        <p v-if="testResult.modelTersedia.length" class="mt-1 truncate font-mono text-xs opacity-80">{{ testResult.modelTersedia.slice(0, 8).join(", ") }}</p>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4">
      <button
        type="button"
        class="flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50"
        :disabled="testing"
        @click="runTest"
      >
        <Loader2 v-if="testing" class="h-4 w-4 animate-spin" />
        <PlugZap v-else class="h-4 w-4" />
        Uji sambungan
      </button>
      <button
        type="button"
        class="flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800 disabled:opacity-50"
        :disabled="saving"
        @click="save"
      >
        <Loader2 v-if="saving" class="h-4 w-4 animate-spin" />
        <Save v-else class="h-4 w-4" />
        Simpan
      </button>
      <span class="flex-1" />
      <template v-for="u in (['teks', 'imej'] as const)" :key="u">
        <span
          v-if="u === 'teks' ? info.aktif : info.aktifImej"
          class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700"
        >
          <CheckCircle2 class="h-3.5 w-3.5" /> Aktif untuk {{ u }}
        </span>
        <button
          v-else
          type="button"
          class="flex items-center gap-2 rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="!info.dikonfigurasi || (u === 'imej' && !info.vision) || activating !== null"
          :title="!info.dikonfigurasi ? 'Simpan konfigurasi dahulu' : u === 'imej' && !info.vision ? 'Penyedia ini tidak menyokong imej' : ''"
          @click="activate(u)"
        >
          <Loader2 v-if="activating === u" class="h-4 w-4 animate-spin" />
          <component :is="u === 'teks' ? Type : ImageIcon" v-else class="h-4 w-4" />
          Guna untuk {{ u }}
        </button>
      </template>
    </div>
  </div>
</template>
