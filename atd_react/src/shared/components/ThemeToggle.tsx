import { Moon, Sun } from "lucide-react";
import { useTheme } from "../useTheme";
export function ThemeToggle() {
  const {theme,toggleTheme}=useTheme();
  const label=theme === "dark" ? "Chuyển sang chế độ sáng" : "Chuyển sang chế độ tối";
  return <button type="button" className="icon-button theme-toggle" aria-label={label} title={label} aria-pressed={theme === "dark"} onClick={toggleTheme}>{theme === "dark" ? <Sun size={19}/> : <Moon size={19}/>}</button>;
}
