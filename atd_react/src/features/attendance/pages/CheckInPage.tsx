import { Modal } from "../../../shared/components/Modal";
import { useSearchParams, Link } from "react-router-dom";
import { useState } from "react";
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
  const [camera, setCamera] = useState(false);
  const [result, setResult] = useState<Attendance | null>(null);
  const { user } = useSession();
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
      <section className="panel checkin-panel">
        {result ? (
          <div className="empty">
            <CheckCircle2 size={48} className="teal" />
            <h2>Đã ghi nhận điểm danh</h2>
            <Badge value={result.status} />
            <p>Mã ghi nhận: {result.id}</p>
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
            <span className="empty-icon">
              <ScanLine size={42} />
            </span>
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
                  Đóng camera (giữ mã vừa quét)
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
                  : "Quét QR hoặc dán liên kết điểm danh."}
              </p>
              <button
                type="button"
                className="button secondary"
                onClick={() => {
                  setQrDraft(token);
                  setEditing(true);
                }}
              >
                {token ? "Sửa mã QR" : "Dán mã QR"}
              </button>
              <button className="button wide">Xác nhận điểm danh</button>
            </AsyncForm>
            {editing ? (
              <Modal title="Nhập mã QR" onClose={() => setEditing(false)}>
                <AsyncForm
                  onSubmit={async () => {
                    setToken(qrDraft);
                    setEditing(false);
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
                      placeholder="Quét QR hoặc dán liên kết điểm danh…"
                    />
                  </Field>
                  <footer className="modal-actions">
                    <button
                      type="button"
                      className="button secondary"
                      onClick={() => setEditing(false)}
                    >
                      Đóng
                    </button>
                    <button className="button">Áp dụng</button>
                  </footer>
                </AsyncForm>
              </Modal>
            ) : null}
          </>
        )}
      </section>
    </>
  );
}
