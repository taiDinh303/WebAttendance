import { useQuery, useQueryClient } from "@tanstack/react-query";
import { useSearchParams } from "react-router-dom";
import { useSession } from "./sessionContext";
export function useResource<T>(
  key: readonly unknown[],
  load: (signal: AbortSignal) => Promise<T>,
  options: { enabled?: boolean; refetchInterval?: number | false } = {},
) {
  const { user } = useSession();
  return useQuery({
    queryKey: ["private", user?.id, ...key],
    queryFn: ({ signal }) => load(signal),
    retry: false,
    ...options,
    enabled: !!user?.emailVerified && (options.enabled ?? true),
  });
}
export function useRefresh() {
  const client = useQueryClient();
  const { user } = useSession();
  return () => client.invalidateQueries({ queryKey: ["private", user?.id] });
}
export function usePage() {
  const [search, setSearch] = useSearchParams();
  const raw = Number(search.get("page") ?? 1);
  const page = Number.isSafeInteger(raw) && raw > 0 ? raw : 1;
  return {
    page,
    search,
    setSearch,
    setPage: (value: number) =>
      setSearch((prev) => {
        const next = new URLSearchParams(prev);
        next.set("page", String(value));
        return next;
      }),
  };
}
