-- Tạo database
CREATE DATABASE IF NOT EXISTS phimchill;
USE phimchill;

-- Tắt foreign key checks để import được
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Xóa các bảng cũ nếu có (theo thứ tự đúng để tránh lỗi foreign key)
DROP TABLE IF EXISTS booking_combos;
DROP TABLE IF EXISTS booking_seats;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS seats;
DROP TABLE IF EXISTS showtimes;
DROP TABLE IF EXISTS screens;
DROP TABLE IF EXISTS theaters;
DROP TABLE IF EXISTS cities;
DROP TABLE IF EXISTS movies;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS combos;

-- Bảng users (người dùng)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('user', 'admin') DEFAULT 'user',
    status ENUM('active', 'blocked', 'deleted') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng cities (thành phố)
CREATE TABLE cities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(10) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng theaters (rạp chiếu)
CREATE TABLE theaters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(255) NOT NULL,
    city_id INT,
    phone VARCHAR(20),
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    total_screens INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE SET NULL
);

-- Bảng movies (phim)
CREATE TABLE movies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    duration INT NOT NULL,
    genre VARCHAR(100),
    release_date DATE,
    poster_url VARCHAR(255),
    status ENUM('showing', 'coming_soon', 'ended') DEFAULT 'showing',
    rating DECIMAL(3,1) DEFAULT 0.0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng screens (phòng chiếu)
CREATE TABLE screens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    theater_id INT NOT NULL,
    screen_name VARCHAR(50) NOT NULL,
    total_seats INT DEFAULT 100,
    FOREIGN KEY (theater_id) REFERENCES theaters(id) ON DELETE CASCADE
);

-- Bảng showtimes (lịch chiếu)
CREATE TABLE showtimes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    movie_id INT NOT NULL,
    screen_id INT NOT NULL,
    show_date DATE NOT NULL,
    show_time TIME NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    available_seats INT DEFAULT 100,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (screen_id) REFERENCES screens(id) ON DELETE CASCADE
);

-- Bảng seats (ghế ngồi)
CREATE TABLE seats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    screen_id INT NOT NULL,
    seat_row VARCHAR(5) NOT NULL,
    seat_number INT NOT NULL,
    seat_type ENUM('standard', 'vip', 'couple') DEFAULT 'standard',
    FOREIGN KEY (screen_id) REFERENCES screens(id) ON DELETE CASCADE,
    UNIQUE KEY unique_seat (screen_id, seat_row, seat_number)
);

-- Bảng bookings (đặt vé)
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    showtime_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    booking_status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('unpaid', 'paid', 'refunded') DEFAULT 'unpaid',
    booking_code VARCHAR(20) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (showtime_id) REFERENCES showtimes(id) ON DELETE CASCADE
);

-- Bảng booking_seats (ghế đã đặt)
CREATE TABLE booking_seats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    seat_id INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (seat_id) REFERENCES seats(id) ON DELETE CASCADE
);

-- Bảng payments (thanh toán)
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    payment_method ENUM('cash', 'card', 'online') DEFAULT 'online',
    amount DECIMAL(10,2) NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    transaction_id VARCHAR(100),
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

-- Bảng combo bắp nước/ đồ ăn
CREATE TABLE combos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active'
);

-- Bảng combo trong đơn đặt vé
CREATE TABLE booking_combos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    combo_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(id),
    FOREIGN KEY (combo_id) REFERENCES combos(id)
);

-- Thêm dữ liệu mẫu
-- Users
INSERT INTO users (name, email, password, role, status) VALUES 
('Admin', 'admin@cgv.com', 'admin123', 'admin', 'active'),
('Nguyen Van A', 'user@test.com', '123456', 'user', 'active'),
('hungphi', 'hungphi@test.com', '123456', 'user', 'active');

-- Cities (63 Tỉnh thành phố Việt Nam)
INSERT INTO cities (code, name, status, display_order) VALUES 
-- Thành phố trực thuộc trung ương (5)
('HN', 'Hà Nội', 'active', 1),
('HCM', 'TP. Hồ Chí Minh', 'active', 2),
('HP', 'Hải Phòng', 'active', 3),
('DN', 'Đà Nẵng', 'active', 4),
('CT', 'Cần Thơ', 'active', 5),

-- Miền Bắc (26 tỉnh)
('AG', 'An Giang', 'active', 6),
('BR', 'Bà Rịa - Vũng Tàu', 'active', 7),
('BK', 'Bắc Kạn', 'active', 8),
('BG', 'Bắc Giang', 'active', 9),
('BL', 'Bạc Liêu', 'active', 10),
('BN', 'Bắc Ninh', 'active', 11),
('BT', 'Bến Tre', 'active', 12),
('BD', 'Bình Định', 'active', 13),
('BP', 'Bình Dương', 'active', 14),
('BPh', 'Bình Phước', 'active', 15),
('BTh', 'Bình Thuận', 'active', 16),
('CM', 'Cà Mau', 'active', 17),
('CB', 'Cao Bằng', 'active', 18),
('DL', 'Đắk Lắk', 'active', 19),
('DN2', 'Đắk Nông', 'active', 20),
('DB', 'Điện Biên', 'active', 21),
('DNA', 'Đồng Nai', 'active', 22),
('DT', 'Đồng Tháp', 'active', 23),
('GL', 'Gia Lai', 'active', 24),
('HG', 'Hà Giang', 'active', 25),
('HB', 'Hà Nam', 'active', 26),
('HT', 'Hà Tĩnh', 'active', 27),
('HY', 'Hải Dương', 'active', 28),
('HU', 'Hậu Giang', 'active', 29),
('HB2', 'Hòa Bình', 'active', 30),
('HY2', 'Hưng Yên', 'active', 31),
('KH', 'Khánh Hòa', 'active', 32),
('KG', 'Kiên Giang', 'active', 33),
('KT', 'Kon Tum', 'active', 34),
('LChau', 'Lai Châu', 'active', 35),
('LĐ', 'Lâm Đồng', 'active', 36),
('LS', 'Lạng Sơn', 'active', 37),
('LC', 'Lào Cai', 'active', 38),
('LAN', 'Long An', 'active', 39),
('ND', 'Nam Định', 'active', 40),
('NA', 'Nghệ An', 'active', 41),
('NB', 'Ninh Bình', 'active', 42),
('NT', 'Ninh Thuận', 'active', 43),
('PT', 'Phú Thọ', 'active', 44),
('PY', 'Phú Yên', 'active', 45),
('QB', 'Quảng Bình', 'active', 46),
('QN', 'Quảng Nam', 'active', 47),
('QG', 'Quảng Ngãi', 'active', 48),
('QNi', 'Quảng Ninh', 'active', 49),
('QT', 'Quảng Trị', 'active', 50),
('ST', 'Sóc Trăng', 'active', 51),
('SL', 'Sơn La', 'active', 52),
('TY', 'Tây Ninh', 'active', 53),
('TB', 'Thái Bình', 'active', 54),
('TNg', 'Thái Nguyên', 'active', 55),
('TH', 'Thanh Hóa', 'active', 56),
('TTH', 'Thừa Thiên Huế', 'active', 57),
('TG', 'Tiền Giang', 'active', 58),
('TV', 'Trà Vinh', 'active', 59),
('TQ', 'Tuyên Quang', 'active', 60),
('VL', 'Vĩnh Long', 'active', 61),
('VP', 'Vĩnh Phúc', 'active', 62),
('YB', 'Yên Bái', 'active', 63);

-- Theaters (Cập nhật với city_id mới)
INSERT INTO theaters (name, location, city_id, phone, status, total_screens) VALUES 
-- Hà Nội (city_id = 1)
('CGV Vincom Center', '191 Bà Triệu, Hai Bà Trưng', 1, '024 3974 3333', 'active', 8),
('CGV Aeon Mall', 'Số 27 Cổ Linh, Long Biên', 1, '024 3974 4444', 'active', 6),
('CGV Times City', '458 Minh Khai, Hai Bà Trưng', 1, '024 3974 5555', 'active', 10),
('Lotte Cinema Keangnam', '72 Phạm Hùng, Nam Từ Liêm', 1, '024 3974 6666', 'active', 8),

-- TP. Hồ Chí Minh (city_id = 2)
('CGV Hùng Vương Plaza', '126 Hùng Vương, Q.5', 2, '028 3833 6666', 'active', 7),
('CGV Crescent Mall', '101 Tôn Dật Tiên, Q.7', 2, '028 5413 7777', 'active', 5),
('Galaxy Nguyễn Du', '116 Nguyễn Du, Q.1', 2, '028 3822 8888', 'active', 6),
('CGV Parkson Saigon Tourist', '35 - 45 Nguyễn Du, Q.1', 2, '028 3825 9999', 'active', 5),
('Mega GS Cinemas Cao Thắng', '19 Cao Thắng, Q.3', 2, '028 3930 0000', 'active', 7),

-- Hải Phòng (city_id = 3)
('Lotte Cinema Hải Phòng', '200A Lê Thánh Tông, Q. Ngô Quyền', 3, '0225 3820 111', 'active', 5),
('CGV Vincom Hải Phòng', '132 Lạch Tray, Q. Ngô Quyền', 3, '0225 3820 222', 'active', 6),

-- Đà Nẵng (city_id = 4)
('CGV Vincom Đà Nẵng', '244-246 Trần Phú, Q. Hải Châu', 4, '0236 3650 999', 'active', 4),
('Lotte Cinema Đà Nẵng', '6-10-12 Trần Phú, Q. Hải Châu', 4, '0236 3650 888', 'active', 5),

-- Cần Thơ (city_id = 5)
('CGV Vincom Cần Thơ', '209 Nguyễn Văn Cừ, Q. Ninh Kiều', 5, '0292 3767 222', 'active', 6),
('Lotte Cinema Cần Thơ', '52 Trần Phú, Q. Cái Răng', 5, '0292 3767 333', 'active', 4),

-- Bình Dương (city_id = 14)
('Galaxy Bình Dương', '01 Đại Lộ Bình Dương, TP. Thủ Dầu Một', 14, '0274 3690 333', 'active', 4),
('CGV Aeon Bình Dương', '1-5 Đại Lộ Bình Dương, TP. Thủ Dầu Một', 14, '0274 3690 444', 'active', 6),

-- Đồng Nai (city_id = 22) 
('CGV Vincom Biên Hòa', '60A Nguyễn Ái Quốc, TP. Biên Hòa', 22, '0251 3836 555', 'active', 5),

-- Khánh Hòa (city_id = 32)
('CGV Vincom Nha Trang', '50 Trần Phú, TP. Nha Trang', 32, '0258 3836 666', 'active', 4),
('Lotte Cinema Nha Trang', '2C Trần Quang Khải, TP. Nha Trang', 32, '0258 3836 777', 'active', 5);

-- THÊM RẠP CHIẾU CHO 55 TỈNH THÀNH CÒN LẠI (city_id 6-63)
INSERT INTO theaters (name, location, city_id, phone, status, total_screens) VALUES 
-- An Giang (city_id = 6)
('CGV Long Xuyên', '15A Trưng Nữ Vương, TP. Long Xuyên', 6, '0296 3841 111', 'active', 4),
('Galaxy An Giang', '123 Nguyễn Huệ, TP. Long Xuyên', 6, '0296 3841 222', 'active', 3),

-- Bà Rịa - Vũng Tàu (city_id = 7)
('CGV Vũng Tàu', '02 Lê Hồng Phong, TP. Vũng Tàu', 7, '0254 3856 111', 'active', 5),
('Lotte Cinema Vũng Tàu', '456 Trường Chinh, TP. Vũng Tàu', 7, '0254 3856 222', 'active', 4),

-- Bắc Kạn (city_id = 8)
('Rạp Chiếu Phim Bắc Kạn', '78 Ngô Quyền, TP. Bắc Kạn', 8, '0209 3822 111', 'active', 2),

-- Bắc Giang (city_id = 9)
('CGV Bắc Giang', '22 Hoàng Văn Thụ, TP. Bắc Giang', 9, '0204 3822 333', 'active', 3),

-- Bạc Liêu (city_id = 10)
('Galaxy Bạc Liêu', '56 Trần Phú, TP. Bạc Liêu', 10, '0291 3822 444', 'active', 3),

-- Bắc Ninh (city_id = 11)  
('CGV Vincom Bắc Ninh', '01 Đại lộ Thăng Long, TP. Bắc Ninh', 11, '0222 3822 555', 'active', 5),
('Lotte Cinema Bắc Ninh', '789 Võ Nguyên Giáp, TP. Bắc Ninh', 11, '0222 3822 666', 'active', 4),

-- Bến Tre (city_id = 12)
('Rạp Chiếu Phim Bến Tre', '34 Đồng Khởi, TP. Bến Tre', 12, '0275 3822 777', 'active', 2),

-- Bình Định (city_id = 13)
('CGV Quy Nhon', '12 Trần Hưng Đạo, TP. Quy Nhon', 13, '0256 3822 888', 'active', 4),
('Galaxy Bình Định', '67 An Dương Vương, TP. Quy Nhon', 13, '0256 3822 999', 'active', 3),

-- Bình Phước (city_id = 15)
('CGV Đồng Xoài', '89 Phú Riềng Đỏ, TP. Đồng Xoài', 15, '0271 3823 111', 'active', 3),

-- Bình Thuận (city_id = 16)
('CGV Phan Thiết', '45 Nguyễn Thái Học, TP. Phan Thiết', 16, '0252 3823 222', 'active', 4),
('Lotte Cinema Phan Thiết', '78 Trần Hưng Đạo, TP. Phan Thiết', 16, '0252 3823 333', 'active', 3),

-- Cà Mau (city_id = 17)
('Galaxy Cà Mau', '23 Phạm Ngũ Lão, TP. Cà Mau', 17, '0290 3823 444', 'active', 2),

-- Cao Bằng (city_id = 18)
('Rạp Chiếu Phim Cao Bằng', '56 Võ Nguyên Giáp, TP. Cao Bằng', 18, '0206 3823 555', 'active', 2),

-- Đắk Lắk (city_id = 19)
('CGV Buôn Ma Thuột', '01 Y Jút, TP. Buôn Ma Thuột', 19, '0262 3823 666', 'active', 4),
('Galaxy Đắk Lắk', '123 Trường Chinh, TP. Buôn Ma Thuột', 19, '0262 3823 777', 'active', 3),

-- Đắk Nông (city_id = 20)
('Rạp Chiếu Phim Gia Nghĩa', '45 Nguyễn Chí Thanh, TP. Gia Nghĩa', 20, '0261 3823 888', 'active', 2),

-- Điện Biên (city_id = 21)
('Galaxy Điện Biên', '78 Võ Nguyên Giáp, TP. Điện Biên Phủ', 21, '0215 3823 999', 'active', 2),

-- Đồng Tháp (city_id = 23)
('CGV Cao Lãnh', '12 Nguyễn Huệ, TP. Cao Lãnh', 23, '0277 3824 111', 'active', 3),

-- Gia Lai (city_id = 24)
('CGV Pleiku', '34 Hùng Vương, TP. Pleiku', 24, '0269 3824 222', 'active', 4),
('Galaxy Gia Lai', '67 Lê Duẩn, TP. Pleiku', 24, '0269 3824 333', 'active', 3),

-- Hà Giang (city_id = 25)
('Rạp Chiếu Phim Hà Giang', '89 Nguyễn Trãi, TP. Hà Giang', 25, '0219 3824 444', 'active', 2),

-- Hà Nam (city_id = 26)
('Galaxy Phủ Lý', '23 Trần Phú, TP. Phủ Lý', 26, '0226 3824 555', 'active', 3),

-- Hà Tĩnh (city_id = 27)
('CGV Hà Tĩnh', '45 Trần Phú, TP. Hà Tĩnh', 27, '0239 3824 666', 'active', 3),

-- Hải Dương (city_id = 28)
('CGV Hải Dương', '67 Nguyễn Lương Bằng, TP. Hải Dương', 28, '0220 3824 777', 'active', 4),

-- Hậu Giang (city_id = 29)
('Galaxy Vị Thanh', '89 Nguyễn Thái Học, TP. Vị Thanh', 29, '0293 3824 888', 'active', 2),

-- Hòa Bình (city_id = 30)
('Rạp Chiếu Phim Hòa Bình', '12 Cù Chính Lan, TP. Hòa Bình', 30, '0218 3824 999', 'active', 2),

-- Hưng Yên (city_id = 31)
('CGV Hưng Yên', '34 Lê Duẩn, TP. Hưng Yên', 31, '0221 3825 111', 'active', 3),

-- Kiên Giang (city_id = 33)
('CGV Rạch Giá', '56 Lê Lợi, TP. Rạch Giá', 33, '0297 3825 222', 'active', 4),
('Galaxy Kiên Giang', '78 Nguyễn Trung Trực, TP. Rạch Giá', 33, '0297 3825 333', 'active', 3),

-- Kon Tum (city_id = 34)
('Galaxy Kon Tum', '23 Phan Đình Phùng, TP. Kon Tum', 34, '0260 3825 444', 'active', 2),

-- Lai Châu (city_id = 35)
('Rạp Chiếu Phim Lai Châu', '45 Trần Phú, TP. Lai Châu', 35, '0213 3825 555', 'active', 2),

-- Lâm Đồng (city_id = 36)
('CGV Đà Lạt', '67 Nguyễn Thi Minh Khai, TP. Đà Lạt', 36, '0263 3825 666', 'active', 5),
('Galaxy Lâm Đồng', '89 Trần Phú, TP. Đà Lạt', 36, '0263 3825 777', 'active', 4),

-- Lạng Sơn (city_id = 37)
('Galaxy Lạng Sơn', '12 Lê Duẩn, TP. Lạng Sơn', 37, '0205 3825 888', 'active', 2),

-- Lào Cai (city_id = 38)
('CGV Lào Cai', '34 Trần Hưng Đạo, TP. Lào Cai', 38, '0214 3825 999', 'active', 3),

-- Long An (city_id = 39)
('CGV Tân An', '56 Hùng Vương, TP. Tân An', 39, '0272 3826 111', 'active', 3),

-- Nam Định (city_id = 40)
('CGV Nam Định', '78 Trường Chinh, TP. Nam Định', 40, '0228 3826 222', 'active', 4),

-- Nghệ An (city_id = 41)
('CGV Vinh', '23 Quang Trung, TP. Vinh', 41, '0238 3826 333', 'active', 5),
('Galaxy Nghệ An', '45 Lê Lợi, TP. Vinh', 41, '0238 3826 444', 'active', 4),

-- Ninh Bình (city_id = 42)
('Galaxy Ninh Bình', '67 Trần Hưng Đạo, TP. Ninh Bình', 42, '0229 3826 555', 'active', 3),

-- Ninh Thuận (city_id = 43)
('CGV Phan Rang', '89 Thống Nhất, TP. Phan Rang-Tháp Chàm', 43, '0259 3826 666', 'active', 3),

-- Phú Thọ (city_id = 44)
('Galaxy Việt Trì', '12 Hùng Vương, TP. Việt Trì', 44, '0210 3826 777', 'active', 3),

-- Phú Yên (city_id = 45)
('CGV Tuy Hòa', '34 Lê Thành Phương, TP. Tuy Hòa', 45, '0257 3826 888', 'active', 3),

-- Quảng Bình (city_id = 46)
('Galaxy Đồng Hới', '56 Quách Xuân Kỳ, TP. Đồng Hới', 46, '0232 3826 999', 'active', 3),

-- Quảng Nam (city_id = 47)
('CGV Tam Kỳ', '78 Phan Bội Châu, TP. Tam Kỳ', 47, '0235 3827 111', 'active', 3),
('Galaxy Hội An', '23 Lê Lợi, TP. Hội An', 47, '0235 3827 222', 'active', 2),

-- Quảng Ngãi (city_id = 48)
('CGV Quảng Ngãi', '45 Quang Trung, TP. Quảng Ngãi', 48, '0255 3827 333', 'active', 3),

-- Quảng Ninh (city_id = 49)
('CGV Hạ Long', '67 Hạ Long, TP. Hạ Long', 49, '0203 3827 444', 'active', 5),
('Galaxy Quảng Ninh', '89 Bãi Cháy, TP. Hạ Long', 49, '0203 3827 555', 'active', 4),

-- Quảng Trị (city_id = 50)
('Galaxy Đông Hà', '12 Lê Duẩn, TP. Đông Hà', 50, '0233 3827 666', 'active', 2),

-- Sóc Trăng (city_id = 51)
('CGV Sóc Trăng', '34 Trần Hưng Đạo, TP. Sóc Trăng', 51, '0299 3827 777', 'active', 3),

-- Sơn La (city_id = 52)
('Galaxy Sơn La', '56 Tô Hiến Thành, TP. Sơn La', 52, '0212 3827 888', 'active', 2),

-- Tây Ninh (city_id = 53)
('CGV Tây Ninh', '78 Cách Mạng Tháng 8, TP. Tây Ninh', 53, '0276 3827 999', 'active', 3),

-- Thái Bình (city_id = 54)
('Galaxy Thái Bình', '23 Lý Bôn, TP. Thái Bình', 54, '0227 3828 111', 'active', 3),

-- Thái Nguyên (city_id = 55)
('CGV Thái Nguyên', '45 Hoàng Văn Thụ, TP. Thái Nguyên', 55, '0208 3828 222', 'active', 4),

-- Thanh Hóa (city_id = 56)
('CGV Thanh Hóa', '67 Quang Trung, TP. Thanh Hóa', 56, '0237 3828 333', 'active', 4),
('Galaxy Thanh Hóa', '89 Phan Chu Trinh, TP. Thanh Hóa', 56, '0237 3828 444', 'active', 3),

-- Thừa Thiên Huế (city_id = 57)
('CGV Huế', '12 Lê Lợi, TP. Huế', 57, '0234 3828 555', 'active', 5),
('Galaxy Huế', '34 Nguyễn Huệ, TP. Huế', 57, '0234 3828 666', 'active', 4),

-- Tiền Giang (city_id = 58)
('CGV Mỹ Tho', '56 Trưng Trắc, TP. Mỹ Tho', 58, '0273 3828 777', 'active', 3),

-- Trà Vinh (city_id = 59)
('Galaxy Trà Vinh', '78 Nguyễn Đáng, TP. Trà Vinh', 59, '0294 3828 888', 'active', 2),

-- Tuyên Quang (city_id = 60)
('Rạp Chiếu Phim Tuyên Quang', '23 Tân Trào, TP. Tuyên Quang', 60, '0207 3828 999', 'active', 2),

-- Vĩnh Long (city_id = 61)
('CGV Vĩnh Long', '45 Phạm Thái Bường, TP. Vĩnh Long', 61, '0270 3829 111', 'active', 3),

-- Vĩnh Phúc (city_id = 62)
('Galaxy Vĩnh Yên', '67 Mê Linh, TP. Vĩnh Yên', 62, '0211 3829 222', 'active', 3),

-- Yên Bái (city_id = 63)
('CGV Yên Bái', '89 Điện Biên, TP. Yên Bái', 63, '0216 3829 333', 'active', 2);

-- Movies
INSERT INTO movies (title, description, duration, genre, release_date, poster_url, status, rating) VALUES 
('Lật Mặt 8', 'Phim hành động Việt Nam đầy kịch tính với những pha action mãn nhãn', 120, 'Hành động, Hài', '2024-01-15', 'img/Phim/latmat8.jpg', 'showing', 8.5),
('Thám Tử Kiên', 'Phim trinh thám hấp dẫn với cốt truyện ly kỳ, bí ẩn', 110, 'Trinh thám, Hành động', '2024-02-01', 'img/Phim/thamtukien.jpg', 'showing', 8.0),
('Địa Đạo', 'Phim chiến tranh lịch sử Việt Nam đầy cảm động và hùng tráng', 130, 'Chiến tranh, Hành động', '2024-01-20', 'img/Phim/diadao.jpg', 'showing', 8.8),
('Đội Săn Quỷ', 'Phim kinh dị siêu nhiên với những cảnh quay rùng rợn', 105, 'Kinh dị, Hành động', '2024-02-10', 'img/Phim/doisanquy.jpg', 'showing', 7.5),
('Shin Cậu Bé Bút Chì', 'Phim hoạt hình gia đình vui nhộn và đáng yêu', 95, 'Hoạt hình, Gia đình', '2024-01-25', 'img/Phim/shin.jpg', 'showing', 9.0),
('Doraemon Movie 44', 'Phim hoạt hình Doraemon mới nhất với cuộc phiêu lưu thú vị', 100, 'Hoạt hình, Phiêu lưu', '2024-02-05', 'img/Phim/doraemon_movie44.jpg', 'showing', 8.7);

-- Screens (Phòng chiếu cho 19 rạp)
INSERT INTO screens (theater_id, screen_name, total_seats) VALUES 
-- CGV Vincom Center (8 phòng)
(1, 'Phòng 1', 120), (1, 'Phòng 2', 100), (1, 'Phòng 3', 80), (1, 'Phòng 4', 150),
(1, 'Phòng 5', 140), (1, 'Phòng 6', 160), (1, 'Phòng 7', 90), (1, 'Phòng 8', 110),

-- CGV Aeon Mall (6 phòng)
(2, 'Phòng 1', 130), (2, 'Phòng 2', 120), (2, 'Phòng 3', 100), (2, 'Phòng 4', 140),
(2, 'Phòng 5', 110), (2, 'Phòng 6', 95),

-- CGV Times City (10 phòng)
(3, 'Phòng 1', 200), (3, 'Phòng 2', 180), (3, 'Phòng 3', 160), (3, 'Phòng 4', 190),
(3, 'Phòng 5', 150), (3, 'Phòng 6', 170), (3, 'Phòng 7', 140), (3, 'Phòng 8', 130),
(3, 'Phòng 9', 120), (3, 'Phòng 10', 100),

-- Lotte Cinema Keangnam (8 phòng)
(4, 'Phòng 1', 180), (4, 'Phòng 2', 160), (4, 'Phòng 3', 140), (4, 'Phòng 4', 120),
(4, 'Phòng 5', 100), (4, 'Phòng 6', 180), (4, 'Phòng 7', 150), (4, 'Phòng 8', 130),

-- CGV Hùng Vương Plaza (7 phòng)
(5, 'Phòng 1', 140), (5, 'Phòng 2', 120), (5, 'Phòng 3', 100), (5, 'Phòng 4', 130),
(5, 'Phòng 5', 110), (5, 'Phòng 6', 90), (5, 'Phòng 7', 160),

-- CGV Crescent Mall (5 phòng)
(6, 'Phòng 1', 90), (6, 'Phòng 2', 100), (6, 'Phòng 3', 80), (6, 'Phòng 4', 120), (6, 'Phòng 5', 110),

-- Galaxy Nguyễn Du (6 phòng)
(7, 'Phòng 1', 150), (7, 'Phòng 2', 130), (7, 'Phòng 3', 120), (7, 'Phòng 4', 140),
(7, 'Phòng 5', 100), (7, 'Phòng 6', 110),

-- CGV Parkson Saigon Tourist (5 phòng)
(8, 'Phòng 1', 120), (8, 'Phòng 2', 100), (8, 'Phòng 3', 90), (8, 'Phòng 4', 110), (8, 'Phòng 5', 130),

-- Mega GS Cinemas Cao Thắng (7 phòng)
(9, 'Phòng 1', 160), (9, 'Phòng 2', 140), (9, 'Phòng 3', 120), (9, 'Phòng 4', 100),
(9, 'Phòng 5', 180), (9, 'Phòng 6', 150), (9, 'Phòng 7', 110),

-- Lotte Cinema Hải Phòng (5 phòng)
(10, 'Phòng 1', 130), (10, 'Phòng 2', 110), (10, 'Phòng 3', 120), (10, 'Phòng 4', 140), (10, 'Phòng 5', 100),

-- CGV Vincom Hải Phòng (6 phòng)
(11, 'Phòng 1', 150), (11, 'Phòng 2', 130), (11, 'Phòng 3', 110), (11, 'Phòng 4', 120),
(11, 'Phòng 5', 140), (11, 'Phòng 6', 100),

-- CGV Vincom Đà Nẵng (4 phòng)
(12, 'Phòng 1', 140), (12, 'Phòng 2', 120), (12, 'Phòng 3', 100), (12, 'Phòng 4', 160),

-- Lotte Cinema Đà Nẵng (5 phòng)
(13, 'Phòng 1', 150), (13, 'Phòng 2', 130), (13, 'Phòng 3', 110), (13, 'Phòng 4', 140), (13, 'Phòng 5', 120),

-- CGV Vincom Cần Thơ (6 phòng)
(14, 'Phòng 1', 150), (14, 'Phòng 2', 130), (14, 'Phòng 3', 120), (14, 'Phòng 4', 140),
(14, 'Phòng 5', 110), (14, 'Phòng 6', 100),

-- Lotte Cinema Cần Thơ (4 phòng)
(15, 'Phòng 1', 120), (15, 'Phòng 2', 100), (15, 'Phòng 3', 140), (15, 'Phòng 4', 110),

-- Galaxy Bình Dương (4 phòng)
(16, 'Phòng 1', 120), (16, 'Phòng 2', 100), (16, 'Phòng 3', 140), (16, 'Phòng 4', 130),

-- CGV Aeon Bình Dương (6 phòng)
(17, 'Phòng 1', 160), (17, 'Phòng 2', 140), (17, 'Phòng 3', 120), (17, 'Phòng 4', 100),
(17, 'Phòng 5', 180), (17, 'Phòng 6', 150),

-- CGV Vincom Biên Hòa (5 phòng)
(18, 'Phòng 1', 130), (18, 'Phòng 2', 110), (18, 'Phòng 3', 120), (18, 'Phòng 4', 140), (18, 'Phòng 5', 100),

-- CGV Vincom Nha Trang (4 phòng)
(19, 'Phòng 1', 140), (19, 'Phòng 2', 120), (19, 'Phòng 3', 100), (19, 'Phòng 4', 160);

-- Seats cho phòng chiếu đầu tiên (120 ghế)
INSERT INTO seats (screen_id, seat_row, seat_number, seat_type) VALUES 
-- Hàng A-D: ghế thường
(1, 'A', 1, 'standard'), (1, 'A', 2, 'standard'), (1, 'A', 3, 'standard'), (1, 'A', 4, 'standard'), (1, 'A', 5, 'standard'),
(1, 'A', 6, 'standard'), (1, 'A', 7, 'standard'), (1, 'A', 8, 'standard'), (1, 'A', 9, 'standard'), (1, 'A', 10, 'standard'),
(1, 'B', 1, 'standard'), (1, 'B', 2, 'standard'), (1, 'B', 3, 'standard'), (1, 'B', 4, 'standard'), (1, 'B', 5, 'standard'),
(1, 'B', 6, 'standard'), (1, 'B', 7, 'standard'), (1, 'B', 8, 'standard'), (1, 'B', 9, 'standard'), (1, 'B', 10, 'standard'),
(1, 'C', 1, 'standard'), (1, 'C', 2, 'standard'), (1, 'C', 3, 'standard'), (1, 'C', 4, 'standard'), (1, 'C', 5, 'standard'),
(1, 'C', 6, 'standard'), (1, 'C', 7, 'standard'), (1, 'C', 8, 'standard'), (1, 'C', 9, 'standard'), (1, 'C', 10, 'standard'),
(1, 'D', 1, 'standard'), (1, 'D', 2, 'standard'), (1, 'D', 3, 'standard'), (1, 'D', 4, 'standard'), (1, 'D', 5, 'standard'),
(1, 'D', 6, 'standard'), (1, 'D', 7, 'standard'), (1, 'D', 8, 'standard'), (1, 'D', 9, 'standard'), (1, 'D', 10, 'standard'),
-- Hàng E-G: ghế VIP
(1, 'E', 1, 'vip'), (1, 'E', 2, 'vip'), (1, 'E', 3, 'vip'), (1, 'E', 4, 'vip'), (1, 'E', 5, 'vip'),
(1, 'E', 6, 'vip'), (1, 'E', 7, 'vip'), (1, 'E', 8, 'vip'), (1, 'E', 9, 'vip'), (1, 'E', 10, 'vip'),
(1, 'F', 1, 'vip'), (1, 'F', 2, 'vip'), (1, 'F', 3, 'vip'), (1, 'F', 4, 'vip'), (1, 'F', 5, 'vip'),
(1, 'F', 6, 'vip'), (1, 'F', 7, 'vip'), (1, 'F', 8, 'vip'), (1, 'F', 9, 'vip'), (1, 'F', 10, 'vip'),
(1, 'G', 1, 'vip'), (1, 'G', 2, 'vip'), (1, 'G', 3, 'vip'), (1, 'G', 4, 'vip'), (1, 'G', 5, 'vip'),
(1, 'G', 6, 'vip'), (1, 'G', 7, 'vip'), (1, 'G', 8, 'vip'), (1, 'G', 9, 'vip'), (1, 'G', 10, 'vip'),
-- Hàng H-J: ghế thường
(1, 'H', 1, 'standard'), (1, 'H', 2, 'standard'), (1, 'H', 3, 'standard'), (1, 'H', 4, 'standard'), (1, 'H', 5, 'standard'),
(1, 'H', 6, 'standard'), (1, 'H', 7, 'standard'), (1, 'H', 8, 'standard'), (1, 'H', 9, 'standard'), (1, 'H', 10, 'standard'),
(1, 'I', 1, 'standard'), (1, 'I', 2, 'standard'), (1, 'I', 3, 'standard'), (1, 'I', 4, 'standard'), (1, 'I', 5, 'standard'),
(1, 'I', 6, 'standard'), (1, 'I', 7, 'standard'), (1, 'I', 8, 'standard'), (1, 'I', 9, 'standard'), (1, 'I', 10, 'standard'),
(1, 'J', 1, 'standard'), (1, 'J', 2, 'standard'), (1, 'J', 3, 'standard'), (1, 'J', 4, 'standard'), (1, 'J', 5, 'standard'),
(1, 'J', 6, 'standard'), (1, 'J', 7, 'standard'), (1, 'J', 8, 'standard'), (1, 'J', 9, 'standard'), (1, 'J', 10, 'standard');

-- Showtimes (Lịch chiếu từ hôm nay)
INSERT INTO showtimes (movie_id, screen_id, show_date, show_time, price, available_seats) VALUES 
-- Hôm nay - CGV Vincom Center
(1, 1, CURDATE(), '09:00:00', 80000, 120),
(1, 1, CURDATE(), '12:00:00', 80000, 120),
(1, 1, CURDATE(), '15:00:00', 90000, 120),
(1, 1, CURDATE(), '18:00:00', 100000, 120),
(1, 1, CURDATE(), '21:00:00', 100000, 120),

(2, 9, CURDATE(), '10:00:00', 75000, 130), -- CGV Aeon Mall Phòng 1
(2, 9, CURDATE(), '13:30:00', 75000, 130),
(2, 9, CURDATE(), '16:30:00', 85000, 130),
(2, 9, CURDATE(), '19:30:00', 95000, 130),

(3, 15, CURDATE(), '11:00:00', 85000, 200), -- CGV Times City Phòng 1
(3, 15, CURDATE(), '14:00:00', 85000, 200),
(3, 15, CURDATE(), '17:00:00', 95000, 200),
(3, 15, CURDATE(), '20:00:00', 105000, 200),

-- Mai - Các rạp khác nhau
(1, 25, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00', 80000, 140), -- CGV Hùng Vương Phòng 1
(1, 25, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '12:00:00', 80000, 140),
(1, 25, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '15:00:00', 90000, 140),
(1, 25, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '18:00:00', 100000, 140),
(1, 25, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '21:00:00', 100000, 140),

(4, 32, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:30:00', 90000, 90), -- CGV Crescent Mall Phòng 1
(4, 32, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '13:00:00', 90000, 90),
(4, 32, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '16:00:00', 100000, 90),
(4, 32, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '19:00:00', 110000, 90),

(5, 38, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:30:00', 70000, 150), -- Galaxy Nguyễn Du Phòng 1
(5, 38, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '11:30:00', 70000, 150),
(5, 38, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '14:00:00', 80000, 150),
(5, 38, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '16:30:00', 80000, 150),

(6, 44, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', 75000, 140), -- CGV Vincom Đà Nẵng Phòng 1
(6, 44, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '12:30:00', 75000, 140),
(6, 44, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '15:30:00', 85000, 140),
(6, 44, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '18:30:00', 95000, 140),

-- Ngày kia - Lotte Cinema Hải Phòng
(2, 48, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '09:00:00', 75000, 130),
(2, 48, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '12:00:00', 75000, 130),
(2, 48, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '15:00:00', 85000, 130),
(2, 48, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '18:00:00', 95000, 130),
(2, 48, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '21:00:00', 95000, 130),

(3, 53, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '10:00:00', 85000, 150), -- CGV Vincom Cần Thơ Phòng 1
(3, 53, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '13:00:00', 85000, 150),
(3, 53, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '16:00:00', 95000, 150),
(3, 53, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '19:00:00', 105000, 150),

-- Cuối tuần - Galaxy Bình Dương
(1, 59, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '08:00:00', 80000, 120),
(1, 59, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '11:00:00', 80000, 120),
(1, 59, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '14:00:00', 90000, 120),
(1, 59, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '17:00:00', 100000, 120),
(1, 59, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '20:00:00', 100000, 120),
(1, 59, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '22:30:00', 110000, 120);

-- Data combo bắp nước CGV
INSERT INTO combos (name, description, price, image_url, status) VALUES 
("Combo Cặp Đôi", "Gồm 2 ly nước ngọt size L + 1 hộp bắp rang bơ size L. Hoàn hảo cho buổi hẹn hò lãng mạn tại rạp chiếu phim.", 149000, "img/combos/combo_capdoi.png", "active"),

("Combo Gia Đình", "Gồm 4 ly nước ngọt size M + 2 hộp bắp rang bơ size L + 1 gói kẹo. Lựa chọn tuyệt vời cho cả gia đình cùng thưởng thức phim.", 299000, "img/combos/combo_giadinh.jpg", "active"),

("Combo Teen", "Gồm 1 ly nước ngọt size L + 1 hộp bắp rang phô mai size M + 1 bánh kẹo. Combo năng động dành cho giới trẻ.", 89000, "img/combos/combo_teen.png", "active"),

("Combo Chill Nhẹ", "Gồm 1 ly nước chanh dây + 1 hộp bắp rang caramel size S + 1 pack snack. Combo nhẹ nhàng cho những phút giây thư giãn.", 69000, "img/combos/combo_chillnhe.jpg", "active");
-- Commit transaction và bật lại foreign key checks
COMMIT;
SET FOREIGN_KEY_CHECKS = 1;

-- Thông báo hoàn thành
SELECT 'Database đã được cập nhật với rạp chiếu cho tất cả 63 tỉnh thành và combo bắp nước!' as 'Trạng thái';
SELECT COUNT(*) as 'Tổng số rạp' FROM theaters;
SELECT COUNT(*) as 'Tổng số tỉnh thành' FROM cities;
SELECT COUNT(*) as 'Tổng số phòng chiếu' FROM screens;
SELECT COUNT(*) as 'Tổng số lịch chiếu' FROM showtimes;
SELECT COUNT(*) as 'Tổng số combo' FROM combos;


