import { useState } from "react";
import { useResource } from "../../../application/queries";
import { classApi } from "../../../infrastructure/classApi";
import { Field, Pagination, QueryState } from "../../../shared/ui";

function CatalogSelect({
  kind,
  name,
  label,
  initialValue = "",
}: {
  kind: "courses" | "semesters";
  name: string;
  label: string;
  initialValue?: string;
}) {
  const [page, setPage] = useState(1);
  const [value, setValue] = useState(initialValue);
  const query = useResource(["catalog", kind, page], (signal) =>
    classApi.catalog(kind, page, signal),
  );
  const selected = useResource(
    ["catalog", kind, value],
    (signal) => classApi.catalogItem(kind, value, signal),
    { enabled: value !== "" },
  );
  return (
    <div className="catalog-select">
      <Field label={label}>
        <select
          name={name}
          value={value}
          onChange={(event) => setValue(event.target.value)}
        >
          <option value="">Không chọn</option>
          {value && !query.data?.items.some((item) => item.id === value) ? (
            <option value={value}>
              {selected.data?.name ?? "Mục đang chọn"}
            </option>
          ) : null}
          {query.data?.items.map((item) => (
            <option key={item.id} value={item.id}>
              {item.name}
            </option>
          ))}
        </select>
      </Field>
      <QueryState
        pending={query.isPending}
        error={query.error}
        retry={query.refetch}
      />
      {query.data && query.data.totalPages > 1 ? (
        <Pagination data={query.data} onPage={setPage} />
      ) : null}
    </div>
  );
}

export function CatalogFields({
  courseId,
  semesterId,
}: {
  courseId?: string | null;
  semesterId?: string | null;
}) {
  return (
    <div className="form-grid">
      <CatalogSelect
        kind="courses"
        name="courseId"
        label="Môn học (không bắt buộc)"
        initialValue={courseId ?? ""}
      />
      <CatalogSelect
        kind="semesters"
        name="semesterId"
        label="Học kỳ (không bắt buộc)"
        initialValue={semesterId ?? ""}
      />
    </div>
  );
}
