import type { PrivateFile, Submission, Revision } from "../domain/models";

type SubmissionBody = { fileIds: string[]; note: string };
type SubmissionReceipt = { submission: Submission; revision: Revision };
type SubmissionProgress = (
  stage: "upload" | "submit",
  current: number,
  total: number,
) => void;

const MAX_FILES = 5;
const MAX_FILE_BYTES = 20 * 1024 * 1024;

export interface SubmissionGateway {
  upload: (
    classId: string,
    assignmentId: string,
    purpose: string,
    file: File,
  ) => Promise<PrivateFile>;
  submit: (
    classId: string,
    assignmentId: string,
    body: SubmissionBody,
    key: string,
  ) => Promise<SubmissionReceipt>;
}
export class SubmissionFlow {
  private active: Promise<SubmissionReceipt> | null = null;
  private readonly staged = new Map<File, PrivateFile>();
  private pending: {
    key: string;
    body: SubmissionBody;
  } | null = null;
  constructor(
    private readonly classId: string,
    private readonly assignmentId: string,
    private readonly gateway: SubmissionGateway,
    private readonly keyFactory: () => string = () => crypto.randomUUID(),
  ) {}
  get locked(): boolean {
    return this.pending !== null || this.active !== null;
  }
  resetFiles(): void {
    if (this.locked)
      throw Error("Kiểm tra lần nộp đang chờ xác nhận trước khi đổi file.");
    this.staged.clear();
  }
  send(
    files: File[],
    note: string,
    progress: SubmissionProgress = () => {},
  ): Promise<SubmissionReceipt> {
    if (this.active) return this.active;
    this.active = this.performSend([...files], note, progress).finally(() => {
      this.active = null;
    });
    return this.active;
  }
  private async performSend(
    files: File[],
    note: string,
    progress: SubmissionProgress,
  ): Promise<SubmissionReceipt> {
    if (!this.pending) {
      if (files.length < 1 || files.length > MAX_FILES)
        throw Error("Chọn từ 1 đến 5 file.");
      for (const file of files)
        if (file.size > MAX_FILE_BYTES)
          throw Error(file.name + " vượt giới hạn 20 MiB.");
      for (const [index, file] of files.entries())
        if (!this.staged.has(file)) {
          progress("upload", index + 1, files.length);
          this.staged.set(
            file,
            await this.gateway.upload(
              this.classId,
              this.assignmentId,
              "submission",
              file,
            ),
          );
        }
      this.pending = {
        key: this.keyFactory(),
        body: { fileIds: files.map((f) => this.staged.get(f)!.id), note },
      };
    }
    progress("submit", 0, 0);
    return this.gateway.submit(
      this.classId,
      this.assignmentId,
      this.pending.body,
      this.pending.key,
    );
  }
}
