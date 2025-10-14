// Data/CinemaContext.cs
using System.Data.Common;
using System.Threading.Tasks;
using Microsoft.EntityFrameworkCore;

namespace Cinema_ticket.Data
{
    public class CinemaContext : DbContext
    {
        public CinemaContext(DbContextOptions<CinemaContext> options) : base(options) {}

        public DbConnection Connection => Database.GetDbConnection();

        public Task<bool> CanConnectAsync() => Database.CanConnectAsync();
    }
}