import { useSyncExternalStore } from "react";
const key = "attendance.theme.v1";
const eventName = "attendance:theme-change";
type Theme = "light" | "dark";
function preference(): Theme {
  try { const saved = localStorage.getItem(key); if (saved === "light" || saved === "dark") return saved; } catch { /* Storage is optional. */ }
  return window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
}
function apply(theme: Theme) {
  document.documentElement.dataset.theme = theme;
  document.documentElement.style.colorScheme = theme;
  document.querySelector('meta[name="theme-color"]')?.setAttribute("content",theme === "dark" ? "#20252b" : "#ffffff");
}
export function initializeTheme() { apply(preference()); }
function snapshot(): Theme { return document.documentElement.dataset.theme === "dark" ? "dark" : "light"; }
function subscribe(notify:()=>void) {
  const changed = () => notify();
  const storage = (event:StorageEvent) => { if(event.key === key || event.key === null) { apply(preference()); notify(); } };
  const media = window.matchMedia("(prefers-color-scheme: dark)");
  const system = () => { try { if(localStorage.getItem(key)) return; } catch { return; } apply(preference()); notify(); };
  window.addEventListener(eventName,changed);window.addEventListener("storage",storage);media.addEventListener("change",system);
  return ()=>{window.removeEventListener(eventName,changed);window.removeEventListener("storage",storage);media.removeEventListener("change",system)};
}
function toggleTheme() {
  const next = snapshot() === "dark" ? "light" : "dark";
  apply(next);try { localStorage.setItem(key,next); } catch { /* Keep the current document preference. */ }
  window.dispatchEvent(new Event(eventName));
}
export function useTheme() { return {theme:useSyncExternalStore(subscribe,snapshot),toggleTheme}; }

export function applyAccountTheme(theme: "light"|"dark") {apply(theme);window.dispatchEvent(new Event(eventName));}
