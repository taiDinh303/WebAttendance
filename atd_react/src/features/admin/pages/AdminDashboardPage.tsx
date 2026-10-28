import { Link } from "react-router-dom";
import {
  ArrowRight,
  GraduationCap,
  ShieldCheck,
  Users,
  UserRound,
} from "lucide-react";
import { useResource } from "../../../application/queries";
import { adminApi } from "../../../infrastructure/adminApi";
import { PageHeader, QueryState } from "../../../shared/ui";
import { dateTime } from "../../../domain/format";

export default function AdminDashboardPage() {
  const query = useResource(["admin-summary"], (signal) =>
    adminApi.summary(signal),
  );
  const data = query.data;
  const metrics = [
    { key: "users", label: "Tổng tài khoản", icon: Users, value: data?.users },
    {
      key: "admins",
      label: "Quản trị viên",
      icon: ShieldCheck,
      value: data?.admins,
    },
    {
      key: "teachers",
      label: "Giáo viên",
      icon: GraduationCap,
      value: data?.teachers,
    },
    {
      key: "students",
      label: "Học sinh",
      icon: UserRound,
      value: data?.students,
    },
  ];
  return (
    <>
      <PageHeader
        eyebrow="QUẢN TRỊ HỆ THỐNG"
        title="Một góc nhìn toàn hệ thống"
        description="Theo dõi quy mô và quản lý tài khoản. Nội dung học tập vẫn thuộc quyền của từng lớp."
        action={
          <Link to="/admin/users" className="button">
            <Users size={18} />
            Quản lý tài khoản
          </Link>
        }
      />
      <QueryState
        pending={query.isPending}
        error={query.error}
        retry={query.refetch}
      />
      <div className="stats-grid v2-metrics">
        {metrics.map((metric) => (
          <div
            className="stat-card"
            key={metric.key}
            data-testid={"admin-stat-" + metric.key}
          >
            <span className="stat-icon blue">
              <metric.icon size={21} />
            </span>
            <div>
              <p>{metric.label}</p>
              <strong>{metric.value ?? "—"}</strong>
            </div>
          </div>
        ))}
      </div>
      {data ? (
        <div className="detail-grid">
          <section className="panel editor-panel">
            <span className="eyebrow">HOẠT ĐỘNG TỔNG HỢP</span>
            <h2>Không gian lớp học</h2>
            <dl className="admin-overview">
              <div>
                <dt>Lớp đang hoạt động</dt>
                <dd>
                  {data.activeClasses} / {data.classes}
                </dd>
              </div>
              <div>
                <dt>Bài tập</dt>
                <dd>{data.assignments}</dd>
              </div>
              <div>
                <dt>Bài nộp</dt>
                <dd>{data.submissions}</dd>
              </div>
              <div>
                <dt>Kết quả điểm danh</dt>
                <dd>{data.attendanceRecords}</dd>
              </div>
              <div>
                <dt>Cửa sổ điểm danh đang mở</dt>
                <dd>{data.openAttendanceWindows}</dd>
              </div>
            </dl>
            <p className="muted">
              Thống kê tổng hợp, không chứa điểm cá nhân, nội dung bài nộp hoặc
              danh sách lớp riêng.
            </p>
          </section>
          <aside className="panel editor-panel">
            <span className="eyebrow">AN TOÀN TÀI KHOẢN</span>
            <h2>Quản trị có kiểm soát</h2>
            <p>
              <strong>{data.lockedAccounts}</strong> tài khoản đang bị khóa.
            </p>
            <p>
              <strong>{data.adminEvents}</strong> thao tác quản trị đã được ghi
              nhận.
            </p>
            <p className="info">
              Khóa và mở khóa cần lý do. Các phiên đăng nhập cũ bị thu hồi; lịch
              sử trước/sau được giữ để đối chiếu.
            </p>
            <Link className="button secondary" to="/admin/audit">
              Xem nhật ký <ArrowRight size={16} />
            </Link>
            <small className="muted">
              Cập nhật lúc {dateTime(data.serverTime)}
            </small>
          </aside>
        </div>
      ) : null}
    </>
  );
}
