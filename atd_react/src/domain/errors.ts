import type { Fields } from "./models";
export class ApiError extends Error {
  constructor(
    public status: number,
    public code: string,
    message: string,
    public fields: Fields = {},
    public traceId?: string,
  ) {
    super(message);
    this.name = "ApiError";
  }
}
