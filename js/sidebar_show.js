// Sidebar JavaScript - Cập nhật cho thiết kế mới
$(document).ready(function () {
  // Animation cho các menu items
  $(".genre-menu a, .quick-menu a").hover(
    function () {
      $(this).addClass("hover-effect");
    },
    function () {
      $(this).removeClass("hover-effect");
    }
  );

  // Animation cho promo items
  $(".promo-item").hover(
    function () {
      $(this).find("img").css("transform", "scale(1.1)");
    },
    function () {
      $(this).find("img").css("transform", "scale(1)");
    }
  );

  // Smooth scroll cho các links nội bộ
  $('.genre-menu a[href^="#"], .quick-menu a[href^="#"]').click(function (e) {
    e.preventDefault();
    var target = $(this).attr("href");
    if ($(target).length) {
      $("html, body").animate(
        {
          scrollTop: $(target).offset().top - 80,
        },
        500
      );
    }
  });

  // Loading animation cho stats
  animateStats();

  // Auto refresh stats mỗi 30 giây
  setInterval(animateStats, 30000);

  // Responsive sidebar toggle cho mobile
  if (window.innerWidth <= 768) {
    createMobileSidebarToggle();
  }
});

// Animation cho số liệu thống kê
function animateStats() {
  $(".stat-number").each(function () {
    var $this = $(this);
    var target = parseInt($this.text());
    var current = 0;
    var increment = target / 20;

    var timer = setInterval(function () {
      current += increment;
      if (current >= target) {
        current = target;
        clearInterval(timer);
      }
      $this.text(Math.floor(current));
    }, 50);
  });
}

// Tạo nút toggle cho mobile
function createMobileSidebarToggle() {
  // Kiểm tra xem đã có toggle button chưa
  if ($(".sidebar-toggle").length === 0) {
    var toggleButton = $('<button class="sidebar-toggle">☰ Menu</button>');
    toggleButton.css({
      position: "fixed",
      top: "20px",
      right: "20px",
      "z-index": "1000",
      background: "#e71a0f",
      color: "white",
      border: "none",
      padding: "10px 15px",
      "border-radius": "5px",
      cursor: "pointer",
      display: "none",
    });

    $("body").append(toggleButton);

    // Toggle sidebar visibility
    toggleButton.click(function () {
      $(".sidebar").slideToggle(300);
    });

    // Hiển thị toggle button trên mobile
    if (window.innerWidth <= 768) {
      toggleButton.show();
      $(".sidebar").hide();
    }
  }
}

// Xử lý resize window
$(window).resize(function () {
  if (window.innerWidth <= 768) {
    createMobileSidebarToggle();
    $(".sidebar-toggle").show();
    $(".sidebar").hide();
  } else {
    $(".sidebar-toggle").hide();
    $(".sidebar").show();
  }
});

// Lazy loading cho hình ảnh promo
$(window).scroll(function () {
  $(".promo-item img").each(function () {
    var $img = $(this);
    if (!$img.hasClass("loaded")) {
      var imageTop = $img.offset().top;
      var imageHeight = $img.outerHeight();
      var windowTop = $(window).scrollTop();
      var windowHeight = $(window).height();

      if (
        imageTop < windowTop + windowHeight &&
        imageTop + imageHeight > windowTop
      ) {
        $img.addClass("loaded");
        $img.css("opacity", "1");
      }
    }
  });
});

// Hiệu ứng fade in cho các section khi load
$(window).on("load", function () {
  $(".sidebar-section").each(function (index) {
    $(this)
      .delay(index * 100)
      .fadeIn(500);
  });
});

// Click tracking cho analytics (tùy chọn)
$(".genre-menu a, .quick-menu a, .promo-item").click(function () {
  var clickedElement = $(this).text() || $(this).find("h4").text();
  console.log("Sidebar clicked:", clickedElement);

  // Có thể gửi dữ liệu analytics tại đây
  // ga('send', 'event', 'Sidebar', 'click', clickedElement);
});

// Keyboard accessibility
$(document).keydown(function (e) {
  // ESC key để đóng sidebar trên mobile
  if (e.keyCode === 27 && window.innerWidth <= 768) {
    $(".sidebar").slideUp(300);
  }
});

// CSS Animation helper
function addAnimationCSS() {
  var css = `
        .promo-item img {
            transition: transform 0.3s ease, opacity 0.3s ease;
            opacity: 0.8;
        }
        
        .promo-item img.loaded {
            opacity: 1;
        }
        
        .hover-effect {
            transform: translateX(5px) !important;
        }
        
        .sidebar-section {
            display: none;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 70px;
                left: 0;
                right: 0;
                z-index: 999;
                width: 100%;
                max-height: 80vh;
                overflow-y: auto;
            }
        }
    `;

  $("<style>").text(css).appendTo("head");
}

// Khởi chạy animation CSS
addAnimationCSS();
