namespace Cinema_ticket.Models.User.Interface
{
    public interface ICustomer : IUser
    {
        void ViewHistoryBooking();
        void BookTicket(int movieId);
        void CancelBooking(int bookingId);
    }
}
