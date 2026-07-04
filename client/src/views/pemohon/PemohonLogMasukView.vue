<script setup lang="ts">
import { ref } from "vue";
import { useRouter } from "vue-router";
import { AlertCircle, ArrowRight, Eye, EyeOff, Shield } from "lucide-vue-next";
import { usePemohonStore } from "@/stores/pemohon";

const router = useRouter();
const pemohon = usePemohonStore();

const email = ref("demo@pemohon.my");
const password = ref("demo1234");
const showPassword = ref(false);
const error = ref("");

function submit() {
  error.value = "";
  const ok = pemohon.login(email.value, password.value);
  if (!ok) {
    error.value = "Emel atau kata laluan tidak sah.";
    return;
  }
  router.push({ name: "pemohon-dashboard" });
}
</script>

<template>
  <div class="flex min-h-screen flex-col items-center justify-center bg-[#f6f9fc] px-4">
    <div class="w-full max-w-[400px]">
      <div class="mb-7 flex justify-center">
        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-violet-600 to-indigo-600">
          <Shield class="h-4 w-4 text-white" />
        </div>
      </div>

      <div class="rounded-lg border border-[#e3e8ee] bg-white px-10 pb-10 pt-8 shadow-[0_2px_4px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.06)]">
        <h1 class="mb-1 text-center text-xl font-semibold tracking-tight text-[#1a1f36]">Log Masuk Pemohon</h1>
        <p class="mb-8 text-center text-[13px] text-[#697386]">Portal Pemohon SPPT TEKUN Nasional</p>

        <form class="space-y-5" @submit.prevent="submit">
          <div class="space-y-1.5">
            <label class="text-[13px] font-medium text-[#1a1f36]">Emel</label>
            <input
              v-model="email"
              type="email"
              class="w-full rounded-md border border-[#d8dee4] bg-white px-3 py-[9px] text-sm text-[#1a1f36] shadow-[0_1px_2px_rgba(0,0,0,0.04)] focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-200"
              placeholder="nama@email.com"
            />
          </div>

          <div class="space-y-1.5">
            <label class="text-[13px] font-medium text-[#1a1f36]">Kata Laluan</label>
            <div class="relative">
              <input
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                class="w-full rounded-md border border-[#d8dee4] bg-white px-3 py-[9px] pr-10 text-sm text-[#1a1f36] shadow-[0_1px_2px_rgba(0,0,0,0.04)] focus:border-violet-500 focus:outline-none focus:ring-2 focus:ring-violet-200"
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
            class="flex w-full items-center justify-center gap-2 rounded-md bg-violet-600 px-4 py-[9px] text-sm font-medium text-white shadow-sm transition-colors hover:bg-violet-700"
          >
            Log Masuk
            <ArrowRight class="h-4 w-4" />
          </button>
        </form>

        <p class="mt-6 rounded-md bg-slate-50 px-3 py-2 text-center text-[12px] text-slate-500">
          Demo: demo@pemohon.my / demo1234
        </p>
      </div>

      <p class="mt-8 text-center text-[12px] text-[#8792a2]">
        Belum ada akaun?
        <router-link to="/pemohon/daftar" class="font-medium text-violet-600 hover:text-violet-700">Daftar di sini</router-link>
      </p>
    </div>
  </div>
</template>
