import { useEffect, useRef, useState } from "react";
import { ErrorNotice } from "../../../shared/ui";
type Detector = {
  detect: (source: HTMLVideoElement) => Promise<{ rawValue: string }[]>;
};
type DetectorConstructor = new (options: { formats: string[] }) => Detector;
export function CameraScanner({
  onResult,
}: {
  onResult: (value: string) => void;
}) {
  const video = useRef<HTMLVideoElement>(null);
  const callback = useRef(onResult);
  useEffect(() => {
    callback.current = onResult;
  }, [onResult]);
  const [error, setError] = useState<unknown>(null);
  useEffect(() => {
    let stream: MediaStream | undefined;
    let frame = 0;
    let stopped = false;
    const Detector = (
      window as unknown as { BarcodeDetector?: DetectorConstructor }
    ).BarcodeDetector;
    async function start() {
      try {
        if (!Detector)
          throw Error(
            "Trình duyệt chưa hỗ trợ đọc QR. Dùng camera điện thoại mở liên kết QR, hoặc dán liên kết bên dưới.",
          );
        stream = await navigator.mediaDevices.getUserMedia({
          video: { facingMode: "environment" },
          audio: false,
        });
        if (stopped) {
          stream.getTracks().forEach((t) => t.stop());
          return;
        }
        if (!video.current) return;
        video.current.srcObject = stream;
        await video.current.play();
        const detector = new Detector({ formats: ["qr_code"] });
        const scan = async () => {
          if (stopped || !video.current) return;
          try {
            const codes = await detector.detect(video.current);
            if (stopped) return;
            if (codes[0]) {
              stream?.getTracks().forEach((t) => t.stop());
              callback.current(codes[0].rawValue);
              return;
            }
          } catch {
            /* Retry the next camera frame. */
          }
          frame = requestAnimationFrame(() => void scan());
        };
        void scan();
      } catch (e) {
        setError(e);
      }
    }
    void start();
    return () => {
      stopped = true;
      cancelAnimationFrame(frame);
      stream?.getTracks().forEach((t) => t.stop());
    };
  }, []);
  return (
    <>
      <video
        className="camera-video"
        ref={video}
        muted
        playsInline
        aria-label="Camera quét mã QR"
      />
      <ErrorNotice error={error} />
    </>
  );
}
