import React from "react";
import ReactDOM from "react-dom/client";
import "@fontsource-variable/manrope";
import { App } from "./app/App";
import "./shared/styles/tokens.css";
import "./shared/styles/global.css";
import "./shared/styles/workspace.css";
import "./shared/styles/admin.css";
ReactDOM.createRoot(document.getElementById("root")!).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>,
);
