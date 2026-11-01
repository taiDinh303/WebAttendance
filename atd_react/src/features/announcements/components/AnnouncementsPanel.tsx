import { useRef, useState } from "react";
import { Plus, Megaphone, MessageSquare, Pin, EyeOff, Trash2 } from "lucide-react";
import { useQuery } from "@tanstack/react-query";
import { useSession } from "../../../application/sessionContext";
import { usePage, useRefresh, useResource } from "../../../application/queries";
import { classApi } from "../../../infrastructure/classApi";
import {
  AsyncForm,
  Field,
  QueryState,
  Badge,
  Empty,
  Pagination,
  ActionButton,
  ErrorNotice,
} from "../../../shared/ui";
import { text, iso, dateTime } from "../../../domain/format";
import type { Announcement, AnnouncementComment } from "../../../domain/models";

export function AnnouncementsPanel({ classId }: { classId: string }) {
  const { user } = useSession();
  const { page, setPage } = usePage();
  const [open, setOpen] = useState(false);
  const refresh = useRefresh();
  const teacher = user?.role === "teacher";
  const query = useResource(["announcements", classId, page], (s) =>
    classApi.announcements(classId, page, s),
  );

  return (
    <>
      <div className="section-heading">
        <h2>Bản tin lớp</h2>
        {teacher ? (
          <button className="button small" onClick={() => setOpen(!open)}>
            <Plus size={16} />
            Viết bản tin
          </button>
        ) : null}
      </div>
      {open ? (
        <div className="panel editor-panel">
          <AsyncForm
            onSubmit={async (f) => {
              const draft = await classApi.saveAnnouncement(classId, {
                title: text(f, "title"),
                body: text(f, "body"),
                pinned: f.has("pinned"),
                expiresAt: iso(f.get("expiresAt")),
              });
              if (f.has("publishNow")) {
                await classApi.publishAnnouncement(classId, draft.id, draft.version);
              }
              await refresh();
              setOpen(false);
            }}
          >
            <Field label="Tiêu đề">
              <input name="title" required maxLength={200} placeholder="Ví dụ: Sắp xếp lịch tuần này" />
            </Field>
            <Field label="Nội dung">
              <textarea name="body" rows={4} maxLength={20000} />
            </Field>
            <Field label="Hết hiệu lực (giờ máy của bạn) — tùy chọn">
              <input name="expiresAt" type="datetime-local" />
            </Field>
            <label className="check">
              <input type="checkbox" name="pinned" />
              Gắn ở đầu danh sách
            </label>
            <button className="button">Lưu bản nháp</button>
            <button className="button secondary" type="submit" name="publishNow" value="now">
              Lưu & công bố ngay
            </button>
          </AsyncForm>
        </div>
      ) : null}
      <QueryState pending={query.isPending} error={query.error} retry={query.refetch} />
      <div className="panel">
        {query.data?.items.map((a) => (
          <AnnouncementRow key={a.id} announcement={a} classId={classId} teacher={teacher} refresh={refresh} />
        ))}
        {query.isSuccess && !query.data.items.length ? (
          <Empty title="Chưa có bản tin" description="Bản tin do giáo viên công bố sẽ hiển thị ở đây." />
        ) : null}
      </div>
      <Pagination data={query.data} onPage={setPage} />
    </>
  );
}

function AnnouncementRow({
  announcement,
  classId,
  teacher,
  refresh,
}: {
  announcement: Announcement;
  classId: string;
  teacher: boolean;
  refresh: () => Promise<void>;
}) {
  const [editing,setEditing]=useState(false);
  const [expanded, setExpanded] = useState(false);
  const [commentPage,setCommentPage] = useState(1);
  const { user } = useSession();
  const detail = useQuery({
    queryKey: ["private", user?.id, "announcement-detail", classId, announcement.id, commentPage],
    queryFn: ({ signal }) => classApi.announcement(classId, announcement.id, signal, commentPage),
    enabled: expanded,
    retry: false,
  });
  const comments: AnnouncementComment[] = detail.data?.comments ?? [];

  return (
    <div className="list-row announcement-row">
      <div className="date-tile">
        <Megaphone size={18} />
      </div>
      <div className="grow">
        <h3>
          {announcement.pinned ? <Pin size={14} /> : null} {announcement.title}
        </h3>
        {announcement.body ? <p>{announcement.body}</p> : null}
        <small>
          {announcement.state === "draft" ? "Bản nháp · " : ""}
          {announcement.expiresAt ? "Hiệu lực đến " + dateTime(announcement.expiresAt) : "Không hết hạn"}
        </small>
      </div>
      <Badge value={announcement.state} />
      {teacher && announcement.state !== "archived" ? <><button className="button small secondary" onClick={()=>setEditing(v=>!v)}>Sửa</button><ActionButton className="button small secondary" action={async()=>{await classApi.saveAnnouncement(classId,{pinned:!announcement.pinned,expectedVersion:announcement.version},announcement.id);await refresh()}}>{announcement.pinned?"Bỏ ghim":"Ghim"}</ActionButton><ActionButton className="button small secondary" action={async()=>{await classApi.saveAnnouncement(classId,{hidden:!announcement.hidden,expectedVersion:announcement.version},announcement.id);await refresh()}}>{announcement.hidden?"Hiện bản tin":"Ẩn bản tin"}</ActionButton></> : null}
      {editing ? <div className="comments-panel"><AsyncForm onSubmit={async f=>{await classApi.saveAnnouncement(classId,{title:text(f,"title"),body:text(f,"body"),expiresAt:iso(f.get("expiresAt")),expectedVersion:announcement.version},announcement.id);await refresh();setEditing(false)}}><Field label="Tiêu đề"><input name="title" defaultValue={announcement.title} required maxLength={200}/></Field><Field label="Nội dung"><textarea name="body" defaultValue={announcement.body ?? ""} maxLength={20000}/></Field><Field label="Hết hạn"><input type="datetime-local" name="expiresAt" defaultValue={announcement.expiresAt ? new Date(new Date(announcement.expiresAt).getTime()-new Date(announcement.expiresAt).getTimezoneOffset()*60000).toISOString().slice(0,16):""}/></Field><button className="button">Lưu</button></AsyncForm></div> : null}
      {teacher && announcement.state === "draft" ? <ActionButton className="button small" action={async()=>{await classApi.publishAnnouncement(classId,announcement.id,announcement.version);await refresh()}}>Công bố</ActionButton> : null}
      <button
        className="button ghost small"
        aria-expanded={expanded}
        aria-label="Xem bình luận"
        onClick={() => setExpanded(!expanded)}
      >
        <MessageSquare size={16} />
        {comments.length}
      </button>
      {teacher && announcement.state === "published" ? (
        <ActionButton
          className="button ghost small"
          title="Lưu trữ bản tin"
          action={async () => {
            if (!window.confirm("Lưu trữ bản tin? Nó sẽ khỏi danh sách công khai.")) return;
            await classApi.archiveAnnouncement(classId, announcement.id, announcement.version);
            await refresh();
          }}
        >
          <EyeOff size={16} />
        </ActionButton>
      ) : null}
      {expanded ? (
        <div className="comments-panel">
          <QueryState pending={detail.isPending} error={detail.error} retry={detail.refetch} />
          {comments.map((c) => (
            <div className="list-row" key={c.id}>
              <div className="grow">
                <p>{c.deletedAt ? "Bình luận đã được giáo viên xóa." : c.body}</p>
                <small>{dateTime(c.createdAt)}</small>
                {c.hidden ? <Badge value="hidden" /> : null}
              </div>
              {teacher && !c.deletedAt ? (
                <div className="inline-actions">
                  {!c.deletedAt ? (
                    <ActionButton
                      className="button ghost small"
                      title={c.hidden ? "Hiện lại bình luận" : "Ẩn bình luận"}
                      action={async () => {
                        await classApi.hideComment(classId, c.id, !c.hidden);
                        await detail.refetch();
                      }}
                    >
                      <EyeOff size={14} />
                    </ActionButton>
                  ) : null}
                  <ActionButton
                    className="button ghost small danger"
                    title="Xóa bình luận"
                    action={async () => {
                      if (!window.confirm("Xóa bình luận này?")) return;
                      await classApi.deleteComment(classId, c.id);
                      await detail.refetch();
                    }}
                  >
                    <Trash2 size={14} />
                  </ActionButton>
                </div>
              ) : null}
            </div>
          ))}
          {detail.isSuccess && !comments.length ? <p className="muted">Chưa có bình luận.</p> : null}
          <Pagination data={detail.data?.commentPagination} onPage={setCommentPage}/>
          {announcement.state === "published" ? (
            <CommentComposer
              classId={classId}
              announcementId={announcement.id}
              onSent={() => void detail.refetch()}
            />
          ) : null}
        </div>
      ) : null}
    </div>
  );
}

function CommentComposer({
  classId,
  announcementId,
  onSent,
}: {
  classId: string;
  announcementId: string;
  onSent: () => void;
}) {
  const [draft, setDraft] = useState("");
  const [sending, setSending] = useState(false);
  const [error,setError] = useState<unknown>(null);
  const pending = useRef<{body:string;key:string} | null>(null);

  const submit = async () => {
    if (!draft.trim() || sending) return;
    setSending(true);
    setError(null);
    pending.current ??= {body:draft,key:Array.from(crypto.getRandomValues(new Uint8Array(26)),v=>"0123456789ABCDEFGHJKMNPQRSTVWXYZ"[v%32]).join("").replace(/^./,"0")};
    try {
      await classApi.addComment(classId, announcementId, pending.current.body, pending.current.key);
      pending.current=null;
      setDraft("");
      onSent();
    } catch(error) { setError(error); } finally {
      setSending(false);
    }
  };

  return (
    <Field label="Phản hồi">
      <textarea
        rows={2}
        maxLength={20000}
        placeholder="Nhập phản hồi..."
        value={draft}
        disabled={sending || error !== null}
        onChange={(e) => setDraft(e.target.value)}
        onKeyDown={(e) => {
          if ((e.ctrlKey || e.metaKey) && e.key === "Enter" && draft.trim()) {
            void submit();
          }
        }}
      />
      {error ? <button type="button" className="button secondary small" onClick={()=>{pending.current=null;setError(null)}}>Chỉnh lại nội dung</button> : null}
      <ErrorNotice error={error}/><div className="inline-actions">
        <button className="button small" onClick={() => void submit()} disabled={sending}>
          Gửi
        </button>
      </div>
    </Field>
  );
}
