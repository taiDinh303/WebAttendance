export const dateTime = (value?: string | null) =>
  value
    ? new Intl.DateTimeFormat("vi-VN", {
        dateStyle: "medium",
        timeStyle: "short",
      }).format(new Date(value))
    : "Chưa đặt";
export const dateOnly = (value: string) =>
  new Intl.DateTimeFormat("vi-VN", { day: "2-digit", month: "short" }).format(
    new Date(value),
  );
export const initials = (name: string) =>
  name
    .trim()
    .split(/\s+/)
    .slice(-2)
    .map((n) => n[0])
    .join("")
    .toUpperCase();
export const iso = (value: FormDataEntryValue | null) =>
  value ? new Date(String(value)).toISOString() : null;
export const text = (form: FormData, name: string) =>
  String(form.get(name) ?? "").trim();
export const statusLabel: Record<string, string> = {
  active: "Đang hoạt động",
  archived: "Đã lưu trữ",
  pending: "Chờ duyệt",
  rejected: "Bị từ chối",
  left: "Đã rời",
  removed: "Đã loại",
  draft: "Bản nháp",
  published: "Đã công bố",
  submitted: "Đã nộp",
  closed: "Đã đóng",
  scheduled: "Sắp diễn ra",
  cancelled: "Đã hủy",
  present: "Có mặt",
  late: "Đi muộn",
  absent: "Vắng",
  unmarked: "Chưa điểm danh",
  open: "Đang mở",
  expired: "Hết hạn",
};
