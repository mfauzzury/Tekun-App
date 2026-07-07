type EchoLike = {
  private: (ch: string) => { listen: (event: string, cb: (data: unknown) => void) => void };
  leave: (ch: string) => void;
  disconnect: () => void;
};

/** Reverb/Echo optional — returns null when VITE_REVERB_APP_KEY is unset; chat falls back to polling. */
export function getEcho(): EchoLike | null {
  return null;
}

export function disconnectEcho(): void {
  // no-op without Reverb
}
