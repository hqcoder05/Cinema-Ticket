namespace Cinema_ticket.Models.User.Interface
{
    public interface ICustomer : IUser
    {
        public void ViewBookingHistory();
        public void BookTicket(int showtimeId, int seatId);
        void CancelBooking(int bookingId);
    }
}
