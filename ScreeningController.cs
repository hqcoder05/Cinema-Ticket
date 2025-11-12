using System;
using System.Collections.Generic;
using System.Diagnostics;
using System.Linq;
using System.Threading.Tasks;
using System.Transactions;
using Microsoft.AspNetCore.Http;
using Cinema_ticket.Data;
using Cinema_ticket.Models;
using Cinema_ticket.Models.Cinema;
using Cinema_ticket.Models.Movie;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Logging;

namespace Cinema_ticket.Controllers
{
    public class ScreeningController : Controller
    {
        private readonly ILogger<ScreeningController> _logger;
        private readonly CinemaContext _context;

        public ScreeningController(ILogger<ScreeningController> logger, CinemaContext context)
        {
            _logger = logger;
            _context = context;
        }

        // F6: Chọn rạp chiếu và lịch chiếu
        public async Task<IActionResult> SelectCinema(int movieId)
        {
            try
            {
                var movie = await _context.Movies.FirstOrDefaultAsync(m => m.Id == movieId);
                if (movie == null)
                    return NotFound();

                // Lấy danh sách rạp có lịch chiếu phim này (Include để tránh null)
                var cinemas = await _context.Screenings
                    .Include(s => s.Screen)
                    .ThenInclude(sc => sc.Cinema)
                    .Where(s => s.MovieId == movieId && s.ScreeningDateTime > DateTime.Now)
                    .Select(s => s.Screen.Cinema)
                    .Distinct()
                    .ToListAsync();

                ViewBag.MovieId = movieId;
                ViewBag.MovieTitle = movie.Title;
                return View(cinemas);
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Error loading cinemas for screening");
                return View("Error", new ErrorViewModel { RequestId = Activity.Current?.Id ?? HttpContext.TraceIdentifier });
            }
        }

        // Chọn ngày và giờ chiếu
        public async Task<IActionResult> SelectDateTime(int movieId, int cinemaId)
        {
            try
            {
                var movie = await _context.Movies.FirstOrDefaultAsync(m => m.Id == movieId);
                if (movie == null)
                    return NotFound();

                var cinema = await _context.Cinemas.FirstOrDefaultAsync(c => c.Id == cinemaId);
                if (cinema == null)
                    return NotFound();

                var screenings = await _context.Screenings
                    .Where(s => s.MovieId == movieId &&
                                s.Screen.CinemaId == cinemaId &&
                                s.ScreeningDateTime > DateTime.Now &&
                                s.AvailableSeats > 0)
                    .Include(s => s.Screen)
                    .OrderBy(s => s.ScreeningDateTime)
                    .ToListAsync();

                ViewBag.MovieId = movieId;
                ViewBag.MovieTitle = movie.Title;
                ViewBag.CinemaId = cinemaId;
                ViewBag.CinemaName = cinema.Name;
                ViewBag.CinemaAddress = cinema.Address;

                return View(screenings);
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Error loading screenings");
                return View("Error", new ErrorViewModel { RequestId = Activity.Current?.Id ?? HttpContext.TraceIdentifier });
            }
        }

        // F7: Chọn ghế
        public async Task<IActionResult> SelectSeats(int screeningId)
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

                return View(screening);
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Error loading seats");
                return View("Error", new ErrorViewModel { RequestId = Activity.Current?.Id ?? HttpContext.TraceIdentifier });
            }
        }

        // F6: API Lọc Suất chiếu theo Phim, Rạp, Ngày
        [HttpGet("api/screenings")]
        public async Task<IActionResult> GetScreenings(int? movieId, int? cinemaId, DateTime? date)
        {
            try
            {
                var query = _context.Screenings
                    .Include(s => s.Movie)
                    .Include(s => s.Screen)
                    .ThenInclude(sc => sc.Cinema)
                    .Where(s => s.ScreeningDateTime > DateTime.Now)
                    .AsQueryable();

                if (movieId.HasValue)
                    query = query.Where(s => s.MovieId == movieId.Value);

                if (cinemaId.HasValue)
                    query = query.Where(s => s.Screen.CinemaId == cinemaId.Value);

                if (date.HasValue)
                {
                    var startDate = date.Value.Date;
                    var endDate = startDate.AddDays(1);
                    query = query.Where(s => s.ScreeningDateTime >= startDate && s.ScreeningDateTime < endDate);
                }

                var screenings = await query
                    .OrderBy(s => s.ScreeningDateTime)
                    .Select(s => new
                    {
                        s.Id,
                        s.ScreeningDateTime,
                        s.Price,
                        s.AvailableSeats,
                        s.TotalSeats,
                        Movie = new { s.Movie.Id, s.Movie.Title },
                        Cinema = new { s.Screen.Cinema.Id, s.Screen.Cinema.Name, s.Screen.Cinema.Address },
                        Screen = new { s.Screen.Id, s.Screen.ScreenName }
                    })
                    .ToListAsync();

                return Ok(screenings);
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Error filtering screenings");
                return StatusCode(500, new { error = "Internal server error" });
            }
        }

        // F7: API Lấy trạng thái ghế cho suất chiếu
        [HttpGet("api/screenings/{id}/seats")]
        public async Task<IActionResult> GetSeats(int id)
        {
            try
            {
                var screening = await _context.Screenings
                    .Include(s => s.Screen)
                    .FirstOrDefaultAsync(s => s.Id == id);

                if (screening == null)
                    return NotFound(new { error = "Screening not found" });

                var bookedSeats = await _context.TicketDetails
                    .Where(td => td.Ticket.ScreeningId == id && td.Ticket.Status == Models.Booking.TicketStatus.Confirmed)
                    .Select(td => td.SeatLabel)
                    .ToListAsync();

                var lockedSeats = GetLockedSeatsFromSessions(id);

                // layout 8x10
                var seats = new List<object>();
                for (int row = 0; row < 8; row++)
                {
                    for (int col = 1; col <= 10; col++)
                    {
                        var seatLabel = $"{(char)('A' + row)}{col}";
                        var status = "available";

                        if (bookedSeats.Contains(seatLabel)) status = "booked";
                        else if (lockedSeats.Contains(seatLabel)) status = "locked";

                        seats.Add(new { label = seatLabel, row = row + 1, column = col, status });
                    }
                }

                return Ok(new
                {
                    screeningId = id,
                    totalSeats = screening.TotalSeats,
                    availableSeats = screening.AvailableSeats,
                    seats
                });
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Error getting seats");
                return StatusCode(500, new { error = "Internal server error" });
            }
        }

        // F7: API Khóa ghế tạm thời
        [HttpPost("api/screenings/{id}/lock-seats")]
        public IActionResult LockSeats(int id, [FromBody] List<string> seatLabels)
        {
            try
            {
                if (seatLabels == null || !seatLabels.Any())
                    return BadRequest(new { error = "No seats specified" });

                var sessionKey = $"locked_seats_{id}";
                var lockedSeats = HttpContext.Session.GetString(sessionKey);
                var currentLocked = string.IsNullOrEmpty(lockedSeats) ? new List<string>() : lockedSeats.Split(',').ToList();

                foreach (var seat in seatLabels)
                    if (!currentLocked.Contains(seat)) currentLocked.Add(seat);

                HttpContext.Session.SetString(sessionKey, string.Join(",", currentLocked));
                HttpContext.Session.SetString($"{sessionKey}_timestamp", DateTime.UtcNow.ToString());

                return Ok(new { lockedSeats = currentLocked });
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Error locking seats");
                return StatusCode(500, new { error = "Internal server error" });
            }
        }

        // API: Unlock ghế hết hạn
        [HttpPost("api/screenings/{id}/unlock-expired")]
        public IActionResult UnlockExpiredSeats(int id)
        {
            try
            {
                var sessionKey = $"locked_seats_{id}";
                var timestampKey = $"{sessionKey}_timestamp";

                var timestampStr = HttpContext.Session.GetString(timestampKey);
                if (!string.IsNullOrEmpty(timestampStr))
                {
                    var timestamp = DateTime.Parse(timestampStr);
                    if ((DateTime.UtcNow - timestamp).TotalMinutes > 10)
                    {
                        HttpContext.Session.Remove(sessionKey);
                        HttpContext.Session.Remove(timestampKey);
                    }
                }

                return Ok(new { message = "Expired locks checked" });
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Error unlocking expired seats");
                return StatusCode(500, new { error = "Internal server error" });
            }
        }

        // API: Kiểm tra tình trạng nhiều ghế
        [HttpPost("api/screenings/{id}/check-seats")]
        public async Task<IActionResult> CheckSeatsAvailability(int id, [FromBody] List<string> seatLabels)
        {
            try
            {
                if (seatLabels == null || !seatLabels.Any())
                    return BadRequest(new { error = "No seats specified" });

                var screening = await _context.Screenings.FirstOrDefaultAsync(s => s.Id == id);
                if (screening == null)
                    return NotFound(new { error = "Screening not found" });

                var bookedSeats = await _context.TicketDetails
                    .Where(td => td.Ticket.ScreeningId == id && td.Ticket.Status == Models.Booking.TicketStatus.Confirmed)
                    .Select(td => td.SeatLabel)
                    .ToListAsync();

                var lockedSeats = GetLockedSeatsFromSessions(id);

                var results = new List<SeatAvailability>();
                foreach (var seatLabel in seatLabels)
                {
                    var status = "available";
                    if (bookedSeats.Contains(seatLabel)) status = "booked";
                    else if (lockedSeats.Contains(seatLabel)) status = "locked";

                    results.Add(new SeatAvailability(seatLabel: seatLabel, status: status, available: status == "available"));
                }

                return Ok(new
                {
                    screeningId = id,
                    seats = results,
                    allAvailable = results.All(r => r.available)
                });
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Error checking seats availability");
                return StatusCode(500, new { error = "Internal server error" });
            }
        }

        // API: Tạo booking draft
        [HttpPost("api/screenings/{id}/create-draft")]
        public async Task<IActionResult> CreateBookingDraft(int id, [FromBody] CreateDraftRequest request)
        {
            using (var transaction = new TransactionScope(TransactionScopeAsyncFlowOption.Enabled))
            {
                try
                {
                    if (request.SeatLabels == null || !request.SeatLabels.Any())
                        return BadRequest(new { error = "No seats specified" });

                    var screening = await _context.Screenings.FirstOrDefaultAsync(s => s.Id == id);
                    if (screening == null)
                        return NotFound(new { error = "Screening not found" });

                    var bookedSeats = await _context.TicketDetails
                        .Where(td => td.Ticket.ScreeningId == id && td.Ticket.Status == Models.Booking.TicketStatus.Confirmed)
                        .Select(td => td.SeatLabel)
                        .ToListAsync();

                    var lockedSeats = GetLockedSeatsFromSessions(id);

                    foreach (var seat in request.SeatLabels)
                        if (bookedSeats.Contains(seat) || lockedSeats.Contains(seat))
                            return BadRequest(new { error = $"Seat {seat} is not available" });

                    var draftTicket = new Models.Booking.Ticket
                    {
                        UserId = request.UserId,
                        ScreeningId = id,
                        TotalPrice = screening.Price * request.SeatLabels.Count,
                        CustomerName = request.CustomerName ?? "Draft",
                        CustomerEmail = request.CustomerEmail ?? "draft@example.com",
                        CustomerPhone = request.CustomerPhone ?? "0000000000",
                        Status = Models.Booking.TicketStatus.Pending
                    };

                    _context.Tickets.Add(draftTicket);
                    await _context.SaveChangesAsync();

                    foreach (var seat in request.SeatLabels)
                    {
                        var ticketDetail = new Models.Booking.TicketDetail
                        {
                            TicketId = draftTicket.Id,
                            SeatLabel = seat,
                            Price = screening.Price
                        };
                        _context.TicketDetails.Add(ticketDetail);
                    }

                    await _context.SaveChangesAsync();

                    var sessionKey = $"locked_seats_{id}";
                    var currentLocked = GetLockedSeatsFromSessions(id);
                    foreach (var seat in request.SeatLabels)
                        if (!currentLocked.Contains(seat)) currentLocked.Add(seat);

                    HttpContext.Session.SetString(sessionKey, string.Join(",", currentLocked));
                    HttpContext.Session.SetString($"{sessionKey}_timestamp", DateTime.UtcNow.ToString());

                    transaction.Complete();

                    return Ok(new
                    {
                        draftId = draftTicket.Id,
                        screeningId = id,
                        totalPrice = draftTicket.TotalPrice,
                        seatCount = request.SeatLabels.Count,
                        expiresAt = DateTime.UtcNow.AddMinutes(10),
                        message = "Draft booking created successfully"
                    });
                }
                catch (Exception ex)
                {
                    _logger.LogError(ex, "Error creating booking draft");
                    return StatusCode(500, new { error = "Internal server error" });
                }
            }
        }

        // API: Hủy draft booking
        [HttpPost("api/bookings/drafts/{draftId}/cancel")]
        public async Task<IActionResult> CancelBookingDraft(int draftId)
        {
            try
            {
                var draftTicket = await _context.Tickets
                    .Include(t => t.TicketDetails)
                    .FirstOrDefaultAsync(t => t.Id == draftId && t.Status == Models.Booking.TicketStatus.Pending);

                if (draftTicket == null)
                    return NotFound(new { error = "Draft booking not found" });

                if ((DateTime.UtcNow - draftTicket.CreatedAt).TotalMinutes > 10)
                    return BadRequest(new { error = "Draft booking has expired" });

                var seatLabels = draftTicket.TicketDetails.Select(td => td.SeatLabel).ToList();

                _context.TicketDetails.RemoveRange(draftTicket.TicketDetails);
                _context.Tickets.Remove(draftTicket);
                await _context.SaveChangesAsync();

                var sessionKey = $"locked_seats_{draftTicket.ScreeningId}";
                var currentLocked = GetLockedSeatsFromSessions(draftTicket.ScreeningId)
                    .Where(s => !seatLabels.Contains(s))
                    .ToList();
                HttpContext.Session.SetString(sessionKey, string.Join(",", currentLocked));

                return Ok(new { message = "Draft booking cancelled successfully" });
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Error cancelling draft booking");
                return StatusCode(500, new { error = "Internal server error" });
            }
        }

        // API: Xác nhận draft booking
        [HttpPost("api/bookings/drafts/{draftId}/confirm")]
        public async Task<IActionResult> ConfirmBookingDraft(int draftId, [FromBody] ConfirmDraftRequest request)
        {
            using (var transaction = new TransactionScope(TransactionScopeAsyncFlowOption.Enabled))
            {
                try
                {
                    var draftTicket = await _context.Tickets
                        .Include(t => t.Screening)
                        .Include(t => t.TicketDetails)
                        .FirstOrDefaultAsync(t => t.Id == draftId && t.Status == Models.Booking.TicketStatus.Pending);

                    if (draftTicket == null)
                        return NotFound(new { error = "Draft booking not found" });

                    if ((DateTime.UtcNow - draftTicket.CreatedAt).TotalMinutes > 10)
                        return BadRequest(new { error = "Draft booking has expired" });

                    draftTicket.CustomerName = request.CustomerName;
                    draftTicket.CustomerEmail = request.CustomerEmail;
                    draftTicket.CustomerPhone = request.CustomerPhone;
                    draftTicket.Status = Models.Booking.TicketStatus.Confirmed;
                    draftTicket.UpdatedAt = DateTime.UtcNow;

                    draftTicket.Screening.AvailableSeats -= draftTicket.TicketDetails.Count;
                    draftTicket.Screening.UpdatedAt = DateTime.UtcNow;

                    await _context.SaveChangesAsync();

                    var sessionKey = $"locked_seats_{draftTicket.ScreeningId}";
                    var seatLabels = draftTicket.TicketDetails.Select(td => td.SeatLabel).ToList();
                    var currentLocked = GetLockedSeatsFromSessions(draftTicket.ScreeningId)
                        .Where(s => !seatLabels.Contains(s))
                        .ToList();
                    HttpContext.Session.SetString(sessionKey, string.Join(",", currentLocked));

                    transaction.Complete();

                    return Ok(new
                    {
                        bookingId = draftTicket.Id,
                        totalPrice = draftTicket.TotalPrice,
                        message = "Booking confirmed successfully"
                    });
                }
                catch (Exception ex)
                {
                    _logger.LogError(ex, "Error confirming draft booking");
                    return StatusCode(500, new { error = "Internal server error" });
                }
            }
        }

        // Helper: lấy locked seats từ session hiện tại (demo)
        private List<string> GetLockedSeatsFromSessions(int screeningId)
        {
            var sessionKey = $"locked_seats_{screeningId}";
            var lockedSeats = HttpContext.Session.GetString(sessionKey);
            return string.IsNullOrEmpty(lockedSeats) ? new List<string>() : lockedSeats.Split(',').ToList();
        }
    }
}

// Request models cho APIs
public class CreateDraftRequest
{
    public string? UserId { get; set; }
    public List<string> SeatLabels { get; set; } = new List<string>();
    public string? CustomerName { get; set; }
    public string? CustomerEmail { get; set; }
    public string? CustomerPhone { get; set; }
}

public class ConfirmDraftRequest
{
    public string CustomerName { get; set; } = "";
    public string CustomerEmail { get; set; } = "";
    public string CustomerPhone { get; set; } = "";
}

// DTO kết quả check ghế
public record SeatAvailability(string seatLabel, string status, bool available);
