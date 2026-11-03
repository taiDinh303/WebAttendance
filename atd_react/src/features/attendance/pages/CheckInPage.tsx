import { CheckInClock } from "../components/CheckInClock";
import { readQrImage } from "../../../infrastructure/qrImage";
import { Modal } from "../../../shared/components/Modal";
import { useSearchParams, Link } from "react-router-dom";
import { useState, useRef, useEffect } from "react";
import { ScanLine, Camera, CheckCircle2 } from "lucide-react";
import { useSession } from "../../../application/sessionContext";
import { attendanceApi } from "../../../infrastructure/attendanceApi";
import { extractQrToken } from "../../../domain/qr";
import type { Attendance } from "../../../domain/models";
import { PageHeader, AsyncForm, Field, Badge } from "../../../shared/ui";
import { CameraScanner } from "../components/CameraScanner";
export default function CheckInPage() {
  const [search, setSearch] = useSearchParams();
  const [token, setToken] = useState(search.get("token") ?? "");
  const [editing, setEditing] = useState(false);
  const [qrDraft, setQrDraft] = useState("");
  const [readingImage, setReadingImage] = useState(false);
  const [imageMessage, setImageMessage] = useState("");
  const [camera, setCamera] = useState(false);
  const [result, setResult] = useState<Attendance | null>(null);
  const { user } = useSession();
  const imageRequest = useRef(0);
  useEffect(
    () => () => {
      imageRequest.current++;
    },
    [editing],
  );
  async function acceptImage(file: File) {
    if (readingImage) return;
    const request = ++imageRequest.current;
    setQrDraft("");
    setReadingImage(true);
    setImageMessage("Đang đọc ảnh QR…");
    try {
      const value = await readQrImage(file);
      if (request !== imageRequest.current) return;
      extractQrToken(value, window.location.origin);
      setQrDraft(value);
      setImageMessage("Đã đọc ảnh QR. Nhấn Áp dụng rồi xác nhận điểm danh.");
    } catch (error) {
      if (request === imageRequest.current)
        setImageMessage(
          error instanceof Error ? error.message : "Không đọc được ảnh QR.",
        );
    } finally {
      if (request === imageRequest.current) setReadingImage(false);
    }
  }
  if (user?.role !== "student")
    return (
      <>
        <PageHeader title="Điểm danh dành cho học sinh" />
        <Link to="/app/calendar">Mở lịch để quản lý QR của lớp</Link>
      </>
    );
  return (
    <>
      <h1 className="sr-only">Điểm danh bằng QR</h1>
      <div className="checkin-stage">
        <section className="panel checkin-panel checkin-centered">
          <CheckInClock />
          {result ? (
            <div className="empty">
              <CheckCircle2 size={48} className="teal" />
              <h2>Đã ghi nhận điểm danh</h2>
              <Badge value={result.status} />
              <p>
                Chờ giáo viên kiểm tra và duyệt. Nếu cần điều chỉnh, hãy liên hệ
                giáo viên.
              </p>
              <button
                className="button secondary"
                onClick={() => {
                  setResult(null);
                  setToken("");
                  setSearch({}, { replace: true });
                }}
              >
                Quét buổi học khác
              </button>
            </div>
          ) : (
            <>
              {!camera ? (
                <span className="empty-icon checkin-scan-icon">
                  <ScanLine size={32} />
                </span>
              ) : null}
              {camera ? (
                <>
                  <CameraScanner
                    onResult={(value) => {
                      setToken(value);
                      setCamera(false);
                    }}
                  />
                  <button
                    className="button secondary"
                    onClick={() => setCamera(false)}
                  >
                    Đóng camera
                  </button>
                </>
              ) : (
                <button
                  className="button secondary"
                  onClick={() => setCamera(true)}
                >
                  <Camera size={17} />
                  {token ? "Quét mã khác" : "Mở camera"}
                </button>
              )}
              <AsyncForm
                onSubmit={async () => {
                  const qrToken = extractQrToken(token, window.location.origin);
                  setResult(await attendanceApi.checkIn(qrToken));
                  setSearch({}, { replace: true });
                }}
              >
                <p className="muted">
                  {token
                    ? "Đã nhận mã QR. Bạn có thể xác nhận điểm danh."
                    : "Quét QR, dán ảnh hoặc liên kết điểm danh."}
                </p>
                <button
                  type="button"
                  className="button secondary"
                  onClick={() => {
                    setReadingImage(false);
                    setImageMessage("");
                    setQrDraft(token);
                    setEditing(true);
                  }}
                >
                  {token ? "Sửa mã QR" : "Dán mã QR"}
                </button>
                <button className="button wide" disabled={!token.trim()}>
                  Xác nhận điểm danh
                </button>
              </AsyncForm>
              {editing ? (
                <Modal title="Nhập mã QR" onClose={() => setEditing(false)}>
                  <AsyncForm
                    onSubmit={async () => {
                      if (readingImage)
                        throw Error("Đang đọc ảnh QR. Vui lòng chờ một chút.");
                      extractQrToken(qrDraft, window.location.origin);
                      setToken(qrDraft);
                      setEditing(false);
                    }}
                  >
                    <div
                      onPaste={(event) => {
                        const image = Array.from(event.clipboardData.items)
                          .find((item) => item.type.startsWith("image/"))
                          ?.getAsFile();
                        if (image) {
                          event.preventDefault();
                          void acceptImage(image);
                        }
                      }}
                    >
                      <Field
                        label="Liên kết hoặc nội dung mã QR"
                        hint="Mã thay đổi mỗi 30 giây. Không nhập mã lớp vào ô này."
                      >
                        <textarea
                          name="qrToken"
                          required
                          value={qrDraft}
                          onChange={(e) => setQrDraft(e.target.value)}
                          rows={4}
                          placeholder="Ctrl+V để dán ảnh QR hoặc liên kết điểm danh…"
                        />
                      </Field>
                      <Field label="Chọn ảnh QR từ máy">
                        <input
                          type="file"
                          accept="image/*"
                          disabled={readingImage}
                          onChange={(event) => {
                            const file = event.target.files?.[0];
                            if (file) void acceptImage(file);
                            event.target.value = "";
                          }}
                        />
                      </Field>
                      {imageMessage ? (
                        <p role="status">{imageMessage}</p>
                      ) : null}
                    </div>
                    <footer className="modal-actions">
                      <button
                        type="button"
                        className="button secondary"
                        onClick={() => setEditing(false)}
                      >
                        Đóng
                      </button>
                      <button className="button" disabled={readingImage}>
                        Áp dụng
                      </button>
                    </footer>
                  </AsyncForm>
                </Modal>
              ) : null}
            </>
          )}
        </section>
      </div>
    </>
  );
}
