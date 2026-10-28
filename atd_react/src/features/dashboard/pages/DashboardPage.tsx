import { Link } from "react-router-dom";
import {
  ArrowRight,
  BookOpen,
  CalendarDays,
  ClipboardCheck,
  Clock3,
  Plus,
  ScanLine,
  Users,
  CheckCircle2,
} from "lucide-react";
import { useSession } from "../../../application/sessionContext";
import { useResource } from "../../../application/queries";
import { classApi } from "../../../infrastructure/classApi";
import { PageHeader, QueryState, Empty } from "../../../shared/ui";
import { ClassCard } from "../../classes/components/ClassCard";
import { dateTime } from "../../../domain/format";

export default function DashboardPage() {
  const { user } = useSession();
  const query = useResource(
    ["dashboard"],
    (signal) => classApi.dashboard(signal),
    { refetchInterval: 60000 },
  );
  const teacher = user?.role === "teacher";
  const data = query.data;
  const summary = data?.summary;
  const metrics = [
    {
      label: "Lớp đang hoạt động",
      value: summary?.activeClasses,
      icon: BookOpen,
      color: "blue",
      to: "/app/classes",
    },
    {
      label: "Buổi học hôm nay",
      value: summary?.todaySessions,
      icon: CalendarDays,
      color: "teal",
      to: "/app/calendar",
    },
    {
      label: teacher ? "Yêu cầu chờ duyệt" : "Bài tập chưa nộp",
      value: teacher ? summary?.pendingRequests : summary?.pendingAssignments,
      icon: teacher ? Users : Clock3,
      color: "amber",
      to: "#priority",
    },
    {
      label: teacher ? "Bài chờ chấm / công bố" : "Bài đã có điểm",
      value: teacher ? summary?.pendingReviews : summary?.publishedGrades,
      icon: ClipboardCheck,
      color: "blue",
      to: "#priority",
    },
  ];
  return (
    <>
      <PageHeader
        eyebrow={teacher ? "KHÔNG GIAN GIẢNG DẠY" : "HÀNH TRÌNH HỌC TẬP"}
        title={
          "Chào " +
          (user?.displayName.split(" ").at(-1) ?? "bạn") +
          ", hôm nay thế nào?"
        }
        description={
          teacher
            ? "Nắm bắt lớp học. Tập trung vào những việc cần bạn."
            : "Biết việc cần làm. Chủ động cho từng buổi học."
        }
        action={
          <Link
            className="button"
            to={teacher ? "/app/classes?create=1" : "/app/check-in"}
          >
            {teacher ? <Plus size={18} /> : <ScanLine size={18} />}
            {teacher ? "Tạo lớp học" : "Điểm danh QR"}
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
          <Link to={metric.to} className="stat-card" key={metric.label}>
            <span className={"stat-icon " + metric.color}>
              <metric.icon size={21} />
            </span>
            <div>
              <p>{metric.label}</p>
              <strong>{metric.value ?? "—"}</strong>
            </div>
            <ArrowRight size={16} />
          </Link>
        ))}
      </div>
      {data ? (
        <>
          <div className="v2-work-grid">
            <section id="priority" className="panel priority-panel">
              <div className="section-heading">
                <div>
                  <span className="eyebrow">ƯU TIÊN CỦA BẠN</span>
                  <h2>
                    {teacher
                      ? "Bàn làm việc hôm nay"
                      : "Việc học cần hoàn thành"}
                  </h2>
                </div>
                <span className="live-label">
                  <span /> Dữ liệu lớp học
                </span>
              </div>
              {teacher ? (
                <>
                  <h3 className="queue-title">
                    Yêu cầu vào lớp{" "}
                    <span className="count-pill">
                      {summary?.pendingRequests}
                    </span>
                  </h3>
                  {data.requests?.map((item) => (
                    <Link
                      className="work-item"
                      key={item.classId}
                      to={
                        "/app/classes/" +
                        item.classId +
                        "/members?queue=pending"
                      }
                    >
                      <span className="work-icon amber">
                        <Users size={19} />
                      </span>
                      <div>
                        <strong>{item.className}</strong>
                        <p>{item.pendingCount} học sinh đang chờ bạn duyệt</p>
                      </div>
                      <ArrowRight size={17} />
                    </Link>
                  ))}
                  {!data.requests?.length ? (
                    <p className="queue-clear">
                      <CheckCircle2 size={17} /> Không có yêu cầu chờ duyệt.
                    </p>
                  ) : null}
                  <h3 className="queue-title">
                    Chấm bài & công bố điểm{" "}
                    <span className="count-pill">
                      {summary?.pendingReviews}
                    </span>
                  </h3>
                  {data.reviews?.map((item) => (
                    <Link
                      className="work-item"
                      key={item.submissionId}
                      to={"/app/submissions/" + item.submissionId}
                    >
                      <span className="work-icon blue">
                        <ClipboardCheck size={19} />
                      </span>
                      <div>
                        <strong>{item.title}</strong>
                        <p>Bài nộp mới hoặc điểm đang ở bản nháp</p>
                      </div>
                      <ArrowRight size={17} />
                    </Link>
                  ))}
                  {!data.reviews?.length ? (
                    <p className="queue-clear">
                      <CheckCircle2 size={17} /> Các bài nộp hiện tại đã được xử
                      lý.
                    </p>
                  ) : null}
                </>
              ) : (
                <>
                  {data.assignments?.map((item) => {
                    const overdue =
                      item.dueAt !== null &&
                      new Date(item.dueAt) < new Date(data.serverTime);
                    return (
                      <Link
                        className="work-item"
                        key={item.id}
                        to={
                          "/app/classes/" +
                          item.classId +
                          "/assignments/" +
                          item.id
                        }
                      >
                        <span
                          className={
                            "work-icon " + (overdue ? "amber" : "blue")
                          }
                        >
                          <BookOpen size={19} />
                        </span>
                        <div>
                          <strong>{item.title}</strong>
                          <p>{item.className}</p>
                          <small
                            className={overdue ? "deadline-overdue" : "muted"}
                          >
                            {item.dueAt
                              ? (overdue ? "Đã quá hạn · " : "Hạn nộp · ") +
                                dateTime(item.dueAt)
                              : "Chưa đặt hạn nộp"}
                            {overdue && !item.allowLate
                              ? " · Không nhận nộp muộn"
                              : ""}
                          </small>
                        </div>
                        <ArrowRight size={17} />
                      </Link>
                    );
                  })}
                  {!data.assignments?.length ? (
                    <Empty
                      title="Bạn đã theo kịp bài tập"
                      description="Bài tập chưa nộp trong các lớp đang học sẽ xuất hiện ở đây."
                    />
                  ) : null}
                  <h3 className="queue-title">Kết quả mới công bố</h3>
                  {data.grades?.map((item) => (
                    <Link
                      className="work-item"
                      key={item.submissionId}
                      to={"/app/submissions/" + item.submissionId}
                    >
                      <span className="score-token">
                        {item.score}
                        <small>/10</small>
                      </span>
                      <div>
                        <strong>{item.title}</strong>
                        <p>{dateTime(item.publishedAt)}</p>
                      </div>
                      <ArrowRight size={17} />
                    </Link>
                  ))}
                  {!data.grades?.length ? (
                    <p className="muted">
                      Điểm sẽ hiển thị khi giáo viên công bố.
                    </p>
                  ) : null}
                </>
              )}
              <p className="muted queue-caption">
                Hiển thị tối đa 5 mục mỗi nhóm. Mở lớp học để xem toàn bộ.
              </p>
            </section>
            <aside className="panel agenda-panel">
              <div className="section-heading">
                <div>
                  <span className="eyebrow">7 NGÀY TỚI</span>
                  <h2>Lịch trình của bạn</h2>
                </div>
                <CalendarDays size={21} />
              </div>
              {data.sessions.map((session) => (
                <Link
                  className="agenda-item"
                  key={session.id}
                  to={
                    teacher
                      ? "/app/classes/" +
                        session.classId +
                        "/sessions/" +
                        session.id +
                        "/qr"
                      : "/app/classes/" + session.classId + "/schedule"
                  }
                >
                  <span className="agenda-date">
                    <strong>{new Date(session.startsAt).getDate()}</strong>
                    <small>TH{new Date(session.startsAt).getMonth() + 1}</small>
                  </span>
                  <div>
                    <strong>{session.title || "Buổi học"}</strong>
                    <p>{session.className}</p>
                    <small>
                      {dateTime(session.startsAt)}
                      {session.room ? " · " + session.room : ""}
                    </small>
                  </div>
                </Link>
              ))}
              {!data.sessions.length ? (
                <Empty
                  title="Chưa có lịch sắp tới"
                  description="Các buổi đã lên lịch sẽ xuất hiện tại đây."
                />
              ) : null}
              <Link className="button secondary agenda-link" to="/app/calendar">
                Mở lịch học <ArrowRight size={16} />
              </Link>
              <p className="muted queue-caption">
                Giờ hiển thị theo thiết bị của bạn.
              </p>
            </aside>
          </div>
          <section className="v2-classes">
            <div className="section-heading">
              <div>
                <span className="eyebrow">TIẾP TỤC TỪ ĐÂY</span>
                <h2>
                  Lớp học của bạn{" "}
                  <span className="count-pill">{summary?.activeClasses}</span>
                </h2>
              </div>
              <Link className="text-link" to="/app/classes">
                Xem tất cả <ArrowRight size={16} />
              </Link>
            </div>
            {data.classes.length ? (
              <div className="class-grid">
                {data.classes.map((classroom, index) => (
                  <ClassCard
                    key={classroom.id}
                    classroom={classroom}
                    index={index}
                  />
                ))}
              </div>
            ) : (
              <div className="panel">
                <Empty
                  title={
                    teacher ? "Tạo lớp học đầu tiên" : "Bắt đầu với một lớp học"
                  }
                  description={
                    teacher
                      ? "Tạo lớp và gửi mã mời cho học sinh."
                      : "Nhập mã từ giáo viên, sau đó chờ duyệt tham gia."
                  }
                >
                  <Link className="button secondary" to="/app/classes">
                    {teacher ? "Quản lý lớp học" : "Tham gia lớp"}
                  </Link>
                </Empty>
              </div>
            )}
          </section>
        </>
      ) : null}
    </>
  );
}
