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
  X,
  ShieldCheck,
  Users,
  ScrollText,
} from "lucide-react";
import { useEffect, useRef, useState } from "react";
import { useSession } from "../application/sessionContext";
import { useResource } from "../application/queries";
import { classApi } from "../infrastructure/classApi";
import { initials } from "../domain/format";
import { roleLabel } from "../domain/access";
import { ActionButton } from "../shared/ui";
export default function Shell() {
  const { user, logout } = useSession();
  const [menu, setMenu] = useState(false);
  const [compact, setCompact] = useState(
    () => window.matchMedia("(max-width: 760px)").matches,
  );
  const sidebar = useRef<HTMLElement>(null);
  useEffect(() => {
    const media = window.matchMedia("(max-width: 760px)");
    const update = (event: MediaQueryListEvent) => {
      setCompact(event.matches);
      if (!event.matches) setMenu(false);
    };
    media.addEventListener("change", update);
    return () => media.removeEventListener("change", update);
  }, []);
  useEffect(() => {
    if (!menu || !compact) return;
    const previous = document.activeElement as HTMLElement | null;
    const controls = () =>
      Array.from(
        sidebar.current?.querySelectorAll<HTMLElement>(
          "a[href], button:not(:disabled)",
        ) ?? [],
      );
    controls()[0]?.focus();
    const close = (event: KeyboardEvent) => {
      if (event.key === "Escape") setMenu(false);
      if (event.key === "Tab") {
        const items = controls();
        const first = items[0],
          last = items.at(-1);
        if (event.shiftKey && document.activeElement === first) {
          event.preventDefault();
          last?.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
          event.preventDefault();
          first?.focus();
        }
      }
    };
    window.addEventListener("keydown", close);
    return () => {
      window.removeEventListener("keydown", close);
      previous?.focus();
    };
  }, [menu, compact]);
  const unread = useResource(["unread"], (signal) => classApi.unread(signal), {
    refetchInterval: 60000,
  });
  if (!user) return null;
  const admin = user.role === "admin";
  const navigation = admin
    ? [
        {
          to: "/admin",
          icon: ShieldCheck,
          label: "Tổng quan quản trị",
          end: true,
        },
        { to: "/admin/users", icon: Users, label: "Quản lý tài khoản" },
        { to: "/admin/audit", icon: ScrollText, label: "Nhật ký quản trị" },
        { to: "/app/settings", icon: Settings, label: "Tài khoản của tôi" },
      ]
    : [
        { to: "/app", icon: LayoutDashboard, label: "Tổng quan", end: true },
        { to: "/app/classes", icon: BookOpen, label: "Lớp học" },
        { to: "/app/calendar", icon: CalendarDays, label: "Lịch học" },
        { to: "/app/check-in", icon: ScanLine, label: "Quét điểm danh" },
        { to: "/app/notifications", icon: Bell, label: "Thông báo" },
        { to: "/app/settings", icon: Settings, label: "Cài đặt" },
      ];
  return (
    <div className="workspace workspace-v2">
      <a className="skip-link" href="#main">
        Bỏ qua đến nội dung
      </a>
      {menu ? (
        <button
          className="menu-backdrop"
          aria-label="Đóng menu từ nền"
          onClick={() => setMenu(false)}
        />
      ) : null}
      <aside
        ref={sidebar}
        id="workspace-navigation"
        inert={compact && !menu}
        role={compact && menu ? "dialog" : undefined}
        aria-modal={compact && menu ? true : undefined}
        aria-label="Menu chính"
        className={"sidebar " + (menu ? "is-open" : "")}
      >
        <button
          type="button"
          className="sidebar-close"
          aria-label="Đóng menu"
          onClick={() => setMenu(false)}
        >
          <X size={20} />
        </button>
        <Link to={admin ? "/admin" : "/app"} className="brand">
          <span className="brand-mark">
            <GraduationCap size={24} />
          </span>
          <span>
            attendance<span className="brand-dot">.</span>
            <small>CLASSROOM WORKSPACE · 2.0</small>
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
          {!admin && user.role === "teacher" ? (
            <NavLink to="/app/catalog" onClick={() => setMenu(false)}>
              <Library size={19} />
              Môn học & học kỳ
            </NavLink>
          ) : !admin ? (
            <NavLink to="/app/join-requests" onClick={() => setMenu(false)}>
              <Clock3 size={19} />
              Yêu cầu vào lớp
            </NavLink>
          ) : null}
        </nav>
        <div className="sidebar-account">
          <span className="avatar">{initials(user.displayName)}</span>
          <div>
            <strong>{user.displayName}</strong>
            <small>{roleLabel(user.role)}</small>
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
      <div className="workspace-body" inert={compact && menu}>
        <header className="topbar">
          <button
            className="icon-button mobile-menu"
            aria-label="Mở menu"
            aria-expanded={menu}
            aria-controls="workspace-navigation"
            onClick={() => setMenu(!menu)}
          >
            <Menu />
          </button>
          <span className="breadcrumb">
            Không gian học tập <span>/</span>{" "}
            <strong>{roleLabel(user.role)}</strong>
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

          </div>
        </header>
        <main id="main" className="main-content">
          <Outlet />
        </main>

      </div>
    </div>
  );
}
