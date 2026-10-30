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
  PanelLeftClose,
  PanelLeftOpen,
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
  const [collapsed, setCollapsed] = useState(()=>localStorage.getItem("attendance.sidebar.collapsed")==="true");
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
    <div className={"workspace workspace-v2 " + (collapsed && !compact ? "sidebar-collapsed" : "")}>
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
            .filter((n) => n.to !== "/app/settings" && (user.role === "student" || n.to !== "/app/check-in"))
            .map((n) => (
              <NavLink
                key={n.to}
                to={n.to}
                title={n.label}
                aria-label={n.label}
                end={n.end}
                onClick={() => setMenu(false)}
              >
                <n.icon size={19} />
                <span className="nav-label">{n.label}</span>
                {n.to === "/app/notifications" && unread.data?.unreadCount ? (
                  <span className="nav-count">{unread.data.unreadCount}</span>
                ) : null}
              </NavLink>
            ))}
          {!admin && user.role === "teacher" ? (
            <NavLink aria-label="Môn học & học kỳ" title="Môn học & học kỳ" to="/app/catalog" onClick={() => setMenu(false)}>
              <Library size={19} />
              <span className="nav-label">Môn học & học kỳ</span>
            </NavLink>
          ) : !admin ? (
            <NavLink aria-label="Yêu cầu vào lớp" title="Yêu cầu vào lớp" to="/app/join-requests" onClick={() => setMenu(false)}>
              <Clock3 size={19} />
              <span className="nav-label">Yêu cầu vào lớp</span>
            </NavLink>
          ) : null}
        </nav>
        <nav className="sidebar-bottom"><NavLink to="/app/settings" title="Cài đặt" aria-label="Cài đặt" onClick={()=>setMenu(false)}><Settings size={19}/><span className="nav-label">Cài đặt</span></NavLink></nav>
        <div className="sidebar-account">
          <span className={"avatar avatar-"+(user.avatarKey ?? "blue")}>{initials(user.displayName)}</span>
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
          <button className="icon-button sidebar-toggle" aria-label={collapsed?"Mở rộng sidebar":"Thu gọn sidebar"} aria-expanded={!collapsed} onClick={()=>setCollapsed(v=>{localStorage.setItem("attendance.sidebar.collapsed",String(!v));return !v})}>{collapsed?<PanelLeftOpen/>:<PanelLeftClose/>}</button>
        </header>
        <main id="main" className="main-content">
          <Outlet />
        </main>

      </div>
    </div>
  );
}
