# Booking Sports Web – Checklist & Laravel Rules

> Stack: Laravel 11 (API) + Nuxt 3 (SPA). DB: MySQL. Cache/Queue: Redis + Horizon. Auth: Sanctum (SPA) or JWT (alt). Realtime (stretch): Soketi/Pusher.

---

## 📋 MVP Checklist

- [ ] **Khởi tạo & DevOps**
  - [ ] Init repo Laravel + Nuxt + Docker Compose
  - [ ] Thiết lập `.env`, `Makefile`, `.editorconfig`
  - [ ] Sanctum SPA (CORS, CSRF, cookie)
  - [ ] Redis + Horizon (queue) + Telescope (dev)
  - [ ] Pint + ESLint + Prettier + Husky (pre-commit)

- [ ] **Auth & Roles**
  - [ ] Đăng ký / Đăng nhập / Đổi mật khẩu
  - [ ] Spatie Permission: `Player`, `Owner`, `Admin`
  - [ ] Hồ sơ cơ bản user (level, sports, vị trí)

- [ ] **Venues & Courts**
  - [ ] Migrations/Models: `sports`, `venues`, `courts`
  - [ ] CRUD API cho owner quản lý sân/court
  - [ ] Upload ảnh venue (local → S3 sau)

- [ ] **Pricing & Availability**
  - [ ] Migrations/Model: `pricing_rules` (DOW, time range, price, slot_minutes)
  - [ ] Lệnh artisan generate `time_slots` theo rules
  - [ ] API: `GET /courts/{id}/availability?date=YYYY-MM-DD`
  - [ ] **Anti double-booking:** Transaction + `SELECT ... FOR UPDATE` + unique index `(court_id, date, slot_start)`

- [ ] **Booking & Payment**
  - [ ] API `POST /bookings` (pending)
  - [ ] Mock payment (checkout) + webhook (confirm `paid`)
  - [ ] Chính sách hủy cơ bản (trước X giờ)
  - [ ] Lịch sử booking của user (`GET /me/bookings`)

- [ ] **Notifications**
  - [ ] Mail template (xác nhận/nhắc lịch)
  - [ ] Push notification (FCM web)
  - [ ] Reminder job T–2h (queue + retry policy)

- [ ] **Frontend (Nuxt – MVP)**
  - [ ] Layout + Auth guard + Axios baseURL
  - [ ] Trang tìm sân (map/list/filter theo sport, distance, date)
  - [ ] Trang chi tiết court + Calendar slot picker
  - [ ] Flow đặt sân → checkout → kết quả
  - [ ] Trang “My bookings”

- [ ] **Chất lượng**
  - [ ] Policies/Authorization (row-level cho Owner)
  - [ ] Tests: unit (pricing), feature (booking overlap)
  - [ ] Seed dữ liệu demo
  - [ ] README (run dev/prod, tài khoản demo, ảnh chụp)

---

## ✨ Stretch Goals

- [ ] **Social Features**
  - [ ] API/UI: `match_posts` (bài tìm đồng đội)
  - [ ] Feed + filter (khu vực, level, khung giờ)
  - [ ] Join/leave → auto tạo chat room
  - [ ] Realtime chat (Soketi/Pusher)
  - [ ] Groups + invites

- [ ] **Chủ sân & Admin**
  - [ ] Owner dashboard (occupancy, revenue)
  - [ ] Quản lý nhanh pricing (copy ngày/tuần)
  - [ ] Admin: review venues, user management

- [ ] **Triển khai & Monitoring**
  - [ ] Deploy Docker lên VPS/Render
  - [ ] Sentry + Log JSON (prod)
  - [ ] Cloud: S3 (ảnh), SES (mail), FCM (push)

---

## 📦 API Sketch (tham chiếu nhanh)

**Auth**
- `POST /auth/login`, `POST /auth/logout`, `POST /password/forgot/reset`
- (Tùy chọn) `POST /2fa/enable/verify`

**Booking**
- `GET /sports`
- `GET /venues?lat&lng&radius&sport_id`
- `GET /venues/{id}/courts`
- `GET /courts/{id}/availability?date=YYYY-MM-DD`
- `POST /bookings` `{court_id,date,start_at,end_at}`
- `GET /me/bookings`, `DELETE /bookings/{id}`

**Payment**
- `POST /payments/checkout`
- `POST /payments/webhook` (HMAC)

**Owner/Admin**
- `POST /venues`, `POST /pricing-rules`
- `GET /bookings?venue_id&date`

---

## 🧰 Laravel Rules (Design Patterns, Validate, Security, etc.)

### 1) Kiến trúc & Design Patterns
- **Controller mỏng – Service/Action dày**  
  - Controller: nhận request, gọi Service, trả API response.
  - Service (hoặc Action): chứa business logic, có thể tách nhỏ theo use-case (e.g., `CreateBookingAction`).
- **Repository (tùy chọn)** cho truy vấn phức tạp/đa nguồn (DB, cache). Tránh over-abstraction nếu model đơn giản.
- **DTO / Data Objects**: xác định dữ liệu vào/ra rõ ràng giữa tầng (Spatie Data hoặc class DTO riêng).
- **Domain-first naming**: đặt tên theo ngôn ngữ nghiệp vụ (Booking, PricingRule, TimeSlot).
- **Event-Driven** (khi phù hợp): `BookingPaid`, `BookingCancelled` → Listener gửi mail/push.
- **Command/Query separation (CQS)**: Command (tạo/sửa), Query (đọc). Dễ test và tối ưu riêng.
- **Idempotency** cho các POST quan trọng (booking/payment): dùng header `Idempotency-Key` + table lưu key + TTL.

### 2) Validation & Requests
- Dùng **Form Request classes** (`php artisan make:request`) cho mỗi endpoint quan trọng.
- Quy tắc:
  - Validate kiểu dữ liệu, ràng buộc tồn tại (`exists:...`), enum (`in:`), ngày/giờ, **before/after**.
  - Tùy chỉnh message (i18n).  
  - **Authorize()** trong Form Request kết hợp Policy.
- Chuẩn hóa Response lỗi: mã 422 (validation), 401/403 (authz), 404 (not found). Trả JSON dạng thống nhất.

### 3) Security
- **Auth**: Sanctum SPA (cookie + CSRF) hoặc JWT (access + refresh). Không trộn lẫn.
- **Authorization**: Spatie Permission + **Policies** ở model (Owner chỉ truy cập venue của mình).
- **CSRF**: bật đầy đủ khi dùng cookie. Nếu JWT, tắt CSRF nhưng phải xử lý refresh token an toàn.
- **Mass Assignment**: dùng `$fillable` hoặc `$guarded = []` có chủ đích, không `Model::create($request->all())` bừa.
- **Rate Limiting**: limit cho login, booking, webhook (e.g., `RateLimiter::for('booking', ...)`).
- **SQL Injection**: chỉ dùng query builder/Eloquent, không nối chuỗi raw. Nếu `DB::raw`, dùng binding.
- **Webhooks**: verify HMAC (secret), **idempotent** (check tx_ref), log mọi request.
- **XSS**: Escape output phía frontend; nếu có blade, dùng `{{ }}` thay cho `{!! !!}` (trừ khi đã sanitize).
- **CORS**: chỉ allow origin hợp lệ, phương thức/headers cần thiết.
- **Secrets**: `.env` không push git; rotate định kỳ; `APP_KEY` phải set trên prod.
- **File Upload**: validate mime/size, lưu S3/private nếu nhạy cảm, generate URL tạm (signed URL).

### 4) Transactions & Concurrency
- Bao bọc logic ghi quan trọng trong **`DB::transaction()`**.
- **Anti double-booking**:  
  - Chuẩn hóa thành các `time_slots` cố định (ví dụ 60/90 phút).  
  - Lúc book: `SELECT ... FOR UPDATE` trên các slots liên quan, nếu slot đã booked → reject.  
  - Unique index `court_id+date+slot_start` để “khóa cứng” ở mức DB.
- Định nghĩa **retry** hợp lý: khi deadlock hoặc lỗi hạ tầng (queue, webhook).

### 5) Queue, Jobs & Notifications
- Dùng **Jobs** cho tác vụ chậm: mail, push, PDF, đồng bộ gateway.  
- Cấu hình **retry/backoff** phù hợp; jobs **idempotent**.  
- Dùng **Horizon** để quan sát; đặt tên queues theo domain (`emails`, `payments`, `reminders`).

### 6) Logging & Observability
- Log JSON trên production; thêm `request_id`/`user_id` vào context.  
- **Levels**: `info` cho business event (booking created), `warning` cho retryable, `error` cho fatal.  
- Sentry (error tracking), Telescope (dev), Health check endpoints.

### 7) Eloquent & Database
- **Indexing**: thêm index cho cột lọc/join thường dùng (`court_id`, `date`, `slot_start`, `user_id`).  
- **Eager Loading** để tránh N+1 (`->with(...)`).  
- **Soft Deletes** khi cần, kèm constraint logic (không soft delete các bảng master quan trọng nếu phá khóa ngoại).  
- **Factories/Seeders**: tạo dữ liệu demo thực tế (pricing theo khung giờ, vài sân thật).

### 8) API Design
- **Versioning**: prefix `/api/v1/...` để dễ nâng cấp.  
- **Consistent JSON**: `{ "success": true, "data": ..., "error": null, "meta": { ... } }`.  
- **Pagination**: cursor/offset rõ ràng (`?page=`, `?per_page=`).  
- **Filtering/Sorting**: whitelist fields (`allowedFilters`, `allowedSorts`).  
- **OpenAPI/Swagger**: tạo docs (e.g., `l5-swagger`) → điểm cộng lớn khi phỏng vấn.

### 9) Performance & Caching
- Cache **availability** theo court/date trong 2–5 phút để giảm tải.  
- Cache pricing rules theo venue.  
- Sử dụng `response caching` (nếu phù hợp) và HTTP caching headers (ETag/Last-Modified).
- Dùng `chunk()`/`cursor()` cho xử lý lớn; tránh load all.  

### 10) Testing
- Unit: pricing calculator, slot generator.  
- Feature: booking overlap (simulate 2 request song song), webhook signature.  
- HTTP tests: auth, policies, rate limit.  
- Snapshot/Contract tests cho API JSON khi cần.

### 11) Code Style & Naming
- PSR-12, Laravel Pint default.  
- Tên rõ ràng: `CreateBookingAction`, `PricingRuleService`.  
- Controller theo resource: `index/show/store/update/destroy`.  
- Không tạo “God class”. Tách nhỏ, single responsibility.

### 12) Git Workflow
- Branch: `feat/`, `fix/`, `chore/`, `docs/`.  
- Commit message semantic: `feat(booking): lock slots with FOR UPDATE`.  
- PR nhỏ, có checklist/description, CI pass trước khi merge.

---

## ✅ Definition of Done (DoD)
- Endpoint có **FormRequest** + **Policy** + test tối thiểu.  
- Booking overlap đã **lock transaction** và có **unique index**.  
- Queue jobs có retry/backoff; webhook **idempotent**; logs có context.  
- README có hướng dẫn chạy, tài khoản demo, ảnh chụp UI.

