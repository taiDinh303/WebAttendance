import { GraduationCap } from "lucide-react";
import { Link } from "react-router-dom";
export function Brand({className=""}:{className?:string}) {
  return <Link to="/" className={"app-brand "+className} aria-label="Attendance — trang chủ"><span className="brand-mark"><GraduationCap size={23} aria-hidden="true"/></span><span>Attendance<span className="brand-dot">.</span></span></Link>;
}
