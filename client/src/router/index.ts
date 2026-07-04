import { createRouter, createWebHistory } from "vue-router";
import type { RouteLocationGeneric, RouteLocationNormalizedLoaded, RouteRecordRaw } from "vue-router";

import DashboardView from "@/views/DashboardView.vue";
import MainDashboardView from "@/views/MainDashboardView.vue";
import KitchenChartsView from "@/views/KitchenChartsView.vue";
import KitchenFormsView from "@/views/KitchenFormsView.vue";
import LoginView from "@/views/LoginView.vue";
import MediaLibraryView from "@/views/MediaLibraryView.vue";
import KitchenSinkView from "@/views/KitchenSinkView.vue";
import PageEditorView from "@/views/PageEditorView.vue";
import PagesListView from "@/views/PagesListView.vue";
import PostEditorView from "@/views/PostEditorView.vue";
import PostsListView from "@/views/PostsListView.vue";
import CategoriesListView from "@/views/CategoriesListView.vue";
import CategoryEditorView from "@/views/CategoryEditorView.vue";
import DatabaseSchemaView from "@/views/DatabaseSchemaView.vue";
import DevelopersGuideView from "@/views/DevelopersGuideView.vue";
import ApiManagementView from "@/views/ApiManagementView.vue";
import MenusView from "@/views/MenusView.vue";
import StorefrontMenuView from "@/views/StorefrontMenuView.vue";
import WebfrontSettingsView from "@/views/WebfrontSettingsView.vue";
import AuditLogsView from "@/views/AuditLogsView.vue";
import QueueMonitorView from "@/views/QueueMonitorView.vue";
import ComingSoonView from "@/views/ComingSoonView.vue";
import PlaceholderView from "@/views/PlaceholderView.vue";
import PermohonanListView from "@/views/sppt/PermohonanListView.vue";
import PermohonanBaruView from "@/views/sppt/PermohonanBaruView.vue";
import PermohonanDetailView from "@/views/sppt/PermohonanDetailView.vue";
import RekodUsahawanView from "@/views/sppt/RekodUsahawanView.vue";
import DaftarUsahawanBaruView from "@/views/sppt/DaftarUsahawanBaruView.vue";
import PenilaianKelulusanView from "@/views/sppt/PenilaianKelulusanView.vue";
import PenilaianBaruView from "@/views/sppt/PenilaianBaruView.vue";
import PengeluaranDanaView from "@/views/sppt/PengeluaranDanaView.vue";
import PengurusanAkaunView from "@/views/sppt/PengurusanAkaunView.vue";
import BayaranPembiayaanView from "@/views/sppt/BayaranPembiayaanView.vue";
import TerimaBayaranView from "@/views/sppt/TerimaBayaranView.vue";
import PemadananResitView from "@/views/sppt/PemadananResitView.vue";
import RekonsiliasiBankView from "@/views/sppt/RekonsiliasiBankView.vue";
import LebihanKekuranganView from "@/views/sppt/LebihanKekuranganView.vue";
import PenyataBayaranView from "@/views/sppt/PenyataBayaranView.vue";
import EarlySettlementView from "@/views/sppt/EarlySettlementView.vue";
import AkaunSelesaiBayarView from "@/views/sppt/AkaunSelesaiBayarView.vue";
import AIBayaranView from "@/views/sppt/AIBayaranView.vue";
import PemantauanTunggakanView from "@/views/sppt/PemantauanTunggakanView.vue";
import PemantauanUsahawanView from "@/views/sppt/PemantauanUsahawanView.vue";
import KutipanView from "@/views/sppt/KutipanView.vue";
import RekodKutipanView from "@/views/sppt/RekodKutipanView.vue";
import LitigasiView from "@/views/sppt/LitigasiView.vue";
import PanelPeguamView from "@/views/sppt/litigasi/PanelPeguamView.vue";
import AkaunNpfView from "@/views/sppt/litigasi/AkaunNpfView.vue";
import NodView from "@/views/sppt/litigasi/NodView.vue";
import SamanView from "@/views/sppt/litigasi/SamanView.vue";
import PenghakimanView from "@/views/sppt/litigasi/PenghakimanView.vue";
import ExecutionView from "@/views/sppt/litigasi/ExecutionView.vue";
import WssView from "@/views/sppt/litigasi/WssView.vue";
import GarnisheeView from "@/views/sppt/litigasi/GarnisheeView.vue";
import JdsView from "@/views/sppt/litigasi/JdsView.vue";
import KebankrapanView from "@/views/sppt/litigasi/KebankrapanView.vue";
import WindingUpView from "@/views/sppt/litigasi/WindingUpView.vue";
import LitigasiAuditView from "@/views/sppt/litigasi/LitigasiAuditView.vue";
import LitigasiLaporanView from "@/views/sppt/litigasi/LitigasiLaporanView.vue";
import KesDetailView from "@/views/sppt/litigasi/KesDetailView.vue";
import InsolvensiIntegrasiView from "@/views/sppt/litigasi/InsolvensiIntegrasiView.vue";
import PengurusanJaminanView from "@/views/sppt/PengurusanJaminanView.vue";
import KemaskiniJaminanView from "@/views/sppt/KemaskiniJaminanView.vue";
import LaporanAnalitikView from "@/views/sppt/LaporanAnalitikView.vue";
import AuditKawalanView from "@/views/sppt/AuditKawalanView.vue";
import IntegrasiView from "@/views/sppt/IntegrasiView.vue";
import TetapanView from "@/views/sppt/TetapanView.vue";
import RolesView from "@/views/RolesView.vue";
import SettingsView from "@/views/SettingsView.vue";
import SystemInfoView from "@/views/SystemInfoView.vue";
import UsersView from "@/views/UsersView.vue";
import UserEditView from "@/views/UserEditView.vue";
import StorefrontHomeView from "@/views/StorefrontHomeView.vue";
import StorefrontPageView from "@/views/StorefrontPageView.vue";
import { useAuthStore } from "@/stores/auth";
import { useSiteStore } from "@/stores/site";
import { translateRouteTitle } from "@/i18n";
import { useUiLocaleStore } from "@/stores/uiLocale";
import { usePemohonStore } from "@/stores/pemohon";

import PemohonLandingView from "@/views/pemohon/PemohonLandingView.vue";
import PemohonDaftarView from "@/views/pemohon/PemohonDaftarView.vue";
import PemohonLogMasukView from "@/views/pemohon/PemohonLogMasukView.vue";
import PemohonSemakanKelayakanView from "@/views/pemohon/PemohonSemakanKelayakanView.vue";
import PemohonEkycView from "@/views/pemohon/PemohonEkycView.vue";
import PemohonDashboardView from "@/views/pemohon/PemohonDashboardView.vue";
import PemohonPermohonanListView from "@/views/pemohon/PemohonPermohonanListView.vue";
import PemohonPermohonanBaruView from "@/views/pemohon/PemohonPermohonanBaruView.vue";
import PemohonPermohonanDetailView from "@/views/pemohon/PemohonPermohonanDetailView.vue";
import PemohonProfilView from "@/views/pemohon/PemohonProfilView.vue";
import PemohonBantuanView from "@/views/pemohon/PemohonBantuanView.vue";

const legacyAdminPaths = [
  "/login",
  "/portal/dashboard",
  "/posts",
  "/posts/new",
  "/posts/:id",
  "/categories",
  "/categories/new",
  "/categories/:id",
  "/pages",
  "/pages/new",
  "/pages/:id",
  "/media",
  "/menus",
  "/webfront-menu",
  "/webfront-settings",
  "/storefront-menu",
  "/kitchen-sink",
  "/kitchen-sink/forms",
  "/kitchen-sink/charts",
  "/development/database-schema",
  "/development/api-management",
  "/profile",
  "/settings",
  "/settings/users",
  "/settings/users/new",
  "/settings/users/:id",
  "/settings/roles",
  "/settings/audit-logs",
  "/settings/queue-monitor",
  "/settings/system",
];

// Backward-compat redirects: old /admin/settings/* → new /admin/platform/* paths
const settingsRedirects: RouteRecordRaw[] = [
  { path: "/admin/settings/users", redirect: "/admin/platform/identity/users" },
  { path: "/admin/settings/users/new", redirect: "/admin/platform/identity/users/new" },
  { path: "/admin/settings/users/:id", redirect: (to: RouteLocationGeneric) => `/admin/platform/identity/users/${String(to.params.id ?? "")}` },
  { path: "/admin/settings/roles", redirect: "/admin/platform/identity/roles" },
  { path: "/admin/settings/audit-logs", redirect: "/admin/platform/observability/audit-trail" },
  { path: "/admin/settings/queue-monitor", redirect: "/admin/platform/queue" },
];

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: "/admin/login", name: "login", component: LoginView, meta: { guestOnly: true, title: "Login" } },
    { path: "/admin", name: "main-dashboard", component: MainDashboardView, meta: { requiresAuth: true, title: "Main Dashboard" } },
    { path: "/admin/portal/dashboard", name: "dashboard", component: DashboardView, meta: { requiresAuth: true, title: "Dashboard" } },
    { path: "/admin/posts", name: "posts", component: PostsListView, meta: { requiresAuth: true, title: "Posts" } },
    { path: "/admin/posts/new", name: "post-create", component: PostEditorView, meta: { requiresAuth: true, title: "New Post" } },
    { path: "/admin/posts/:id", name: "post-edit", component: PostEditorView, meta: { requiresAuth: true, title: "Edit Post" } },
    { path: "/admin/categories", name: "categories", component: CategoriesListView, meta: { requiresAuth: true, title: "Categories" } },
    { path: "/admin/categories/new", name: "category-create", component: CategoryEditorView, meta: { requiresAuth: true, title: "New Category" } },
    { path: "/admin/categories/:id", name: "category-edit", component: CategoryEditorView, meta: { requiresAuth: true, title: "Edit Category" } },
    { path: "/admin/pages", name: "pages", component: PagesListView, meta: { requiresAuth: true, title: "Pages" } },
    { path: "/admin/pages/new", name: "page-create", component: PageEditorView, meta: { requiresAuth: true, title: "New Page" } },
    { path: "/admin/pages/:id", name: "page-edit", component: PageEditorView, meta: { requiresAuth: true, title: "Edit Page" } },
    { path: "/admin/media", name: "media", component: MediaLibraryView, meta: { requiresAuth: true, title: "Media" } },
    { path: "/admin/webfront-menu", name: "storefront-menu", component: StorefrontMenuView, meta: { requiresAuth: true, title: "Menus" } },
    { path: "/admin/storefront-menu", redirect: "/admin/webfront-menu" },
    { path: "/admin/webfront-settings", name: "webfront-settings", component: WebfrontSettingsView, meta: { requiresAuth: true, title: "Settings" } },
    { path: "/admin/menus", name: "menus", component: MenusView, meta: { requiresAuth: true, title: "Menus" } },
    { path: "/admin/kitchen-sink", name: "kitchen-sink", component: KitchenSinkView, meta: { requiresAuth: true, title: "Kitchen Sink" } },
    { path: "/admin/kitchen-sink/forms", name: "kitchen-forms", component: KitchenFormsView, meta: { requiresAuth: true, title: "Forms" } },
    { path: "/admin/kitchen-sink/charts", name: "kitchen-charts", component: KitchenChartsView, meta: { requiresAuth: true, title: "Charts" } },
    { path: "/admin/development/developers-guide", name: "developers-guide", component: DevelopersGuideView, meta: { requiresAuth: true, title: "Developers Guide" } },
    { path: "/admin/development/database-schema", name: "database-schema", component: DatabaseSchemaView, meta: { requiresAuth: true, title: "Database Schema" } },
    { path: "/admin/development/api-explorer", name: "api-explorer", component: ApiManagementView, meta: { requiresAuth: true, title: "API Explorer" } },
    { path: "/admin/development/api-management", redirect: "/admin/development/api-explorer" },
    {
      path: "/admin/profile",
      name: "profile",
      meta: { requiresAuth: true },
      beforeEnter: async () => {
        const auth = useAuthStore();
        await auth.initialize();
        if (auth.user?.id) return `/admin/platform/identity/users/${auth.user.id}`;
        return { name: "login" };
      },
      component: { template: "" },
    },

    // ── Administration ──
    { path: "/admin/settings", name: "settings", component: SettingsView, meta: { requiresAuth: true, title: "Settings" } },
    { path: "/admin/settings/system", name: "settings-system", component: SystemInfoView, meta: { requiresAuth: true, title: "System Info" } },
    { path: "/admin/settings/integrasi", name: "settings-integrasi", component: IntegrasiView, meta: { requiresAuth: true, title: "Integrasi" } },

    // ── SPPT: PENGURUSAN PEMBIAYAAN ──
    { path: "/admin/permohonan", name: "permohonan", component: PermohonanListView, meta: { requiresAuth: true, title: "Pendaftaran Permohonan" } },
    { path: "/admin/permohonan/baru", name: "permohonan-baru", component: PermohonanBaruView, meta: { requiresAuth: true, title: "Daftar Permohonan Baru" } },
    { path: "/admin/permohonan/usahawan/daftar", name: "daftar-usahawan", component: DaftarUsahawanBaruView, meta: { requiresAuth: true, title: "Daftar Usahawan Baru" } },
    { path: "/admin/permohonan/usahawan", name: "rekod-usahawan", component: RekodUsahawanView, meta: { requiresAuth: true, title: "Rekod Usahawan" } },
    { path: "/admin/permohonan/:id", name: "permohonan-detail", component: PermohonanDetailView, meta: { requiresAuth: true, title: "Butiran Permohonan" } },
    { path: "/admin/pembiayaan/penilaian", name: "penilaian-kelulusan", component: PenilaianKelulusanView, meta: { requiresAuth: true, title: "Penilaian & Kelulusan" } },
    { path: "/admin/pembiayaan/penilaian/baru", name: "penilaian-baru", component: PenilaianBaruView, meta: { requiresAuth: true, title: "Penilaian Baru" } },
    { path: "/admin/pembiayaan/pengeluaran", name: "pengeluaran-dana", component: PengeluaranDanaView, meta: { requiresAuth: true, title: "Pengeluaran Dana" } },
    { path: "/admin/pembiayaan/pengeluaran/proses", name: "proses-pengeluaran", component: PlaceholderView, meta: { requiresAuth: true, title: "Proses Pengeluaran" } },
    { path: "/admin/pembiayaan/akaun", name: "pengurusan-akaun", component: PengurusanAkaunView, meta: { requiresAuth: true, title: "Pengurusan Akaun" } },
    { path: "/admin/pembiayaan/akaun/kemaskini", name: "kemaskini-akaun", component: PlaceholderView, meta: { requiresAuth: true, title: "Kemaskini Akaun" } },
    { path: "/admin/pembayaran", name: "bayaran-pembiayaan", component: BayaranPembiayaanView, meta: { requiresAuth: true, title: "Bayaran Pembiayaan" } },
    { path: "/admin/pembayaran/terima", name: "terima-bayaran", component: TerimaBayaranView, meta: { requiresAuth: true, title: "Terima Bayaran" } },
    { path: "/admin/pembayaran/pemadanan", name: "pemadanan-resit", component: PemadananResitView, meta: { requiresAuth: true, title: "Pemadanan Resit" } },
    { path: "/admin/pembayaran/rekon", name: "rekonsiliasi-bank", component: RekonsiliasiBankView, meta: { requiresAuth: true, title: "Rekonsiliasi Bank" } },
    { path: "/admin/pembayaran/lebihan-kekurangan", name: "lebihan-kekurangan", component: LebihanKekuranganView, meta: { requiresAuth: true, title: "Lebihan & Kekurangan" } },
    { path: "/admin/pembayaran/penyata", name: "penyata-bayaran", component: PenyataBayaranView, meta: { requiresAuth: true, title: "Penyata Bayaran" } },
    { path: "/admin/pembayaran/early-settlement", name: "early-settlement", component: EarlySettlementView, meta: { requiresAuth: true, title: "Early Settlement" } },
    { path: "/admin/pembayaran/selesai-bayar", name: "akaun-selesai-bayar", component: AkaunSelesaiBayarView, meta: { requiresAuth: true, title: "Akaun Selesai Bayar" } },
    { path: "/admin/pembayaran/ai", name: "ai-bayaran", component: AIBayaranView, meta: { requiresAuth: true, title: "AI Bayaran" } },
    { path: "/admin/pembayaran/tunggakan", name: "pemantauan-tunggakan", component: PemantauanTunggakanView, meta: { requiresAuth: true, title: "Pemantauan Tunggakan" } },
    { path: "/admin/pemantauan/usahawan", name: "pemantauan-usahawan", component: PemantauanUsahawanView, meta: { requiresAuth: true, title: "Pemantauan Usahawan" } },
    { path: "/admin/pemantauan/usahawan/prestasi", name: "prestasi-usahawan", component: PlaceholderView, meta: { requiresAuth: true, title: "Prestasi Usahawan" } },
    { path: "/admin/pemantauan/kutipan", name: "kutipan", component: KutipanView, meta: { requiresAuth: true, title: "Kutipan" } },
    { path: "/admin/pemantauan/kutipan/rekod", name: "rekod-kutipan", component: RekodKutipanView, meta: { requiresAuth: true, title: "Rekod Kutipan" } },
    { path: "/admin/undang-undang/litigasi", name: "litigasi", component: LitigasiView, meta: { requiresAuth: true, title: "Litigasi" } },
    { path: "/admin/undang-undang/litigasi/rekod", name: "rekod-litigasi", component: PlaceholderView, meta: { requiresAuth: true, title: "Rekod Kes" } },
    { path: "/admin/undang-undang/kes/:id", name: "kes-detail", component: KesDetailView, meta: { requiresAuth: true, title: "Detail Kes" } },
    { path: "/admin/undang-undang/panel-peguam", name: "panel-peguam", component: PanelPeguamView, meta: { requiresAuth: true, title: "Panel Peguam" } },
    { path: "/admin/undang-undang/panel-peguam/baru", name: "panel-peguam-baru", component: PlaceholderView, meta: { requiresAuth: true, title: "Pelantikan Panel Baru" } },
    { path: "/admin/undang-undang/akaun-npf", name: "akaun-npf", component: AkaunNpfView, meta: { requiresAuth: true, title: "Akaun NPF" } },
    { path: "/admin/undang-undang/akaun-npf/submit", name: "akaun-npf-submit", component: PlaceholderView, meta: { requiresAuth: true, title: "Submit NPF" } },
    { path: "/admin/undang-undang/nod", name: "nod", component: NodView, meta: { requiresAuth: true, title: "NOD" } },
    { path: "/admin/undang-undang/nod/mohon", name: "nod-mohon", component: PlaceholderView, meta: { requiresAuth: true, title: "Mohon NOD" } },
    { path: "/admin/undang-undang/saman", name: "saman", component: SamanView, meta: { requiresAuth: true, title: "Saman" } },
    { path: "/admin/undang-undang/saman/permohonan", name: "saman-permohonan", component: PlaceholderView, meta: { requiresAuth: true, title: "Permohonan Saman" } },
    { path: "/admin/undang-undang/penghakiman", name: "penghakiman", component: PenghakimanView, meta: { requiresAuth: true, title: "Penghakiman" } },
    { path: "/admin/undang-undang/execution", name: "execution", component: ExecutionView, meta: { requiresAuth: true, title: "Execution" } },
    { path: "/admin/undang-undang/execution/permohonan", name: "execution-permohonan", component: PlaceholderView, meta: { requiresAuth: true, title: "Permohonan Execution" } },
    { path: "/admin/undang-undang/wss", name: "wss", component: WssView, meta: { requiresAuth: true, title: "WSS" } },
    { path: "/admin/undang-undang/wss/permohonan", name: "wss-permohonan", component: PlaceholderView, meta: { requiresAuth: true, title: "Permohonan WSS" } },
    { path: "/admin/undang-undang/garnishee", name: "garnishee", component: GarnisheeView, meta: { requiresAuth: true, title: "Garnishee" } },
    { path: "/admin/undang-undang/garnishee/permohonan", name: "garnishee-permohonan", component: PlaceholderView, meta: { requiresAuth: true, title: "Permohonan Garnishee" } },
    { path: "/admin/undang-undang/jds", name: "jds", component: JdsView, meta: { requiresAuth: true, title: "JDS" } },
    { path: "/admin/undang-undang/jds/permohonan", name: "jds-permohonan", component: PlaceholderView, meta: { requiresAuth: true, title: "Permohonan JDS" } },
    { path: "/admin/undang-undang/kebankrapan", name: "kebankrapan", component: KebankrapanView, meta: { requiresAuth: true, title: "Kebankrapan" } },
    { path: "/admin/undang-undang/kebankrapan/rekod", name: "kebankrapan-rekod", component: PlaceholderView, meta: { requiresAuth: true, title: "Rekod Kebankrapan" } },
    { path: "/admin/undang-undang/winding-up", name: "winding-up", component: WindingUpView, meta: { requiresAuth: true, title: "Winding-Up" } },
    { path: "/admin/undang-undang/winding-up/petisyen", name: "winding-up-petisyen", component: PlaceholderView, meta: { requiresAuth: true, title: "Petisyen Winding-Up" } },
    { path: "/admin/undang-undang/audit", name: "litigasi-audit", component: LitigasiAuditView, meta: { requiresAuth: true, title: "Audit Litigasi" } },
    { path: "/admin/undang-undang/laporan", name: "litigasi-laporan", component: LitigasiLaporanView, meta: { requiresAuth: true, title: "Laporan Litigasi" } },
    { path: "/admin/undang-undang/insolvensi", name: "insolvensi-integrasi", component: InsolvensiIntegrasiView, meta: { requiresAuth: true, title: "Integrasi Insolvensi" } },
    { path: "/admin/jaminan", name: "pengurusan-jaminan", component: PengurusanJaminanView, meta: { requiresAuth: true, title: "Pengurusan Jaminan" } },
    { path: "/admin/jaminan/kemaskini", name: "kemaskini-jaminan", component: KemaskiniJaminanView, meta: { requiresAuth: true, title: "Kemaskini Jaminan" } },
    { path: "/admin/laporan", name: "laporan-analitik", component: LaporanAnalitikView, meta: { requiresAuth: true, title: "Laporan & Analitik" } },
    { path: "/admin/laporan/kewangan", name: "laporan-kewangan", component: PlaceholderView, meta: { requiresAuth: true, title: "Laporan Kewangan" } },
    { path: "/admin/laporan/prestasi", name: "laporan-prestasi", component: PlaceholderView, meta: { requiresAuth: true, title: "Laporan Prestasi" } },
    { path: "/admin/laporan/audit", name: "audit-kawalan", component: AuditKawalanView, meta: { requiresAuth: true, title: "Audit & Kawalan" } },
    { path: "/admin/laporan/audit/kawalan", name: "kawalan-dalaman", component: PlaceholderView, meta: { requiresAuth: true, title: "Kawalan Dalaman" } },
    { path: "/admin/pembiayaan/tetapan", name: "tetapan", component: TetapanView, meta: { requiresAuth: true, title: "Tetapan (Setup)" } },

    // ── Core Platform: Identity & Access ──
    { path: "/admin/platform/identity", redirect: "/admin/platform/identity/users" },
    { path: "/admin/platform/identity/users", name: "platform-users", component: UsersView, meta: { requiresAuth: true, title: "Users" } },
    { path: "/admin/platform/identity/users/new", name: "platform-user-create", component: UserEditView, meta: { requiresAuth: true, title: "New User" } },
    { path: "/admin/platform/identity/users/:id", name: "platform-user-edit", component: UserEditView, meta: { requiresAuth: true, title: "Edit User" } },
    { path: "/admin/platform/identity/roles", name: "platform-rbac", component: RolesView, meta: { requiresAuth: true, title: "RBAC" } },
    { path: "/admin/platform/identity/tokens", name: "platform-tokens", component: ComingSoonView, meta: { requiresAuth: true, title: "Token Management" } },

    // ── Core Platform: Observability (Grafana) ──
    { path: "/admin/platform/observability", redirect: "/admin/platform/observability/audit-trail" },
    { path: "/admin/platform/observability/audit-trail", name: "platform-audit-trail", component: AuditLogsView, meta: { requiresAuth: true, title: "Audit Trail" } },
    { path: "/admin/platform/observability/activity-log", name: "platform-activity-log", component: ComingSoonView, meta: { requiresAuth: true, title: "Activity Log" } },
    { path: "/admin/platform/observability/logging", name: "platform-logging", component: ComingSoonView, meta: { requiresAuth: true, title: "Logging" } },
    { path: "/admin/platform/observability/errors", name: "platform-error-tracking", component: ComingSoonView, meta: { requiresAuth: true, title: "Error Tracking" } },
    { path: "/admin/platform/observability/monitoring", name: "platform-monitoring", component: ComingSoonView, meta: { requiresAuth: true, title: "Monitoring" } },

    // ── Core Platform: Queue (Laravel Queue) ──
    { path: "/admin/platform/queue", name: "platform-queue", component: QueueMonitorView, meta: { requiresAuth: true, title: "Queue" } },
    { path: "/admin/platform/queue/failed", name: "platform-queue-failed", component: ComingSoonView, meta: { requiresAuth: true, title: "Failed Jobs" } },
    { path: "/admin/platform/queue/scheduled", name: "platform-queue-scheduled", component: ComingSoonView, meta: { requiresAuth: true, title: "Scheduled Jobs" } },

    // ── Core Platform: Messaging ──
    { path: "/admin/platform/messaging", redirect: "/admin/platform/messaging/event-bus" },
    { path: "/admin/platform/messaging/event-bus", name: "platform-event-bus", component: ComingSoonView, meta: { requiresAuth: true, title: "Event Bus" } },
    { path: "/admin/platform/messaging/notifications", name: "platform-notifications", component: ComingSoonView, meta: { requiresAuth: true, title: "Notifications" } },

    // ── Backward-compat redirects from old governance/communication paths ──
    { path: "/admin/platform/governance", redirect: "/admin/platform/observability/audit-trail" },
    { path: "/admin/platform/governance/audit-trail", redirect: "/admin/platform/observability/audit-trail" },
    { path: "/admin/platform/governance/activity-log", redirect: "/admin/platform/observability/activity-log" },
    { path: "/admin/platform/communication", redirect: "/admin/platform/messaging/notifications" },
    { path: "/admin/platform/communication/notifications", redirect: "/admin/platform/messaging/notifications" },
    { path: "/admin/platform/messaging/queue", redirect: "/admin/platform/queue" },
    { path: "/admin/platform/messaging/queue/failed", redirect: "/admin/platform/queue/failed" },
    { path: "/admin/platform/messaging/queue/scheduled", redirect: "/admin/platform/queue/scheduled" },

    // ── Core Platform: System Management ──
    { path: "/admin/platform/system", redirect: "/admin/platform/system/configuration" },
    { path: "/admin/platform/system/configuration", name: "platform-config", component: ComingSoonView, meta: { requiresAuth: true, title: "Configuration" } },
    { path: "/admin/platform/system/feature-flags", name: "platform-feature-flags", component: ComingSoonView, meta: { requiresAuth: true, title: "Feature Flags" } },
    { path: "/admin/platform/system/scheduler", name: "platform-scheduler", component: ComingSoonView, meta: { requiresAuth: true, title: "Scheduler" } },

    // ── Core Platform: Storage ──
    { path: "/admin/platform/storage", redirect: "/admin/platform/storage/media" },
    { path: "/admin/platform/storage/media", name: "platform-file-media", component: ComingSoonView, meta: { requiresAuth: true, title: "File / Media Management" } },

    // ── Core Platform: API Gateway (APISIX) ──
    { path: "/admin/platform/gateway", redirect: "/admin/platform/gateway/routes" },
    { path: "/admin/platform/gateway/routes", name: "platform-gateway-routes", component: ComingSoonView, meta: { requiresAuth: true, title: "Routes" } },
    { path: "/admin/platform/gateway/upstreams", name: "platform-gateway-upstreams", component: ComingSoonView, meta: { requiresAuth: true, title: "Upstreams" } },
    { path: "/admin/platform/gateway/consumers", name: "platform-gateway-consumers", component: ComingSoonView, meta: { requiresAuth: true, title: "Consumers" } },
    { path: "/admin/platform/gateway/plugins", name: "platform-gateway-plugins", component: ComingSoonView, meta: { requiresAuth: true, title: "Plugins" } },
    { path: "/admin/platform/gateway/ssl", name: "platform-gateway-ssl", component: ComingSoonView, meta: { requiresAuth: true, title: "SSL Certificates" } },
    { path: "/admin/platform/gateway/webhooks", name: "platform-webhooks", component: ComingSoonView, meta: { requiresAuth: true, title: "Webhooks" } },

    // ── Backward-compat redirects from old integration paths ──
    { path: "/admin/platform/integration", redirect: "/admin/platform/gateway/routes" },
    { path: "/admin/platform/integration/api", redirect: "/admin/platform/gateway/routes" },
    { path: "/admin/platform/integration/webhooks", redirect: "/admin/platform/gateway/webhooks" },

    // ── Core Platform: AI Integration ──
    { path: "/admin/platform/ai", redirect: "/admin/platform/ai/providers" },
    { path: "/admin/platform/ai/providers", name: "platform-ai-providers", component: ComingSoonView, meta: { requiresAuth: true, title: "AI Providers" } },
    { path: "/admin/platform/ai/models", name: "platform-ai-models", component: ComingSoonView, meta: { requiresAuth: true, title: "AI Models" } },
    { path: "/admin/platform/ai/prompts", name: "platform-ai-prompts", component: ComingSoonView, meta: { requiresAuth: true, title: "Prompt Templates" } },
    { path: "/admin/platform/ai/usage", name: "platform-ai-usage", component: ComingSoonView, meta: { requiresAuth: true, title: "AI Usage & Billing" } },

    // ── Backward-compat redirects from old settings paths ──
    ...settingsRedirects,

    ...legacyAdminPaths.map<RouteRecordRaw>((path) => ({
      path,
      redirect: (to: RouteLocationGeneric) => `/admin${to.fullPath}`,
    })),

    // ── Pemohon Portal (Public Self-Service) ──
    { path: "/pemohon", name: "pemohon-landing", component: PemohonLandingView, meta: { title: "Portal Pemohon" } },
    { path: "/pemohon/daftar", name: "pemohon-daftar", component: PemohonDaftarView, meta: { pemohonGuestOnly: true, title: "Pendaftaran Akaun" } },
    { path: "/pemohon/log-masuk", name: "pemohon-log-masuk", component: PemohonLogMasukView, meta: { pemohonGuestOnly: true, title: "Log Masuk" } },
    { path: "/pemohon/semakan-kelayakan", name: "pemohon-semakan-kelayakan", component: PemohonSemakanKelayakanView, meta: { requiresPemohonAuth: true, title: "Semakan Kelayakan" } },
    { path: "/pemohon/ekyc", name: "pemohon-ekyc", component: PemohonEkycView, meta: { requiresPemohonAuth: true, title: "Pengesahan Identiti" } },
    { path: "/pemohon/dashboard", name: "pemohon-dashboard", component: PemohonDashboardView, meta: { requiresPemohonAuth: true, title: "Dashboard Pemohon" } },
    { path: "/pemohon/permohonan", name: "pemohon-permohonan", component: PemohonPermohonanListView, meta: { requiresPemohonAuth: true, title: "Permohonan Saya" } },
    { path: "/pemohon/permohonan/baru", name: "pemohon-permohonan-baru", component: PemohonPermohonanBaruView, meta: { requiresPemohonAuth: true, title: "Permohonan Baharu" } },
    { path: "/pemohon/permohonan/:id", name: "pemohon-permohonan-detail", component: PemohonPermohonanDetailView, meta: { requiresPemohonAuth: true, title: "Butiran Permohonan" } },
    { path: "/pemohon/profil", name: "pemohon-profil", component: PemohonProfilView, meta: { requiresPemohonAuth: true, title: "Profil Saya" } },
    { path: "/pemohon/bantuan", name: "pemohon-bantuan", component: PemohonBantuanView, meta: { title: "Bantuan & Soalan Lazim" } },

    { path: "/", name: "storefront-home", component: StorefrontHomeView, meta: { title: "Webfront" } },
    { path: "/:slug", name: "storefront-page", component: StorefrontPageView, meta: { title: "Webfront" } },
  ],
});

router.beforeEach(async (to) => {
  const auth = useAuthStore();
  await auth.initialize();

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: "login" };
  }

  if (to.meta.guestOnly && auth.isAuthenticated) {
    return { name: "main-dashboard" };
  }

  const pemohon = usePemohonStore();
  pemohon.initFromStorage();

  if (to.meta.requiresPemohonAuth && !pemohon.isLoggedIn) {
    return { name: "pemohon-log-masuk" };
  }

  if (to.meta.pemohonGuestOnly && pemohon.isLoggedIn) {
    return { name: "pemohon-dashboard" };
  }

  return true;
});

export function applyDocumentTitle(to: RouteLocationNormalizedLoaded) {
  const site = useSiteStore();
  const locale = useUiLocaleStore().language;
  const fallback = (to.meta.title as string) || "Admin";
  const routeName = typeof to.name === "string" ? to.name : "";
  const pageTitle = routeName ? translateRouteTitle(locale, routeName, fallback) : fallback;
  site.setDocumentTitle(pageTitle);
}

router.afterEach((to) => {
  applyDocumentTitle(to);
});

export default router;
