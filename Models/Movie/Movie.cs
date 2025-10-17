using System;
using System.Collections.Generic;

namespace Cinema_ticket.Models.Movie
{
    public class Movie
    {
        public int Id { get; set; }
        public string Title { get; set; }
        public string Description { get; set; }
        public string Director { get; set; }
        public string Cast { get; set; }
        public string Genre { get; set; }
        public int Duration { get; set; } // phút
        public string Language { get; set; }
        public string Subtitle { get; set; }
        public string ReleaseDate { get; set; }
        public string PosterUrl { get; set; }
        public string TrailerUrl { get; set; }
        public decimal Rating { get; set; } // 0-10
        public bool IsNowShowing { get; set; }
        public bool IsComingSoon { get; set; }
        public DateTime CreatedAt { get; set; }
        public DateTime UpdatedAt { get; set; }

        // Navigation properties
        public ICollection<Screening> Screenings { get; set; } = new List<Screening>();
    }
}
