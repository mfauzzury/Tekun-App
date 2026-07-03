export type PublishStatus = "draft" | "published" | "archived";
export type ThemeColor = "violet" | "blue" | "green" | "red" | "black-white" | "grey";
export type AppLanguage = "bm" | "bi";

export type ApiError = { error: { code: string; message: string; details?: unknown } };

export type ApiResponse<T> = { data: T; meta?: Record<string, unknown> };

export type User = {
  id: number;
  email: string;
  name: string;
  photoUrl?: string;
  role?: string;
};

export type PostInput = {
  title: string;
  slug?: string;
  excerpt?: string;
  content: string;
  status: PublishStatus;
  featuredImageId?: number | null;
  categoryIds?: number[];
};

export type Post = PostInput & {
  id: number;
  slug: string;
  publishedAt: string | null;
  createdAt: string;
  updatedAt: string;
  featuredImage?: Media | null;
  categories?: Category[];
};

export type CategoryInput = {
  name: string;
  slug?: string;
  description?: string;
};

export type Category = {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  createdAt: string;
  updatedAt: string;
  _count?: { posts: number };
};

export type PageInput = {
  title: string;
  slug?: string;
  content: string;
  status: PublishStatus;
  featuredImageId?: number | null;
};

export type Page = PageInput & {
  id: number;
  slug: string;
  publishedAt: string | null;
  createdAt: string;
  updatedAt: string;
  featuredImage?: Media | null;
};

export type Media = {
  id: number;
  filename: string;
  originalName: string;
  title: string | null;
  caption: string | null;
  description: string | null;
  mimeType: string;
  size: number;
  width: number | null;
  height: number | null;
  altText: string | null;
  path: string;
  url: string;
  createdAt: string;
};

export type MediaMetadataInput = {
  title: string;
  altText: string;
  caption: string;
  description: string;
};

export type SettingsPayload = {
  siteTitle: string;
  tagline: string;
  webfrontTitle: string;
  webfrontTagline: string;
  titleFormat: string;
  metaDescription: string;
  siteIconUrl: string;
  webfrontLogoUrl: string;
  sidebarLogoUrl: string;
  faviconUrl: string;
  language: string;
  timezone: string;
  footerText: string;
  frontPageId: number | null;
};

export type PublicSiteSettings = Pick<
  SettingsPayload,
  "siteTitle" | "tagline" | "webfrontTitle" | "webfrontTagline" | "metaDescription" | "footerText" | "siteIconUrl" | "webfrontLogoUrl" | "sidebarLogoUrl" | "faviconUrl"
> & {
  storefrontMenu: StorefrontMenuItem[];
};

export type StorefrontMenuItem = {
  id: string;
  label: string;
  href: string;
  parentId: string | null;
  openInNewTab: boolean;
};

export type Role = {
  id: number;
  name: string;
  description: string;
  permissions: string[];
  createdAt: string;
  updatedAt: string;
};

export type RoleInput = {
  name: string;
  description: string;
  permissions: string[];
};

export type UserDetail = {
  id: number;
  name: string;
  email: string;
  role: string;
  isActive: boolean;
  createdAt: string;
  updatedAt: string;
};

export type UserInput = {
  name: string;
  email: string;
  password?: string;
  role: string;
  isActive: boolean;
};

export type AuditLog = {
  id: number;
  userId: number | null;
  action: string;
  auditableType: string | null;
  auditableId: number | null;
  oldValues: Record<string, unknown> | null;
  newValues: Record<string, unknown> | null;
  ipAddress: string | null;
  userAgent: string | null;
  createdAt: string;
  user?: { id: number; name: string; email: string } | null;
};

// ── SPPT / Pengurusan Pembiayaan ──

export type SpptDashboardSummary = {
  permohonanDalamProses: number;
  akaunAktif: number;
  kutipanBulanIni: number;
  tunggakan: number;
  kesLitigasiAktif: number;
  jaminanAktif: number;
  pengeluaranMenunggu: number;
  jumlahUsahawan: number;
  permohonanSelesaiBulanIni: number;
};

export type SpptReferenceData = Record<string, unknown>;

export type UsahawanInput = {
  noUsahawan?: string;
  nama: string;
  noIc?: string;
  alamat?: string;
  poskod?: string;
  negeri?: string;
  noTelefon?: string;
  email?: string;
  jenisPerniagaan?: string;
  status?: string;
};

export type Usahawan = UsahawanInput & {
  id: number;
  createdAt: string;
  updatedAt: string;
};

export type PermohonanInput = {
  noRujukan?: string;
  usahawanId?: number;
  nama: string;
  kategoriPembiayaan?: string;
  status?: string;
  jumlahPermohonan?: number;
  tarikhPermohonan?: string;
  details?: Record<string, unknown>;
};

export type Permohonan = PermohonanInput & {
  id: number;
  createdAt: string;
  updatedAt: string;
  usahawan?: Usahawan | null;
};

export type AkaunPembiayaan = {
  id: number;
  noAkaun: string;
  permohonanId?: number | null;
  usahawanId?: number | null;
  ic?: string | null;
  nama: string;
  namaSyarikat?: string | null;
  ssm?: string | null;
  pukonsa?: string | null;
  cawangan?: string | null;
  negeri?: string | null;
  produk?: string | null;
  tarikhMula?: string | null;
  tarikhTamat?: string | null;
  jumlahPembiayaan: number;
  bakiPokok: number;
  bakiKeuntungan: number;
  bakiSimpanan: number;
  penalti: number;
  tunggakan: number;
  bakiAkhir: number;
  bayaranBulanan: number;
  status: string;
  risiko: string;
  noBsas?: string | null;
  snc: boolean;
  createdAt: string;
  updatedAt: string;
};

export type SpptSetupStatusItem = {
  value: string;
  label: string;
  color?: string;
  active?: boolean;
  sort?: number;
};

export type SpptSetupCategory = {
  key: string;
  label: string;
  labelEn?: string;
  description?: string;
  items: SpptSetupStatusItem[];
};
