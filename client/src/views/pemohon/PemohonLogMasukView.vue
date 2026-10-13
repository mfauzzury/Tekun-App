<script setup lang="ts">
import { ref } from "vue";
import { useRouter } from "vue-router";
import { AlertCircle, ArrowRight, CheckCircle2, Eye, EyeOff, FileText, Info, ShieldCheck, Wallet } from "lucide-vue-next";
import PButton from "@/components/pemohon/ui/PButton.vue";
import PField from "@/components/pemohon/ui/PField.vue";
import { usePemohonStore } from "@/stores/pemohon";
import { pInputClass } from "@/utils/pemohon-ui";

const features = [
  { icon: FileText, label: "Permohonan Dalam Talian" },
  { icon: ShieldCheck, label: "Pengesahan Identiti Pantas" },
  { icon: CheckCircle2, label: "Semakan Kelayakan Segera" },
  { icon: Wallet, label: "Penjejakan Status Masa Nyata" },
];

const router = useRouter();
const pemohon = usePemohonStore();

const email = ref("demo@pemohon.my");
const password = ref("demo1234");
const showPassword = ref(false);
const error = ref("");
const loadingMyDigitalId = ref(false);

function submit() {
  error.value = "";
  const ok = pemohon.login(email.value, password.value);
  if (!ok) {
    error.value = "Emel atau kata laluan tidak sah.";
    return;
  }
  router.push({ name: "pemohon-dashboard" });
}

async function loginWithMyDigitalId() {
  loadingMyDigitalId.value = true;
  await new Promise((resolve) => setTimeout(resolve, 800));
  pemohon.loginWithMyDigitalId();
  router.push({ name: "pemohon-dashboard" });
}
</script>

<template>
  <div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-[#f6f7fb] px-4 py-10">
    <div
      aria-hidden="true"
      class="pointer-events-none absolute inset-x-0 top-0 h-[480px] bg-[radial-gradient(60%_60%_at_50%_0%,rgba(214,32,42,0.10),transparent_70%)]"
    />

    <div class="relative w-full max-w-[440px]">
      <div class="mb-8 flex justify-center">
        <img src="/logo_tekun.png" alt="TEKUN Nasional" class="h-10 w-auto" />
      </div>

      <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-[0_1px_2px_rgba(16,24,40,0.04),0_16px_48px_-16px_rgba(16,24,40,0.16)] sm:p-8">
        <div class="mb-7 text-center">
          <h1 class="text-2xl font-bold tracking-tight text-ink-900">Selamat kembali</h1>
          <p class="mt-1.5 text-sm text-slate-500">Log masuk ke Portal Pemohon SPPT TEKUN Nasional</p>
        </div>

        <PButton block size="lg" variant="outline" :loading="loadingMyDigitalId" @click="loginWithMyDigitalId">
          <img v-if="!loadingMyDigitalId" src="/mydigital-id-logo.png" alt="" class="h-6 w-6 rounded-sm" />
          {{ loadingMyDigitalId ? "Menyambung..." : "Log masuk dengan MyDigital ID" }}
        </PButton>

        <div class="relative my-6">
          <div class="absolute inset-0 flex items-center" aria-hidden="true">
            <div class="w-full border-t border-slate-200" />
          </div>
          <div class="relative flex justify-center">
            <span class="bg-white px-3 text-xs font-medium text-slate-400">atau dengan emel</span>
          </div>
        </div>

        <form class="space-y-4" @submit.prevent="submit">
          <PField label="Emel" for="login-email">
            <input id="login-email" v-model="email" type="email" autocomplete="email" :class="pInputClass" placeholder="nama@email.com" />
          </PField>

          <PField label="Kata laluan" for="login-password">
            <div class="relative">
              <input
                id="login-password"
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                autocomplete="current-password"
                :class="[pInputClass, 'pr-11!']"
                placeholder="Kata laluan"
              />
              <button
                type="button"
                class="absolute right-1.5 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-slate-100 hover:text-ink-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-200"
                :aria-label="showPassword ? 'Sembunyikan kata laluan' : 'Tunjuk kata laluan'"
                @click="showPassword = !showPassword"
              >
                <EyeOff v-if="showPassword" class="h-4 w-4" />
                <Eye v-else class="h-4 w-4" />
              </button>
            </div>
          </PField>

          <div v-if="error" role="alert" class="flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2.5 text-sm text-rose-700">
            <AlertCircle class="h-4 w-4 shrink-0" />
            {{ error }}
          </div>

          <PButton type="submit" block size="lg" class="mt-2">
            Log masuk
            <ArrowRight class="h-4 w-4" />
          </PButton>
        </form>

        <div class="mt-5 flex items-center gap-2.5 rounded-xl bg-ink-50 px-3.5 py-2.5 text-xs text-ink-700">
          <Info class="h-4 w-4 shrink-0 text-ink-400" />
          <span>Akaun demo: <span class="font-mono font-medium text-ink-900">demo@pemohon.my</span> / <span class="font-mono font-medium text-ink-900">demo1234</span></span>
        </div>

        <p class="mt-6 text-center text-sm text-slate-500">
          Belum ada akaun?
          <router-link to="/pemohon/daftar" class="font-semibold text-brand-600 hover:text-brand-700">Daftar di sini</router-link>
        </p>
      </div>

      <ul class="mt-8 grid grid-cols-2 gap-x-4 gap-y-3">
        <li v-for="feature in features" :key="feature.label" class="flex items-center gap-2.5 text-xs font-medium text-ink-700">
          <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-brand-600 shadow-xs ring-1 ring-slate-200/80">
            <component :is="feature.icon" class="h-4 w-4" />
          </span>
          {{ feature.label }}
        </li>
      </ul>

      <p class="mt-8 text-center text-xs text-slate-400">© TEKUN Nasional · Portal Usahawan</p>
    </div>
  </div>
</template>
