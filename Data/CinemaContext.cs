// Data/CinemaContext.cs
using System.Data.Common;
using System.Threading.Tasks;
using Microsoft.EntityFrameworkCore;
using Cinema_ticket.Models.Movie;
using Cinema_ticket.Models.Cinema;

namespace Cinema_ticket.Data
{
    public class CinemaContext : DbContext
    {
        public CinemaContext(DbContextOptions<CinemaContext> options) : base(options) {}

        public DbConnection Connection => Database.GetDbConnection();

        public Task<bool> CanConnectAsync() => Database.CanConnectAsync();

        public DbSet<Movie> Movies { get; set; }
        public DbSet<Cinema> Cinemas { get; set; }
        public DbSet<Screen> Screens { get; set; }
        public DbSet<Screening> Screenings { get; set; }

        protected override void OnModelCreating(ModelBuilder modelBuilder)
        {
            base.OnModelCreating(modelBuilder);

            // Configure relationships
            modelBuilder.Entity<Screen>()
                .HasOne(s => s.Cinema)
                .WithMany(c => c.Screens)
                .HasForeignKey(s => s.CinemaId);

            modelBuilder.Entity<Screening>()
                .HasOne(s => s.Movie)
                .WithMany(m => m.Screenings)
                .HasForeignKey(s => s.MovieId);

            modelBuilder.Entity<Screening>()
                .HasOne(s => s.Screen)
                .WithMany(sc => sc.Screenings)
                .HasForeignKey(s => s.ScreenId);
        }
    }
}