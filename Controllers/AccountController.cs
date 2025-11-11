using System;
using System.Diagnostics;
using System.Linq;
using System.Threading.Tasks;
using Cinema_ticket.Data;
using Cinema_ticket.Models;
using Cinema_ticket.Models.Booking;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Logging;

namespace Cinema_ticket.Controllers
{
    public class AccountController : Controller
    {
        private readonly ILogger<AccountController> _logger;
        private readonly CinemaContext _context;

        public AccountController(ILogger<AccountController> logger, CinemaContext context)
        {
            _logger = logger;
            _context = context;
        }

        // F2: Đăng Nhập
        [HttpGet]
        public IActionResult Login()
        {
            return View();
        }

        [HttpPost]
        public async Task<IActionResult> Login(string email, string password)
        {
            try
            {
                // TODO: Xử lý đăng nhập từ database
                // Kiểm tra email và password
                // Tạo session hoặc token

                // Tạm thời mock
                if (!string.IsNullOrEmpty(email) && !string.IsNullOrEmpty(password))
                {
                    // Đăng nhập thành công
                    return RedirectToAction("Index", "Movie");
                }

                ModelState.AddModelError("", "Email hoặc mật khẩu không chính xác");
                return View();
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Error during login");
                return View(new ErrorViewModel { RequestId = Activity.Current?.Id ?? HttpContext.TraceIdentifier });
            }
        }

        // F2: Đăng Ký
        [HttpGet]
        public IActionResult Register()
        {
            return View();
        }

        [HttpPost]
        public async Task<IActionResult> Register(string fullName, string email, string password, string confirmPassword)
        {
            try
            {
                // Kiểm tra mật khẩu trùng khớp
                if (password != confirmPassword)
                {
                    ModelState.AddModelError("", "Mật khẩu không trùng khớp");
                    return View();
                }

                // TODO: Kiểm tra email đã tồn tại
                // TODO: Lưu user vào database
                // TODO: Gửi email xác nhận

                TempData["Message"] = "Đăng ký thành công! Vui lòng kiểm tra email để xác nhận tài khoản.";
                return RedirectToAction("Login");
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Error during registration");
                return View(new ErrorViewModel { RequestId = Activity.Current?.Id ?? HttpContext.TraceIdentifier });
            }
        }

        // F10: Quản Lý Tài Khoản
        [HttpGet]
        public IActionResult Account()
        {
            // TODO: Lấy thông tin user hiện tại từ session/token
            return View();
        }

        [HttpPost]
        public async Task<IActionResult> UpdateAccount(string fullName, string email, string phone)
        {
            try
            {
                // TODO: Cập nhật thông tin user
                TempData["Message"] = "Cập nhật thông tin thành công!";
                return RedirectToAction("Account");
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Error updating account");
                TempData["Error"] = "Lỗi khi cập nhật thông tin!";
                return RedirectToAction("Account");
            }
        }

        // F10: Đổi Mật Khẩu
        [HttpPost]
        public async Task<IActionResult> ChangePassword(string currentPassword, string newPassword, string confirmPassword)
        {
            try
            {
                if (newPassword != confirmPassword)
                {
                    TempData["Error"] = "Mật khẩu mới không trùng khớp!";
                    return RedirectToAction("Account");
                }

                // TODO: Kiểm tra mật khẩu hiện tại
                // TODO: Cập nhật mật khẩu mới

                TempData["Message"] = "Đổi mật khẩu thành công!";
                return RedirectToAction("Account");
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Error changing password");
                TempData["Error"] = "Lỗi khi đổi mật khẩu!";
                return RedirectToAction("Account");
            }
        }

        // F9: Lịch Sử Vé
        public async Task<IActionResult> BookingHistory()
        {
            try
            {
                // TODO: Lấy email user từ session/token thực tế
                // Tạm thời dùng email mẫu để demo
                var userEmail = "user@example.com"; // Thay bằng logic lấy email thực tế

                var bookings = await _context.Tickets
                    .Include(t => t.Screening)
                    .ThenInclude(s => s.Movie)
                    .Include(t => t.Screening)
                    .ThenInclude(s => s.Screen)
                    .ThenInclude(sc => sc.Cinema)
                    .Include(t => t.TicketDetails)
                    .Where(t => t.CustomerEmail == userEmail)
                    .OrderByDescending(t => t.CreatedAt)
                    .Select(t => new BookingHistoryViewModel
                    {
                        Id = t.Id,
                        MovieTitle = t.Screening.Movie.Title,
                        ScreeningDateTime = t.Screening.ScreeningDateTime,
                        CinemaName = t.Screening.Screen.Cinema.Name,
                        ScreenName = t.Screening.Screen.ScreenName,
                        SeatLabels = string.Join(", ", t.TicketDetails.Select(td => td.SeatLabel).OrderBy(s => s)),
                        TotalPrice = t.TotalPrice,
                        Status = t.Status,
                        CreatedAt = t.CreatedAt
                    })
                    .ToListAsync();

                return View(bookings);
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Error loading booking history");
                return View(new List<BookingHistoryViewModel>());
            }
        }

        [ResponseCache(Duration = 0, Location = ResponseCacheLocation.None, NoStore = true)]
        public IActionResult Error()
        {
            return View(new ErrorViewModel { RequestId = Activity.Current?.Id ?? HttpContext.TraceIdentifier });
        }
    }
}
