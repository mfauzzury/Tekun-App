import { DEFAULT_MENU, type AdminMenuPrefs } from "@/config/admin-menu";

/** SPPT menu children that must always appear in the sidebar (even if previously hidden in saved prefs). */
const FORCE_VISIBLE_CHILDREN = new Set([
  "pembayaran-tunggakan",
]);

export function buildDefaultMenuPrefs(): AdminMenuPrefs {
  return {
    groupOrder: DEFAULT_MENU.map((group) => group.id),
    itemOrder: Object.fromEntries(DEFAULT_MENU.map((group) => [group.id, group.items.map((item) => item.id)])),
    childOrder: Object.fromEntries(
      DEFAULT_MENU.flatMap((group) =>
        group.items
          .filter((item) => item.children && item.children.length > 0)
          .map((item) => [item.id, (item.children || []).map((child) => child.id)]),
      ),
    ),
    grandchildOrder: Object.fromEntries(
      DEFAULT_MENU.flatMap((group) =>
        group.items.flatMap((item) =>
          (item.children || [])
            .filter((child) => child.children && child.children.length > 0)
            .map((child) => [child.id, (child.children || []).map((grandchild) => grandchild.id)]),
        ),
      ),
    ),
    hidden: [],
    hiddenChildren: [],
    hiddenGrandchildren: [],
    hiddenGroups: [],
  };
}

export function mergeMenuPrefsWithDefaults(prefs: AdminMenuPrefs): AdminMenuPrefs {
  const defaults = buildDefaultMenuPrefs();

  const groupOrder = [...prefs.groupOrder];
  for (const groupId of defaults.groupOrder) {
    if (!groupOrder.includes(groupId)) groupOrder.push(groupId);
  }

  const itemOrder: Record<string, string[]> = {};
  for (const group of DEFAULT_MENU) {
    const saved = prefs.itemOrder[group.id] || [];
    const merged = [...saved];
    for (const item of group.items) {
      if (!merged.includes(item.id)) merged.push(item.id);
    }
    itemOrder[group.id] = merged.filter((id) => group.items.some((item) => item.id === id));
  }

  const childOrder: Record<string, string[]> = {};
  for (const group of DEFAULT_MENU) {
    for (const item of group.items) {
      if (!item.children || item.children.length === 0) continue;
      const saved = prefs.childOrder[item.id] || [];
      const merged = [...saved];
      for (const child of item.children) {
        if (!merged.includes(child.id)) merged.push(child.id);
      }
      childOrder[item.id] = merged.filter((id) => item.children?.some((child) => child.id === id));
    }
  }

  const grandchildOrder: Record<string, string[]> = {};
  for (const group of DEFAULT_MENU) {
    for (const item of group.items) {
      for (const child of item.children || []) {
        if (!child.children || child.children.length === 0) continue;
        const saved = prefs.grandchildOrder[child.id] || [];
        const merged = [...saved];
        for (const grandchild of child.children) {
          if (!merged.includes(grandchild.id)) merged.push(grandchild.id);
        }
        grandchildOrder[child.id] = merged.filter((id) => child.children?.some((node) => node.id === id));
      }
    }
  }

  const allItemIds = new Set(DEFAULT_MENU.flatMap((group) => group.items.map((item) => item.id)));
  const allChildIds = new Set(DEFAULT_MENU.flatMap((group) => group.items.flatMap((item) => (item.children || []).map((child) => child.id))));
  const allGrandchildIds = new Set(
    DEFAULT_MENU.flatMap((group) => group.items.flatMap((item) => (item.children || []).flatMap((child) => (child.children || []).map((node) => node.id)))),
  );

  return {
    groupOrder: groupOrder.filter((groupId) => defaults.groupOrder.includes(groupId)),
    itemOrder,
    childOrder,
    grandchildOrder,
    hidden: prefs.hidden.filter((id) => allItemIds.has(id)),
    hiddenChildren: (prefs.hiddenChildren || [])
      .filter((id) => allChildIds.has(id))
      .filter((id) => !FORCE_VISIBLE_CHILDREN.has(id)),
    hiddenGrandchildren: (prefs.hiddenGrandchildren || []).filter((id) => allGrandchildIds.has(id)),
    hiddenGroups: (prefs.hiddenGroups || []).filter((groupId) => defaults.groupOrder.includes(groupId)),
  };
}

export function normalizeMenuPrefs(raw: AdminMenuPrefs | null): AdminMenuPrefs | null {
  if (!raw) return null;

  const prefs: AdminMenuPrefs = {
    groupOrder: raw.groupOrder || [],
    itemOrder: raw.itemOrder || {},
    childOrder: raw.childOrder || {},
    grandchildOrder: raw.grandchildOrder || {},
    hidden: raw.hidden || [],
    hiddenChildren: raw.hiddenChildren || [],
    hiddenGrandchildren: raw.hiddenGrandchildren || [],
    hiddenGroups: raw.hiddenGroups || [],
  };

  return mergeMenuPrefsWithDefaults(prefs);
}
