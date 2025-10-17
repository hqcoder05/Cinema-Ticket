# Cinema Ticket - Hệ Thống Đặt Vé Xem Phim Online

## 📋 Tổng Quan Dự Án

Ứng dụng Cinema Ticket là một hệ thống web hoàn chỉnh cho phép người dùng tìm kiếm, xem thông tin chi tiết phim, chọn rạp, lịch chiếu, ghế ngồi, và thanh toán vé xem phim trực tuyến.

---

## 🎯 Các Chức Năng Chính

### **F3, F4, F5 - Trang Chủ, Danh Sách & Chi Tiết Phim**
- **F3 (Index)**: Trang chủ hiển thị phim đang chiếu và phim sắp chiếu với giao diện Responsive Design
- **F4 (List)**: Danh sách phim với bộ lọc (tìm kiếm, thể loại, phim đang chiếu)
- **F5 (Details)**: Chi tiết phim với thông tin đầy đủ, trailer, và danh sách lịch chiếu

📁 **Files**:
- `Controllers/MovieController.cs` - Logic xử lý phim
- `Views/Movie/Index.cshtml` - Trang chủ
- `Views/Movie/List.cshtml` - Danh sách phim
- `Views/Movie/Details.cshtml` - Chi tiết phim
- `Models/Movie/Movie.cs` - Model Phim
- `Models/Movie/Screening.cs` - Model Lịch chiếu

---

### **F6 - Giao Diện Chọn Lịch Chiếu**
- Chọn rạp chiếu
- Chọn ngày và giờ chiếu
- Hiển thị giá vé và số ghế còn lại
- Tương tác API lấy dữ liệu lịch chiếu

📁 **Files**:
- `Controllers/ScreeningController.cs` - Logic xử lý lịch chiếu
- `Views/Screening/SelectCinema.cshtml` - Chọn rạp
- `Views/Screening/SelectDateTime.cshtml` - Chọn giờ chiếu
- `Models/Cinema/Cinema.cs` - Model Rạp
- `Models/Cinema/Screen.cs` - Model Phòng chiếu

---

### **F7 - Giao Diện Chọn Ghế Ngồi**
- Sơ đồ ghế tương tác (8 hàng × 10 cột)
- Hiển thị trạng thái ghế: Trống, Đã đặt, Đang chọn
- Tính toán tổng tiền theo số ghế chọn
- Hiển thị danh sách ghế được chọn

📁 **Files**:
- `Views/Screening/SelectSeats.cshtml` - Chọn ghế (có JavaScript tương tác)

---

### **F8 - Giao Diện Xác Nhận & Thanh Toán**
- Tóm tắt thông tin đơn hàng
- Form nhập thông tin khách hàng (Họ tên, Email, SĐT)
- Chọn phương thức thanh toán (Thẻ tín dụng, Chuyển khoản, Ví điện tử)
- Nút xác nhận thanh toán
- Trang xác nhận thành công

📁 **Files**:
- `Controllers/BookingController.cs` - Logic xử lý đặt vé
- `Views/Booking/Checkout.cshtml` - Trang thanh toán
- `Views/Booking/BookingConfirm.cshtml` - Xác nhận thành công

---

### **F1, F2, F9, F10 - Forms Cơ Bản & Quản Lý Tài Khoản**

#### **F2 - Đăng Ký & Đăng Nhập**
- Form đăng nhập: Email/SĐT, mật khẩu
- Form đăng ký: Họ tên, Email, Mật khẩu, Xác nhận mật khẩu
- Đăng nhập qua mạng xã hội (Facebook, Google)

📁 **Files**:
- `Views/Account/Login.cshtml` - Trang đăng nhập
- `Views/Account/Register.cshtml` - Trang đăng ký

#### **F10 - Quản Lý Tài Khoản**
- Xem và cập nhật thông tin tài khoản
- Đổi mật khẩu
- Xem lịch sử vé đặt
- Quản lý yêu thích

📁 **Files**:
- `Views/Account/Account.cshtml` - Quản lý tài khoản
- `Views/Account/BookingHistory.cshtml` - Lịch sử vé

📁 **Controller**:
- `Controllers/AccountController.cs` - Logic xử lý tài khoản

---

## 🗂️ Cấu Trúc Thư Mục

```
Cinema-Ticket/
├── Controllers/
│   ├── MovieController.cs        # F3, F4, F5
│   ├── ScreeningController.cs    # F6, F7
│   ├── BookingController.cs      # F8
│   ├── AccountController.cs      # F1, F2, F9, F10
│   └── HomeController.cs
│
├── Models/
│   ├── Movie/
│   │   ├── Movie.cs             # Model phim
│   │   └── Screening.cs         # Model lịch chiếu
│   ├── Cinema/
│   │   ├── Cinema.cs            # Model rạp
│   │   └── Screen.cs            # Model phòng chiếu
│   └── User/
│       └── (User models)
│
├── Views/
│   ├── Movie/
│   │   ├── Index.cshtml         # Trang chủ (F3)
│   │   ├── List.cshtml          # Danh sách phim (F4)
│   │   └── Details.cshtml       # Chi tiết phim (F5)
│   ├── Screening/
│   │   ├── SelectCinema.cshtml  # Chọn rạp (F6)
│   │   ├── SelectDateTime.cshtml # Chọn giờ (F6)
│   │   └── SelectSeats.cshtml   # Chọn ghế (F7)
│   ├── Booking/
│   │   ├── Checkout.cshtml      # Thanh toán (F8)
│   │   └── BookingConfirm.cshtml # Xác nhận (F8)
│   ├── Account/
│   │   ├── Login.cshtml         # Đăng nhập (F2)
│   │   ├── Register.cshtml      # Đăng ký (F2)
│   │   ├── Account.cshtml       # Quản lý TK (F10)
│   │   └── BookingHistory.cshtml # Lịch sử vé (F9)
│   └── Shared/
│       └── _Layout.cshtml       # Layout chung
│
├── Data/
│   └── CinemaContext.cs         # DbContext
│
├── Program.cs                   # Cấu hình ứng dụng
└── appsettings.json            # Cấu hình
```

---

## 🎨 Thiết Kế Giao Diện

### **Màu Sắc Chính**
- Primary Gradient: `#667eea` → `#764ba2` (Tím - Xanh)
- Accent: `#ff6b6b` (Đỏ)
- Success: `#4caf50` (Xanh lá)
- Warning: `#ff9800` (Cam)

### **Responsive Design**
- Sử dụng CSS Grid và Flexbox
- Mobile-first approach
- Thích ứng với mọi kích thước màn hình

---

## 🔧 Công Nghệ & Framework

- **Backend**: ASP.NET Core MVC
- **Database**: PostgreSQL
- **Frontend**: HTML5, CSS3, JavaScript
- **ORM**: Entity Framework Core
- **Container**: Docker & Docker Compose

---

## 📦 Models Chính

### **Movie**
```csharp
- Id, Title, Description
- Director, Cast, Genre
- Duration, Language, Subtitle
- Rating, PosterUrl, TrailerUrl
- IsNowShowing, IsComingSoon
- Screenings (Navigation)
```

### **Screening**
```csharp
- Id, MovieId, ScreenId
- ScreeningDateTime, Price
- AvailableSeats, TotalSeats
- Movie, Screen (Navigation)
```

### **Cinema**
```csharp
- Id, Name, Address
- Phone, City, District
- Screens (Navigation)
```

### **Screen**
```csharp
- Id, CinemaId, ScreenName
- Capacity
- Cinema, Screenings (Navigation)
```

---

## 🚀 Hướng Dẫn Chạy

1. **Clone dự án**
   ```bash
   git clone <repository-url>
   ```

2. **Cấu hình Database**
   - Cập nhật connection string trong `appsettings.json`
   - Chạy migrations (nếu có)

3. **Khôi phục Dependencies**
   ```bash
   dotnet restore
   ```

4. **Chạy ứng dụng**
   ```bash
   dotnet run
   ```

5. **Truy cập**
   - Trang chủ: `http://localhost:5000`

---

## 📝 Ghi Chú

- Các chức năng thanh toán cần kết nối với gateway thanh toán thực tế
- Xác thực người dùng cần tích hợp Session/JWT
- Gửi email cần cấu hình SMTP server
- Database seeding có thể cần thêm để test dữ liệu

---

## 👥 Tác Giả

Cinema Ticket - Hệ Thống Đặt Vé Xem Phim Online

---

## 📞 Hỗ Trợ

Liên hệ: `support@cinematicket.com`
