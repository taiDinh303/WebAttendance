import { Component, Suspense, lazy, type ReactNode } from "react";
import {
  BrowserRouter,
  Routes,
  Route,
  Navigate,
  useLocation,
  Outlet,
  Link,
} from "react-router-dom";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { SessionProvider } from "../application/session";
import { useSession } from "../application/sessionContext";
import { Loading, ErrorNotice, ActionButton } from "../shared/ui";
import { authApi } from "../infrastructure/authApi";
import { canAdminister } from "../domain/access";
const Shell = lazy(() => import("./Shell"));
const Auth = lazy(() => import("../features/auth/pages/AuthPage"));
const Dashboard = lazy(
  () => import("../features/dashboard/pages/DashboardPage"),
);
const Classes = lazy(() => import("../features/classes/pages/ClassesPage"));
const ClassWorkspace = lazy(
  () => import("../features/classes/pages/ClassWorkspace"),
);
const Calendar = lazy(() => import("../features/calendar/pages/CalendarPage"));
const Assignment = lazy(
  () => import("../features/assignments/pages/AssignmentPage"),
);
const Submission = lazy(
  () => import("../features/assignments/pages/SubmissionPage"),
);
const Settings = lazy(() => import("../features/settings/pages/SettingsPage"));
const Notices = lazy(
  () => import("../features/notifications/pages/NotificationsPage"),
);
const JoinRequests = lazy(
  () => import("../features/classes/pages/JoinRequestsPage"),
);
const CheckIn = lazy(() => import("../features/attendance/pages/CheckInPage"));
const QrPage = lazy(() => import("../features/attendance/pages/QrPage"));
const Catalog = lazy(() => import("../features/catalog/pages/CatalogPage"));
const AdminDashboard = lazy(
  () => import("../features/admin/pages/AdminDashboardPage"),
);
const AdminUsers = lazy(() => import("../features/admin/pages/AdminUsersPage"));
const AdminAudit = lazy(() => import("../features/admin/pages/AdminAuditPage"));
const queryClient = new QueryClient({
  defaultOptions: {
    queries: { retry: false, staleTime: 20000, refetchOnWindowFocus: true },
    mutations: { retry: false },
  },
});
class Boundary extends Component<
  { children: ReactNode },
  { error: Error | null }
> {
  state = { error: null as Error | null };
  static getDerivedStateFromError(error: Error) {
    return { error };
  }
  render() {
    return this.state.error ? (
      <div className="fatal">
        <h1>Trang chưa tải được</h1>
        <ErrorNotice error={this.state.error} />
        <button className="button" onClick={() => window.location.reload()}>
          Tải lại trang
        </button>
      </div>
    ) : (
      this.props.children
    );
  }
}
function Guard() {
  const { user, loading, error, reload } = useSession();
  const location = useLocation();
  if (loading) return <Loading />;
  if (error)
    return (
      <div className="fatal">
        <ErrorNotice error={error} />
        <ActionButton className="button" action={reload}>
          Kết nối lại
        </ActionButton>
      </div>
    );
  if (!user)
    return (
      <Navigate
        to={
          "/login?next=" +
          encodeURIComponent(location.pathname + location.search)
        }
        replace
      />
    );
  if (!user.emailVerified)
    return (
      <div className="verify-card panel">
        <h1>Xác minh email để bắt đầu</h1>
        <p>
          Mở liên kết xác minh gửi đến <strong>{user.email}</strong>, sau đó
          kiểm tra lại.
        </p>
        <p className="muted">
          Nếu môi trường local chưa cấu hình gửi email, liên hệ người quản lý
          môi trường.
        </p>
        <ActionButton className="button" action={() => authApi.resend()}>
          Gửi lại email
        </ActionButton>
        <ActionButton className="button secondary" action={reload}>
          Tôi đã xác minh
        </ActionButton>
        <Link to="/login">Đổi tài khoản</Link>
      </div>
    );
  return <Outlet />;
}
function AdminGuard() {
  const { user } = useSession();
  return canAdminister(user) ? (
    <Outlet />
  ) : (
    <div className="fatal">
      <h1>Không có quyền quản trị</h1>
      <Link className="button" to="/app">
        Về không gian lớp học
      </Link>
    </div>
  );
}
function WorkspaceHome() {
  const { user } = useSession();
  return canAdminister(user) ? <Navigate to="/admin" replace /> : <Dashboard />;
}
export function App() {
  return (
    <Boundary>
      <QueryClientProvider client={queryClient}>
        <SessionProvider>
          <BrowserRouter>
            <Suspense fallback={<Loading />}>
              <Routes>
                <Route path="/" element={<Navigate to="/app" replace />} />
                <Route path="/login" element={<Auth />} />
                <Route path="/register" element={<Auth />} />
                <Route path="/forgot-password" element={<Auth />} />
                <Route path="/reset-password" element={<Auth />} />
                <Route element={<Guard />}>
                  <Route element={<AdminGuard />}>
                    <Route path="/admin" element={<Shell />}>
                      <Route index element={<AdminDashboard />} />
                      <Route path="users" element={<AdminUsers />} />
                      <Route path="audit" element={<AdminAudit />} />
                    </Route>
                  </Route>
                  <Route path="/app" element={<Shell />}>
                    <Route index element={<WorkspaceHome />} />
                    <Route path="classes" element={<Classes />} />
                    <Route
                      path="classes/:classId"
                      element={<ClassWorkspace />}
                    />
                    <Route
                      path="classes/:classId/:tab"
                      element={<ClassWorkspace />}
                    />
                    <Route
                      path="classes/:classId/assignments/:assignmentId"
                      element={<Assignment />}
                    />
                    <Route
                      path="classes/:classId/sessions/:sessionId/qr"
                      element={<QrPage />}
                    />
                    <Route
                      path="submissions/:submissionId"
                      element={<Submission />}
                    />
                    <Route path="calendar" element={<Calendar />} />
                    <Route path="check-in" element={<CheckIn />} />
                    <Route path="notifications" element={<Notices />} />
                    <Route path="settings" element={<Settings />} />
                    <Route path="join-requests" element={<JoinRequests />} />
                    <Route path="catalog" element={<Catalog />} />
                  </Route>
                </Route>
                <Route
                  path="*"
                  element={
                    <div className="fatal">
                      <h1>Không tìm thấy trang</h1>
                      <Link className="button" to="/app">
                        Về tổng quan
                      </Link>
                    </div>
                  }
                />
              </Routes>
            </Suspense>
          </BrowserRouter>
        </SessionProvider>
      </QueryClientProvider>
    </Boundary>
  );
}
