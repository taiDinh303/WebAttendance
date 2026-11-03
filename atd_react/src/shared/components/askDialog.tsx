import { createRoot } from "react-dom/client";
import { useState } from "react";
import { Modal } from "./Modal";
export function askDialog(message:string, input=false):Promise<string|null> {
 return new Promise(resolve=>{const host=document.createElement("div");document.body.append(host);const root=createRoot(host);const finish=(value:string|null)=>{root.unmount();host.remove();resolve(value)};
 function Content(){const [value,setValue]=useState("");return <Modal title={input?"Nhập thông tin":"Xác nhận"} onClose={()=>finish(null)}><p>{message}</p>{input?<label className="field"><span>Nội dung</span><textarea autoFocus value={value} onChange={e=>setValue(e.target.value)}/></label>:null}<footer className="modal-actions"><button className="button secondary" onClick={()=>finish(null)}>Hủy</button><button className="button" onClick={()=>finish(input?value:"yes")}>Xác nhận</button></footer></Modal>};root.render(<Content/>);
 });
}
