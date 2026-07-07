<script setup lang="ts">
import { ref } from "vue";
import { useRouter } from "vue-router";
import { AlertCircle, ArrowRight, CheckCircle2, Eye, EyeOff, FileText, Loader2, ShieldCheck, Wallet } from "lucide-vue-next";
import { usePemohonStore } from "@/stores/pemohon";

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
  <div class="flex min-h-screen bg-[#f6f9fc]">
    <!-- Left: form -->
    <div class="flex w-full flex-col items-center justify-center px-4 py-10 lg:w-1/2 lg:px-16">
      <div class="w-full max-w-[400px]">
        <div class="mb-7 flex justify-center lg:justify-start">
          <img src="/logo_tekun.png" alt="TEKUN Nasional" class="h-8 w-auto" />
        </div>

        <h1 class="mb-1 text-center text-xl font-semibold tracking-tight text-[#1a1f36] lg:text-left">Log Masuk Pemohon</h1>
        <p class="mb-8 text-center text-[13px] text-[#697386] lg:text-left">Portal Pemohon SPPT TEKUN Nasional</p>

        <form class="space-y-5" @submit.prevent="submit">
          <div class="space-y-1.5">
            <label class="text-[13px] font-medium text-[#1a1f36]">Emel</label>
            <input
              v-model="email"
              type="email"
              class="w-full rounded-md border border-[#d8dee4] bg-white px-3 py-[9px] text-sm text-[#1a1f36] focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
              placeholder="nama@email.com"
            />
          </div>

          <div class="space-y-1.5">
            <label class="text-[13px] font-medium text-[#1a1f36]">Kata Laluan</label>
            <div class="relative">
              <input
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                class="w-full rounded-md border border-[#d8dee4] bg-white px-3 py-[9px] pr-10 text-sm text-[#1a1f36] focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                placeholder="Kata laluan"
              />
              <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-[#a3acb9] hover:text-[#697386]" @click="showPassword = !showPassword">
                <EyeOff v-if="showPassword" class="h-4 w-4" />
                <Eye v-else class="h-4 w-4" />
              </button>
            </div>
          </div>

          <div v-if="error" class="flex items-center gap-2 rounded-md border border-[#f8d7da] bg-[#fdf2f2] px-3.5 py-2.5 text-[13px] text-[#cd3d64]">
            <AlertCircle class="h-4 w-4 shrink-0" />
            {{ error }}
          </div>

          <button
            type="submit"
            class="flex w-full items-center justify-center gap-2 rounded-md bg-blue-600 px-4 py-[9px] text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-700"
          >
            Log Masuk
            <ArrowRight class="h-4 w-4" />
          </button>
        </form>

        <div class="relative my-6">
          <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-[#e3e8ee]" />
          </div>
          <div class="relative flex justify-center">
            <span class="bg-[#f6f9fc] px-3 text-[12px] text-[#8792a2] lg:bg-white">Atau</span>
          </div>
        </div>

        <button
          type="button"
          class="flex w-full items-center justify-center gap-2 rounded-md border border-[#d8dee4] bg-white px-4 py-[9px] text-sm font-medium text-[#1a1f36] shadow-sm transition-colors hover:bg-slate-50 disabled:opacity-60"
          :disabled="loadingMyDigitalId"
          @click="loginWithMyDigitalId"
        >
          <Loader2 v-if="loadingMyDigitalId" class="h-6 w-6 animate-spin" />
          <img v-else src="/mydigital-id-logo.png" alt="" class="h-6 w-6 rounded-sm" />
          {{ loadingMyDigitalId ? "Menyambung..." : "Log Masuk dengan MyDigital ID" }}
        </button>

        <p class="mt-6 rounded-md bg-slate-100 px-3 py-2 text-center text-[12px] text-slate-500 lg:bg-slate-50">
          Demo: demo@pemohon.my / demo1234
        </p>

        <p class="mt-8 text-center text-[12px] text-[#8792a2] lg:text-left">
          Belum ada akaun?
          <router-link to="/pemohon/daftar" class="font-medium text-blue-600 hover:text-blue-700">Daftar di sini</router-link>
        </p>
      </div>
    </div>

    <!-- Right: banner -->
    <div class="relative hidden overflow-hidden lg:block lg:w-1/2">
      <img src="/bg-tekun.png" alt="" class="absolute inset-0 h-full w-full object-cover" />
      <div class="absolute inset-0 bg-linear-to-br from-blue-800/90 via-blue-900/88 to-slate-900/92" />
      <div class="silk-layer">
        <div class="silk-blob silk-blob-1" />
        <div class="silk-blob silk-blob-2" />
        <div class="silk-blob silk-blob-3" />
        <div class="silk-blob silk-blob-4" />
      </div>

      <div class="relative flex h-full flex-col justify-center px-14 py-16 text-white">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-300">Sistem Pengurusan Pembiayaan TEKUN</p>
        <h2 class="mt-3 max-w-md text-3xl font-bold leading-tight">
          Permohonan Pembiayaan Perniagaan Yang Mudah &amp; Pantas
        </h2>
        <p class="mt-4 max-w-sm text-sm leading-relaxed text-blue-100">
          Daftar, semak kelayakan, dan pantau permohonan pembiayaan anda dalam satu portal.
        </p>

        <div class="mt-10 space-y-4">
          <div v-for="feature in features" :key="feature.label" class="flex items-center gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/10">
              <component :is="feature.icon" class="h-4.5 w-4.5 text-blue-200" />
            </div>
            <span class="text-sm font-medium text-blue-50">{{ feature.label }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.silk-layer {
  position: absolute;
  inset: 0;
  overflow: hidden;
}

.silk-blob {
  position: absolute;
  border-radius: 9999px;
  filter: blur(40px);
  mix-blend-mode: overlay;
  opacity: 1;
  will-change: transform;
}

.silk-blob-1 {
  top: -20%;
  right: -15%;
  height: 38rem;
  width: 38rem;
  background: radial-gradient(circle, rgba(240, 249, 255, 1), rgba(59, 130, 246, 0.45) 60%, transparent 78%);
  animation: silk-drift-1 7s ease-in-out infinite;
}

.silk-blob-2 {
  bottom: -5%;
  left: -18%;
  height: 32rem;
  width: 32rem;
  background: radial-gradient(circle, rgba(219, 234, 254, 1), rgba(29, 78, 216, 0.4) 60%, transparent 78%);
  animation: silk-drift-2 8.5s ease-in-out infinite;
}

.silk-blob-3 {
  bottom: -25%;
  right: 10%;
  height: 36rem;
  width: 36rem;
  background: radial-gradient(circle, rgba(224, 231, 255, 0.95), rgba(15, 23, 42, 0.3) 60%, transparent 78%);
  animation: silk-drift-3 10s ease-in-out infinite;
}

.silk-blob-4 {
  top: 30%;
  left: 20%;
  height: 24rem;
  width: 24rem;
  background: radial-gradient(circle, rgba(191, 219, 254, 0.9), rgba(37, 99, 235, 0.35) 60%, transparent 78%);
  animation: silk-drift-4 6s ease-in-out infinite;
}

@keyframes silk-drift-1 {
  0%, 100% { transform: translate(0, 0) scale(1) rotate(0deg); }
  33% { transform: translate(-180px, 140px) scale(1.5) rotate(25deg); }
  66% { transform: translate(100px, 80px) scale(0.75) rotate(-18deg); }
}

@keyframes silk-drift-2 {
  0%, 100% { transform: translate(0, 0) scale(1) rotate(0deg); }
  33% { transform: translate(170px, -120px) scale(1.4) rotate(-28deg); }
  66% { transform: translate(-80px, -50px) scale(0.7) rotate(20deg); }
}

@keyframes silk-drift-3 {
  0%, 100% { transform: translate(0, 0) scale(1) rotate(0deg); }
  33% { transform: translate(-140px, -170px) scale(0.65) rotate(30deg); }
  66% { transform: translate(120px, -70px) scale(1.35) rotate(-22deg); }
}

@keyframes silk-drift-4 {
  0%, 100% { transform: translate(0, 0) scale(1) rotate(0deg); }
  50% { transform: translate(90px, -100px) scale(1.3) rotate(-25deg); }
}

@media (prefers-reduced-motion: reduce) {
  .silk-blob {
    animation: none;
  }
}
</style>
