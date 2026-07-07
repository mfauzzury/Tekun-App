import { ref, watch } from "vue";
import { defineStore } from "pinia";

import {
  PERMOHONAN_DUMMY,
  PROFIL_USAHAWAN_DUMMY,
  evaluateEligibility,
  simulateEkyc,
  type EkycSimulationInput,
  type EligibilityInput,
} from "@/data/portal-dummy";
import { runHardRuleCheck } from "@/api/pemohon";
import type { HardRuleCheckInput, HardRuleCheckResult } from "@/types";
import { requestOtp as requestOtpApi, verifyOtp as verifyOtpApi, type OtpChannel } from "@/api/otp";

const SESSION_KEY = "pemohon.session";

const DEMO_ACCOUNT = {
  nama: "Ahmad bin Abdullah",
  email: "demo@pemohon.my",
  telefon: "012-3456789",
  password: "demo1234",
};

export interface PemohonAccount {
  nama: string;
  email: string;
  telefon: string;
  password: string;
}

export interface PemohonProfil {
  nama: string;
  noKp: string;
  email: string;
  telefon: string;
  alamat: string;
  perniagaan: string;
  noSsm: string;
  sektor: string;
  statusSyariah: string;
}

export interface PermohonanItem {
  id: string;
  produk: string;
  jumlah: string;
  tarikh: string;
  status: string;
  statusKey: string;
}

export const usePemohonStore = defineStore("pemohon", () => {
  const isLoggedIn = ref(false);
  const account = ref<PemohonAccount | null>(null);
  const profil = ref<PemohonProfil>({ ...PROFIL_USAHAWAN_DUMMY });

  const onboarding = ref({
    eligibilityChecked: false,
    eligibilityResult: null as "lulus" | "gagal" | null,
    eligibilityReasons: [] as string[],
    ekycVerified: false,
    ekycResult: null as "lulus" | "gagal" | null,
  });

  const otp = ref({
    code: null as string | null,
    sentAt: null as number | null,
    verified: false,
    target: null as "sms" | "email" | null,
  });

  const permohonanList = ref<PermohonanItem[]>([...PERMOHONAN_DUMMY]);
  const draftPermohonan = ref<Record<string, unknown> | null>(null);

  let restoring = false;

  function persist() {
    if (typeof window === "undefined" || restoring) return;
    const snapshot = {
      isLoggedIn: isLoggedIn.value,
      account: account.value,
      profil: profil.value,
      onboarding: onboarding.value,
      otp: otp.value,
      permohonanList: permohonanList.value,
      draftPermohonan: draftPermohonan.value,
    };
    localStorage.setItem(SESSION_KEY, JSON.stringify(snapshot));
  }

  function initFromStorage() {
    if (typeof window === "undefined") return;
    const raw = localStorage.getItem(SESSION_KEY);
    if (!raw) return;

    try {
      const snapshot = JSON.parse(raw);
      restoring = true;
      isLoggedIn.value = snapshot.isLoggedIn ?? false;
      account.value = snapshot.account ?? null;
      profil.value = snapshot.profil ?? { ...PROFIL_USAHAWAN_DUMMY };
      onboarding.value = snapshot.onboarding ?? onboarding.value;
      otp.value = snapshot.otp ?? otp.value;
      permohonanList.value = snapshot.permohonanList ?? [...PERMOHONAN_DUMMY];
      draftPermohonan.value = snapshot.draftPermohonan ?? null;
    } catch {
      // Corrupt/old snapshot shape — ignore and keep defaults.
    } finally {
      restoring = false;
    }
  }

  async function register(payload: { nama: string; email: string; telefon: string; password: string }, channel: OtpChannel = "sms") {
    account.value = { ...payload };
    profil.value = { ...profil.value, nama: payload.nama, email: payload.email, telefon: payload.telefon };
    await requestOtp(channel);
  }

  function destinationFor(channel: OtpChannel): string {
    const destination = channel === "sms" ? account.value?.telefon : account.value?.email;
    if (!destination) throw new Error(channel === "sms" ? "Tiada nombor telefon berdaftar." : "Tiada emel berdaftar.");
    return destination;
  }

  async function requestOtp(channel: OtpChannel) {
    await requestOtpApi(channel, destinationFor(channel));
    otp.value = { code: null, sentAt: Date.now(), verified: false, target: channel };
  }

  async function verifyOtp(code: string): Promise<boolean> {
    const channel = otp.value.target ?? "sms";
    const response = await verifyOtpApi(channel, destinationFor(channel), code.trim());
    if (response.data.verified) {
      otp.value.verified = true;
      isLoggedIn.value = true;
      return true;
    }
    return false;
  }

  function login(email: string, password: string): boolean {
    const candidate = account.value?.email === email && account.value?.password === password;
    const isDemo = email === DEMO_ACCOUNT.email && password === DEMO_ACCOUNT.password;

    if (isDemo && !account.value) {
      account.value = { ...DEMO_ACCOUNT };
      profil.value = { ...profil.value, nama: DEMO_ACCOUNT.nama, email: DEMO_ACCOUNT.email, telefon: DEMO_ACCOUNT.telefon };
    }

    if (candidate || isDemo) {
      isLoggedIn.value = true;
      return true;
    }
    return false;
  }

  function logout() {
    isLoggedIn.value = false;
  }

  async function runEligibilityCheck(input: EligibilityInput | HardRuleCheckInput): Promise<HardRuleCheckResult> {
    try {
      const res = await runHardRuleCheck({
        umur: input.umur,
        noKp: "noKp" in input ? input.noKp : undefined,
        pendapatanBulanan: "pendapatanBulanan" in input ? input.pendapatanBulanan : undefined,
        jumlahKomitmenSediaAda: "jumlahKomitmenSediaAda" in input ? input.jumlahKomitmenSediaAda : undefined,
      });
      const result = res.data;
      onboarding.value.eligibilityChecked = true;
      onboarding.value.eligibilityResult = result.eligible ? "lulus" : "gagal";
      onboarding.value.eligibilityReasons = result.reasons;
      return result;
    } catch {
      const fallback = evaluateEligibility(input as EligibilityInput);
      const result: HardRuleCheckResult = {
        eligible: fallback.eligible,
        autoReject: !fallback.eligible,
        reasons: fallback.reasons,
        failedRules: [],
      };
      onboarding.value.eligibilityChecked = true;
      onboarding.value.eligibilityResult = result.eligible ? "lulus" : "gagal";
      onboarding.value.eligibilityReasons = result.reasons;
      return result;
    }
  }

  function runEkyc(input: EkycSimulationInput) {
    const result = simulateEkyc(input);
    onboarding.value.ekycVerified = true;
    onboarding.value.ekycResult = result.status;
    return result;
  }

  function saveDraft(data: Record<string, unknown>) {
    draftPermohonan.value = { ...data };
  }

  function loadDraft() {
    return draftPermohonan.value;
  }

  function clearDraft() {
    draftPermohonan.value = null;
  }

  function createPermohonan(formData: { produk: string; jumlah: number }) {
    const year = new Date().getFullYear();
    const suffix = String(Math.floor(100000 + Math.random() * 900000));
    const item: PermohonanItem = {
      id: `SPPT-${year}-${suffix}`,
      produk: formData.produk,
      jumlah: `RM ${formData.jumlah.toLocaleString("ms-MY")}`,
      tarikh: new Date().toLocaleDateString("ms-MY", { day: "numeric", month: "short", year: "numeric" }),
      status: "Dalam Semakan",
      statusKey: "dalam_semakan",
    };
    permohonanList.value = [item, ...permohonanList.value];
    clearDraft();
    return item;
  }

  watch(
    [isLoggedIn, account, profil, onboarding, otp, permohonanList, draftPermohonan],
    persist,
    { deep: true },
  );

  return {
    isLoggedIn,
    account,
    profil,
    onboarding,
    otp,
    permohonanList,
    draftPermohonan,
    initFromStorage,
    register,
    requestOtp,
    verifyOtp,
    login,
    logout,
    runEligibilityCheck,
    runEkyc,
    saveDraft,
    loadDraft,
    clearDraft,
    createPermohonan,
  };
});
