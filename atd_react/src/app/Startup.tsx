import { Link } from "react-router-dom";
import { LoaderCircle, Check } from "lucide-react";
export default function Startup() {
  return <main className="startup"><section aria-labelledby="startup-title"><div className="startup-icon"><LoaderCircle className="spin" size={32} /></div><h1 id="startup-title">Chuẩn bị lớp học của bạn</h1><p role="status">Đang kết nối và kiểm tra phiên đăng nhập…</p><div className="startup-progress" role="progressbar" aria-label="Đang chờ máy chủ"><span /></div><ul><li><Check size={16} /> Giao diện đã sẵn sàng</li><li><LoaderCircle className="spin" size={16} /> Đang xác thực tài khoản</li></ul><p>Máy chủ có thể cần một lúc để khởi động.</p><Link to="/">Về trang chủ</Link></section></main>;
}
