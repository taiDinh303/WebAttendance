import { ErrorNotice } from "../shared/ui";
import { useAccountPreference } from "../application/accountPreference";
import { SidebarClasses } from "./SidebarClasses";
import { NavLink, Outlet } from "react-router-dom";
import {
  BookOpen,
  CalendarDays,
  LayoutDashboard,
  Bell,
  Settings,
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
import { roleLabel } from "../domain/access";
import { Brand } from "../shared/components/Brand";
import { AccountThemeToggle as ThemeToggle } from "./AccountThemeToggle";
import { AccountMenu } from "./AccountMenu";
export default function Shell() {
  const { user } = useSession();
  const collapsePreference=useAccountPreference("sidebar-collapsed",false);
  const collapsed=collapsePreference.value;
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
        
        { to: "/app/settings", icon: Settings, label: "Cài đặt" },
      ];
  return (
    <div className={"workspace workspace-v2 workspace-notelike " + (collapsed && !compact ? "sidebar-collapsed" : "")}>
      <a className="skip-link" href="#main">
        Bỏ qua đến nội dung
      </a>
      <header className="topbar" inert={compact && menu}>
        <div className="topbar-brand">
          <button type="button" className="icon-button navigation-toggle" aria-label={compact ? (menu ? "Đóng menu" : "Mở menu") : (collapsed ? "Hiện sidebar" : "Ẩn sidebar")} aria-expanded={compact ? menu : !collapsed} aria-controls="workspace-navigation" onClick={()=>{if(compact)setMenu(v=>!v);else void collapsePreference.save(!collapsed)}}><Menu size={21}/></button>
          <Brand/>
        </div>
        <span className="workspace-context"><ShieldCheck size={14}/>{roleLabel(user.role)}</span>
        <div className="header-controls"><ThemeToggle/><AccountMenu/></div>
      </header>
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
        inert={compact ? !menu : collapsed}
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
        {!admin && user.role === "teacher" ? <SidebarClasses key={user.id} actorId={user.id} onNavigate={()=>setMenu(false)}/> : null}
        <nav className="sidebar-bottom">{!admin ? <NavLink to="/app/notifications" onClick={()=>setMenu(false)}><Bell size={19}/><span className="nav-label">Thông báo</span>{unread.data?.unreadCount ? <span className="nav-count">{unread.data.unreadCount}</span>:null}</NavLink>:null}<NavLink to="/app/settings" title="Cài đặt" aria-label="Cài đặt" onClick={()=>setMenu(false)}><Settings size={19}/><span className="nav-label">Cài đặt</span></NavLink></nav>
      </aside>
      <div className="workspace-body" inert={compact && menu}>

        <main id="main" className="main-content">
          <ErrorNotice error={collapsePreference.error}/><Outlet />
        </main>

      </div>
    </div>
  );
}
