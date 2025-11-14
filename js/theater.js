// Global variables
let currentCityId = null;
let isLoading = false;

// Global function để có thể gọi từ HTML onclick với city_id
window.showTheaters = function (cityId) {
  console.log("Loading theaters for city ID:", cityId);

  if (isLoading) {
    console.log("Already loading, skipping...");
    return;
  }

  if (currentCityId === cityId) {
    console.log("Same city selected, skipping...");
    return;
  }

  currentCityId = cityId;
  isLoading = true;

  // Show loading indicator
  $("#loading-indicator").show();

  // Update active city
  $(".cgv-city-col li").removeClass("active");
  $("#city-" + cityId).addClass("active");

  // Load theaters via AJAX
  $.get(
    "pages/actions/get_theater_showtimes.php?action=get_theaters&city_id=" +
      cityId
  )
    .done(function (theaters) {
      console.log("Loaded theaters:", theaters);
      displayTheaters(theaters, cityId);
    })
    .fail(function (xhr, status, error) {
      console.error("Error loading theaters:", error);
      showError("Không thể tải danh sách rạp. Vui lòng thử lại!");
    })
    .always(function () {
      $("#loading-indicator").hide();
      isLoading = false;
    });
};

// Function to display theaters
function displayTheaters(theaters, cityId) {
  const container = $("#theaters-content");

  if (!theaters || theaters.length === 0) {
    container.html(`
      <div class="empty-state">
        <div>🏢</div>
        <h3>Chưa có rạp nào</h3>
        <p>Khu vực này hiện chưa có rạp CGV.</p>
      </div>
    `);
    return;
  }

  // Get city name from citiesData
  const cityName = getCityName(cityId);

  // Build theaters grid
  let html = `
    <div class="theaters-header">
      <h3 style="color: #e71a0f; text-align: center; margin-bottom: 20px;">
        📍 DANH SÁCH RẬP CGV - ${cityName.toUpperCase()}
      </h3>
    </div>
    <div class="theaters-grid">
  `;

  // Tạo một theater card cho mỗi theater
  theaters.forEach((theater) => {
    const phone = theater.phone || "Chưa cập nhật";

    html += `
      <div class="cgv-theater-list">
        <ul>
          <li onclick="showTheaterInfo('${escapeHtml(
            theater.name
          )}', '${escapeHtml(theater.location)}', '${escapeHtml(phone)}')">
            <strong>${escapeHtml(theater.name)}</strong>
            <br><small style="color: #aaa;">${escapeHtml(
              theater.location
            )}</small>
          </li>
        </ul>
      </div>
    `;
  });

  html += "</div>";
  container.html(html);
}

// Helper function to get city name by ID
function getCityName(cityId) {
  if (window.citiesData) {
    const city = window.citiesData.find((c) => c.id == cityId);
    return city ? city.name : "Khu vực";
  }
  return "Khu vực";
}

// Helper function to escape HTML
function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text || "";
  return div.innerHTML;
}

// Global function để hiển thị thông tin rạp
window.showTheaterInfo = function (name, address, phone) {
  console.log("Showing theater info:", name);

  const content = `
    <h2 style="color: #e71a0f; text-align: center; margin-bottom: 25px;">
      🏢 ${escapeHtml(name)}
    </h2>
    
    <div style="margin-bottom: 20px;">
      <div style="display: flex; align-items: center; margin-bottom: 15px;">
        <span style="color: #e71a0f; margin-right: 10px; font-size: 18px;">📍</span>
        <div>
          <strong>Địa chỉ:</strong><br>
          <span style="color: #ccc;">${escapeHtml(address)}</span>
        </div>
      </div>
      
      <div style="display: flex; align-items: center; margin-bottom: 15px;">
        <span style="color: #e71a0f; margin-right: 10px; font-size: 18px;">📞</span>
        <div>
          <strong>Điện thoại:</strong><br>
          <span style="color: #ccc;">${escapeHtml(phone)}</span>
        </div>
      </div>
      
      <div style="display: flex; align-items: center; margin-bottom: 15px;">
        <span style="color: #e71a0f; margin-right: 10px; font-size: 18px;">🎬</span>
        <div>
          <strong>Dịch vụ:</strong><br>
          <span style="color: #ccc;">Bán vé online, Đặt vé trước, Combo bỏng nước</span>
        </div>
      </div>
      
      <div style="display: flex; align-items: center; margin-bottom: 15px;">
        <span style="color: #e71a0f; margin-right: 10px; font-size: 18px;">⏰</span>
        <div>
          <strong>Giờ hoạt động:</strong><br>
          <span style="color: #ccc;">9:00 - 23:00 (Hàng ngày)</span>
        </div>
      </div>
    </div>
    
    <div style="display: flex; gap: 10px; margin-bottom: 20px; justify-content: center; flex-wrap: wrap;">
      <button onclick="openGoogleMaps('${escapeHtml(name)}', '${escapeHtml(
    address
  )}')" 
              style="background: linear-gradient(135deg, #4285f4, #34a853); color: white; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: bold; transition: all 0.3s ease; display: flex; align-items: center; gap: 8px; min-width: 140px; justify-content: center;">
        🗺️ Xem bản đồ
      </button>
      <button onclick="viewTheaterShowtimes('${escapeHtml(name)}')" 
              style="background: linear-gradient(135deg, #e71a0f, #c41e3a); color: white; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: bold; transition: all 0.3s ease; display: flex; align-items: center; gap: 8px; min-width: 140px; justify-content: center;">
        🎬 Lịch chiếu
      </button>
    </div>
    
    <div style="background: rgba(231, 26, 15, 0.1); padding: 15px; border-radius: 8px; border-left: 4px solid #e71a0f;">
      <h4 style="color: #e71a0f; margin: 0 0 10px 0;">💡 Lưu ý</h4>
      <p style="margin: 0; color: #ccc; font-size: 14px;">
        Vui lòng liên hệ trực tiếp với rạp để biết thông tin lịch chiếu chi tiết và đặt vé.
      </p>
    </div>
  `;

  $("#theater-modal-content").html(content);
  $("#theater-modal").show();
};

// Global function để đóng modal
window.closeTheaterModal = function () {
  $("#theater-modal").hide();
};

// Function mở Google Maps
window.openGoogleMaps = function (theaterName, address) {
  console.log("Opening Google Maps for:", theaterName);

  // Tạo query search cho Google Maps
  const searchQuery = encodeURIComponent(`${theaterName} ${address} Vietnam`);
  const mapsUrl = `https://www.google.com/maps/search/?api=1&query=${searchQuery}`;

  // Mở trong tab mới
  window.open(mapsUrl, "_blank");
};

// Function xem lịch chiếu rạp
window.viewTheaterShowtimes = function (theaterName) {
  console.log("Viewing showtimes for:", theaterName);

  // Đóng modal
  closeTheaterModal();

  // Kiểm tra đường dẫn hiện tại để redirect đúng
  const currentPath = window.location.pathname;
  let basePath = "";

  if (currentPath.includes("/pages/pages/")) {
    basePath = "../../";
  } else if (currentPath.includes("/pages/")) {
    basePath = "../";
  }

  // Chuyển đến trang đặt vé với thông tin rạp
  const theaterParam = encodeURIComponent(theaterName);
  window.location.href = `${basePath}index.php?quanly=ve&theater=${theaterParam}`;
};

// Helper function để hiển thị lỗi
function showError(message) {
  const container = $("#theaters-content");
  container.html(`
    <div class="empty-state" style="color: #e71a0f;">
      <div>⚠️</div>
      <h3>Có lỗi xảy ra</h3>
      <p>${escapeHtml(message)}</p>
      <button onclick="location.reload()" 
              style="background: #e71a0f; color: white; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; margin-top: 20px; font-size: 14px; transition: all 0.3s ease;">
        🔄 Thử lại
      </button>
    </div>
  `);
}

// Document ready
$(document).ready(function () {
  console.log("Theater page initialized");

  // Close modal when clicking outside
  $("#theater-modal").click(function (e) {
    if (e.target === this) {
      closeTheaterModal();
    }
  });

  // Close modal with ESC key
  $(document).keydown(function (e) {
    if (e.keyCode === 27) {
      // ESC key
      closeTheaterModal();
    }
  });

  // Gán sự kiện click cho các tỉnh/thành (nếu cần fallback)
  $(document).on("click", ".cgv-city-col li", function () {
    const cityId = $(this).attr("id")?.replace("city-", "");
    if (cityId && !isNaN(cityId)) {
      showTheaters(parseInt(cityId));
    }
  });

  // Auto-select first city if available
  if (window.citiesData && window.citiesData.length > 0) {
    const firstCity = window.citiesData[0];
    console.log("Auto-selecting first city:", firstCity.name);
    currentCityId = firstCity.id;
  }

  console.log("Cities data loaded:", window.citiesData?.length || 0, "cities");
});
