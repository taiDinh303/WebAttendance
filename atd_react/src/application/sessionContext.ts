import { createContext, useContext } from "react";
import type { User } from "../domain/models";
export interface SessionState {
  user: User | null | undefined;
  loading: boolean;
  error: Error | null;
  login: (email: string, password: string) => Promise<User>;
  logout: () => Promise<void>;
  reload: () => Promise<void>;
}
export const SessionContext = createContext<SessionState | null>(null);
export function useSession() {
  const session = useContext(SessionContext);
  if (!session) throw Error("SessionProvider missing");
  return session;
}
