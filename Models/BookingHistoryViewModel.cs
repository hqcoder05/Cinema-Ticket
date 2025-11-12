using System;
using Cinema_ticket.Models.Booking;

namespace Cinema_ticket.Models
{
    public class BookingHistoryViewModel
    {
        public int Id { get; set; }
        public string MovieTitle { get; set; } = "";
        public DateTime ScreeningDateTime { get; set; }
        public string CinemaName { get; set; } = "";
        public string ScreenName { get; set; } = "";
        public string SeatLabels { get; set; } = "";
        public decimal TotalPrice { get; set; }
        public TicketStatus Status { get; set; }
        public DateTime CreatedAt { get; set; }
    }
}