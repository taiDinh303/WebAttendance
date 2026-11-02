import { Modal } from "../../../shared/components/Modal";
import { useState } from "react";
import { useSession } from "../../../application/sessionContext";
import { usePage, useResource, useRefresh } from "../../../application/queries";
import { classApi } from "../../../infrastructure/classApi";
import {
  PageHeader,
  AsyncForm,
  Field,
  QueryState,
  Empty,
  Pagination,
  ActionButton,
} from "../../../shared/ui";
import { text } from "../../../domain/format";
import type { CatalogItem } from "../../../domain/models";
export default function CatalogPage() {
  const { user } = useSession();
  const [kind, setKind] = useState("courses");
  const [editing, setEditing] = useState<CatalogItem | null>(null);
  const [mode,setMode]=useState<"view"|"edit"|"create"|null>(null);
  const [formVersion, setFormVersion] = useState(0);
  const { page, setPage } = usePage();
  const refresh = useRefresh();
  const query = useResource(
    ["catalog", kind, page],
    (s) => classApi.catalog(kind, page, s),
    { enabled: user?.role === "teacher" },
  );
  if (user?.role !== "teacher")
    return <p>Danh mục riêng dành cho giáo viên.</p>;
  return (
    <>
      <PageHeader compact
        title="Môn học & học kỳ"
        description="Danh mục độc lập của riêng bạn. Môn học và học kỳ không bắt buộc khi tạo lớp."
      />
      <div className="tabs">
        <button
          className={kind === "courses" ? "active" : ""}
          onClick={() => {
            setKind("courses");
            setEditing(null);
            setPage(1);
          }}
        >
          Môn học
        </button>
        <button
          className={kind === "semesters" ? "active" : ""}
          onClick={() => {
            setKind("semesters");
            setEditing(null);
            setPage(1);
          }}
        >
          Học kỳ
        </button>
      </div>
      <div className="catalog-toolbar"><button className="button" onClick={()=>{setEditing(null);setMode("create")}}>Thêm {kind === "courses" ? "môn học" : "học kỳ"}</button></div>
      <div className="catalog-content">
        <section>
          <QueryState
            pending={query.isPending}
            error={query.error}
            retry={query.refetch}
          />
          <div className="panel">
            {query.data?.items.map((item) => (
              <div className="list-row" key={item.id}>
                <div className="grow">
                  <h3>{item.name}</h3>
                  <small>
                    {kind === "courses"
                      ? item.code || "Chưa đặt mã môn"
                      : item.startDate + " → " + item.endDate}
                  </small>
                </div>
                <div className="inline-actions">
                  <button
                    type="button"
                    className="button secondary small"
                    onClick={() => {setEditing(item);setMode("view")}}
                  >
                    Điều chỉnh
                  </button>
                  <ActionButton
                    className="button danger small"
                    action={async () => {
                      if (
                        !window.confirm(
                          "Xóa " +
                            item.name +
                            "? Danh mục đang được lớp sử dụng sẽ không thể xóa.",
                        )
                      )
                        return;
                      await classApi.deleteCatalog(kind, item);
                      if (editing?.id === item.id) setEditing(null);
                      await refresh();
                    }}
                  >
                    Xóa
                  </ActionButton>
                </div>
              </div>
            ))}
            {query.isSuccess && !query.data.items.length ? (
              <Empty title="Chưa có danh mục" />
            ) : null}
          </div>
          <Pagination data={query.data} onPage={setPage} />
        </section>
        {mode ? <Modal title={mode==="view" ? (editing?.name ?? "Chi tiết") : (mode==="create" ? "Thêm danh mục" : "Điều chỉnh")} onClose={()=>setMode(null)}>
          {mode==="view" && editing ? <><dl className="profile-facts"><dt>Tên</dt><dd>{editing.name}</dd><dt>{kind==="courses" ? "Mã môn" : "Thời gian"}</dt><dd>{kind==="courses" ? editing.code || "Chưa đặt mã" : editing.startDate+" → "+editing.endDate}</dd></dl><footer className="modal-actions"><button className="button secondary" onClick={()=>setMode(null)}>Đóng</button><button className="button" onClick={()=>setMode("edit")}>Điều chỉnh</button></footer></> : <>
          <h2>
            {editing ? "Sửa " : "Thêm "}
            {kind === "courses" ? "môn học" : "học kỳ"}
          </h2>
          <AsyncForm
            key={kind + (editing?.id ?? "new") + formVersion}
            onSubmit={async (f) => {
              await classApi.saveCatalog(
                kind,
                {
                  ...(kind === "courses"
                    ? { name: text(f, "name"), code: text(f, "code") || null }
                    : {
                        name: text(f, "name"),
                        startDate: text(f, "startDate"),
                        endDate: text(f, "endDate"),
                        isActive: f.has("isActive"),
                      }),
                  ...(editing ? { expectedVersion: editing.version } : {}),
                },
                editing?.id,
              );
              await refresh();
              setEditing(null);
              setFormVersion((version) => version + 1);
              setMode(null);
            }}
          >
            <Field label="Tên">
              <input
                name="name"
                required
                maxLength={120}
                defaultValue={editing?.name}
              />
            </Field>
            {kind === "courses" ? (
              <Field label="Mã môn (không bắt buộc)">
                <input
                  name="code"
                  maxLength={50}
                  defaultValue={editing?.code ?? ""}
                />
              </Field>
            ) : (
              <>
                <Field label="Ngày bắt đầu">
                  <input
                    name="startDate"
                    type="date"
                    required
                    defaultValue={editing?.startDate}
                  />
                </Field>
                <Field label="Ngày kết thúc">
                  <input
                    name="endDate"
                    type="date"
                    required
                    defaultValue={editing?.endDate}
                  />
                </Field>
                <label className="check">
                  <input
                    type="checkbox"
                    name="isActive"
                    defaultChecked={editing?.isActive ?? true}
                  />
                  Học kỳ đang hoạt động
                </label>
              </>
            )}
            <footer className="modal-actions"><button type="button" className="button secondary" onClick={()=>setMode(null)}>Đóng</button><button className="button">Lưu</button></footer>
          </AsyncForm></>}
        </Modal> : null}
      </div>
    </>
  );
}
