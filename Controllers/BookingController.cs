using System;
using System.Diagnostics;
using System.Linq;
using System.Threading.Tasks;
using Cinema_ticket.Data;
using Cinema_ticket.Models;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Logging;

namespace Cinema_ticket.Controllers
{
    public class BookingController : Controller
    {
        private readonly ILogger<BookingController> _logger;
        private readonly CinemaContext _context;

        public BookingController(ILogger<BookingController> logger, CinemaContext context)
        {
            _logger = logger;
            _context = context;
        }

        // F8: Trang xác nhận và thanh toán
        public async Task<IActionResult> Checkout(int screeningId, string seats)
        {
            try
            {
                var screening = await _context.Screenings
                    .Include(s => s.Movie)
                    .Include(s => s.Screen)
                    .ThenInclude(sc => sc.Cinema)
                    .FirstOrDefaultAsync(s => s.Id == screeningId);

                if (screening == null)
                    return NotFound();

                ViewBag.SelectedSeats = seats;
                ViewBag.SeatCount = seats.Split(',').Length;
                ViewBag.TotalPrice = screening.Price * ViewBag.SeatCount;

                return View(screening);
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Error loading checkout");
                return View(new ErrorViewModel { RequestId = Activity.Current?.Id ?? HttpContext.TraceIdentifier });
            }
        }

        [HttpPost]
        public async Task<IActionResult> ProcessPayment(int screeningId, string seats, string fullName, string email, string phone)
        {
            try
            {
                var screening = await _context.Screenings
                    .FirstOrDefaultAsync(s => s.Id == screeningId);

                if (screening == null)
                    return NotFound();

                // TODO: Xử lý thanh toán qua gateway
                // Lưu booking vào database
                // Gửi email xác nhận

                TempData["Message"] = "Đặt vé thành công! Vui lòng check email để nhận vé.";
                return RedirectToAction("BookingConfirm");
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Error processing payment");
                TempData["Error"] = "Lỗi trong quá trình xử lý thanh toán!";
                return RedirectToAction("Checkout", new { screeningId });
            }
        }

        public IActionResult BookingConfirm()
        {
            return View();
        }

        [ResponseCache(Duration = 0, Location = ResponseCacheLocation.None, NoStore = true)]
        public IActionResult Error()
        {
            return View(new ErrorViewModel { RequestId = Activity.Current?.Id ?? HttpContext.TraceIdentifier });
        }
    }
}
