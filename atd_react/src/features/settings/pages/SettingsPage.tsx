import { Modal } from "../../../shared/components/Modal";
import { useState } from "react";
import { useSearchParams } from "react-router-dom";
import {
  Pencil,
  Camera,
  UserRound,
  ShieldCheck,
  Mail,
  Phone,
  KeyRound,
  Link2,
} from "lucide-react";
import { useSession } from "../../../application/sessionContext";
import { authApi } from "../../../infrastructure/authApi";
import { AsyncForm, Field, PasswordInput } from "../../../shared/ui";
import { text } from "../../../domain/format";
import { GoogleSignIn } from "../../auth/GoogleSignIn";
export default function SettingsPage() {
  const [editing, setEditing] = useState(false);
  const [passwordEditing, setPasswordEditing] = useState(false);
  const [googleLinking, setGoogleLinking] = useState(false);
  const [params, setParams] = useSearchParams();
  const tab = params.get("tab") === "account" ? "account" : "profile";
  const { user, reload } = useSession();
  if (!user) return null;
  return (
    <>
      <nav className="settings-tabs" aria-label="Cài đặt">
        <button
          type="button"
          aria-current={tab === "profile" ? "page" : undefined}
          onClick={() => setParams({ tab: "profile" })}
        >
          <UserRound size={17} />
          Profile
        </button>
        <button
          type="button"
          aria-current={tab === "account" ? "page" : undefined}
          onClick={() => setParams({ tab: "account" })}
        >
          <ShieldCheck size={17} />
          Account
        </button>
      </nav>
      <div className="settings-content">
        {tab === "profile" ? (
          <section className="panel editor-panel">
            <h2>Thông tin cá nhân</h2>
            <span
              className={
                "avatar profile-avatar avatar-" + (user.avatarKey ?? "blue")
              }
            >
              {user.displayName.slice(0, 1)}
            </span>
            <p className="muted">
              {user.email} ·{" "}
              {user.role === "teacher" ? "Giáo viên" : "Học sinh"}
            </p>
            <>
              <dl className="profile-facts">
                {user.role === "student" ? (
                  <div>
                    <dt>Mã sinh viên</dt>
                    <dd>{user.studentCode || "Chưa cung cấp"}</dd>
                  </div>
                ) : null}
                <div>
                  <dt>Họ tên</dt>
                  <dd>{user.displayName}</dd>
                </div>
                <div>
                  <dt>Email</dt>
                  <dd>{user.email}</dd>
                </div>
                <div>
                  <dt>Điện thoại</dt>
                  <dd>{user.phone || "Chưa cung cấp"}</dd>
                </div>
                <div>
                  <dt>Giới tính</dt>
                  <dd>
                    {
                      {
                        male: "Nam",
                        female: "Nữ",
                        other: "Khác",
                        undisclosed: "Không chia sẻ",
                      }[user.gender ?? "undisclosed"]
                    }
                  </dd>
                </div>
                <div>
                  <dt>Ngày sinh</dt>
                  <dd>
                    {user.birthday
                      ? user.birthday.split("-").reverse().join("/")
                      : "Chưa cung cấp"}
                  </dd>
                </div>
              </dl>
              <button
                className="button secondary"
                onClick={() => setEditing(true)}
              >
                <Pencil size={16} />
                Chỉnh sửa
              </button>
            </>
            {editing ? (
              <Modal
                title="Sửa thông tin cá nhân"
                onClose={() => setEditing(false)}
              >
                <AsyncForm
                  key={user.id}
                  success="Đã lưu thông tin cá nhân."
                  onSubmit={async (f) => {
                    await authApi.profile({
                      displayName: text(f, "displayName"),
                      ...(user.role === "student"
                        ? { studentCode: text(f, "studentCode") || null }
                        : {}),
                      phone: text(f, "phone") || null,
                      gender: text(f, "gender") || null,
                      avatarKey: text(f, "avatarKey") || null,
                      birthday: text(f, "birthday") || null,
                      timezone:
                        Intl.DateTimeFormat().resolvedOptions().timeZone,
                      locale: "vi",
                      expectedVersion: user.version,
                    });
                    await reload();
                    setEditing(false);
                  }}
                >
                  <Field label="Avatar">
                    <div className="avatar-options">
                      {["blue", "green", "amber", "rose", "violet"].map(
                        (key) => (
                          <label key={key}>
                            <input
                              type="radio"
                              name="avatarKey"
                              value={key}
                              defaultChecked={
                                (user.avatarKey ?? "blue") === key
                              }
                            />
                            <span className={"avatar avatar-" + key}>
                              {user.displayName.slice(0, 1)}
                            </span>
                          </label>
                        ),
                      )}
                    </div>
                  </Field>
                  <button
                    type="button"
                    className="button secondary"
                    disabled
                    title="Chưa cấu hình kho ảnh"
                  >
                    <Camera size={16} />
                    Tải ảnh cá nhân
                  </button>
                  <small className="muted">
                    Tải ảnh sẽ mở khi kho lưu ảnh được kết nối.
                  </small>
                  {user.role === "student" ? (
                    <Field label="Mã sinh viên (không bắt buộc)">
                      <input
                        name="studentCode"
                        defaultValue={user.studentCode ?? ""}
                        maxLength={40}
                      />
                    </Field>
                  ) : null}
                  <Field label="Họ tên">
                    <input
                      name="displayName"
                      required
                      defaultValue={user.displayName}
                    />
                  </Field>
                  <Field label="Số điện thoại">
                    <input
                      name="phone"
                      type="tel"
                      defaultValue={user.phone ?? ""}
                    />
                  </Field>

                  <Field label="Giới tính">
                    <select
                      name="gender"
                      defaultValue={user.gender ?? "undisclosed"}
                    >
                      <option value="undisclosed">Không chia sẻ</option>
                      <option value="male">Nam</option>
                      <option value="female">Nữ</option>
                      <option value="other">Khác</option>
                    </select>
                  </Field>
                  <Field label="Ngày sinh">
                    <input
                      type="date"
                      name="birthday"
                      defaultValue={user.birthday ?? ""}
                      max={new Date().toLocaleDateString("en-CA")}
                    />
                  </Field>
                  <footer className="modal-actions">
                    <button
                      type="button"
                      className="button secondary"
                      onClick={() => setEditing(false)}
                    >
                      Đóng
                    </button>
                    <button className="button">Lưu thông tin</button>
                  </footer>
                </AsyncForm>
              </Modal>
            ) : null}
          </section>
        ) : (
          <section className="panel account-settings">
            <div className="account-setting-row">
              <span className="setting-icon">
                <Mail size={21} />
              </span>
              <strong>Email</strong>
              <span>{user.email}</span>
              <span className="badge">
                {user.emailVerified ? "Đã xác minh" : "Chưa xác minh"}
              </span>
            </div>
            <div className="account-setting-row">
              <span className="setting-icon">
                <Phone size={21} />
              </span>
              <strong>Điện thoại</strong>
              <span>{user.phone || "Chưa cung cấp"}</span>
              <button
                className="button secondary small"
                onClick={() => {
                  setEditing(true);
                  setParams({ tab: "profile" });
                }}
              >
                Điều chỉnh
              </button>
            </div>
            <div className="account-setting-row">
              <span className="setting-icon">
                <Link2 size={21} />
              </span>
              <strong>Tài khoản liên kết</strong>
              <span>{user.googleLinked ? "Google đã liên kết" : "Google"}</span>
              {user.googleLinked ? (
                <AsyncForm
                  onSubmit={async () => {
                    if (
                      window.confirm("Ngắt liên kết Google khỏi tài khoản?")
                    ) {
                      await authApi.unlinkGoogle();
                      await reload();
                    }
                  }}
                >
                  <button
                    className="button secondary small"
                    disabled={user.portfolioDemo}
                  >
                    Ngắt liên kết
                  </button>
                </AsyncForm>
              ) : (
                <button
                  type="button"
                  className="button secondary small"
                  disabled={user.portfolioDemo}
                  onClick={() => setGoogleLinking(true)}
                >
                  Liên kết Google
                </button>
              )}
            </div>
            <div className="account-setting-row">
              <span className="setting-icon">
                <KeyRound size={21} />
              </span>
              <strong>Mật khẩu</strong>
              <span>
                {user.hasPassword === false ? "Chưa thiết lập" : "••••••••"}
              </span>
              <button
                className="button secondary small"
                onClick={() => setPasswordEditing(true)}
              >
                {user.hasPassword === false
                  ? "Thiết lập mật khẩu"
                  : "Đổi mật khẩu"}
              </button>
            </div>
            {googleLinking ? (
              <Modal
                title="Liên kết Google"
                onClose={() => setGoogleLinking(false)}
              >
                <p>Chọn tài khoản Google có email {user.email}.</p>
                <GoogleSignIn
                  onCredential={async (credential) => {
                    await authApi.linkGoogle(credential);
                    await reload();
                    setGoogleLinking(false);
                  }}
                />
              </Modal>
            ) : null}
            {passwordEditing ? (
              <Modal
                title={user.hasPassword === false ? "Thiết lập mật khẩu" : "Đổi mật khẩu"}
                onClose={() => setPasswordEditing(false)}
              >
                {user.hasPassword === false ? (
                  <AsyncForm
                    success="Đã yêu cầu email thiết lập mật khẩu. Hãy kiểm tra hộp thư."
                    onSubmit={async () => {
                      await authApi.forgot({ email: user.email });
                    }}
                  >
                    <p>
                      Gửi liên kết đến email tài khoản để thiết lập mật khẩu
                      trước khi ngắt Google.
                    </p>
                    <button className="button">Gửi liên kết thiết lập</button>
                  </AsyncForm>
                ) : (
                  <AsyncForm
                    success="Đã đổi mật khẩu. Vui lòng đăng nhập lại nếu được yêu cầu."
                    onSubmit={async (f) => {
                      await authApi.password({
                        currentPassword: text(f, "currentPassword"),
                        password: text(f, "password"),
                        passwordConfirmation: text(f, "passwordConfirmation"),
                      });
                      await reload();
                      setPasswordEditing(false);
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
                    <footer className="modal-actions">
                      <button
                        type="button"
                        className="button secondary"
                        onClick={() => setPasswordEditing(false)}
                      >
                        Đóng
                      </button>
                      <button className="button">Đổi mật khẩu</button>
                    </footer>
                  </AsyncForm>
                )}
              </Modal>
            ) : null}
          </section>
        )}
      </div>
    </>
  );
}
