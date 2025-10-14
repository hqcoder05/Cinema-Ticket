namespace Cinema_ticket.Models.User.Interface
{
    public interface IAdmin : IUser
    {
        void AddMovie(string title, int duration, decimal price);
        void RemoveMovie(int movieId);
        void ManageUserRoles(string userId, string newRole);
        void ViewAllBookings();
    }
}