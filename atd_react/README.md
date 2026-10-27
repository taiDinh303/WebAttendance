# Attendance React

React TypeScript/Vite frontend của AttendanceWeb. Đặc tả, phân tầng, trạng thái và giới hạn được quản lý ở [FRONTEND-IMPLEMENTATION](../docs/FRONTEND-IMPLEMENTATION.md), [API-CURRENT](../docs/API-CURRENT.md) và [React evidence](../docs/evidence/react-validation.md).

Node24.x, `npm ci`, `npm run dev` tại folder này. Mở http://127.0.0.1:5173; Laravel cần chạy8000. Kiểm tra: `npm run typecheck`, `npm run lint`, `npm run test`, `npm run build`. Browser E2E dùng fixture riêng theo docs, không chạy vào database dev/production.

Không lưu bearer token/credentials vào localStorage hoặc bundle. `VITE_*` luôn public. Chưa có deploy production.
