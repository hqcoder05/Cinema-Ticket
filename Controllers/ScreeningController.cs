using System;
using System.Diagnostics;
using System.Linq;
using System.Threading.Tasks;
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

                // Lấy danh sách các rạp có lịch chiếu phim này
                var cinemas = await _context.Screenings
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
                return View(new ErrorViewModel { RequestId = Activity.Current?.Id ?? HttpContext.TraceIdentifier });
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

                // Lấy các lịch chiếu
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
                return View(new ErrorViewModel { RequestId = Activity.Current?.Id ?? HttpContext.TraceIdentifier });
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
                return View(new ErrorViewModel { RequestId = Activity.Current?.Id ?? HttpContext.TraceIdentifier });
            }
        }

        [ResponseCache(Duration = 0, Location = ResponseCacheLocation.None, NoStore = true)]
        public IActionResult Error()
        {
            return View(new ErrorViewModel { RequestId = Activity.Current?.Id ?? HttpContext.TraceIdentifier });
        }
    }
}
