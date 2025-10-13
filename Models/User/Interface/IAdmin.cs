namespace Cinema_ticket.Models.User.Interface
{
    public interface IAdmin : IUser
    {
        void ManageUsers();
        void ManageMovies();
    }
}
