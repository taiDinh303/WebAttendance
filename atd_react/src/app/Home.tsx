import { Link } from "react-router-dom";
import { GraduationCap, QrCode, BookOpen, Users, ArrowRight } from "lucide-react";
import { useSession } from "../application/sessionContext";
export default function Home() {
  const { user, loading, error } = useSession();
  return <main className="public-home">
    <header><Link to="/" className="home-brand"><span className="brand-mark"><GraduationCap /></span>attendance.</Link>{user ? <Link className="button secondary" to="/app">Vào lớp học</Link> : loading ? <span role="status">Đang kiểm tra phiên…</span> : <Link className="button secondary" to={error ? "/app" : "/login"}>{error ? "Kết nối lại" : "Đăng nhập"}</Link>}</header>
    <section className="home-hero"><span className="home-eyebrow">KHÔNG GIAN LỚP HỌC CỦA BẠN</span><h1>Mỗi buổi học,<br />kết nối dễ dàng hơn.</h1><p>Quản lý lớp học, điểm danh bằng QR và theo dõi bài tập trong cùng một không gian.</p><div className="home-actions"><Link className="button" to="/app">Vào lớp học <ArrowRight size={17} /></Link>{!user && !loading && !error ? <Link className="button secondary" to="/register">Tạo tài khoản</Link> : null}</div></section>
    <section className="home-features" aria-label="Tính năng"><article><Users size={24} /><h2>Lớp học riêng</h2><p>Tham gia bằng mã lớp và được giáo viên phê duyệt.</p></article><article><QrCode size={24} /><h2>Điểm danh QR</h2><p>Check-in buổi học và xem lịch học của bạn.</p></article><article><BookOpen size={24} /><h2>Bài tập & điểm</h2><p>Nộp bài và theo dõi kết quả đã được công bố.</p></article></section>
  </main>;
}
