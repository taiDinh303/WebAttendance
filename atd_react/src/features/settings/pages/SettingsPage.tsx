import { useState } from "react";
import { Pencil, X, Camera } from "lucide-react";
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
  const [editing,setEditing]=useState(false);
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
          <span className={"avatar profile-avatar avatar-"+(user.avatarKey ?? "blue")}>{user.displayName.slice(0,1)}</span>
          <p className="muted">
            {user.email} · {user.role === "teacher" ? "Giáo viên" : "Học sinh"}
          </p>
          {!editing ? <><dl className="profile-facts"><div><dt>Họ tên</dt><dd>{user.displayName}</dd></div><div><dt>Email</dt><dd>{user.email}</dd></div><div><dt>Điện thoại</dt><dd>{user.phone || "Chưa cung cấp"}</dd></div><div><dt>Giới tính</dt><dd>{({male:"Nam",female:"Nữ",other:"Khác",undisclosed:"Không chia sẻ"})[user.gender ?? "undisclosed"]}</dd></div><div><dt>Ngày sinh</dt><dd>{user.birthday ? user.birthday.split("-").reverse().join("/") : "Chưa cung cấp"}</dd></div></dl><button className="button secondary" onClick={()=>setEditing(true)}><Pencil size={16}/>Chỉnh sửa</button></> : <><button className="icon-button" aria-label="Hủy chỉnh sửa" onClick={()=>setEditing(false)}><X size={18}/></button><AsyncForm
            key={user.id}
            success="Đã lưu thông tin cá nhân."
            onSubmit={async (f) => {
              await authApi.profile({
                displayName: text(f, "displayName"),
                phone: text(f, "phone") || null,
                gender: text(f,"gender") || null,
                avatarKey: text(f,"avatarKey") || null,
                birthday: text(f,"birthday") || null,
                timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
                locale: "vi",
                expectedVersion: user.version,
              });
              await reload();
              setEditing(false);
            }}
          >
            <Field label="Avatar"><div className="avatar-options">{["blue","green","amber","rose","violet"].map(key=><label key={key}><input type="radio" name="avatarKey" value={key} defaultChecked={(user.avatarKey ?? "blue")===key}/><span className={"avatar avatar-"+key}>{user.displayName.slice(0,1)}</span></label>)}</div></Field><button type="button" className="button secondary" disabled title="Chưa cấu hình kho ảnh"><Camera size={16}/>Tải ảnh cá nhân</button><small className="muted">Tải ảnh sẽ mở khi kho lưu ảnh được kết nối.</small>
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

            <Field label="Giới tính"><select name="gender" defaultValue={user.gender ?? "undisclosed"}><option value="undisclosed">Không chia sẻ</option><option value="male">Nam</option><option value="female">Nữ</option><option value="other">Khác</option></select></Field><Field label="Ngày sinh"><input type="date" name="birthday" defaultValue={user.birthday ?? ""} max={new Date().toLocaleDateString("en-CA")}/></Field>
            <button className="button">Lưu thông tin</button>
          </AsyncForm></>}
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
