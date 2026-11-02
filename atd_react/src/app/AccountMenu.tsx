import { useEffect, useRef } from "react";
import { Link } from "react-router-dom";
import { ChevronDown, LogOut, Settings, LogIn } from "lucide-react";
import { useSession } from "../application/sessionContext";
import { initials } from "../domain/format";
import { roleLabel } from "../domain/access";
import { ActionButton } from "../shared/ui";
export function AccountMenu() {
  const {user,logout}=useSession();
  const root=useRef<HTMLDetailsElement>(null);
  const trigger=useRef<HTMLElement>(null);
  useEffect(()=>{
    const outside=(event:PointerEvent)=>{if(root.current?.open && !root.current.contains(event.target as Node)) root.current.open=false};
    const escape=(event:KeyboardEvent)=>{if(event.key === "Escape" && root.current?.open){root.current.open=false;trigger.current?.focus()}};
    document.addEventListener("pointerdown",outside);document.addEventListener("keydown",escape);
    return ()=>{document.removeEventListener("pointerdown",outside);document.removeEventListener("keydown",escape)};
  },[]);
  if(!user) return <Link className="button small" to="/login"><LogIn size={16}/>Đăng nhập</Link>;
  return <details className="account-menu" ref={root}>
    <summary ref={trigger} aria-label="Tài khoản của bạn"><span className={"avatar avatar-"+(user.avatarKey ?? "blue")}>{initials(user.displayName)}</span><span className="account-trigger-name">{user.displayName}</span><ChevronDown size={14} aria-hidden="true"/></summary>
    <div className="account-popover"><div className="account-identity"><strong>{user.displayName}</strong><small>{roleLabel(user.role)}</small></div><Link to="/app/settings" onClick={()=>{if(root.current)root.current.open=false}}><Settings size={17}/>Cài đặt tài khoản</Link><ActionButton className="account-logout" action={logout}><LogOut size={17}/>Đăng xuất</ActionButton></div>
  </details>;
}
