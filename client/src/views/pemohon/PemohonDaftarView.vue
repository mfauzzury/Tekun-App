<script setup lang="ts">
import { computed, ref } from "vue";
import { useRouter } from "vue-router";
import { AlertCircle, ArrowRight, Eye, EyeOff, Mail, MessageSquare } from "lucide-vue-next";

import PemohonMiniStepper from "@/components/pemohon/PemohonMiniStepper.vue";
import PemohonOtpInput from "@/components/pemohon/PemohonOtpInput.vue";
import AppToastRegion from "@/components/AppToastRegion.vue";
import { useToast } from "@/composables/useToast";
import { usePemohonStore } from "@/stores/pemohon";
import type { OtpChannel } from "@/api/otp";

const router = useRouter();
const pemohon = usePemohonStore();
const toast = useToast();

const STEPS = [
  { id: "akaun", label: "Butiran Akaun" },
  { id: "otp", label: "Pengesahan OTP" },
];
const currentStep = ref(0);
const channel = ref<OtpChannel>("sms");

const form = ref({ nama: "", email: "", telefon: "", password: "" });
const showPassword = ref(false);
const error = ref("");
const otpValue = ref("");
const otpError = ref("");
const submittingAccount = ref(false);
const verifying = ref(false);
const resending = ref(false);

const otpDestination = computed(() => (channel.value === "sms" ? form.value.telefon : form.value.email));
const channelLabel = computed(() => (channel.value === "sms" ? "SMS" : "emel"));

async function submitAccountDetails() {
  error.value = "";
  if (!form.value.nama.trim()) { error.value = "Sila masukkan nama penuh."; return; }
  if (!/^\S+@\S+\.\S+$/.test(form.value.email)) { error.value = "Sila masukkan alamat emel yang sah."; return; }
  if (!form.value.telefon.trim()) { error.value = "Sila masukkan nombor telefon."; return; }
  if (form.value.password.length < 8) { error.value = "Kata laluan mesti sekurang-kurangnya 8 aksara."; return; }

  submittingAccount.value = true;
  try {
    await pemohon.register({ ...form.value }, channel.value);
    toast.success("OTP Dihantar", `Kod pengesahan telah dihantar ke ${otpDestination.value} melalui ${channelLabel.value}.`);
    currentStep.value = 1;
  } catch (e) {
    error.value = e instanceof Error ? e.message : "Gagal menghantar OTP. Sila cuba lagi.";
  } finally {
    submittingAccount.value = false;
  }
}

async function verifyOtp() {
  otpError.value = "";
  verifying.value = true;
  try {
    const isValid = await pemohon.verifyOtp(otpValue.value);
    if (!isValid) {
      otpError.value = "Kod OTP tidak sah atau telah tamat tempoh. Sila cuba lagi.";
      return;
    }
    toast.success("Pendaftaran Berjaya", "Akaun anda telah disahkan.");
    router.push({ name: "pemohon-semakan-kelayakan" });
  } catch (e) {
    otpError.value = e instanceof Error ? e.message : "Gagal mengesahkan OTP. Sila cuba lagi.";
  } finally {
    verifying.value = false;
  }
}

async function resendOtp() {
  resending.value = true;
  try {
    await pemohon.requestOtp(channel.value);
    toast.success("OTP Dihantar Semula", `Kod pengesahan baharu telah dihantar ke ${otpDestination.value}.`);
  } catch (e) {
    toast.error("Gagal Menghantar OTP", e instanceof Error ? e.message : "Sila cuba lagi.");
  } finally {
    resending.value = false;
  }
}
</script>

<template>
  <div class="flex min-h-screen flex-col items-center justify-center bg-[#f6f9fc] px-4 py-10">
    <div class="w-full max-w-[440px]">
      <div class="mb-6 flex justify-center">
        <img src="/logo_tekun.png" alt="TEKUN Nasional" class="h-9 w-auto" />
      </div>

      <div class="rounded-lg border border-[#e3e8ee] bg-white px-8 pb-8 pt-6 shadow-[0_2px_4px_rgba(0,0,0,0.05),0_1px_2px_rgba(0,0,0,0.06)]">
        <h1 class="mb-1 text-center text-xl font-semibold tracking-tight text-[#1a1f36]">Daftar Akaun Pemohon</h1>
        <p class="mb-6 text-center text-[13px] text-[#697386]">Portal Pemohon SPPT TEKUN Nasional</p>

        <div class="mb-6">
          <PemohonMiniStepper :steps="STEPS" :current-step="currentStep" />
        </div>

        <form v-if="currentStep === 0" class="space-y-4" @submit.prevent="submitAccountDetails">
          <div class="space-y-1.5">
            <label class="text-[13px] font-medium text-[#1a1f36]">Nama Penuh</label>
            <input
              v-model="form.nama"
              type="text"
              class="w-full rounded-md border border-[#d8dee4] bg-white px-3 py-[9px] text-sm text-[#1a1f36] focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
              placeholder="cth: Ahmad bin Abdullah"
            />
          </div>
          <div class="space-y-1.5">
            <label class="text-[13px] font-medium text-[#1a1f36]">Emel</label>
            <input
              v-model="form.email"
              type="email"
              class="w-full rounded-md border border-[#d8dee4] bg-white px-3 py-[9px] text-sm text-[#1a1f36] focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
              placeholder="nama@email.com"
            />
          </div>
          <div class="space-y-1.5">
            <label class="text-[13px] font-medium text-[#1a1f36]">No. Telefon</label>
            <input
              v-model="form.telefon"
              type="tel"
              class="w-full rounded-md border border-[#d8dee4] bg-white px-3 py-[9px] text-sm text-[#1a1f36] focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
              placeholder="012-3456789"
            />
          </div>
          <div class="space-y-1.5">
            <label class="text-[13px] font-medium text-[#1a1f36]">Kata Laluan</label>
            <div class="relative">
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                class="w-full rounded-md border border-[#d8dee4] bg-white px-3 py-[9px] pr-10 text-sm text-[#1a1f36] focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                placeholder="Sekurang-kurangnya 8 aksara"
              />
              <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-[#a3acb9] hover:text-[#697386]" @click="showPassword = !showPassword">
                <EyeOff v-if="showPassword" class="h-4 w-4" />
                <Eye v-else class="h-4 w-4" />
              </button>
            </div>
          </div>

          <div class="space-y-1.5">
            <label class="text-[13px] font-medium text-[#1a1f36]">Hantar OTP Melalui</label>
            <div class="grid grid-cols-2 gap-2">
              <button
                type="button"
                class="flex items-center justify-center gap-1.5 rounded-md border px-3 py-[9px] text-sm font-medium transition-colors"
                :class="channel === 'sms' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-[#d8dee4] text-[#697386] hover:bg-slate-50'"
                @click="channel = 'sms'"
              >
                <MessageSquare class="h-4 w-4" />
                SMS
              </button>
              <button
                type="button"
                class="flex items-center justify-center gap-1.5 rounded-md border px-3 py-[9px] text-sm font-medium transition-colors"
                :class="channel === 'email' ? 'border-blue-500 bg-blue-50 text-blue-700' : 'border-[#d8dee4] text-[#697386] hover:bg-slate-50'"
                @click="channel = 'email'"
              >
                <Mail class="h-4 w-4" />
                Emel
              </button>
            </div>
          </div>

          <div v-if="error" class="flex items-center gap-2 rounded-md border border-[#f8d7da] bg-[#fdf2f2] px-3.5 py-2.5 text-[13px] text-[#cd3d64]">
            <AlertCircle class="h-4 w-4 shrink-0" />
            {{ error }}
          </div>

          <button
            type="submit"
            class="flex w-full items-center justify-center gap-2 rounded-md bg-blue-600 px-4 py-[9px] text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-700 disabled:opacity-60"
            :disabled="submittingAccount"
          >
            {{ submittingAccount ? "Menghantar OTP..." : "Seterusnya" }}
            <ArrowRight v-if="!submittingAccount" class="h-4 w-4" />
          </button>
        </form>

        <form v-else class="space-y-4" @submit.prevent="verifyOtp">
          <p class="text-center text-sm text-slate-600">
            Kod pengesahan telah dihantar ke {{ otpDestination }} melalui {{ channelLabel }}. Sila masukkan kod 6 digit di bawah.
          </p>

          <div class="flex justify-center">
            <PemohonOtpInput v-model="otpValue" :length="6" />
          </div>

          <div v-if="otpError" class="flex items-center gap-2 rounded-md border border-[#f8d7da] bg-[#fdf2f2] px-3.5 py-2.5 text-[13px] text-[#cd3d64]">
            <AlertCircle class="h-4 w-4 shrink-0" />
            {{ otpError }}
          </div>

          <button
            type="submit"
            class="flex w-full items-center justify-center gap-2 rounded-md bg-blue-600 px-4 py-[9px] text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-700 disabled:opacity-60"
            :disabled="otpValue.length !== 6 || verifying"
          >
            {{ verifying ? "Mengesahkan..." : "Sahkan & Log Masuk" }}
            <ArrowRight v-if="!verifying" class="h-4 w-4" />
          </button>

          <button type="button" class="w-full text-center text-[13px] font-medium text-blue-600 hover:text-blue-700 disabled:opacity-60" :disabled="resending" @click="resendOtp">
            {{ resending ? "Menghantar semula..." : "Hantar semula kod OTP" }}
          </button>
        </form>
      </div>

      <p class="mt-6 text-center text-[13px] text-[#697386]">
        Sudah ada akaun?
        <router-link to="/pemohon/log-masuk" class="font-medium text-blue-600 hover:text-blue-700">Log Masuk</router-link>
      </p>
    </div>

    <div class="fixed right-4 top-4 z-50 h-16">
      <AppToastRegion />
    </div>
  </div>
</template>
