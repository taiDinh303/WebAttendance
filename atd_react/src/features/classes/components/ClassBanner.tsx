import { useAccountPreference } from "../../../application/accountPreference";
import { useState } from "react";
import { Modal } from "../../../shared/components/Modal";
import { ErrorNotice } from "../../../shared/ui";
const colors = [
  "#356875",
  "#3564a0",
  "#477c55",
  "#76539b",
  "#a66835",
  "#5f6770",
];
export function ClassBanner({
  id,
  name,
  description,
  editable,
  actorId,
}: {
  id: string;
  name: string;
  description: string | null;
  editable: boolean;
  actorId: string;
}) {
  void actorId;
  const preference = useAccountPreference("banner-" + id, {
    color: colors[0],
    image: "",
  });
  const saved = preference.value;
  const [draft, setDraft] = useState(saved);
  const [open, setOpen] = useState(false);
  const [error, setError] = useState<unknown>(null);
  const style = (v: typeof saved) => ({
    backgroundColor: v.color,
    backgroundImage: v.image
      ? `linear-gradient(90deg,#0008,#0001),url("${v.image}")`
      : "radial-gradient(ellipse at 90% 20%,#ffffff35 0 15%,transparent 15%),linear-gradient(135deg,transparent 60%,#ffffff20 60% 75%,transparent 75%)",
  });
  return (
    <>
      <ErrorNotice error={preference.error} />
      <section className="class-banner" style={style(saved)}>
        <h1>{name}</h1>
        {description ? <p>{description}</p> : null}
        {editable ? (
          <button
            className="button secondary"
            disabled={!preference.ready}
            onClick={() => {
              setDraft(saved);
              setError(null);
              setOpen(true);
            }}
          >
            Tùy chỉnh
          </button>
        ) : null}
      </section>
      {open ? (
        <Modal title="Tùy chỉnh giao diện lớp" onClose={() => setOpen(false)}>
          <div className="banner-preview" style={style(draft)}>
            {name}
          </div>
          <p className="muted">
            Giao diện lưu theo tài khoản, dùng trên các thiết bị.
          </p>
          <div className="inline-actions">
            {colors.map((color) => (
              <button
                key={color}
                aria-label={"Chọn màu " + color}
                aria-pressed={draft.color === color}
                style={{
                  background: color,
                  width: 44,
                  height: 44,
                  borderRadius: "50%",
                  border:
                    draft.color === color
                      ? "3px solid var(--ui-text)"
                      : "1px solid transparent",
                }}
                onClick={() => setDraft({ ...draft, color })}
              />
            ))}
          </div>
          <label className="field">
            <span>Ảnh banner (JPG, PNG hoặc WebP, tối đa 2 MB)</span>
            <input
              type="file"
              accept="image/jpeg,image/png,image/webp"
              onChange={async (e) => {
                const file = e.target.files?.[0];
                if (!file) return;
                try {
                  if (
                    file.size > 2 * 1024 * 1024 ||
                    !["image/jpeg", "image/png", "image/webp"].includes(
                      file.type,
                    )
                  )
                    throw Error("Chọn ảnh JPG, PNG hoặc WebP tối đa 2 MB.");
                  const image = await new Promise<string>((resolve, reject) => {
                    const reader = new FileReader();
                    reader.onload = () => resolve(String(reader.result));
                    reader.onerror = reject;
                    reader.readAsDataURL(file);
                  });
                  setDraft({ ...draft, image });
                  setError(null);
                } catch (err) {
                  setError(err);
                }
              }}
            />
          </label>
          <button
            className="button secondary"
            onClick={() => setDraft({ ...draft, image: "" })}
          >
            Dùng hình nền có sẵn
          </button>
          <ErrorNotice error={error ?? preference.error} />
          <footer className="modal-actions">
            <button className="button secondary" onClick={() => setOpen(false)}>
              Hủy
            </button>
            <button
              className="button"
              disabled={!preference.ready || preference.saving}
              onClick={async () => {
                if (await preference.save(draft)) setOpen(false);
              }}
            >
              Lưu
            </button>
          </footer>
        </Modal>
      ) : null}
    </>
  );
}
