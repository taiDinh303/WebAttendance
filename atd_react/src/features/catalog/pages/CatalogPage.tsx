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
} from "../../../shared/ui";
import { text } from "../../../domain/format";
export default function CatalogPage() {
  const { user } = useSession();
  const [kind, setKind] = useState("courses");
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
      <PageHeader
        title="Môn học & học kỳ"
        description="Danh mục độc lập của riêng bạn. Môn học và học kỳ không bắt buộc khi tạo lớp."
      />
      <div className="tabs">
        <button
          className={kind === "courses" ? "active" : ""}
          onClick={() => {
            setKind("courses");
            setPage(1);
          }}
        >
          Môn học
        </button>
        <button
          className={kind === "semesters" ? "active" : ""}
          onClick={() => {
            setKind("semesters");
            setPage(1);
          }}
        >
          Học kỳ
        </button>
      </div>
      <div className="detail-grid">
        <section>
          <QueryState
            pending={query.isPending}
            error={query.error}
            retry={query.refetch}
          />
          <div className="panel">
            {query.data?.items.map((item) => (
              <div className="list-row" key={item.id}>
                <div>
                  <h3>{item.name}</h3>
                  <small>
                    {item.code ?? item.startDate + " → " + item.endDate}
                  </small>
                </div>
              </div>
            ))}
            {query.isSuccess && !query.data.items.length ? (
              <Empty title="Chưa có danh mục" />
            ) : null}
          </div>
          <Pagination data={query.data} onPage={setPage} />
        </section>
        <section className="panel editor-panel">
          <h2>Thêm {kind === "courses" ? "môn học" : "học kỳ"}</h2>
          <AsyncForm
            key={kind}
            success="Đã tạo danh mục."
            onSubmit={async (f) => {
              await classApi.saveCatalog(
                kind,
                kind === "courses"
                  ? { name: text(f, "name"), code: text(f, "code") || null }
                  : {
                      name: text(f, "name"),
                      startDate: text(f, "startDate"),
                      endDate: text(f, "endDate"),
                      isActive: true,
                    },
              );
              await refresh();
            }}
          >
            <Field label="Tên">
              <input name="name" required maxLength={160} />
            </Field>
            {kind === "courses" ? (
              <Field label="Mã môn (không bắt buộc)">
                <input name="code" maxLength={30} />
              </Field>
            ) : (
              <>
                <Field label="Ngày bắt đầu">
                  <input name="startDate" type="date" required />
                </Field>
                <Field label="Ngày kết thúc">
                  <input name="endDate" type="date" required />
                </Field>
              </>
            )}
            <button className="button">Thêm mới</button>
          </AsyncForm>
        </section>
      </div>
    </>
  );
}
