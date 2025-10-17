using System;
using System.Diagnostics;
using System.Linq;
using System.Threading.Tasks;
using Cinema_ticket.Data;
using Cinema_ticket.Models;
using Cinema_ticket.Models.Movie;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Logging;

namespace Cinema_ticket.Controllers
{
    public class MovieController : Controller
    {
        private readonly ILogger<MovieController> _logger;
        private readonly CinemaContext _context;

        public MovieController(ILogger<MovieController> logger, CinemaContext context)
        {
            _logger = logger;
            _context = context;
        }

        // F3: Trang chủ - Hiển thị danh sách phim
        public async Task<IActionResult> Index()
        {
            try
            {
                var movies = await _context.Movies.ToListAsync();
                return View(movies);
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Error loading movies");
                return View(new ErrorViewModel { RequestId = Activity.Current?.Id ?? HttpContext.TraceIdentifier });
            }
        }

        // F4: Danh sách phim
        public async Task<IActionResult> List(string searchTerm = "", string genre = "", bool nowShowing = true)
        {
            try
            {
                var query = _context.Movies.AsQueryable();

                if (!string.IsNullOrEmpty(searchTerm))
                {
                    query = query.Where(m => m.Title.Contains(searchTerm) || m.Description.Contains(searchTerm));
                }

                if (!string.IsNullOrEmpty(genre))
                {
                    query = query.Where(m => m.Genre.Contains(genre));
                }

                if (nowShowing)
                {
                    query = query.Where(m => m.IsNowShowing);
                }

                var movies = await query.ToListAsync();
                return View(movies);
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Error loading movie list");
                return View(new ErrorViewModel { RequestId = Activity.Current?.Id ?? HttpContext.TraceIdentifier });
            }
        }

        // F5: Chi tiết phim
        public async Task<IActionResult> Details(int id)
        {
            try
            {
                var movie = await _context.Movies
                    .Include(m => m.Screenings)
                    .ThenInclude(s => s.Screen)
                    .FirstOrDefaultAsync(m => m.Id == id);

                if (movie == null)
                {
                    return NotFound();
                }

                return View(movie);
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Error loading movie details");
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
