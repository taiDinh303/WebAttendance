export type Role = "admin" | "teacher" | "student";
export interface User {
  id: string;
  displayName: string;
  email: string;
  role: Role;
  emailVerified: boolean;
  accountStatus: string;
  phone: string | null;
  gender?: "male" | "female" | "other" | "undisclosed" | null;
  birthday?: string | null;
  avatarKey?: string | null;
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
  membershipVersion?: number;
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
  sessionTitle?: string | null;
  studentName?: string;
}
export interface AttendanceAudit {
  id: string;
  action: string;
  actorId: string | null;
  reason: string | null;
  before: { status: string; recordedSource: string; version: number } | null;
  after: { status: string; recordedSource: string; version: number };
  createdAt: string;
}
export interface Dashboard {
  role: Role;
  serverTime: string;
  timezone: string;
  summary: {
    activeClasses: number;
    todaySessions: number;
    upcomingSessions: number;
    unreadCount: number;
    pendingRequests?: number;
    pendingReviews?: number;
    pendingAssignments?: number;
    publishedGrades?: number;
  };
  classes: Classroom[];
  sessions: (Session & { className: string })[];
  requests?: { classId: string; className: string; pendingCount: number }[];
  reviews?: {
    submissionId: string;
    assignmentId: string;
    classId: string;
    title: string;
  }[];
  assignments?: {
    id: string;
    classId: string;
    className: string;
    title: string;
    dueAt: string | null;
    allowLate: boolean;
  }[];
  grades?: {
    submissionId: string;
    title: string;
    score: number;
    publishedAt: string;
  }[];
}
export interface CatalogItem {
  id: string;
  name: string;
  code?: string | null;
  version: number;
  startDate?: string;
  endDate?: string;
  isActive?: boolean;
}
export type Fields = Record<string, string[]>;

export interface AdminUser {
  id: string;
  displayName: string;
  email: string;
  role: Role;
  accountStatus: "active" | "locked";
  emailVerified: boolean;
  version: number;
  createdAt: string;
  isSelf: boolean;
}
export interface AdminSummary {
  users: number;
  admins: number;
  teachers: number;
  students: number;
  lockedAccounts: number;
  classes: number;
  activeClasses: number;
  assignments: number;
  submissions: number;
  attendanceRecords: number;
  openAttendanceWindows: number;
  adminEvents: number;
  serverTime: string;
}
export interface AdminAccountEvent {
  id: string;
  actorId: string;
  actorName: string;
  subjectId: string;
  subjectName: string;
  operation: "lock" | "unlock";
  reason: string;
  before: { accountStatus: "active" | "locked"; version: number };
  after: { accountStatus: "active" | "locked"; version: number };
  createdAt: string;
}
