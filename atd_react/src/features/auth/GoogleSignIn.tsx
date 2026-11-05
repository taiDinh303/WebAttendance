import { useEffect, useRef, useState } from "react";
import { authApi } from "../../infrastructure/authApi";

type GoogleIdentity = {
  initialize: (options: {
    client_id: string;
    nonce: string;
    callback: (response: { credential: string }) => void;
    auto_select: boolean;
  }) => void;
  renderButton: (
    element: HTMLElement,
    options: {
      theme: string;
      size: string;
      text: string;
      width: number;
      locale: string;
    },
  ) => void;
};
declare global {
  interface Window {
    google?: { accounts: { id: GoogleIdentity } };
  }
}
let scriptPending: Promise<void> | undefined;
function loadGoogle(): Promise<void> {
  if (window.google?.accounts.id) return Promise.resolve();
  scriptPending ??= new Promise<void>((resolve, reject) => {
    const script = document.createElement("script");
    const timeout = window.setTimeout(() => fail(), 20000);
    const fail = () => {
      window.clearTimeout(timeout);
      script.remove();
      reject(
        new Error(
          "Không tải được Google. Kiểm tra kết nối hoặc tiện ích chặn nội dung rồi thử lại.",
        ),
      );
    };
    script.src = "https://accounts.google.com/gsi/client";
    script.async = true;
    script.onload = () => {
      window.clearTimeout(timeout);
      if (window.google?.accounts.id) resolve();
      else fail();
    };
    script.onerror = fail;
    document.head.append(script);
  }).catch((error: unknown) => {
    scriptPending = undefined;
    throw error;
  });
  return scriptPending;
}
export function GoogleSignIn({
  onCredential,
}: {
  onCredential: (credential: string) => Promise<void>;
}) {
  const clientId = import.meta.env.VITE_GOOGLE_CLIENT_ID as string | undefined;
  const container = useRef<HTMLDivElement>(null);
  const callback = useRef(onCredential);
  const busy = useRef(false);
  const [attempt, setAttempt] = useState(0);
  const [pending, setPending] = useState(false);
  const [ready, setReady] = useState(false);
  const [error, setError] = useState("");
  useEffect(() => {
    callback.current = onCredential;
  }, [onCredential]);
  useEffect(() => {
    if (!clientId) return;
    let disposed = false;
    const element = container.current;
    async function initialize() {
      try {
        await loadGoogle();
        if (disposed) return;
        const challenge = await authApi.googleChallenge();
        if (disposed || !element || !window.google) return;
        if (challenge.clientId !== clientId)
          throw new Error(
            "Cấu hình Google trên web và máy chủ chưa trùng nhau.",
          );
        window.google.accounts.id.initialize({
          client_id: clientId!,
          nonce: challenge.nonce,
          auto_select: false,
          callback: (response) => {
            if (disposed || busy.current) return;
            busy.current = true;
            setPending(true);
            void callback
              .current(response.credential)
              .catch((failure: unknown) => {
                if (!disposed)
                  setError(
                    failure instanceof Error
                      ? failure.message
                      : "Không đăng nhập được bằng Google.",
                  );
              })
              .finally(() => {
                busy.current = false;
                if (!disposed) setPending(false);
              });
          },
        });
        element.replaceChildren();
        window.google.accounts.id.renderButton(element, {
          theme: "outline",
          size: "large",
          text: "continue_with",
          width: Math.min(360, element.clientWidth || 300),
          locale: "vi",
        });
        setReady(true);
      } catch (failure) {
        if (!disposed)
          setError(
            failure instanceof Error
              ? failure.message
              : "Không tải được Google.",
          );
      }
    }
    void initialize();
    return () => {
      disposed = true;
      element?.replaceChildren();
    };
  }, [clientId, attempt]);
  if (!clientId)
    return <p className="muted">Đăng nhập Google chưa được cấu hình.</p>;
  return (
    <div className="google-auth-control" aria-busy={pending}>
      <div ref={container} hidden={Boolean(error) || pending} />
      {!ready && !error ? <p role="status">Đang chuẩn bị Google…</p> : null}
      {pending ? <p role="status">Đang xác minh tài khoản Google…</p> : null}
      {error ? (
        <>
          <p role="alert">{error}</p>
          <button
            type="button"
            className="button secondary"
            onClick={() => {
              setError("");
              setReady(false);
              setAttempt((value) => value + 1);
            }}
          >
            Thử lại Google
          </button>
        </>
      ) : null}
    </div>
  );
}
