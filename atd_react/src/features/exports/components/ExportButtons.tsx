import { Download, FileSpreadsheet } from "lucide-react";
import { ActionButton } from "../../../shared/ui";
import { downloadReport } from "../../../infrastructure/http";
export function ExportButtons({classId,kind}:{classId:string;kind:"attendance"|"grades"}) {
  async function download(template:boolean) {
    const blob=await downloadReport(classId,kind,template);
    const url=URL.createObjectURL(blob);
    const link=document.createElement("a");link.href=url;link.download=(template?"mau-":"")+kind+".xlsx";document.body.append(link);link.click();link.remove();setTimeout(()=>URL.revokeObjectURL(url),1000);
  }
  return <div className="inline-actions"><ActionButton className="button small" action={()=>download(false)}><Download size={16}/>Xuất Excel</ActionButton><ActionButton className="button secondary small" action={()=>download(true)}><FileSpreadsheet size={16}/>Mẫu trống</ActionButton></div>;
}
