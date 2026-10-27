import { NavLink, Outlet, Link } from "react-router-dom";
import {
  BookOpen,
  CalendarDays,
  GraduationCap,
  LayoutDashboard,
  Bell,
  Settings,
  LogOut,
  ScanLine,
  Menu,
  Library,
  Clock3,
} from "lucide-react";
import { useState } from "react";
import { useSession } from "../application/sessionContext";
import { useResource } from "../application/queries";
import { classApi } from "../infrastructure/classApi";
import { initials } from "../domain/format";
import { ActionButton } from "../shared/ui";
export default function Shell() {
  const { user, logout } = useSession();
  const [menu, setMenu] = useState(false);
  const unread = useResource(["unread"], (signal) => classApi.unread(signal), {
    refetchInterval: 60000,
  });
  if (!user) return null;
  const navigation = [
    { to: "/app", icon: LayoutDashboard, label: "Tổng quan", end: true },
    { to: "/app/classes", icon: BookOpen, label: "Lớp học" },
    { to: "/app/calendar", icon: CalendarDays, label: "Lịch học" },
    { to: "/app/check-in", icon: ScanLine, label: "Quét điểm danh" },
    { to: "/app/notifications", icon: Bell, label: "Thông báo" },
    { to: "/app/settings", icon: Settings, label: "Cài đặt" },
  ];
  return (
    <div className="workspace">
      <a className="skip-link" href="#main">
        Bỏ qua đến nội dung
      </a>
      <aside className={"sidebar " + (menu ? "is-open" : "")}>
        <Link to="/app" className="brand">
          <span className="brand-mark">
            <GraduationCap size={24} />
          </span>
          <span>
            attendance<span className="brand-dot">.</span>
            <small>KHÔNG GIAN LỚP HỌC</small>
          </span>
        </Link>
        <div className="workspace-label">KHÔNG GIAN CỦA BẠN</div>
        <nav>
          {navigation
            .filter((n) => user.role === "student" || n.to !== "/app/check-in")
            .map((n) => (
              <NavLink
                key={n.to}
                to={n.to}
                end={n.end}
                onClick={() => setMenu(false)}
              >
                <n.icon size={19} />
                {n.label}
                {n.to === "/app/notifications" && unread.data?.unreadCount ? (
                  <span className="nav-count">{unread.data.unreadCount}</span>
                ) : null}
              </NavLink>
            ))}
          {user.role === "teacher" ? (
            <NavLink to="/app/catalog">
              <Library size={19} />
              Môn học & học kỳ
            </NavLink>
          ) : (
            <NavLink to="/app/join-requests">
              <Clock3 size={19} />
              Yêu cầu vào lớp
            </NavLink>
          )}
        </nav>
        <div className="sidebar-note">
          <span className="mini-dot" />
          Học tập, cùng kết nối.<p>Một nơi cho những giờ học tốt hơn.</p>
        </div>
        <div className="sidebar-account">
          <span className="avatar">{initials(user.displayName)}</span>
          <div>
            <strong>{user.displayName}</strong>
            <small>{user.role === "teacher" ? "Giáo viên" : "Học sinh"}</small>
          </div>
          <ActionButton
            className="icon-button"
            title="Đăng xuất"
            aria-label="Đăng xuất"
            action={logout}
          >
            <LogOut size={18} />
          </ActionButton>
        </div>
      </aside>
      <div className="workspace-body">
        <header className="topbar">
          <button
            className="icon-button mobile-menu"
            aria-label="Mở menu"
            onClick={() => setMenu(!menu)}
          >
            <Menu />
          </button>
          <span className="breadcrumb">
            Không gian học tập <span>/</span>{" "}
            <strong>
              {user.role === "teacher" ? "Giáo viên" : "Học sinh"}
            </strong>
          </span>
          <div className="topbar-actions">
            <span className="today">
              {new Intl.DateTimeFormat("vi-VN", {
                weekday: "short",
                day: "numeric",
                month: "long",
              }).format(new Date())}
            </span>
            <Link
              to="/app/notifications"
              className="icon-button"
              aria-label="Thông báo"
            >
              <Bell size={19} />
              {unread.data?.unreadCount ? (
                <i className="notification-dot" />
              ) : null}
            </Link>
            <span className="avatar small">{initials(user.displayName)}</span>
          </div>
        </header>
        <main id="main" className="main-content">
          <Outlet />
        </main>
        <footer className="app-footer">
          Attendance · Mỗi buổi học đều có ý nghĩa.
          <span>Không gian của {user.displayName}</span>
        </footer>
      </div>
    </div>
  );
}
