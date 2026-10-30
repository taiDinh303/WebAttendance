import { useEffect, type ReactNode } from "react";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { authApi } from "../infrastructure/authApi";
import { ApiError } from "../infrastructure/http";
import { SessionContext as Context } from "./sessionContext";
export function SessionProvider({ children }: { children: ReactNode }) {
  const client = useQueryClient();
  const query = useQuery({
    queryKey: ["session"],
    queryFn: async ({ signal }) => {
      try {
        return await authApi.me(AbortSignal.any([signal, AbortSignal.timeout(60000)]));
      } catch (e) {
        if (e instanceof ApiError && e.status === 401) return null;
        throw e;
      }
    },
    retry: false,
    staleTime: 60000,
  });
  useEffect(() => {
    const expire = () => {
      void client.cancelQueries({ queryKey: ["private"] });
      client.removeQueries({ queryKey: ["private"] });
      client.setQueryData(["session"], null);
    };
    window.addEventListener("session-expired", expire);
    return () => window.removeEventListener("session-expired", expire);
  }, [client]);
  async function login(email: string, password: string) {
    await client.cancelQueries();
    client.removeQueries({ queryKey: ["private"] });
    const user = await authApi.login({ email, password });
    client.setQueryData(["session"], user);
    return user;
  }
  async function logout() {
    await authApi.logout();
    await client.cancelQueries();
    client.removeQueries({ queryKey: ["private"] });
    client.setQueryData(["session"], null);
  }
  async function reload() {
    await client.invalidateQueries({ queryKey: ["session"] });
  }
  return (
    <Context.Provider
      value={{
        user: query.data,
        loading: query.isLoading,
        error: query.error,
        login,
        logout,
        reload,
      }}
    >
      {children}
    </Context.Provider>
  );
}
