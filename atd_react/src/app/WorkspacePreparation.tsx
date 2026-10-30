import { useEffect, type ReactNode } from "react";
import { useQueries, useQueryClient } from "@tanstack/react-query";
import { Check, LoaderCircle } from "lucide-react";
import { useSession } from "../application/sessionContext";
import { classApi } from "../infrastructure/classApi";
import { ErrorNotice, ActionButton } from "../shared/ui";
export default function WorkspacePreparation({children}:{children:ReactNode}) {
 const {user}=useSession();
 const client=useQueryClient();
 const tasks=useQueries({queries:[
  {queryKey:["private",user?.id,"dashboard"],queryFn:({signal}:{signal:AbortSignal})=>classApi.dashboard(signal)},
  {queryKey:["private",user?.id,"classes",1,"","active"],queryFn:({signal}:{signal:AbortSignal})=>classApi.list({pageNumber:1,q:"",status:"active"},signal)},
  {queryKey:["private",user?.id,"workspace-code"],queryFn:()=>Promise.all([import("../features/dashboard/pages/DashboardPage"),import("../features/classes/pages/ClassesPage"),import("./Shell")]).then(()=>true)},
 ].map(q=>({...q,enabled:!!user?.emailVerified&&user.role!=="admin",staleTime:60000,retry:false}))});
 const ready=tasks.every(t=>t.isSuccess); const failed=tasks.find(t=>t.error);
 useEffect(()=>{if(!ready)return;const timer=setTimeout(()=>{void client.prefetchQuery({queryKey:["private",user?.id,"unread"],queryFn:({signal})=>classApi.unread(signal),staleTime:60000});void client.prefetchQuery({queryKey:["private",user?.id,"notifications",1],queryFn:({signal})=>classApi.notices(1,signal),staleTime:60000});void Promise.allSettled([import("../features/settings/pages/SettingsPage"),import("../features/calendar/pages/CalendarPage"),import("../features/notifications/pages/NotificationsPage"),import("../features/catalog/pages/CatalogPage")]);},500);return()=>clearTimeout(timer)},[ready,client,user?.id]);
 if(user?.role==="admin"||ready)return children;
 const count=tasks.filter(t=>t.isSuccess).length;
 return <main className="startup"><section><h1>Chuẩn bị workspace</h1><p role="status">{count}/{tasks.length} mục sẵn sàng</p><progress max={tasks.length} value={count} aria-label="Tải workspace" style={{width:"100%"}}/><ul>{tasks.map((t,i)=><li key={i}>{t.isSuccess?<Check size={17}/>:<LoaderCircle size={17} className="spin"/>}{["Tổng quan","Lớp học","Giao diện"][i]}</li>)}</ul>{failed?<><ErrorNotice error={failed.error}/><ActionButton className="button" action={async()=>{await Promise.all(tasks.filter(t=>t.isError).map(t=>t.refetch()))}}>Thử lại</ActionButton></>:null}</section></main>;
}
