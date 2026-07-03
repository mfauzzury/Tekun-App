import type { AppLanguage } from "@/types";

import messagesBm from "./messages/bm";
import messagesBi from "./messages/bi";

type MessageValue = string | { [key: string]: MessageValue };
export type MessageTree = { [key: string]: MessageValue };

const catalogs: Record<AppLanguage, MessageTree> = {
  bm: messagesBm,
  bi: messagesBi,
};

function resolvePath(tree: MessageTree, path: string): string | undefined {
  const parts = path.split(".");
  let current: string | MessageTree | undefined = tree;

  for (const part of parts) {
    if (!current || typeof current === "string") return undefined;
    current = current[part];
  }

  return typeof current === "string" ? current : undefined;
}

export function translate(locale: AppLanguage, key: string, fallback?: string): string {
  return resolvePath(catalogs[locale], key) ?? fallback ?? key;
}

function flattenMessages(tree: MessageTree, prefix = "", out: Record<string, string> = {}): Record<string, string> {
  for (const [key, value] of Object.entries(tree)) {
    const nextKey = prefix ? `${prefix}.${key}` : key;
    if (typeof value === "string") {
      out[nextKey] = value;
    } else {
      flattenMessages(value, nextKey, out);
    }
  }
  return out;
}

const flatBm = flattenMessages(messagesBm);
const flatBi = flattenMessages(messagesBi);

const phraseBiByBm: Record<string, string> = {};
for (const [key, bmValue] of Object.entries(flatBm)) {
  const biValue = flatBi[key];
  if (biValue && biValue !== bmValue) {
    phraseBiByBm[bmValue] = biValue;
  }
}

export function translatePhrase(locale: AppLanguage, source: string): string {
  if (locale === "bm") return source;
  return phraseBiByBm[source] ?? source;
}

export function translateMenuLabel(locale: AppLanguage, id: string, fallback: string): string {
  return translate(locale, `menu.items.${id}`, fallback);
}

export function translateRouteTitle(locale: AppLanguage, routeName: string, fallback: string): string {
  return translate(locale, `routes.${routeName}`, fallback);
}
