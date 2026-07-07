export type PublishStatus = "draft" | "published" | "archived";

// ─── Workflow Configuration ──────────────────────────────────────────────────

export type WfWorkflow = {
  wfaWorkflowCode: string;
  wfaWorkflowTitle: string | null;
  wfaPreventSelfProcess: number | null;
  wfaInvolvePosting: number | null;
};

export type WfWorkflowInput = {
  wfaWorkflowCode: string;
  wfaWorkflowTitle: string;
  wfaPreventSelfProcess: number | null;
  wfaInvolvePosting: number;
};

export type WfWorkflowUpdateInput = {
  wfaWorkflowTitle: string;
  wfaPreventSelfProcess: number | null;
  wfaInvolvePosting: number;
};

export type WfProcess = {
  wfpProcessId: number;
  wfpWorkflowCode: string;
  wfpProcessName: string;
  wfpProcessDescBm: string | null;
  wfpSequence: number;
  wfpStatus: string;
  wfpDurationKpi: number | null;
  wfpIsEmailNotification: number | null;
  wfpIsTodoNotification: number | null;
  wfpIsByPtj: number | null;
  wfpExtendedField?: Record<string, unknown> | null;
  wfpProcessDetailsCount?: number;
};

export type WfProcessInput = {
  wfpWorkflowCode: string;
  wfpProcessName: string;
  wfpProcessDescBm?: string | null;
  wfpSequence: number;
  wfpDurationKpi?: number | null;
  wfpDurationKpiWithquery?: null;
  wfpStatus: string;
  wfpIsEmailNotification: number;
  wfpIsTodoNotification: number;
  wfpIsByUnit?: null;
  wfpIsByPtj: number | null;
  wfpIsAllowQuery?: null;
};

export type WfProcessUpdateInput = Partial<Omit<WfProcessInput, "wfpWorkflowCode">>;

export type WfProcessDetail = {
  wpdProcessDetailsId: number;
  wpdProcessId: number;
  wpdStatusCode: string;
  wpdStatusDesc?: string | null;
  wpdRerouteProcess: number | null;
  wpdRerouteUrl: string | null;
  wpdOrder: number;
  wflIsPositive?: number | string | null;
};

export type WfProcessDetailInput = {
  wpdProcessId: number;
  wpdStatusCode: string;
  wpdStatusDesc: string;
  wpdRerouteProcess: number | null;
  wpdRerouteUrl: string | null;
  wpdOrder: number;
};

export type WfProcessDetailUpdateInput = Omit<WfProcessDetailInput, "wpdProcessId">;

export type WfAuthorizedRole = {
  warAuthorizedRoleId: number;
  warProcessId: number;
  warGroupCode: string;
  warGroupName?: string | null;
  warLimitMin: number | null;
  warLimitMax: number | null;
};

export type WfAuthorizedRoleInput = {
  warProcessId: number;
  warGroupCode: string;
  warLimitMin: number | null;
  warLimitMax: number | null;
};

export type WfAuthorizedRoleUpdateInput = Omit<WfAuthorizedRoleInput, "warProcessId">;

export type WfLookupOption = {
  kod: string;
  keterangan: string;
  wflIsPositive?: number | string | null;
};

export type WfRerouteProcessOption = {
  id: number;
  description: string;
  wfpWorkflowCode: string;
};

export type WfRoleReferenceOption = {
  code: string;
  description: string;
};

export type ThemeColor = "violet" | "blue" | "green" | "red" | "black-white" | "grey";
export type AppLanguage = "bm" | "bi";

export type ApiError = { error: { code: string; message: string; details?: unknown } };

export type ApiResponse<T> = { data: T; meta?: Record<string, unknown> };

export type UserCawangan = {
  id: number;
  code: string;
  name: string;
  negeri?: string | null;
  branchType?: SpptCawanganBranchType | null;
};

export type User = {
  id: number;
  email: string;
  name: string;
  photoUrl?: string;
  role?: string;
  spptCawanganId?: number | null;
  cawangan?: UserCawangan | null;
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
  spptCawanganId?: number | null;
  cawangan?: UserCawangan | null;
  createdAt: string;
  updatedAt: string;
};

export type UserInput = {
  name: string;
  email: string;
  password?: string;
  role: string;
  isActive: boolean;
  spptCawanganId?: number | null;
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

export type DocumentVerification = {
  status: "verified" | "failed" | "pending" | "skipped";
  documentType: "ic_front" | "ic_back" | "ic_combined" | "other";
  confidence: number;
  message: string;
  icMatched?: boolean | null;
  identityMatched?: boolean | null;
  subject?: "applicant" | "spouse" | null;
};

export type DocumentClassification = {
  suggestedClass: string;
  confidence: number;
  message: string;
};

export type PermohonanFormOcrResult = {
  fields: Record<string, unknown>;
  confidence: number;
  populatedCount: number;
  fieldConfidence: Record<string, number>;
  message: string;
};

export type PermohonanAttachment = {
  id: string;
  name: string;
  size: number;
  url: string;
  mimeType?: string;
  documentClass?: string;
  documentClassLabel?: string;
  documentClassOther?: string;
  verification?: DocumentVerification;
  verifying?: boolean;
};

export type PermohonanInput = {
  noRujukan?: string;
  usahawanId?: number;
  nama: string;
  kategoriPembiayaan?: string;
  status?: string;
  negeri?: string;
  cawangan?: string;
  wfWorkflowCode?: string | null;
  wfCurrentProcessId?: number | null;
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

/** Self-service counterpart to Permohonan — includes the once-issued ownership token. */
export type PemohonPermohonanInput = {
  nama?: string;
  kategoriPembiayaan?: string;
  status?: "Draf" | "Dalam Semakan";
  jumlahPermohonan?: number;
  pemohonEmail?: string;
  pemohonTelefon?: string;
  details?: Record<string, unknown>;
};

export type PemohonPermohonan = PemohonPermohonanInput & {
  id: number;
  noRujukan: string;
  pemohonAccessToken: string;
  createdAt: string;
  updatedAt: string;
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

export type SpptCawanganBranchType = "negeri" | "cawangan" | "ibu_pejabat";

export type SpptCawanganInput = {
  code: string;
  name: string;
  branchType?: SpptCawanganBranchType;
  negeri?: string | null;
  locality?: string | null;
  postalCode?: string | null;
  address?: string | null;
  phone?: string | null;
  fax?: string | null;
  contactPerson?: string | null;
  externalId?: string | null;
  isActive?: boolean;
  sortOrder?: number;
};

export type SpptCawangan = SpptCawanganInput & {
  id: number;
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
  type?: "knowledge" | "status" | "hard_rules";
  items: SpptSetupStatusItem[];
  hardRules?: SpptHardRulesConfig;
};

export type SpptHardRuleConfig = {
  minAge?: number;
  maxAge?: number;
  ics?: string[];
  maxRatio?: number;
  maxActiveCount?: number;
  maxTotalAmount?: number;
};

export type SpptHardRuleItem = {
  code: string;
  label: string;
  active: boolean;
  sort: number;
  config: SpptHardRuleConfig;
};

export type SpptHardRulesConfig = {
  active: boolean;
  rules: SpptHardRuleItem[];
};

export type HardRuleCheckInput = {
  umur?: number;
  noKp?: string;
  pendapatanBulanan?: number;
  jumlahKomitmenSediaAda?: number;
  jumlahPembiayaanAktif?: number;
  jumlahPembiayaanAktifRm?: number;
  muflis?: boolean;
};

export type HardRuleCheckResult = {
  eligible: boolean;
  autoReject: boolean;
  reasons: string[];
  failedRules: string[];
};

export type HardRulePublicSummary = {
  active: boolean;
  rules: Array<{ code: string; label: string; hint: string }>;
};

export type AiRiskScoringFactor = {
  factor: string;
  impact: "positif" | "negatif" | "neutral" | string;
  description: string;
};

export type AiRiskScoringInput = {
  umur?: number;
  noKp?: string;
  kategoriPembiayaan?: string;
  sektorPerniagaan?: string;
  tempohPerniagaanTahun?: number;
  pendapatanBulanan?: number;
  jumlahKomitmenSediaAda?: number;
  jumlahPermohonan?: number;
  negeri?: string;
  muflis?: boolean;
  permohonanId?: number;
};

export type AiRiskScoringResult = {
  riskScore: number;
  riskCategory: "Risiko Rendah" | "Risiko Sederhana" | "Risiko Tinggi" | string;
  recommendedLimit: number;
  factors: AiRiskScoringFactor[];
  confidence: number;
  source: "ai" | "heuristic" | string;
  scoredAt: string;
  message: string;
};

export type ApkCreditData = {
  source: string;
  enrichedAt: string;
  ccris: {
    score: number;
    totalFacilities: number;
    totalOutstandingRm: number;
    monthlyCommitmentRm: number;
    specialAttention: boolean;
    legalActions: number;
    npfAccounts: number;
  };
  ctos: {
    score: number;
    litigation: boolean;
    tradeReferenceNegative: boolean;
  };
  experian: {
    score: number;
    delinquency: boolean;
  };
  flags: string[];
};

export type AiCreditScoringInput = AiRiskScoringInput & {
  noSsm?: string;
};

export type AiCreditScoringResult = {
  creditScore: number;
  creditCategory: string;
  recommendedLimit: number;
  riskBand: "low" | "medium" | "high" | string;
  riskBandColor: "green" | "amber" | "red" | string;
  recommendedAction: "auto_approve" | "officer_review" | "reject_escalate" | string;
  decisionLabel: string;
  decisionDescription: string;
  factors: AiRiskScoringFactor[];
  adverseActionReasons: string[];
  apk: ApkCreditData;
  confidence: number;
  source: string;
  scoredAt: string;
  message: string;
};

// AINA User Chat
export type ChatMessage = {
  id: number;
  chatSessionId: number;
  role: "user" | "assistant";
  content: string;
  citations: string[];
  replyToMessageId?: number | null;
  replyToUserId?: number | null;
  createdAt: string;
  replyToMessage?: ChatMessage | null;
  replyToUser?: { id: number; name: string } | null;
};

export type ChatSession = {
  id: number;
  openaiThreadId: string;
  title: string;
  moduleFilter: string | null;
  userId: number | null;
  sessionType?: "solo" | "group";
  chatType?: string;
  messages?: ChatMessage[];
  isFavorited?: boolean;
  createdAt: string;
  updatedAt: string;
};

export type ChatSuggestion = { id: string; label: string; module: string };

export type ChatFavoriteItem = {
  id: number;
  message: ChatMessage;
  session: ChatSession | null;
  createdAt: string;
};
