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
import {
  PEMBIAYAAN_DUMMY,
  TRANSAKSI_DUMMY,
  computeRestructure,
  computeSettlement,
  type PembiayaanAkaun,
  type TransaksiItem,
} from "@/data/pembiayaan-dummy";
import { requestOtp as requestOtpApi, verifyOtp as verifyOtpApi, type OtpChannel } from "@/api/otp";

const SESSION_KEY = "pemohon.session.v2";

const DEMO_ACCOUNT = {
  nama: "Mohd Fauzy Mat Yusop",
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
  foto: string;
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

  const pembiayaanList = ref<PembiayaanAkaun[]>(PEMBIAYAAN_DUMMY.map((a) => ({ ...a })));
  const transaksiMap = ref<Record<string, TransaksiItem[]>>(
    Object.fromEntries(Object.entries(TRANSAKSI_DUMMY).map(([k, v]) => [k, v.map((t) => ({ ...t }))])),
  );

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
      pembiayaanList: pembiayaanList.value,
      transaksiMap: transaksiMap.value,
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
      pembiayaanList.value = snapshot.pembiayaanList ?? PEMBIAYAAN_DUMMY.map((a) => ({ ...a }));
      transaksiMap.value = snapshot.transaksiMap
        ?? Object.fromEntries(Object.entries(TRANSAKSI_DUMMY).map(([k, v]) => [k, v.map((t) => ({ ...t }))]));
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

  function loginWithMyDigitalId() {
    if (!account.value) {
      account.value = { ...DEMO_ACCOUNT };
      profil.value = { ...profil.value, nama: DEMO_ACCOUNT.nama, email: DEMO_ACCOUNT.email, telefon: DEMO_ACCOUNT.telefon };
    }
    isLoggedIn.value = true;
  }

  function registerWithMyDigitalId() {
    if (!account.value) {
      account.value = { ...DEMO_ACCOUNT };
      profil.value = { ...profil.value, nama: DEMO_ACCOUNT.nama, email: DEMO_ACCOUNT.email, telefon: DEMO_ACCOUNT.telefon };
    }
    otp.value.verified = true;
    isLoggedIn.value = true;
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

  function getPembiayaan(id: string): PembiayaanAkaun | undefined {
    return pembiayaanList.value.find((a) => a.id === id);
  }

  function prependTransaksi(akaunId: string, trx: TransaksiItem) {
    const existing = transaksiMap.value[akaunId] ?? [];
    transaksiMap.value = { ...transaksiMap.value, [akaunId]: [trx, ...existing] };
  }

  function refCode(prefix: string): string {
    return `${prefix}${Math.floor(1000000 + Math.random() * 9000000)}`;
  }

  function todayLabel(): string {
    return new Date().toLocaleDateString("ms-MY", { day: "numeric", month: "short", year: "numeric" });
  }

  function makePayment(akaunId: string, amount: number, kaedah: string) {
    const akaun = getPembiayaan(akaunId);
    if (!akaun) throw new Error("Akaun pembiayaan tidak dijumpai.");

    // Clear arrears first, then reduce outstanding principal/profit.
    const tunggakanCleared = Math.min(akaun.tunggakan, amount);
    akaun.tunggakan = Math.max(0, akaun.tunggakan - amount);
    akaun.bakiAkhir = Math.max(0, akaun.bakiAkhir - amount);
    const pokokShare = Math.min(akaun.bakiPokok, Math.round(amount * 0.9));
    akaun.bakiPokok = Math.max(0, akaun.bakiPokok - pokokShare);
    akaun.bakiKeuntungan = Math.max(0, akaun.bakiAkhir - akaun.bakiPokok);
    if (amount > tunggakanCleared) {
      akaun.ansuranDibayar = Math.min(akaun.jumlahAnsuran, akaun.ansuranDibayar + 1);
    }

    if (akaun.bakiAkhir <= 0) {
      akaun.status = "Selesai";
      akaun.statusKey = "selesai";
    } else if (akaun.tunggakan <= 0) {
      akaun.status = "Aktif";
      akaun.statusKey = "aktif";
    }

    const tarikh = todayLabel();
    const rujukan = refCode(kaedah.toLowerCase().includes("wallet") ? "EW-" : "FPX");
    prependTransaksi(akaunId, {
      id: refCode("TRX-"),
      tarikh,
      jenis: "bayaran",
      keterangan: "Bayaran ansuran (dalam talian)",
      jumlah: amount,
      kaedah,
      status: "Berjaya",
      rujukan,
    });

    return { rujukan, tarikh, jumlah: amount, kaedah, bakiBaru: akaun.bakiAkhir };
  }

  function applyRestructure(akaunId: string, tempohBaharu: number, sebab: string) {
    const akaun = getPembiayaan(akaunId);
    if (!akaun) throw new Error("Akaun pembiayaan tidak dijumpai.");

    const quote = computeRestructure(akaun, tempohBaharu);
    akaun.tempohBulan = tempohBaharu;
    akaun.bayaranBulanan = quote.bayaranBaharu;
    akaun.jumlahAnsuran = akaun.ansuranDibayar + tempohBaharu;

    prependTransaksi(akaunId, {
      id: refCode("TRX-"),
      tarikh: todayLabel(),
      jenis: "bayaran",
      keterangan: `Penstrukturan semula pembiayaan (${tempohBaharu} bulan)${sebab ? ` — ${sebab}` : ""}`,
      jumlah: 0,
      status: "Diluluskan",
      rujukan: refCode("RST-"),
    });

    return quote;
  }

  function settleEarly(akaunId: string, kaedah: string) {
    const akaun = getPembiayaan(akaunId);
    if (!akaun) throw new Error("Akaun pembiayaan tidak dijumpai.");

    const quote = computeSettlement(akaun);
    akaun.bakiAkhir = 0;
    akaun.bakiPokok = 0;
    akaun.bakiKeuntungan = 0;
    akaun.tunggakan = 0;
    akaun.ansuranDibayar = akaun.jumlahAnsuran;
    akaun.status = "Selesai";
    akaun.statusKey = "selesai";

    const tarikh = todayLabel();
    const rujukan = refCode("STL-");
    prependTransaksi(akaunId, {
      id: refCode("TRX-"),
      tarikh,
      jenis: "bayaran",
      keterangan: "Penyelesaian awal pembiayaan",
      jumlah: quote.amaunBersih,
      kaedah,
      status: "Berjaya",
      rujukan,
    });

    return { ...quote, rujukan, tarikh, kaedah };
  }

  watch(
    [isLoggedIn, account, profil, onboarding, otp, permohonanList, draftPermohonan, pembiayaanList, transaksiMap],
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
    pembiayaanList,
    transaksiMap,
    initFromStorage,
    register,
    requestOtp,
    verifyOtp,
    login,
    loginWithMyDigitalId,
    registerWithMyDigitalId,
    logout,
    runEligibilityCheck,
    runEkyc,
    saveDraft,
    loadDraft,
    clearDraft,
    createPermohonan,
    getPembiayaan,
    makePayment,
    applyRestructure,
    settleEarly,
  };
});
