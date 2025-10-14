using Cinema_ticket.Models.User.Interface;
                                                                                                                                                                                                                                                                                                                                                                                        
namespace Cinema_ticket.Models.User
{
    public class AdminUser : BaseUser, IAdmin
    {
        public AdminUser(string uid, string email, string displayName)
            : base(uid, email, displayName, UserRole.Admin)
        {
        }                                                                                                                                                                                                                   

        public void AddMovie(string title, int duration, decimal price)
        {
            Console.WriteLine($"🎬 Thêm phim: {title} ({duration} phút, {price}₫)");
        }

        public void RemoveMovie(int movieId)
        {
            Console.WriteLine($"🗑️ Xoá phim có ID: {movieId}");
        }

        public void ManageUserRoles(string userId, string newRole)
        {
            Console.WriteLine($"⚙️ Cập nhật vai trò người dùng {userId} → {newRole}");
        }

        public void ViewAllBookings()
        {
            Console.WriteLine("📊 Hiển thị toàn bộ đơn đặt vé trong hệ thống");
        }
    }
}