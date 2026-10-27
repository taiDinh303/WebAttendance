import { learningApi } from "../infrastructure/learningApi";
import type { PrivateFile, Submission, Revision } from "../domain/models";
export interface SubmissionGateway {
  upload: (
    c: string,
    a: string,
    purpose: string,
    file: File,
  ) => Promise<PrivateFile>;
  submit: (
    c: string,
    a: string,
    body: { fileIds: string[]; note: string },
    key: string,
  ) => Promise<{ submission: Submission; revision: Revision }>;
}
export class SubmissionFlow {
  private staged = new Map<File, PrivateFile>();
  private pending: {
    key: string;
    body: { fileIds: string[]; note: string };
  } | null = null;
  constructor(
    private classId: string,
    private assignmentId: string,
    private gateway: SubmissionGateway = learningApi,
    private keyFactory: () => string = () => crypto.randomUUID(),
  ) {}
  get locked() {
    return this.pending !== null;
  }
  resetFiles() {
    if (this.locked)
      throw Error("Kiểm tra lần nộp đang chờ xác nhận trước khi đổi file.");
    this.staged.clear();
  }
  async send(
    files: File[],
    note: string,
    progress: (
      stage: "upload" | "submit",
      current: number,
      total: number,
    ) => void = () => {},
  ) {
    if (!this.pending) {
      if (files.length < 1 || files.length > 5)
        throw Error("Chọn từ 1 đến 5 file.");
      for (const file of files)
        if (file.size > 20 * 1024 * 1024)
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
