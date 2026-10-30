import { useSession } from "../../../application/sessionContext";
import { authApi } from "../../../infrastructure/authApi";
import {
  PageHeader,
  AsyncForm,
  Field,
  PasswordInput,
} from "../../../shared/ui";
import { text } from "../../../domain/format";
export default function SettingsPage() {
  const { user, reload } = useSession();
  if (!user) return null;
  return (
    <>
      <PageHeader
        title="Cài đặt tài khoản"
        description="Thông tin cá nhân và bảo mật của bạn."
      />
      <div className="detail-grid">
        <section className="panel editor-panel">
          <h2>Thông tin cá nhân</h2>
          <p className="muted">
            {user.email} · {user.role === "teacher" ? "Giáo viên" : "Học sinh"}
          </p>
          <AsyncForm
            key={user.id}
            success="Đã lưu thông tin cá nhân."
            onSubmit={async (f) => {
              await authApi.profile({
                displayName: text(f, "displayName"),
                phone: text(f, "phone") || null,
                timezone: text(f, "timezone"),
                locale: "vi",
                expectedVersion: user.version,
              });
              await reload();
            }}
          >
            <Field label="Họ tên">
              <input
                name="displayName"
                required
                defaultValue={user.displayName}
              />
            </Field>
            <Field label="Số điện thoại">
              <input name="phone" type="tel" defaultValue={user.phone ?? ""} />
            </Field>
            <Field label="Múi giờ">
              <input
                name="timezone"
                required
                defaultValue={user.timezone || "Asia/Ho_Chi_Minh"}
              />
            </Field>
            <button className="button">Lưu thông tin</button>
          </AsyncForm>
        </section>
        <section className="panel editor-panel">
          <h2>Đổi mật khẩu</h2>
          <AsyncForm
            success="Đã đổi mật khẩu. Vui lòng đăng nhập lại nếu được yêu cầu."
            onSubmit={async (f) => {
              await authApi.password({
                currentPassword: text(f, "currentPassword"),
                password: text(f, "password"),
                passwordConfirmation: text(f, "passwordConfirmation"),
              });
              await reload();
            }}
          >
            <Field label="Mật khẩu hiện tại">
              <PasswordInput
                name="currentPassword"
                autoComplete="current-password"
                required
              />
            </Field>
            <Field
              label="Mật khẩu mới"
              hint="Ít nhất 12 ký tự, có chữ hoa, chữ thường và chữ số."
            >
              <PasswordInput
                name="password"
                autoComplete="new-password"
                minLength={12}
                maxLength={128}
                required
              />
            </Field>
            <Field label="Nhập lại mật khẩu mới">
              <PasswordInput
                name="passwordConfirmation"
                autoComplete="new-password"
                minLength={12}
                required
              />
            </Field>
            <button className="button secondary">Đổi mật khẩu</button>
          </AsyncForm>
        </section>
      </div>
    </>
  );
}
