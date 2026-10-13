<script setup lang="ts">
import { computed, ref } from "vue";
import { useRouter } from "vue-router";
import { AlertCircle, ArrowRight, Eye, EyeOff, Mail, MessageSquare, ShieldCheck } from "lucide-vue-next";

import PemohonMiniStepper from "@/components/pemohon/PemohonMiniStepper.vue";
import PemohonOtpInput from "@/components/pemohon/PemohonOtpInput.vue";
import AppToastRegion from "@/components/AppToastRegion.vue";
import PButton from "@/components/pemohon/ui/PButton.vue";
import PField from "@/components/pemohon/ui/PField.vue";
import { useToast } from "@/composables/useToast";
import { usePemohonStore } from "@/stores/pemohon";
import { pInputClass } from "@/utils/pemohon-ui";
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
const loadingMyDigitalId = ref(false);

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

async function registerWithMyDigitalId() {
  loadingMyDigitalId.value = true;
  await new Promise((resolve) => setTimeout(resolve, 800));
  pemohon.registerWithMyDigitalId();
  toast.success("Pendaftaran Berjaya", "Akaun anda telah disahkan melalui MyDigital ID.");
  router.push({ name: "pemohon-semakan-kelayakan" });
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
        <div class="mb-6 text-center">
          <h1 class="text-2xl font-bold tracking-tight text-ink-900">Daftar akaun pemohon</h1>
          <p class="mt-1.5 text-sm text-slate-500">Portal Pemohon SPPT TEKUN Nasional</p>
        </div>

        <div class="mb-7 px-2">
          <PemohonMiniStepper :steps="STEPS" :current-step="currentStep" />
        </div>

        <template v-if="currentStep === 0">
          <PButton block size="lg" variant="outline" :loading="loadingMyDigitalId" @click="registerWithMyDigitalId">
            <img v-if="!loadingMyDigitalId" src="/mydigital-id-logo.png" alt="" class="h-6 w-6 rounded-sm" />
            {{ loadingMyDigitalId ? "Menyambung..." : "Daftar dengan MyDigital ID" }}
          </PButton>

          <div class="relative my-6">
            <div class="absolute inset-0 flex items-center" aria-hidden="true">
              <div class="w-full border-t border-slate-200" />
            </div>
            <div class="relative flex justify-center">
              <span class="bg-white px-3 text-xs font-medium text-slate-400">atau daftar dengan emel</span>
            </div>
          </div>

          <form class="space-y-4" @submit.prevent="submitAccountDetails">
            <PField label="Nama penuh" for="daftar-nama">
              <input id="daftar-nama" v-model="form.nama" type="text" autocomplete="name" :class="pInputClass" placeholder="cth: Ahmad bin Abdullah" />
            </PField>
            <PField label="Emel" for="daftar-email">
              <input id="daftar-email" v-model="form.email" type="email" autocomplete="email" :class="pInputClass" placeholder="nama@email.com" />
            </PField>
            <PField label="No. telefon" for="daftar-telefon">
              <input id="daftar-telefon" v-model="form.telefon" type="tel" autocomplete="tel" :class="pInputClass" placeholder="012-3456789" />
            </PField>
            <PField label="Kata laluan" for="daftar-password" hint="Sekurang-kurangnya 8 aksara.">
              <div class="relative">
                <input
                  id="daftar-password"
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  autocomplete="new-password"
                  :class="[pInputClass, 'pr-11!']"
                  placeholder="Sekurang-kurangnya 8 aksara"
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

            <fieldset class="space-y-1.5">
              <legend class="mb-1.5 block text-sm font-medium text-ink-800">Hantar OTP melalui</legend>
              <div role="radiogroup" aria-label="Saluran OTP" class="grid grid-cols-2 gap-2.5">
                <button
                  type="button"
                  role="radio"
                  :aria-checked="channel === 'sms'"
                  class="flex h-11 items-center justify-center gap-2 rounded-xl border text-sm font-medium transition-all focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-100"
                  :class="channel === 'sms' ? 'border-brand-500 bg-brand-50 text-brand-700 ring-2 ring-brand-500/20' : 'border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50'"
                  @click="channel = 'sms'"
                >
                  <MessageSquare class="h-4 w-4" />
                  SMS
                </button>
                <button
                  type="button"
                  role="radio"
                  :aria-checked="channel === 'email'"
                  class="flex h-11 items-center justify-center gap-2 rounded-xl border text-sm font-medium transition-all focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-100"
                  :class="channel === 'email' ? 'border-brand-500 bg-brand-50 text-brand-700 ring-2 ring-brand-500/20' : 'border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50'"
                  @click="channel = 'email'"
                >
                  <Mail class="h-4 w-4" />
                  Emel
                </button>
              </div>
            </fieldset>

            <div v-if="error" role="alert" class="flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2.5 text-sm text-rose-700">
              <AlertCircle class="h-4 w-4 shrink-0" />
              {{ error }}
            </div>

            <PButton type="submit" block size="lg" class="mt-2" :loading="submittingAccount">
              {{ submittingAccount ? "Menghantar OTP..." : "Seterusnya" }}
              <ArrowRight v-if="!submittingAccount" class="h-4 w-4" />
            </PButton>
          </form>
        </template>

        <form v-else class="space-y-5" @submit.prevent="verifyOtp">
          <div class="text-center">
            <span class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
              <ShieldCheck class="h-6 w-6" />
            </span>
            <p class="text-sm text-slate-600">
              Kod pengesahan telah dihantar ke <span class="font-semibold text-ink-900">{{ otpDestination }}</span> melalui {{ channelLabel }}.
              Sila masukkan kod 6 digit di bawah.
            </p>
          </div>

          <div class="flex justify-center">
            <PemohonOtpInput v-model="otpValue" :length="6" />
          </div>

          <div v-if="otpError" role="alert" class="flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2.5 text-sm text-rose-700">
            <AlertCircle class="h-4 w-4 shrink-0" />
            {{ otpError }}
          </div>

          <PButton type="submit" block size="lg" :loading="verifying" :disabled="otpValue.length !== 6">
            {{ verifying ? "Mengesahkan..." : "Sahkan & log masuk" }}
            <ArrowRight v-if="!verifying" class="h-4 w-4" />
          </PButton>

          <p class="text-center text-sm text-slate-500">
            Tidak menerima kod?
            <button
              type="button"
              class="rounded font-semibold text-brand-600 hover:text-brand-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-200 disabled:opacity-60"
              :disabled="resending"
              @click="resendOtp"
            >
              {{ resending ? "Menghantar semula..." : "Hantar semula" }}
            </button>
          </p>
        </form>
      </div>

      <p class="mt-6 text-center text-sm text-slate-500">
        Sudah ada akaun?
        <router-link to="/pemohon/log-masuk" class="font-semibold text-brand-600 hover:text-brand-700">Log masuk</router-link>
      </p>
      <p class="mt-6 text-center text-xs text-slate-400">© TEKUN Nasional · Portal Usahawan</p>
    </div>

    <div class="fixed right-4 top-4 z-50 h-16">
      <AppToastRegion />
    </div>
  </div>
</template>
