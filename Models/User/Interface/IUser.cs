namespace Cinema_ticket.Models.User.Interface


{
    public interface IUser
    {
        string Uid { get; }
        string Email { get; }
        string DisplayName { get; }
        UserRole Role { get; }

        void ViewProfile();
        public void UpdateProfile(string FirstName, string LastName, string? email, string? phone);
    }
}
