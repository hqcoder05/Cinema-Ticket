$(document).ready(function () {
  // Function để đặt vé phim
  window.bookMovie = function (movieId) {
    console.log("Home page - bookMovie function called with movieId:", movieId);

    // Tạm thời chuyển thẳng đến trang đặt vé để test
    alert("Chuyển đến trang đặt vé cho phim ID: " + movieId);
    window.location.href = `index.php?quanly=ve&movie_id=${movieId}`;

    // Kiểm tra trạng thái đăng nhập (comment tạm thời)
    /*
    const sessionData = document.getElementById("session-data");
    const isLoggedIn = sessionData
      ? sessionData.getAttribute("data-logged-in") === "true"
      : false;

    console.log("Session data found:", !!sessionData);
    console.log("Login status:", isLoggedIn);

    if (isLoggedIn) {
      // Đã đăng nhập, chuyển đến trang đặt vé
      console.log("Redirecting to booking page for movie:", movieId);
      window.location.href = `index.php?quanly=ve&movie_id=${movieId}`;
    } else {
      // Chưa đăng nhập, yêu cầu đăng nhập
      alert("Vui lòng đăng nhập để đặt vé!");
      window.location.href = "index.php?quanly=dangnhap";
    }
    */
  };

  // Smooth scroll cho CTA button
  $(".cta-button").on("click", function (e) {
    if (this.href.includes("#")) {
      e.preventDefault();
      const target = $(this.getAttribute("href"));
      if (target.length) {
        $("html, body").animate(
          {
            scrollTop: target.offset().top - 100,
          },
          800
        );
      }
    }
  });

  // Hover effects cho movie cards
  $(".movie-card").hover(
    function () {
      $(this)
        .css("transform", "translateY(-10px)")
        .css("box-shadow", "0 15px 30px rgba(229, 9, 20, 0.3)");
    },
    function () {
      $(this)
        .css("transform", "translateY(0)")
        .css("box-shadow", "0 5px 15px rgba(0,0,0,0.2)");
    }
  );

  // Hover effects cho promotion cards
  $(".promotion-card").hover(
    function () {
      $(this).css("transform", "scale(1.05)");
    },
    function () {
      $(this).css("transform", "scale(1)");
    }
  );

  // Hover effects cho service cards
  $(".service-card").hover(
    function () {
      $(this).find(".service-icon").css("transform", "scale(1.2)");
      $(this).css("transform", "translateY(-5px)");
    },
    function () {
      $(this).find(".service-icon").css("transform", "scale(1)");
      $(this).css("transform", "translateY(0)");
    }
  );

  // Animation khi scroll vào view
  function animateOnScroll() {
    $(".movie-card, .promotion-card, .service-card").each(function () {
      const elementTop = $(this).offset().top;
      const elementBottom = elementTop + $(this).outerHeight();
      const viewportTop = $(window).scrollTop();
      const viewportBottom = viewportTop + $(window).height();

      if (elementBottom > viewportTop && elementTop < viewportBottom) {
        $(this).addClass("animate-in");
      }
    });
  }

  // Chạy animation khi scroll
  $(window).on("scroll", animateOnScroll);
  animateOnScroll(); // Chạy lần đầu

  // Lazy loading cho images
  if ("IntersectionObserver" in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const img = entry.target;
          img.src = img.dataset.src;
          img.classList.remove("lazy");
          imageObserver.unobserve(img);
        }
      });
    });

    document.querySelectorAll("img[data-src]").forEach((img) => {
      imageObserver.observe(img);
    });
  }
});
