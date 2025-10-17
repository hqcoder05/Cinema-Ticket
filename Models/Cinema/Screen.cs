using System;
using System.Collections.Generic;
using Cinema_ticket.Models.Movie;

namespace Cinema_ticket.Models.Cinema
{
    public class Screen
    {
        public int Id { get; set; }
        public int CinemaId { get; set; }
        public string ScreenName { get; set; }
        public int Capacity { get; set; }
        public DateTime CreatedAt { get; set; }
        public DateTime UpdatedAt { get; set; }

        // Navigation properties
        public Cinema Cinema { get; set; }
        public ICollection<Screening> Screenings { get; set; } = new List<Screening>();
    }
}
