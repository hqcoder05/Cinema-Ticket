namespace Cinema_ticket.Models.User.Interface
{
    public interface IUser
    {
        string FullName { get; }
        string Email { get; }
        UserRole Role { get; }

        void ViewProfile();
        void UpdateProfile(string fullName, string email);
        void DisplayName();
    }
}
