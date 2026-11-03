/** Decode pixels locally; neither the image nor clipboard contents are uploaded. */
export async function readQrImage(file: File): Promise<string> {
  if (!file.type.startsWith("image/")) throw Error("Hãy chọn ảnh chứa mã QR.");
  if (file.size > 10 * 1024 * 1024) throw Error("Ảnh quá lớn. Hãy chọn ảnh dưới 10 MB.");
  let bitmap: ImageBitmap;
  try { bitmap = await createImageBitmap(file); }
  catch { throw Error("Không đọc được ảnh. Hãy dùng ảnh PNG hoặc JPG rõ nét."); }
  try {
    const scale = Math.min(1, 2000 / Math.max(bitmap.width, bitmap.height));
    const canvas = document.createElement("canvas");
    canvas.width = Math.max(1, Math.round(bitmap.width * scale));
    canvas.height = Math.max(1, Math.round(bitmap.height * scale));
    const context = canvas.getContext("2d", { willReadFrequently: true });
    if (!context) throw Error("Trình duyệt không đọc được ảnh. Hãy dán liên kết điểm danh.");
    context.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
    const pixels = context.getImageData(0, 0, canvas.width, canvas.height);
    const { default: jsQR } = await import("jsqr");
    const code = jsQR(pixels.data, pixels.width, pixels.height);
    if (!code) throw Error("Không tìm thấy mã QR trong ảnh. Hãy sao chép ảnh QR rõ nét và thử lại.");
    return code.data;
  } finally { bitmap.close(); }
}
