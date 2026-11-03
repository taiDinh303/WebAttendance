import { CalendarDays, Clock } from "lucide-react";
import { Fragment, useRef, useState } from "react";

type Part = { hint: string; label: string; length: number; min: number; max: number; separator: string };
const dateParts: Part[] = [
  { hint: "dd", label: "Ngày", length: 2, min: 1, max: 31, separator: "/" },
  { hint: "mm", label: "Tháng", length: 2, min: 1, max: 12, separator: "/" },
  { hint: "yyyy", label: "Năm", length: 4, min: 1, max: 9999, separator: " " },
];
const timeParts: Part[] = [
  { hint: "0", label: "Giờ", length: 2, min: 0, max: 23, separator: ":" },
  { hint: "00", label: "Phút", length: 2, min: 0, max: 59, separator: "" },
];

/** Separate numeric segments keep placeholders visible while a date is incomplete. */
export function ScheduleInput({ label, name, kind, defaultValue = "" }: {
  label: string; name: string; kind: "date" | "time" | "datetime"; defaultValue?: string;
}) {
  const parts = kind === "date" ? dateParts : kind === "time" ? timeParts : [...dateParts, ...timeParts];
  const [values, setValues] = useState(() => {
    const match = defaultValue.match(/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})(:\d{2})?$/);
    return match ? [match[3], match[2], match[1], match[4], match[5]] : parts.map(() => "");
  });
  const seconds = defaultValue.match(/T\d{2}:\d{2}(:\d{2})$/)?.[1] ?? "";
  function bounded(next: string[]) {
    if (kind !== "time" && next[0] && next[1]) {
      const year = next[2]?.length === 4 ? Number(next[2]) : 2000;
      const maxDay = new Date(Date.UTC(year, Number(next[1]), 0)).getUTCDate();
      if (Number(next[0]) > maxDay) next[0] = String(maxDay);
    }
    return next;
  }
  const refs = useRef<(HTMLInputElement | null)[]>([]);
  function focus(index: number) { refs.current[index]?.focus(); refs.current[index]?.select(); }
  function finish(index: number) {
    const value = values[index];
    if (value && Number(value) >= parts[index].min) {
      setValues((previous) => previous.map((item, i) => i === index ? item.padStart(parts[i].length, "0") : item));
      focus(index + 1);
    }
  }
  const normalized = values.map((value, index) => value ? value.padStart(parts[index].length, "0") : "").map((value, index) => value + (index < parts.length - 1 ? parts[index].separator : "")).join("") + seconds;
  const complete = values.every((value, index) => value.length === parts[index].length);
  const dateValue = kind !== "time" ? [values[2], values[1], values[0]].join("-") : "";
  const timeValue = kind !== "date" ? values.slice(kind === "time" ? 0 : 3).join(":") : "";
  const pickerValue = complete ? (kind === "date" ? dateValue : kind === "time" ? timeValue : dateValue + "T" + timeValue) : "";
  return <fieldset className="schedule-field">
    <legend>{label}</legend>
    <input type="hidden" name={name} value={normalized} />
    <div className="schedule-segments">
      {parts.map((part, index) => <Fragment key={part.label}>
        <input ref={(element) => { refs.current[index] = element; }}
          aria-label={label + " — " + part.label} inputMode="numeric" type="text"
          autoComplete="off" placeholder={part.hint} value={values[index]}
          style={{ width: part.length + 0.5 + "ch" }} required
          pattern={"[0-9]{" + part.length + "}"}
          onFocus={(event) => event.currentTarget.select()}
          onBlur={() => {
            if (values[index] && Number(values[index]) >= part.min) setValues((previous) => previous.map((value, i) => i === index ? value.padStart(part.length, "0") : value));
          }}
          onKeyDown={(event) => {
            if (["/", ":", " "].includes(event.key)) { event.preventDefault(); finish(index); }
            else if (event.key === "ArrowRight") { event.preventDefault(); focus(index + 1); }
            else if (event.key === "ArrowLeft" || (event.key === "Backspace" && !values[index])) { event.preventDefault(); focus(index - 1); }
          }}
          onPaste={(event) => {
            event.preventDefault();
            const pasted = event.clipboardData.getData("text").trim();
            if (!/^[0-9/ :]+$/.test(pasted)) return;
            const tokens = pasted.split(/[/ :]+/);
            const next = [...values];
            let target = index;
            for (const token of tokens) {
              let remaining = token;
              while (remaining && target < parts.length) {
                const spec = parts[target];
                const chunk = remaining.slice(0, spec.length);
                next[target++] = chunk.length === spec.length ? String(Math.max(spec.min, Math.min(spec.max, Number(chunk)))).padStart(spec.length, "0") : chunk;
                remaining = remaining.slice(spec.length);
              }
            }
            setValues(bounded(next)); focus(Math.min(target, parts.length - 1));
          }}
          onChange={(event) => {
            const value = event.target.value;
            if (!/^\d*$/.test(value) || value.length > part.length) return;
            setValues((previous) => bounded(previous.map((item, i) => i === index ? (value.length === part.length ? String(Math.max(part.min, Math.min(part.max, Number(value)))).padStart(part.length, "0") : value) : item)));
            if (value.length === part.length) focus(index + 1);
          }} />
        {index < parts.length - 1 && <span aria-hidden="true">{part.separator}</span>}
      </Fragment>)}
      <span className="schedule-picker">
        {kind === "time" ? <Clock size={18} aria-hidden="true" /> : <CalendarDays size={18} aria-hidden="true" />}
        <input type={kind === "datetime" ? "datetime-local" : kind}
          aria-label={label + (kind === "time" ? " — Chọn giờ" : " — Chọn lịch")}
          value={pickerValue}
          onClick={(event) => { event.currentTarget.showPicker?.(); }}
          onChange={(event) => {
            const selected = event.target.value;
            if (!selected) return;
            if (kind === "time") setValues(selected.split(":"));
            else {
              const [date, time] = selected.split("T");
              const [year, month, day] = date.split("-");
              setValues([day, month, year, ...(kind === "datetime" ? time.split(":") : [])]);
            }
          }} />
      </span>
    </div>
  </fieldset>;
}
