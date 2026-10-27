export type Role = "teacher" | "student";
export interface User {
  id: string;
  displayName: string;
  email: string;
  role: Role;
  emailVerified: boolean;
  accountStatus: string;
  phone: string | null;
  locale: string;
  timezone: string;
  version: number;
}
export interface Page<T> {
  items: T[];
  pageNumber: number;
  pageSize: number;
  totalItems: number;
  totalPages: number;
  hasNextPage: boolean;
  hasPreviousPage: boolean;
}
export interface Classroom {
  id: string;
  name: string;
  description: string | null;
  timezone: string;
  status: string;
  version: number;
  courseId: string | null;
  semesterId: string | null;
}
export interface Membership {
  id: string;
  classId: string;
  studentId: string;
  studentName?: string;
  className?: string;
  status: string;
  version: number;
  reviewNote?: string;
}
export interface Session {
  id: string;
  classId: string;
  title: string | null;
  room: string | null;
  startsAt: string;
  endsAt: string;
  status: string;
  version: number;
}
export interface Assignment {
  id: string;
  classId: string;
  title: string;
  instructions: string | null;
  dueAt: string | null;
  allowLate: boolean;
  allowResubmit: boolean;
  state: string;
  version: number;
  maxScore: number;
}
export interface PrivateFile {
  id: string;
  originalName: string;
  mimeType: string;
  sizeBytes: number;
  scanState?: string;
}
export interface Publication {
  id: string;
  score: number;
  feedback: string | null;
  assignmentId: string;
  revisionId: string;
  publishedAt: string;
}
export interface Submission {
  studentName?: string;
  id: string;
  assignmentId: string;
  studentId: string;
  latestRevisionId: string;
  version: number;
  publishedGrade: Publication | null;
}
export interface Revision {
  id: string;
  revisionNo: number;
  note: string | null;
  submittedAt: string;
  isLate: boolean;
  files: PrivateFile[];
}
export interface Grade {
  id: string;
  revisionId: string;
  score: number;
  feedback: string | null;
  state: string;
  version: number;
}
export interface GradeRow {
  studentId: string;
  studentName: string;
  membershipStatus: string;
  average: number | null;
  gradedCount: number;
}
export interface Notice {
  id: string;
  type: string;
  data: { classId?: string; assignmentId?: string };
  readAt: string | null;
  createdAt: string;
}
export interface Window {
  id: string;
  sessionId: string;
  opensAt: string;
  closesAt: string;
  lateAfter: string | null;
  state: string;
  version: number;
  serverTime: string;
}
export interface Qr {
  qrToken: string;
  validUntil: string;
  rotatesAt: string;
  serverTime: string;
}
export interface RosterRow {
  studentId: string;
  studentName: string;
  recordId: string | null;
  status: string;
  recordVersion: number;
}
export interface Attendance {
  id: string;
  studentId: string;
  sessionId: string;
  status: string;
  checkedInAt: string | null;
  version: number;
  sessionStartsAt?: string;
}
export interface CatalogItem {
  id: string;
  name: string;
  code?: string | null;
  version: number;
  startDate?: string;
  endDate?: string;
}
export type Fields = Record<string, string[]>;
