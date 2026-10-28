import type { Role, User } from "./models";

export const roleLabel = (role: Role): string =>
  ({
    admin: "Quản trị viên",
    teacher: "Giáo viên",
    student: "Học sinh",
  })[role];

export function canAdminister(
  user:
    Pick<User, "role" | "accountStatus" | "emailVerified"> | null | undefined,
): boolean {
  return (
    user?.role === "admin" &&
    user.accountStatus === "active" &&
    user.emailVerified
  );
}
