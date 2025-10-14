namespace Cinema_ticket.Models.User;

public class User
{
    public long Id { get; set; }
    public string FirebaseUid { get; set; } = default!;
    public string? Email { get; set; }
    public string FirstName { get; set; }
    public string LastName { get; set; }
    public string? PhoneNumber { get; set; }
    public UserRole Role { get; set; } = UserRole.Customer;
    
    public DateTime CreatedAt { get; set; }
    public DateTime UpdatedAt { get; set; }
}