import { MASK_URL } from "@/env";

/** Replace the visible URL with origin only, preserving Vue Router history state. */
export function maskBrowserUrl(): void {
  if (!MASK_URL) return;

  const { origin, pathname, search, hash } = window.location;
  const visiblePath = `${pathname}${search}${hash}`;

  if (visiblePath === "/" || visiblePath === "") return;

  window.history.replaceState(window.history.state, "", origin);
}
