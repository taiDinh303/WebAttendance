import { RosterEntry } from "../components/RosterEntry";
import { useParams, Link } from "react-router-dom";
import { useEffect, useState } from "react";
import QRCode from "qrcode";
import { useSession } from "../../../application/sessionContext";
import { useResource, usePage, useRefresh } from "../../../application/queries";
import { attendanceApi } from "../../../infrastructure/attendanceApi";
import { ApiError } from "../../../infrastructure/http";
import {
  PageHeader,
  AsyncForm,
  Field,
  QueryState,
  Badge,
  ActionButton,
  Pagination,
} from "../../../shared/ui";
import { dateTime, iso } from "../../../domain/format";
import type { Window, Qr } from "../../../domain/models";
export default function QrPage() {
  const { classId = "", sessionId = "" } = useParams();
  const { user } = useSession();
  const [openedAt] = useState(() => Date.now());
  const refresh = useRefresh();
  const query = useResource(
    ["attendance-window", sessionId],
    async (s) => {
      try {
        return await attendanceApi.window(classId, sessionId, s);
      } catch (e) {
        if (e instanceof ApiError && e.status === 404) return null;
        throw e;
      }
    },
    { enabled: user?.role === "teacher", refetchInterval: 15000 },
  );
  if (user?.role !== "teacher")
    return <p className="info">Chỉ giáo viên sở hữu lớp được mở mã QR.</p>;
  return (
    <>
      <Link className="back-link" to={"/app/classes/" + classId + "/schedule"}>
        ← Lịch học của lớp
      </Link>
      <PageHeader
        title="Điểm danh buổi học"
        description="Danh sách học sinh được chốt khi bạn mở cửa sổ điểm danh."
      />
      <QueryState
        pending={query.isPending}
        error={query.error}
        retry={query.refetch}
      />
      {query.isSuccess && !query.data ? (
        <div className="panel editor-panel narrow">
          <h2>Mở điểm danh QR</h2>
          <AsyncForm
            onSubmit={async (f) => {
              await attendanceApi.open(classId, sessionId, {
                opensAt: iso(f.get("opensAt")),
                lateAfter: iso(f.get("lateAfter")),
                closesAt: iso(f.get("closesAt")),
              });
              await refresh();
            }}
          >
            {[
              ["opensAt", "Mở từ", -1],
              ["lateAfter", "Tính muộn sau", 5],
              ["closesAt", "Đóng lúc", 15],
            ].map(([name, label, offset]) => (
              <Field key={name} label={String(label)}>
                <input
                  name={String(name)}
                  type="datetime-local"
                  required
                  defaultValue={new Date(
                    openedAt +
                      Number(offset) * 60000 -
                      new Date().getTimezoneOffset() * 60000,
                  )
                    .toISOString()
                    .slice(0, 16)}
                />
              </Field>
            ))}
            <p className="muted">
              Các mốc được nhập theo giờ máy của bạn. Giờ mở cần bao gồm thời
              điểm hiện tại.
            </p>
            <button className="button">Mở QR & chốt danh sách</button>
          </AsyncForm>
        </div>
      ) : null}
      {query.data ? (
        <ActiveWindow
          classId={classId}
          sessionId={sessionId}
          window={query.data}
        />
      ) : null}
    </>
  );
}
function ActiveWindow({
  classId,
  sessionId,
  window: w,
}: {
  classId: string;
  sessionId: string;
  window: Window;
}) {
  const { page, setPage } = usePage();
  const refresh = useRefresh();
  const qr = useResource(["qr", w.id], (s) => attendanceApi.qr(w.id, s), {
    enabled: w.state === "open",
    refetchInterval: 10000,
  });
  const roster = useResource(
    ["roster", w.id, page],
    (s) => attendanceApi.roster(w.id, page, s),
    { refetchInterval: 10000 },
  );
  return (
    <div className="detail-grid">
      <section className="panel qr-panel">
        <Badge value={w.state} />
        <h2>Quét để bắt đầu buổi học</h2>
        {w.state === "open" ? (
          <>
            <QueryState
              pending={qr.isPending}
              error={qr.error}
              retry={qr.refetch}
            />
            {qr.data ? <QrImage key={qr.data.qrToken} data={qr.data} /> : null}
            <ActionButton
              className="button danger"
              action={async () => {
                if (!confirm("Đóng điểm danh và chốt các trường hợp vắng?"))
                  return;
                await attendanceApi.close(w);
                await refresh();
              }}
            >
              Đóng điểm danh
            </ActionButton>
          </>
        ) : (
          <p>
            Điểm danh đã đóng. Giáo viên có thể điều chỉnh kết quả với lý do.
          </p>
        )}
        <p className="muted">
          Đóng lúc {dateTime(w.closesAt)}. Rời trang không tự đóng QR.
        </p>
      </section>
      <section className="panel editor-panel">
        <h2>Danh sách điểm danh</h2>
        <QueryState
          pending={roster.isPending}
          error={roster.error}
          retry={roster.refetch}
        />
        {roster.data?.items.map((row) => (
          <RosterEntry
            key={row.studentId}
            classId={classId}
            sessionId={sessionId}
            row={row}
            refreshing={roster.isFetching}
          />
        ))}
        <Pagination data={roster.data} onPage={setPage} />
      </section>
    </div>
  );
}
function QrImage({ data }: { data: Qr }) {
  const [src, setSrc] = useState("");
  const [seconds, setSeconds] = useState(() =>
    Math.max(
      0,
      Math.ceil(
        (Date.parse(data.validUntil) - Date.parse(data.serverTime)) / 1000,
      ),
    ),
  );
  useEffect(() => {
    let alive = true;
    const url = new URL("/app/check-in", location.origin);
    url.searchParams.set("token", data.qrToken);
    void QRCode.toDataURL(url.toString(), {
      width: 300,
      margin: 2,
      errorCorrectionLevel: "M",
    }).then((src) => {
      if (alive) setSrc(src);
    });
    const deadline =
      performance.now() +
      Date.parse(data.validUntil) -
      Date.parse(data.serverTime);
    const timer = setInterval(
      () =>
        setSeconds(
          Math.max(0, Math.ceil((deadline - performance.now()) / 1000)),
        ),
      1000,
    );
    return () => {
      alive = false;
      clearInterval(timer);
    };
  }, [data]);
  return (
    <>
      {src && seconds > 0 ? (
        <img
          className="qr-image"
          src={src}
          alt="Mã QR điểm danh đang có hiệu lực"
        />
      ) : (
        <p className="info">Đang lấy mã QR mới…</p>
      )}
      <p className="qr-countdown">Mã hiện tại còn {seconds} giây</p>
    </>
  );
}
