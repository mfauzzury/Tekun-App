import type { MenuGroupDef, MenuNode } from "@/config/admin-menu";
import { PERMOHONAN_WORKFLOW_STAGES } from "@/config/permohonan-workflow";

export const PENGURUSAN_PEMBIAYAAN_MENU_GROUP_ID = "pengurusan-pembiayaan";

const PENGURUSAN_PEMBIAYAAN_ROUTE_PREFIXES = [
  "/admin/permohonan",
  "/admin/pembiayaan",
  "/admin/pembayaran",
  "/admin/pemantauan",
  "/admin/undang-undang",
  "/admin/jaminan",
  "/admin/laporan",
  "/admin/settings/integrasi",
] as const;

const WORKFLOW_MENU_PERMISSIONS: Record<string, string> = Object.fromEntries(
  Object.values(PERMOHONAN_WORKFLOW_STAGES).map((stage) => [stage.menuId, stage.permission]),
);

export function isAdminRole(role: string | undefined): boolean {
  return role === "admin";
}

export function hasPermission(permissions: string[] | undefined, permission: string): boolean {
  return Boolean(permissions?.includes(permission));
}

export function canAccessPengurusanPembiayaanRoute(path: string): boolean {
  if (path === "/admin" || path === "/admin/") {
    return true;
  }

  return PENGURUSAN_PEMBIAYAAN_ROUTE_PREFIXES.some(
    (prefix) => path === prefix || path.startsWith(`${prefix}/`),
  );
}

function filterMenuNodes(nodes: MenuNode[] | undefined, permissions: string[] | undefined): MenuNode[] {
  if (!nodes?.length) return [];

  return nodes
    .filter((node) => {
      const requiredPermission = WORKFLOW_MENU_PERMISSIONS[node.id];
      return !requiredPermission || hasPermission(permissions, requiredPermission);
    })
    .map((node) => ({
      ...node,
      children: filterMenuNodes(node.children, permissions),
    }));
}

export function filterMenuByPermissions(
  menu: MenuGroupDef[],
  permissions: string[] | undefined,
  role?: string,
): MenuGroupDef[] {
  const scopedMenu = isAdminRole(role)
    ? menu
    : menu.filter((group) => group.id === PENGURUSAN_PEMBIAYAAN_MENU_GROUP_ID);

  return scopedMenu
    .map((group) => ({
      ...group,
      items: group.items.map((item) => ({
        ...item,
        children: filterMenuNodes(item.children, permissions),
      })),
    }))
    .filter((group) => group.items.length > 0);
}

export function defaultAdminLandingRoute(permissions: string[] | undefined): string {
  for (const config of Object.values(PERMOHONAN_WORKFLOW_STAGES)) {
    if (hasPermission(permissions, config.permission)) {
      return config.routePath;
    }
  }

  return "/admin";
}
