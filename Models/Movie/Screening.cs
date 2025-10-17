using System;
using Cinema_ticket.Models.Cinema;

namespace Cinema_ticket.Models.Movie
{
    public class Screening
    {
        public int Id { get; set; }
        public int MovieId { get; set; }
        public int ScreenId { get; set; }
        public DateTime ScreeningDateTime { get; set; }
        public decimal Price { get; set; }
        public int AvailableSeats { get; set; }
        public int TotalSeats { get; set; }
        public DateTime CreatedAt { get; set; }
        public DateTime UpdatedAt { get; set; }

        // Navigation properties
        public Movie Movie { get; set; }
        public Screen Screen { get; set; }
    }
}
