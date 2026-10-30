import { Link } from "react-router-dom";
export default function Home() {
  return <main className="public-home">
    <header><Link to="/" className="home-brand">AttendanceWeb</Link><Link className="button secondary" to="/login">Đăng nhập</Link></header>
    <section className="home-hero"><span className="home-eyebrow">KHÔNG GIAN LỚP HỌC CỦA BẠN</span><h1>Mỗi buổi học,<br />kết nối dễ dàng hơn.</h1><p>Quản lý lớp học, điểm danh bằng QR và theo dõi bài tập trong cùng một không gian.</p><div className="home-actions"><Link className="button" to="/app">Vào lớp học</Link><Link className="button secondary" to="/register">Tạo tài khoản</Link></div></section>
    <section className="home-features" aria-label="Tính năng"><article><h2>Lớp học riêng</h2><p>Tham gia bằng mã lớp và được giáo viên phê duyệt.</p></article><article><h2>Điểm danh QR</h2><p>Check-in buổi học và xem lịch học của bạn.</p></article><article><h2>Bài tập & điểm</h2><p>Nộp bài và theo dõi kết quả đã được công bố.</p></article></section>
  </main>;
}
