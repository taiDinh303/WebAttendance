import {
  Link,
  Navigate,
  useLocation,
  useNavigate,
  useSearchParams,
} from "react-router-dom";
import {
  GraduationCap,
  ArrowRight,
  Check,
  ShieldCheck,
  CalendarDays,
  BookOpen,
} from "lucide-react";
import { useSession } from "../../../application/sessionContext";
import { authApi } from "../../../infrastructure/authApi";
import { AsyncForm, Field, PasswordInput } from "../../../shared/ui";
import { text } from "../../../domain/format";
import { useState } from "react";
export default function AuthPage() {
  const { pathname } = useLocation();
  const [search] = useSearchParams();
  const navigate = useNavigate();
  const { login, user, loading } = useSession();
  const [done, setDone] = useState(false);
  const register = pathname === "/register",
    forgot = pathname === "/forgot-password",
    reset = pathname === "/reset-password";
  const title = register
    ? "Một lớp học, nhiều kết nối."
    : forgot
      ? "Lấy lại mật khẩu"
      : reset
        ? "Đặt mật khẩu mới"
        : "Chào mừng trở lại.";
  const next = search.get("next");
  const destination =
    next?.startsWith("/app") && !next.startsWith("//") ? next : "/app";
  if (!forgot && !reset && user) return <Navigate to={destination} replace />;
  if (!forgot && !reset && loading) return <div className="startup" role="status">Đang kiểm tra phiên đăng nhập…</div>;
  return (
    <div className="auth-layout">
      <aside className="auth-story">
        <Link className="brand" to="/">
          <span className="brand-mark">
            <GraduationCap />
          </span>
          <span>attendance.</span>
        </Link>
        <div className="story-content">
          <p className="eyebrow">KHÔNG GIAN CHO VIỆC HỌC</p>
          <h1>
            Tập trung vào
            <br />
            điều <em>quan trọng.</em>
          </h1>
          <p>
            Kết nối giáo viên và học sinh.
            <br />
            Sắp xếp từng buổi học, cùng tiến bộ mỗi ngày.
          </p>
          <div className="story-visual">
            <div className="story-orbit" />
            <span className="float-icon one">
              <BookOpen />
            </span>
            <span className="float-icon two">
              <CalendarDays />
            </span>
            <div className="story-card">
              <span className="story-card-line" />
              <strong>Lớp học luôn kết nối</strong>
              <p>
                <Check size={16} /> Lịch học rõ ràng
              </p>
              <p>
                <Check size={16} /> Bài tập đúng tiến độ
              </p>
              <p>
                <Check size={16} /> Mỗi buổi học đều ý nghĩa
              </p>
            </div>
          </div>
        </div>
        <small>Attendance · Không gian lớp học của bạn.</small>
      </aside>
      <main className="auth-main">
        <Link className="brand auth-mobile-brand" to="/"><span className="brand-mark"><GraduationCap /></span><span>attendance.</span></Link>
        <div className="auth-form">
          <span className="label-chip">CÙNG NHAU HỌC TỐT HƠN</span>
          <h1>{title}</h1>
          <p className="muted">
            {register
              ? "Tạo tài khoản giáo viên hoặc học sinh để bắt đầu."
              : forgot
                ? "Nhập email để nhận hướng dẫn đặt lại mật khẩu."
                : reset
                  ? "Sử dụng mật khẩu mạnh để bảo vệ tài khoản."
                  : "Đăng nhập để tiếp tục hành trình học tập của bạn."}
          </p>
          {!forgot && !reset && !done ? <div className="google-signin"><button type="button" className="button secondary wide" disabled title="Chưa cấu hình Google OAuth"><span aria-hidden="true">G</span>Tiếp tục với Google</button><small className="muted">Google sẽ mở sau khi cấu hình kết nối.</small><p className="muted">hoặc tiếp tục bằng email</p></div> : null}
          {done ? (
            <div className="success panel">
              <ShieldCheck />
              <h2>
                {register ? "Tài khoản đã được tạo" : "Yêu cầu đã được xử lý"}
              </h2>
              <p>
                {register
                  ? "Đăng nhập và xác minh email để sử dụng lớp học."
                  : reset
                    ? "Bạn có thể đăng nhập với mật khẩu mới."
                    : "Nếu email tồn tại, hướng dẫn sẽ được gửi đến email đó."}
              </p>
              <Link to="/login">Về đăng nhập</Link>
            </div>
          ) : (
            <AsyncForm
              onSubmit={async (form) => {
                const email = text(form, "email"),
                  password = text(form, "password");
                if (register) {
                  await authApi.register({
                    displayName: text(form, "displayName"),
                    email,
                    password,
                    passwordConfirmation: text(form, "passwordConfirmation"),
                    role: text(form, "role"),
                  });
                  setDone(true);
                } else if (forgot) {
                  await authApi.forgot({ email });
                  setDone(true);
                } else if (reset) {
                  await authApi.reset({
                    email,
                    password,
                    passwordConfirmation: text(form, "passwordConfirmation"),
                    token: search.get("token"),
                  });
                  setDone(true);
                } else {
                  await login(email, password);
                  navigate(destination, { replace: true });
                }
              }}
            >
              {register ? (
                <>
                  <Field label="Họ và tên">
                    <input
                      name="displayName"
                      autoComplete="name"
                      required
                      maxLength={120}
                      placeholder="Nguyễn Minh Anh"
                    />
                  </Field>
                  <Field label="Bạn tham gia với vai trò">
                    <select name="role">
                      <option value="teacher">Giáo viên</option>
                      <option value="student">Học sinh</option>
                    </select>
                  </Field>
                </>
              ) : null}
              <Field label="Địa chỉ email">
                <input
                  name="email"
                  type="email"
                  autoComplete="email"
                  defaultValue={reset ? (search.get("email") ?? "") : ""}
                  required
                  placeholder="ban@example.com"
                />
              </Field>
              {!forgot ? (
                <>
                  <Field
                    label="Mật khẩu"
                    hint={
                      register || reset
                        ? "Từ 12 ký tự, có chữ hoa, chữ thường và chữ số."
                        : undefined
                    }
                  >
                    <PasswordInput
                      name="password"
                      autoComplete={
                        register || reset ? "new-password" : "current-password"
                      }
                      minLength={register || reset ? 12 : 1}
                      maxLength={128}
                      required
                      placeholder="Nhập mật khẩu của bạn"
                    />
                  </Field>
                  {register || reset ? (
                    <Field label="Nhập lại mật khẩu">
                      <PasswordInput
                        name="passwordConfirmation"
                        autoComplete="new-password"
                        required
                        minLength={12}
                      />
                    </Field>
                  ) : (
                    <div className="form-right">
                      <Link to="/forgot-password">Quên mật khẩu?</Link>
                    </div>
                  )}
                </>
              ) : null}
              <button className="button wide">
                {register
                  ? "Tạo tài khoản"
                  : forgot
                    ? "Gửi hướng dẫn"
                    : reset
                      ? "Đổi mật khẩu"
                      : "Đăng nhập"}
                <ArrowRight size={17} />
              </button>
            </AsyncForm>
          )}
        <div className="auth-switch">
          {register ? "Đã có tài khoản?" : "Chưa có tài khoản?"}{" "}
          <Link to={register ? "/login" : "/register"}>
            {register ? "Đăng nhập" : "Đăng ký miễn phí"}{" "}
            <ArrowRight size={14} />
          </Link>
        </div>
          <div className="auth-footnote">
            <ShieldCheck size={16} /> Tài khoản của bạn. Lớp học của bạn.
          </div>
        </div>
      </main>
    </div>
  );
}
