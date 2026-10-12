export type PermohonanWorkflowStage = "semakan" | "sokongan" | "kelulusan";

export type PermohonanWorkflowStageConfig = {
  stage: PermohonanWorkflowStage;
  title: string;
  listTitle: string;
  listDescription: string;
  routeName: string;
  routePath: string;
  menuId: string;
  permission: string;
  approveLabel: string;
  rejectLabel: string;
  approveHint: string;
  rejectHint: string;
  showCawanganScope: boolean;
};

export const PERMOHONAN_WORKFLOW_STAGES: Record<PermohonanWorkflowStage, PermohonanWorkflowStageConfig> = {
  semakan: {
    stage: "semakan",
    title: "Semakan",
    listTitle: "Permohonan Menunggu Semakan",
    listDescription: "Senarai permohonan di cawangan anda yang perlu disemak.",
    routeName: "permohonan-semakan",
    routePath: "/admin/permohonan/semakan",
    menuId: "permohonan-semakan",
    permission: "sppt.semakan",
    approveLabel: "Sahkan Semakan",
    rejectLabel: "Tolak Semakan",
    approveHint: "Permohonan akan dihantar ke peringkat sokongan.",
    rejectHint: "Permohonan akan ditolak dan tidak diteruskan.",
    showCawanganScope: true,
  },
  sokongan: {
    stage: "sokongan",
    title: "Sokongan",
    listTitle: "Permohonan Menunggu Sokongan",
    listDescription: "Senarai permohonan yang telah disemak di cawangan anda.",
    routeName: "permohonan-sokongan",
    routePath: "/admin/permohonan/sokongan",
    menuId: "permohonan-sokongan",
    permission: "sppt.sokongan",
    approveLabel: "Sokong Permohonan",
    rejectLabel: "Tolak Sokongan",
    approveHint: "Permohonan akan dihantar ke peringkat kelulusan.",
    rejectHint: "Permohonan akan ditolak dan tidak diteruskan.",
    showCawanganScope: true,
  },
  kelulusan: {
    stage: "kelulusan",
    title: "Kelulusan",
    listTitle: "Permohonan Menunggu Kelulusan",
    listDescription: "Senarai permohonan yang telah disokong dari semua cawangan.",
    routeName: "permohonan-kelulusan",
    routePath: "/admin/permohonan/kelulusan",
    menuId: "permohonan-kelulusan",
    permission: "sppt.kelulusan",
    approveLabel: "Luluskan Permohonan",
    rejectLabel: "Tolak Permohonan",
    approveHint: "Permohonan akan diluluskan.",
    rejectHint: "Permohonan akan ditolak.",
    showCawanganScope: false,
  },
};

export function resolveWorkflowStageFromRouteName(routeName?: string | null): PermohonanWorkflowStage | null {
  for (const config of Object.values(PERMOHONAN_WORKFLOW_STAGES)) {
    if (config.routeName === routeName) {
      return config.stage;
    }
  }

  return null;
}

export function canAccessWorkflowStage(permissions: string[] | undefined, stage: PermohonanWorkflowStage): boolean {
  return Boolean(permissions?.includes(PERMOHONAN_WORKFLOW_STAGES[stage].permission));
}

export function firstAccessibleWorkflowRoute(permissions: string[] | undefined): string | null {
  for (const config of Object.values(PERMOHONAN_WORKFLOW_STAGES)) {
    if (canAccessWorkflowStage(permissions, config.stage)) {
      return config.routePath;
    }
  }

  return null;
}
