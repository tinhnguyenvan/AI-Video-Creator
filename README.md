# 🎬 AI Video Creator

Ứng dụng tạo video bằng AI sử dụng **Google AI Studio (Veo 3.1)** — biến ý tưởng thành video chỉ với một đoạn mô tả.

<img width="1468" height="836" alt="Screenshot 2026-02-22 at 4 52 26 PM" src="https://github.com/user-attachments/assets/c5a13d7c-7fce-4cd4-bd60-b66b8322e50f" />


## ✨ Tính năng

- **Tạo video từ text** — Nhập prompt mô tả, AI tự động tạo video
- **Tạo video từ ảnh** — Upload ảnh tham chiếu để tạo video (Image-to-Video)
- **Tùy chỉnh linh hoạt** — Chọn tỉ lệ (16:9, 9:16, 1:1), thời lượng (5-8 giây), độ phân giải (720p/1080p)
- **Theo dõi tiến trình** — Auto-polling trạng thái, hiển thị realtime khi video hoàn thành
- **Dashboard trực quan** — Thống kê tổng quan, danh sách video dạng grid
- **Quản lý video** — Xem, tải xuống, xóa, thử lại khi thất bại
- **Kiểm tra kết nối API** — Test connection ngay từ trang cài đặt

## 🛠 Công nghệ

| Stack | Phiên bản |
|---|---|
| Laravel | 12.x |
| PHP | 8.2+ |
| Bootstrap | 5.3.3 |
| Google AI Studio | Veo 3.1 (REST API) |
| Database | SQLite (mặc định) |

## 📋 Yêu cầu

- PHP >= 8.2
- Composer
- Google AI Studio API Key ([Lấy tại đây](https://aistudio.google.com/apikey))

## 🚀 Cài đặt

### 1. Clone project

```bash
git clone <repo-url> app_create_video
cd app_create_video
```

### 2. Cài đặt dependencies

```bash
composer install
```

### 3. Cấu hình môi trường

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Thêm API Key

Mở file `.env` và thêm API key của bạn:

```env
GOOGLE_AI_STUDIO_API_KEY=your_api_key_here
```

> 💡 Lấy API Key miễn phí tại: https://aistudio.google.com/apikey

### 5. Chạy migration & tạo storage link

```bash
php artisan migrate
php artisan storage:link
```

### 6. Khởi động ứng dụng

```bash
php artisan serve
```

Truy cập: **http://localhost:8000**

## 📖 Hướng dẫn sử dụng

### Tạo video mới

1. Bấm **"Tạo Video Mới"** trên thanh điều hướng hoặc Dashboard
2. Nhập **tiêu đề** cho video
3. Viết **prompt mô tả** chi tiết video bạn muốn tạo
4. Chọn **tỉ lệ khung hình**, **thời lượng**, **độ phân giải**
5. (Tùy chọn) Upload **ảnh tham chiếu** để tạo video từ hình ảnh
6. Bấm **"Tạo Video"** và chờ AI xử lý (thường 2-5 phút)

### Mẹo viết prompt hiệu quả

- Mô tả rõ **chủ thể**, **hành động** và **bối cảnh**
- Dùng thuật ngữ quay phim: `"aerial shot"`, `"close-up"`, `"slow motion"`, `"tracking shot"`
- Thêm mô tả **ánh sáng**, **màu sắc**, **phong cách**: `"cinematic lighting"`, `"warm tones"`
- Ghi rõ **chuyển động camera**: `"panning left"`, `"zooming in"`, `"dolly forward"`

### Ví dụ prompt

```
Cảnh quay drone từ trên cao về một bãi biển nhiệt đới lúc hoàng hôn. 
Nước biển trong xanh, sóng nhẹ nhàng vỗ bờ cát trắng. Ánh nắng vàng 
cam chiếu xuống mặt nước tạo phản chiếu lấp lánh. Camera bay chậm 
dọc bờ biển, phong cách cinematic, 4K.
```

### Quản lý video

- **Dashboard** — Xem tất cả video với trạng thái (Chờ xử lý / Đang tạo / Hoàn thành / Thất bại)
- **Chi tiết** — Xem video, thông tin prompt, tải xuống
- **Thử lại** — Tạo lại video bị lỗi chỉ với 1 click
- **Cài đặt** — Kiểm tra kết nối API, xem hướng dẫn cấu hình

## 📁 Cấu trúc chính

```
app/
├── Http/Controllers/
│   ├── VideoController.php      # CRUD & xử lý video
│   └── SettingsController.php   # Trang cài đặt
├── Models/
│   └── Video.php                # Model video
└── Services/
    └── GoogleAIStudioService.php # Gọi Google AI Studio API

resources/views/
├── layouts/app.blade.php        # Layout chính (Bootstrap 5.3)
├── videos/
│   ├── index.blade.php          # Dashboard
│   ├── create.blade.php         # Form tạo video
│   └── show.blade.php           # Chi tiết video
└── settings/
    └── index.blade.php          # Trang cài đặt

config/
└── services_google.php          # Cấu hình Google AI Studio
```

## ⚙️ Cấu hình nâng cao

Có thể thay đổi model AI trong `.env`:

```env
GOOGLE_AI_STUDIO_MODEL=veo-3.1-generate-preview
```

## 📄 License

MIT
