import { useState } from "react";
import { useQueryClient } from "@tanstack/react-query";
import { useResource } from "./queries";
import { useSession } from "./sessionContext";
import { request } from "../infrastructure/http";
export function useAccountPreference<T>(key: string, fallback: T) {
  const { user } = useSession();
  const client = useQueryClient();
  const [error, setError] = useState<unknown>(null);
  const [saving, setSaving] = useState(false);
  const query = useResource(["preference", key], (signal) =>
    request<{ value: T | null; version: number }>(
      "/me/preferences/" + encodeURIComponent(key),
      { signal },
    ),
  );
  const value = query.data?.value ?? fallback;
  async function save(next: T | ((previous: T) => T)): Promise<boolean> {
    if (!query.isSuccess || saving) return false;
    setSaving(true);
    setError(null);
    try {
      const result = await request<{ value: T; version: number }>(
        "/me/preferences/" + encodeURIComponent(key),
        {
          method: "PUT",
          body: {
            value:
              typeof next === "function" ? (next as (v: T) => T)(value) : next,
            expectedVersion: query.data.version,
          },
        },
      );
      client.setQueryData(["private", user?.id, "preference", key], result);
      return true;
    } catch (e) {
      setError(e);
      return false;
    } finally {
      setSaving(false);
    }
  }
  return {
    value,
    save,
    error: error ?? query.error,
    saving,
    ready: query.isSuccess,
  };
}
