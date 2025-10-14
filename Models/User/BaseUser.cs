using Cinema_ticket.Models.User.Interface;

namespace Cinema_ticket.Models.User;

public class BaseUser : IUser
{
    public String Uid { get; }
    public String Email { get; set; }
    public String DisplayName { get; set; }
    public UserRole Role { get; }

    public BaseUser(string uid, string email, string displayName, UserRole role = UserRole.Customer)
    {
        Uid = uid;
        Email = email;
        DisplayName = displayName;
        Role = role;
    }

    public void ViewProfile()
    {
        Console.WriteLine($" {DisplayName} ({Email})");
    }

    public void UpdateProfile(string firstName, string lastName, string? email, string? phone)
    {
        DisplayName = $"{firstName} {lastName}";
        if (!string.IsNullOrEmpty(email))
            Email = email;

        Console.WriteLine($"Hồ sơ đã được cập nhật: {DisplayName}, Email: {Email}, Phone: {phone ?? "Không có"}");
    }
}