import { Link } from "react-router-dom";
import {
  ArrowRight,
  BookOpen,
  CalendarDays,
  Plus,
  ScanLine,
} from "lucide-react";
import { useSession } from "../../../application/sessionContext";
import { useResource } from "../../../application/queries";
import { classApi } from "../../../infrastructure/classApi";
import { PageHeader, QueryState, Empty, Badge } from "../../../shared/ui";
import { ClassCard } from "../../classes/components/ClassCard";
import { dateTime } from "../../../domain/format";
export default function DashboardPage() {
  const { user } = useSession();
  const classes = useResource(["classes", "dashboard"], (s) =>
    classApi.list({ pageSize: 6, status: "active" }, s),
  );
  const start = new Date();
  start.setHours(0, 0, 0, 0);
  const from = start.toISOString(),
    to = new Date(start.getTime() + 7 * 86400000).toISOString();
  const schedule = useResource(["calendar", from, to], (s) =>
    classApi.sessions({ from, to, pageSize: 5, status: "scheduled" }, s),
  );
  const unread = useResource(["unread"], (s) => classApi.unread(s));
  return (
    <>
      <PageHeader
        eyebrow="HÔM NAY, CÙNG TIẾN BỘ"
        title={
          "Xin chào, " + (user?.displayName.split(" ").at(-1) ?? "bạn") + "!"
        }
        description="Mọi thứ cho lớp học, trong một không gian."
        action={
          <Link className="button" to="/app/classes">
            <Plus size={18} />
            {user?.role === "teacher" ? "Quản lý lớp học" : "Tham gia lớp học"}
          </Link>
        }
      />
      <section className="welcome-banner">
        <div>
          <span className="label-chip">SẴN SÀNG CHO BUỔI HỌC MỚI</span>
          <h2>
            Mỗi buổi học.
            <br />
            Một bước tiến mới.
          </h2>
          <p>Lịch học, bài tập và điểm số — luôn ở trong tầm tay.</p>
          <Link to="/app/calendar" className="button white">
            Xem lịch học <ArrowRight size={17} />
          </Link>
        </div>
        <div className="banner-art" aria-hidden="true">
          <span className="art-ring" />
          <span className="art-book">
            <BookOpen size={66} />
          </span>
          <span className="art-calendar">
            <CalendarDays size={32} />
          </span>
          <span className="art-spark">✦</span>
          <span className="art-line" />
        </div>
      </section>
      <div className="stats-grid">
        <div className="stat-card">
          <span className="stat-icon blue">
            <BookOpen />
          </span>
          <div>
            <p>Lớp đang hoạt động</p>
            <strong>{classes.data?.totalItems ?? "—"}</strong>
          </div>
          <Link to="/app/classes" aria-label="Xem lớp học">
            <ArrowRight size={18} />
          </Link>
        </div>
        <div className="stat-card">
          <span className="stat-icon teal">
            <CalendarDays />
          </span>
          <div>
            <p>Buổi học trong 7 ngày</p>
            <strong>{schedule.data?.totalItems ?? "—"}</strong>
          </div>
          <Link to="/app/calendar" aria-label="Xem lịch học">
            <ArrowRight size={18} />
          </Link>
        </div>
        <div className="stat-card">
          <span className="stat-icon amber">
            <ScanLine />
          </span>
          <div>
            <p>Thông báo chưa đọc</p>
            <strong>{unread.data?.unreadCount ?? "—"}</strong>
          </div>
          <Link to="/app/notifications" aria-label="Xem thông báo">
            <ArrowRight size={18} />
          </Link>
        </div>
      </div>
      <div className="dashboard-grid">
        <section>
          <div className="section-heading">
            <h2>
              Lớp học của bạn{" "}
              <span className="count-pill">
                {classes.data?.totalItems ?? 0}
              </span>
            </h2>
            <Link to="/app/classes">
              Tất cả lớp học <ArrowRight size={15} />
            </Link>
          </div>
          <QueryState
            pending={classes.isPending}
            error={classes.error}
            retry={classes.refetch}
          />
          {classes.data?.items.length ? (
            <div className="class-grid compact">
              {classes.data.items.map((c, i) => (
                <ClassCard key={c.id} classroom={c} index={i} />
              ))}
            </div>
          ) : classes.isSuccess ? (
            <div className="panel">
              <Empty
                title="Bắt đầu từ một lớp học"
                description={
                  user?.role === "teacher"
                    ? "Tạo lớp đầu tiên và mời học sinh bằng mã lớp."
                    : "Nhập mã lớp từ giáo viên để gửi yêu cầu tham gia."
                }
              >
                <Link className="button secondary" to="/app/classes">
                  Đến lớp học <ArrowRight size={15} />
                </Link>
              </Empty>
            </div>
          ) : null}
        </section>
        <aside>
          <div className="section-heading">
            <h2>Lịch trong 7 ngày</h2>
            <CalendarDays size={18} />
          </div>
          <div className="panel upcoming">
            <QueryState
              pending={schedule.isPending}
              error={schedule.error}
              retry={schedule.refetch}
            />
            {schedule.data?.items.length ? (
              schedule.data.items.map((s) => (
                <Link
                  key={s.id}
                  className="upcoming-row"
                  to={"/app/classes/" + s.classId + "/schedule"}
                >
                  <span className="schedule-dot" />
                  <div>
                    <strong>{s.title || "Buổi học"}</strong>
                    <p>{dateTime(s.startsAt)}</p>
                    <Badge value={s.status} />
                  </div>
                </Link>
              ))
            ) : schedule.isSuccess ? (
              <Empty
                title="Lịch đang trống"
                description="Các buổi học trong 7 ngày tới sẽ xuất hiện ở đây."
              />
            ) : null}
            <Link to="/app/calendar" className="text-link">
              Mở lịch học <ArrowRight size={15} />
            </Link>
          </div>
          <div className="tip-card">
            <span className="eyebrow">MỘT CHÚT NHẮC NHỞ</span>
            <h3>Đều đặn tạo nên khác biệt.</h3>
            <p>Ghé thăm lớp học mỗi ngày để theo dõi lịch và bài tập mới.</p>
          </div>
        </aside>
      </div>
    </>
  );
}
