import { useEffect, useId, useRef, type ReactNode } from "react";
import { createPortal } from "react-dom";
export function Modal({
  title,
  onClose,
  children,
}: {
  title: string;
  onClose: () => void;
  children: ReactNode;
}) {
  const ref = useRef<HTMLDialogElement>(null);
  const titleId = useId();
  useEffect(() => {
    const dialog = ref.current;
    const previous = document.activeElement as HTMLElement | null;
    dialog?.showModal();
    const overflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    return () => {
      dialog?.close();
      document.body.style.overflow = overflow;
      if (previous?.isConnected) previous.focus();
    };
  }, []);
  return createPortal(
    <dialog
      ref={ref}
      className="app-modal"
      aria-labelledby={titleId}
      onCancel={(e) => {
        e.preventDefault();
        if (!ref.current?.querySelector('fieldset[aria-busy="true"]'))
          onClose();
      }}
    >
      <header>
        <button className="button secondary small" style={{float:"right"}} onClick={onClose}>Thu gọn</button>
        <h2 id={titleId}>{title}</h2>
      </header>
      <div className="modal-body">{children}</div>
    </dialog>,
    document.body,
  );
}
