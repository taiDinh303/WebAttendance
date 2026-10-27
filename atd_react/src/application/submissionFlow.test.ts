import { describe, it, expect, vi } from "vitest";
import { SubmissionFlow, type SubmissionGateway } from "./submissionFlow";
import type { PrivateFile, Revision, Submission } from "../domain/models";
const file = new File(["My answer"], "answer.txt", { type: "text/plain" });
const stored = {
  id: "file-1",
  originalName: "answer.txt",
  mimeType: "text/plain",
  sizeBytes: 9,
} as PrivateFile;
const receipt = {
  submission: { id: "submission-1" } as Submission,
  revision: { id: "revision-1" } as Revision,
};
describe("SubmissionFlow", () => {
  it("reuses the immutable receipt key and uploaded files after a lost response", async () => {
    const upload = vi.fn().mockResolvedValue(stored);
    const submit = vi
      .fn()
      .mockRejectedValueOnce(Error("lost ACK"))
      .mockResolvedValue(receipt);
    const flow = new SubmissionFlow(
      "class-1",
      "assignment-1",
      { upload, submit },
      () => "fixed-key",
    );
    await expect(flow.send([file], "Original note")).rejects.toThrow(
      "lost ACK",
    );
    expect(flow.locked).toBe(true);
    await expect(flow.send([file], "Changed disabled note")).resolves.toBe(
      receipt,
    );
    expect(upload).toHaveBeenCalledTimes(1);
    expect(submit.mock.calls[0]).toEqual(submit.mock.calls[1]);
    expect(submit.mock.calls[1][2]).toEqual({
      fileIds: ["file-1"],
      note: "Original note",
    });
    expect(() => flow.resetFiles()).toThrow();
  });
  it("rejects too many or oversized files before uploading", async () => {
    const gateway = { upload: vi.fn(), submit: vi.fn() } as SubmissionGateway;
    const flow = new SubmissionFlow("c", "a", gateway);
    await expect(flow.send(Array(6).fill(file), "")).rejects.toThrow();
    const large = new File([new Uint8Array(20 * 1024 * 1024 + 1)], "large.txt");
    await expect(flow.send([large], "")).rejects.toThrow("20 MiB");
    expect(gateway.upload).not.toHaveBeenCalled();
  });
  it("keeps successful staging when the next upload fails", async () => {
    const second = new File(["second"], "second.txt");
    const upload = vi
      .fn()
      .mockResolvedValueOnce(stored)
      .mockRejectedValueOnce(Error("upload failed"))
      .mockResolvedValueOnce({ ...stored, id: "file-2" });
    const submit = vi.fn().mockResolvedValue(receipt);
    const flow = new SubmissionFlow("c", "a", { upload, submit }, () => "key");
    await expect(flow.send([file, second], "")).rejects.toThrow();
    expect(flow.locked).toBe(false);
    await flow.send([file, second], "");
    expect(upload).toHaveBeenCalledTimes(3);
    expect(submit.mock.calls[0][2].fileIds).toEqual(["file-1", "file-2"]);
  });
  it("shares concurrent sends without duplicate uploads or receipt keys", async () => {
    let finishUpload!: (value: PrivateFile) => void;
    const upload = vi.fn(
      () =>
        new Promise<PrivateFile>((resolve) => {
          finishUpload = resolve;
        }),
    );
    const submit = vi.fn().mockResolvedValue(receipt);
    const keyFactory = vi.fn(() => "one-key");
    const flow = new SubmissionFlow(
      "class-1",
      "assignment-1",
      { upload, submit },
      keyFactory,
    );
    const files = [file];
    const first = flow.send(files, "Original note");
    expect(flow.locked).toBe(true);
    expect(() => flow.resetFiles()).toThrow();
    const second = flow.send(
      [new File(["different"], "other.txt")],
      "Changed note",
    );
    files.length = 0;
    expect(first).toBe(second);
    finishUpload(stored);
    await expect(first).resolves.toBe(receipt);
    expect(upload).toHaveBeenCalledTimes(1);
    expect(keyFactory).toHaveBeenCalledTimes(1);
    expect(submit).toHaveBeenCalledExactlyOnceWith(
      "class-1",
      "assignment-1",
      {
        fileIds: ["file-1"],
        note: "Original note",
      },
      "one-key",
    );
  });
  it("rejects empty selection without calling the gateway", async () => {
    const gateway = { upload: vi.fn(), submit: vi.fn() };
    const flow = new SubmissionFlow("c", "a", gateway);
    await expect(flow.send([], "")).rejects.toThrow("1 đến 5");
    expect(flow.locked).toBe(false);
    expect(gateway.upload).not.toHaveBeenCalled();
    expect(gateway.submit).not.toHaveBeenCalled();
  });
  it("accepts exactly five files of at most 20 MiB", async () => {
    const boundary = new File([new Uint8Array(20 * 1024 * 1024)], "limit.txt");
    const files = [
      boundary,
      ...Array.from(
        { length: 4 },
        (_, index) => new File(["x"], `${index}.txt`),
      ),
    ];
    const upload = vi
      .fn()
      .mockImplementation(async (_c, _a, _purpose, selected: File) => ({
        ...stored,
        id: selected.name,
      }));
    const submit = vi.fn().mockResolvedValue(receipt);
    const flow = new SubmissionFlow("c", "a", { upload, submit });
    await expect(flow.send(files, "")).resolves.toBe(receipt);
    expect(upload).toHaveBeenCalledTimes(5);
    expect(submit.mock.calls[0][2].fileIds).toEqual(
      files.map((selected) => selected.name),
    );
  });
  it("can discard staged uploads after a failed upload and choose new files", async () => {
    const second = new File(["second"], "second.txt");
    const upload = vi
      .fn()
      .mockResolvedValueOnce(stored)
      .mockRejectedValueOnce(Error("offline"))
      .mockResolvedValueOnce({ ...stored, id: "replacement" });
    const submit = vi.fn().mockResolvedValue(receipt);
    const flow = new SubmissionFlow("c", "a", { upload, submit });
    await expect(flow.send([file, second], "")).rejects.toThrow("offline");
    flow.resetFiles();
    await flow.send([file], "new note");
    expect(submit.mock.calls[0][2]).toEqual({
      fileIds: ["replacement"],
      note: "new note",
    });
  });
});
