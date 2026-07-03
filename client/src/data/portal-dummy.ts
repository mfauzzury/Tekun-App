/** Dummy data for Portal Pemohon - no API/tables yet */

export const STATUS_OPTIONS = [
  { value: "semua", label: "Semua Status" },
  { value: "diterima", label: "Diterima" },
  { value: "dalam_semakan", label: "Dalam Semakan" },
  { value: "dokumen_tambahan", label: "Dokumen Tambahan Diperlukan" },
  { value: "lulus", label: "Lulus" },
  { value: "tidak_lulus", label: "Tidak Lulus" },
];

export const PERMOHONAN_DUMMY = [
  {
    id: "SPPT-2024-001234",
    produk: "SPUMI",
    jumlah: "RM 50,000",
    tarikh: "5 Mac 2024",
    status: "Dalam Semakan",
    statusKey: "dalam_semakan",
  },
  {
    id: "SPPT-2024-001189",
    produk: "Kontrak-i",
    jumlah: "RM 30,000",
    tarikh: "28 Feb 2024",
    status: "Dokumen Tambahan Diperlukan",
    statusKey: "dokumen_tambahan",
  },
  {
    id: "SPPT-2023-009876",
    produk: "Tekun Niaga",
    jumlah: "RM 75,000",
    tarikh: "15 Dis 2023",
    status: "Lulus",
    statusKey: "lulus",
  },
  {
    id: "SPPT-2023-008521",
    produk: "SPUMI",
    jumlah: "RM 20,000",
    tarikh: "2 Nov 2023",
    status: "Tidak Lulus",
    statusKey: "tidak_lulus",
  },
];

export const PRODUK_OPTIONS = [
  { value: "spumi", label: "SPUMI" },
  { value: "kontrak_i", label: "Kontrak-i" },
  { value: "tekun_niaga", label: "Tekun Niaga" },
  { value: "tawarruq", label: "Tawarruq" },
  { value: "teman_tekun", label: "Teman TEKUN" },
];

export const TEMUDUGA_DUMMY = [
  {
    id: "T-001",
    permohonanId: "SPPT-2024-001234",
    tarikh: "15 Mac 2024",
    masa: "10:00 pagi",
    lokasi: "Pejabat TEKUN Cawangan Selangor",
    jenis: "Fizikal",
    status: "Dijadualkan",
  },
  {
    id: "T-002",
    permohonanId: "SPPT-2024-001189",
    tarikh: "18 Mac 2024",
    masa: "2:30 petang",
    lokasi: "Temuduga Maya (Google Meet)",
    jenis: "Maya",
    status: "Menunggu Pengesahan",
  },
  {
    id: "T-003",
    permohonanId: "SPPT-2023-009876",
    tarikh: "20 Dis 2023",
    masa: "9:00 pagi",
    lokasi: "Pejabat TEKUN Cawangan Selangor",
    jenis: "Fizikal",
    status: "Selesai",
  },
];

export const DOKUMEN_CHECKLIST_DUMMY = [
  { id: "d1", nama: "Kad Pengenalan (depan & belakang)", status: "lengkap", tarikh: "5 Mac 2024" },
  { id: "d2", nama: "Lesen Perniagaan", status: "lengkap", tarikh: "5 Mac 2024" },
  { id: "d3", nama: "Penyata Bank 3 bulan terkini", status: "menunggu_semakan", tarikh: "6 Mac 2024" },
  { id: "d4", nama: "SSM Form 9 / Borang D", status: "diperlukan", tarikh: null },
];

export const PROFIL_USAHAWAN_DUMMY = {
  nama: "Ahmad bin Abdullah",
  noKp: "850101-14-5678",
  email: "ahmad.abdullah@email.com",
  telefon: "012-3456789",
  alamat: "No. 12, Jalan Merdeka, Taman Sri Raya, 43000 Kajang, Selangor",
  perniagaan: "Kedai Runcit Sri Raya",
  noSsm: "SA1234567",
  sektor: "Peruncitan",
  statusSyariah: "Patuh Syariah",
};

export const FAQ_DUMMY = [
  {
    q: "Apakah dokumen yang diperlukan untuk permohonan?",
    a: "Dokumen asas: Kad Pengenalan, Lesen Perniagaan, Penyata Bank 3 bulan, SSM Form 9. Dokumen tambahan bergantung kepada jenis produk.",
  },
  {
    q: "Berapa lama masa semakan permohonan?",
    a: "Biasanya 14-21 hari bekerja selepas dokumen lengkap diterima.",
  },
  {
    q: "Bolehkah saya semak status permohonan secara dalam talian?",
    a: "Ya, log masuk ke Portal SPPT dan pilih 'Permohonan Saya' untuk semakan status masa nyata.",
  },
  {
    q: "Bagaimana untuk menukar tarikh temuduga?",
    a: "Pergi ke halaman Temuduga, pilih permohonan dan klik 'Tukar Tarikh' untuk memilih slot baharu.",
  },
];
