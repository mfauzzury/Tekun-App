import { nextTick, onBeforeUnmount, ref } from "vue";

export const LIVENESS_CABARAN = ["Pandang terus ke kamera", "Pusing kepala sedikit ke kiri", "Senyum"] as const;

export type LivenessFrame = { base64: string; mime: string; arahan: string; preview: string; mirror: boolean };
export type LivenessPhase = "sedia" | "cabaran" | "semak" | "hantar" | "keputusan";

const MAX_W = 640;
const wait = (ms: number) => new Promise((r) => window.setTimeout(r, ms));

function drawToJpeg(source: CanvasImageSource, w: number, h: number): string {
  const scale = Math.min(1, MAX_W / Math.max(1, w));
  const canvas = document.createElement("canvas");
  canvas.width = Math.round(w * scale);
  canvas.height = Math.round(h * scale);
  canvas.getContext("2d")?.drawImage(source, 0, 0, canvas.width, canvas.height);
  return canvas.toDataURL("image/jpeg", 0.8);
}

function toFrame(dataUrl: string, arahan: string, mirror: boolean): LivenessFrame {
  return { base64: dataUrl.replace(/^data:image\/\w+;base64,/, ""), mime: "image/jpeg", arahan, preview: dataUrl, mirror };
}

/**
 * Active liveness capture: front camera, three on-screen challenges with a 3-second countdown,
 * one JPEG frame per challenge. Shared by the officer page and the applicant portal.
 */
export function useLivenessCapture() {
  const videoEl = ref<HTMLVideoElement | null>(null);
  const stream = ref<MediaStream | null>(null);
  const cameraError = ref("");
  const starting = ref(false);
  const phase = ref<LivenessPhase>("sedia");
  const currentIdx = ref(0);
  const countdown = ref(0);
  const frames = ref<LivenessFrame[]>([]);
  let cancelled = false;

  async function startCamera() {
    cameraError.value = "";
    if (!navigator.mediaDevices?.getUserMedia) {
      cameraError.value = "Pelayar ini tidak menyokong akses kamera. Gunakan muat naik gambar.";
      return;
    }
    starting.value = true;
    try {
      stream.value = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: "user", width: { ideal: 1280 }, height: { ideal: 720 } },
        audio: false,
      });
      await nextTick();
      if (videoEl.value) {
        videoEl.value.srcObject = stream.value;
        await videoEl.value.play().catch(() => undefined);
      }
    } catch (e: unknown) {
      cameraError.value =
        e instanceof Error && e.name === "NotAllowedError"
          ? "Akses kamera ditolak. Benarkan kamera pada pelayar atau gunakan muat naik gambar."
          : "Kamera tidak dapat dibuka. Gunakan muat naik gambar sebagai alternatif.";
    } finally {
      starting.value = false;
    }
  }

  function stopCamera() {
    stream.value?.getTracks().forEach((t) => t.stop());
    stream.value = null;
    if (videoEl.value) videoEl.value.srcObject = null;
  }

  function reset() {
    cancelled = true;
    phase.value = "sedia";
    currentIdx.value = 0;
    countdown.value = 0;
    frames.value = [];
  }

  async function runChallenges() {
    const video = videoEl.value;
    if (!video || !stream.value) return;
    cancelled = false;
    frames.value = [];
    phase.value = "cabaran";
    for (let i = 0; i < LIVENESS_CABARAN.length; i++) {
      currentIdx.value = i;
      for (let c = 3; c > 0; c--) {
        if (cancelled) return;
        countdown.value = c;
        await wait(1000);
      }
      if (cancelled) return;
      countdown.value = 0;
      frames.value.push(toFrame(drawToJpeg(video, video.videoWidth || 640, video.videoHeight || 480), LIVENESS_CABARAN[i], true));
      await wait(350);
    }
    phase.value = "semak";
  }

  /** Fallback when no camera: up to 3 still images, labelled with the challenges in order. */
  async function framesFromFiles(fileList: FileList | File[]) {
    const files = Array.from(fileList).slice(0, 3);
    const out: LivenessFrame[] = [];
    for (let i = 0; i < files.length; i++) {
      const url = URL.createObjectURL(files[i]);
      try {
        const img = new Image();
        await new Promise<void>((resolve, reject) => {
          img.onload = () => resolve();
          img.onerror = () => reject(new Error("Imej tidak sah"));
          img.src = url;
        });
        out.push(toFrame(drawToJpeg(img, img.naturalWidth, img.naturalHeight), LIVENESS_CABARAN[i] ?? `Gambar ${i + 1}`, false));
      } finally {
        URL.revokeObjectURL(url);
      }
    }
    frames.value = out;
    phase.value = "semak";
  }

  function payload() {
    return frames.value.map(({ base64, mime, arahan }) => ({ base64, mime, arahan }));
  }

  onBeforeUnmount(() => {
    cancelled = true;
    stopCamera();
  });

  return {
    CABARAN: LIVENESS_CABARAN,
    videoEl,
    stream,
    cameraError,
    starting,
    phase,
    currentIdx,
    countdown,
    frames,
    startCamera,
    stopCamera,
    reset,
    runChallenges,
    framesFromFiles,
    payload,
  };
}
