import { ref } from "vue";

import { translate } from "@/i18n";
import { useUiLocaleStore } from "@/stores/uiLocale";

export type ConfirmDialogOptions = {
  title: string;
  message?: string;
  confirmText?: string;
  cancelText?: string;
  destructive?: boolean;
};

function defaultOptions(): ConfirmDialogOptions {
  const locale = useUiLocaleStore().language;
  return {
    title: translate(locale, "common.confirmTitle", "Confirm"),
    message: translate(locale, "common.confirmMessage", "Are you sure?"),
    confirmText: translate(locale, "common.confirm", "Confirm"),
    cancelText: translate(locale, "common.cancel", "Cancel"),
    destructive: false,
  };
}

const initialOptions: ConfirmDialogOptions = {
  title: "Confirm",
  message: "Are you sure?",
  confirmText: "Confirm",
  cancelText: "Cancel",
  destructive: false,
};

const isOpen = ref(false);
const options = ref<ConfirmDialogOptions>({ ...initialOptions });

let resolver: ((accepted: boolean) => void) | null = null;

function close(value: boolean) {
  isOpen.value = false;
  if (resolver) resolver(value);
  resolver = null;
}

function confirm(next: ConfirmDialogOptions) {
  if (resolver) {
    resolver(false);
    resolver = null;
  }

  options.value = {
    title: next.title,
    message: next.message,
    confirmText: next.confirmText ?? defaultOptions().confirmText,
    cancelText: next.cancelText ?? defaultOptions().cancelText,
    destructive: next.destructive ?? false,
  };
  isOpen.value = true;

  return new Promise<boolean>((resolve) => {
    resolver = resolve;
  });
}

export function useConfirmDialog() {
  return {
    isOpen,
    options,
    confirm,
    close,
    accept: () => close(true),
    cancel: () => close(false),
  };
}
