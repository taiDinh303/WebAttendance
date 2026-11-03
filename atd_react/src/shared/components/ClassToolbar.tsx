import { createContext, useContext, useState, type ReactNode } from "react";
import { createPortal } from "react-dom";
const ToolbarContext=createContext<{target:HTMLDivElement|null;setTarget:(node:HTMLDivElement|null)=>void}|null>(null);
export function ClassToolbarProvider({children}:{children:ReactNode}){const [target,setTarget]=useState<HTMLDivElement|null>(null);return <ToolbarContext.Provider value={{target,setTarget}}>{children}</ToolbarContext.Provider>}
export function ClassToolbarSlot(){const context=useContext(ToolbarContext);return <div className="class-toolbar-slot" ref={context?.setTarget}/>}
export function ClassToolbar({children}:{children:ReactNode}){const context=useContext(ToolbarContext);const content=<div className="class-toolbar-actions">{children}</div>;return context?.target?createPortal(content,context.target):content;}
