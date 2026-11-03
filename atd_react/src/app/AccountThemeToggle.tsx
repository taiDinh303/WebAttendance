import { useEffect } from "react";
import { Moon, Sun } from "lucide-react";
import { useAccountPreference } from "../application/accountPreference";
import { applyAccountTheme } from "../shared/useTheme";
import { ErrorNotice } from "../shared/ui";
export function AccountThemeToggle() {
  const fallback: "light" | "dark" = matchMedia("(prefers-color-scheme: dark)")
    .matches
    ? "dark"
    : "light";
  const pref = useAccountPreference("theme", fallback);
  useEffect(() => applyAccountTheme(pref.value), [pref.value]);
  return (
    <span>
      <button
        className="icon-button"
        disabled={!pref.ready || pref.saving}
        aria-label={
          pref.value === "dark"
            ? "Chuyển sang chế độ sáng"
            : "Chuyển sang chế độ tối"
        }
        onClick={() => void pref.save(pref.value === "dark" ? "light" : "dark")}
      >
        {pref.value === "dark" ? <Sun size={19} /> : <Moon size={19} />}
      </button>
      <ErrorNotice error={pref.error} />
    </span>
  );
}
