export function extractQrToken(value: string, origin: string): string {
  let token = value.trim();
  if (token.startsWith("http://") || token.startsWith("https://")) {
    const url = new URL(token);
    if (url.origin !== origin || url.pathname !== "/app/check-in")
      throw Error("Đây không phải mã điểm danh của ứng dụng này.");
    token = url.searchParams.get("token") ?? "";
  }
  if (!/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/.test(token) || token.length > 4096)
    throw Error(
      "Mã QR không đúng định dạng. Hãy quét lại mã giáo viên đang mở.",
    );
  return token;
}
