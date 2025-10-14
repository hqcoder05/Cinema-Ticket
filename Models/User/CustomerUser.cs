using Cinema_ticket.Models.User.Interface;

namespace Cinema_ticket.Models.User
{
    public class CustomerUser : BaseUser, ICustomer
    {
        public CustomerUser(string uid, string email, string displayName)
            : base(uid, email, displayName, UserRole.Customer) { }

        public void ViewBookingHistory()
        {
            Console.WriteLine("Xem lịch sử đặt vé của người dùng");
        }

        public void BookTicket(int showtimeId, int seatId)
        {
            Console.WriteLine($"Đặt vé: Suất {showtimeId}, Ghế {seatId}");
        }

        public void CancelBooking(int bookingId)
        {
            Console.WriteLine($"Huỷ vé có ID: {bookingId}");
        }
    }
}