$(document).ready(function () {
  // Function để hiển thị chi tiết phim
  window.showMovieDetail = function (movieId) {
    console.log("showMovieDetail called with movieId:", movieId);

    // Hiển thị modal trước
    $("#movieModal").show();

    // Hiển thị loading
    $("#movieDetails").html(
      '<div style="text-align: center; padding: 40px; color: #fff;"><p>Đang tải thông tin phim...</p></div>'
    );

    // Xác định đường dẫn đúng - kiểm tra từ root
    var baseUrl =
      window.location.origin +
      window.location.pathname.replace(/\/[^\/]*$/, "/");
    var ajaxUrl = baseUrl + "pages/actions/get_movie_detail.php";

    console.log("AJAX URL:", ajaxUrl);

    // Gửi AJAX request để lấy chi tiết phim
    $.ajax({
      url: ajaxUrl,
      type: "POST",
      data: { movie_id: movieId },
      dataType: "json",
      timeout: 10000, // 10 seconds timeout
      success: function (response) {
        console.log("AJAX success:", response);
        if (response.success) {
          displayMovieDetail(response.data);
        } else {
          $("#movieDetails").html(`
            <div style="text-align: center; padding: 40px; color: #f44336;">
              <p>Lỗi: ${response.message || "Không thể tải thông tin phim."}</p>
            </div>
          `);
        }
      },
      error: function (xhr, status, error) {
        console.log("AJAX error:", xhr, status, error);
        console.log("Response text:", xhr.responseText);

        // Fallback: hiển thị thông tin cơ bản từ DOM
        const $movieCard = $(
          `button[onclick="showMovieDetail(${movieId})"]`
        ).closest(".movie-card");
        const title = $movieCard.find("h3").text() || "Tên phim";
        const genre = $movieCard.find(".genre").text() || "Thể loại chưa rõ";
        const duration =
          $movieCard.find(".duration").text() || "Thời lượng chưa rõ";
        const rating = $movieCard.find(".rating").text() || "Chưa có đánh giá";
        const posterUrl = $movieCard.find("img").attr("src") || "";

        $("#movieDetails").html(`
          <div class="movie-detail">
            <img src="${posterUrl}" alt="${title}" style="max-width: 200px;">
            <div class="movie-info-detail">
              <h2>${title}</h2>
              <p><strong>Thể loại:</strong> <span class="genre">${genre}</span></p>
              <p><strong>Thời lượng:</strong> ${duration}</p>
              <p><strong>Đạo diễn:</strong> Đang cập nhật</p>
              <p><strong>Diễn viên:</strong> Đang cập nhật</p>
              <p><strong>Đánh giá:</strong> <span class="rating">${rating}</span></p>
              <p><strong>Mô tả:</strong></p>
              <p>Thông tin chi tiết đang được cập nhật. Vui lòng liên hệ rạp để biết thêm thông tin.</p>
              <div style="margin-top: 20px;">
                <button class="btn-book" onclick="bookMovie(${movieId})">Đặt vé ngay</button>
              </div>
            </div>
          </div>
        `);
      },
    });
  };

  // Function để hiển thị thông tin chi tiết phim
  function displayMovieDetail(movie) {
    const html = `
            <div class="movie-detail">
                <img src="${movie.poster_url}" alt="${movie.title}">
                <div class="movie-info-detail">
                    <h2>${movie.title}</h2>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <p><strong>🎭 Thể loại:</strong> <span class="genre">${
                          movie.genre
                        }</span></p>
                        <p><strong>⏱️ Thời lượng:</strong> ${
                          movie.duration
                        } phút</p>
                        <p><strong>🎬 Đạo diễn:</strong> ${
                          movie.director || "Đang cập nhật"
                        }</p>
                        <p><strong>⭐ Đánh giá:</strong> <span class="rating">${
                          movie.rating || "Chưa có"
                        }/10</span></p>
                    </div>
                    <p><strong>🎭 Diễn viên:</strong> ${
                      movie.cast || "Đang cập nhật"
                    }</p>
                    <p><strong>📝 Mô tả:</strong></p>
                    <p style="text-align: justify; margin-bottom: 25px;">${
                      movie.description ||
                      "Thông tin mô tả đang được cập nhật. Hãy liên hệ rạp để biết thêm chi tiết về bộ phim này."
                    }</p>
                    <div style="text-align: center;">
                        <button class="btn-book" onclick="bookMovie(${
                          movie.id
                        })">🎫 Đặt vé ngay</button>
                    </div>
                </div>
            </div>
        `;
    $("#movieDetails").html(html);
  }

  // Function để đặt vé phim - phiên bản đơn giản nhất
  window.bookMovie = function (movieId) {
    console.log("bookMovie called with:", movieId);

    // Debug: in ra URL sẽ chuyển đến
    const url = "index.php?quanly=ve&movie_id=" + movieId;
    console.log("URL to redirect:", url);

    // Chuyển trang đơn giản
    setTimeout(function () {
      window.location = url;
    }, 100);
  };

  // Đóng modal khi click nút close
  $(".close").on("click", function () {
    $("#movieModal").hide();
  });

  // Đóng modal khi click vào nền
  $(window).on("click", function (event) {
    const $modal = $("#movieModal");
    if (event.target === $modal[0]) {
      $modal.hide();
    }
  });

  // Đóng modal bằng phím ESC
  $(document).on("keydown", function (event) {
    if (event.key === "Escape") {
      $("#movieModal").hide();
    }
  });

  // Hover effects cho movie cards
  $(".movie-card").hover(
    function () {
      $(this).css("transform", "translateY(-5px)");
      $(this).find(".movie-overlay").css("opacity", "1");
    },
    function () {
      $(this).css("transform", "translateY(0)");
      $(this).find(".movie-overlay").css("opacity", "0");
    }
  );

  // Hover effects cho buttons
  $(".btn-detail, .btn-book").hover(
    function () {
      $(this).css("transform", "scale(1.05)");
    },
    function () {
      $(this).css("transform", "scale(1)");
    }
  );
});
