using System;
using System.Collections.Generic;

namespace Cinema_ticket.Models.Cinema
{
    public class Cinema
    {
        public int Id { get; set; }
        public string Name { get; set; }
        public string Address { get; set; }
        public string Phone { get; set; }
        public string City { get; set; }
        public string District { get; set; }
        public DateTime CreatedAt { get; set; }
        public DateTime UpdatedAt { get; set; }

        // Navigation properties
        public ICollection<Screen> Screens { get; set; } = new List<Screen>();
    }
}
