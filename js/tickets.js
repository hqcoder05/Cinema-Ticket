$(document).ready(function () {
  // Khởi tạo biến global
  window.selectedSeats = [];
  window.ticketPrice = 0;
  window.showtimeId = 0;

  // Lấy dữ liệu từ div hidden
  const ticketsData = $("#tickets-data");
  if (ticketsData.length) {
    window.ticketPrice = parseInt(ticketsData.data("ticket-price")) || 0;
    window.showtimeId = parseInt(ticketsData.data("showtime-id")) || 0;
  }

  // Backup: Lấy từ element price nếu có
  if (window.ticketPrice === 0) {
    const priceText = $("#ticket-price").text();
    if (priceText) {
      window.ticketPrice = parseInt(priceText.replace(/[^\d]/g, ""));
    }
  }
});

// Chọn suất chiếu
function selectShowtime(id) {
  // Kiểm tra xem đang ở đâu để redirect đúng
  const currentPath = window.location.pathname;
  let basePath = "";

  if (currentPath.includes("/pages/pages/")) {
    basePath = "../../";
  } else if (currentPath.includes("/pages/")) {
    basePath = "../";
  }

  window.location.href = `${basePath}index.php?quanly=ve&showtime_id=${id}`;
}

// Chọn ghế
function selectSeat(seatElement) {
  const $seat = $(seatElement);
  const seatData = {
    id: $seat.data("seat"),
    row: $seat.data("row"),
    number: $seat.data("number"),
    type: $seat.hasClass("vip") ? "vip" : "standard",
  };
  seatData.price =
    seatData.type === "vip" ? window.ticketPrice * 1.5 : window.ticketPrice;

  if ($seat.hasClass("selected")) {
    // Bỏ chọn ghế
    $seat.removeClass("selected");
    window.selectedSeats = window.selectedSeats.filter(
      (seat) => seat.id !== seatData.id
    );
  } else {
    // Chọn ghế
    if (window.selectedSeats.length >= 8) {
      alert("Bạn chỉ có thể đặt tối đa 8 vé!");
      return;
    }
    $seat.addClass("selected");
    window.selectedSeats.push(seatData);
  }

  updateBookingSummary();
}

// Cập nhật tóm tắt đặt vé
function updateBookingSummary() {
  const seatIds = window.selectedSeats.map((seat) => seat.id).join(", ");
  const ticketCount = window.selectedSeats.length;
  const totalAmount = window.selectedSeats.reduce(
    (total, seat) => total + seat.price,
    0
  );

  // Cập nhật UI
  $("#selected-seats").text(seatIds || "Chưa chọn ghế");
  $("#ticket-count").text(ticketCount);
  $("#total-amount").text(formatNumber(totalAmount));

  // Cập nhật button đặt vé
  const $bookBtn = $("#btn-book-tickets");
  if (ticketCount > 0) {
    $bookBtn.prop("disabled", false).css("background-color", "#e50914");
  } else {
    $bookBtn.prop("disabled", true).css("background-color", "#666");
  }
}

// Đặt vé
function bookTickets() {
  if (window.selectedSeats.length === 0) {
    alert("Vui lòng chọn ít nhất một ghế!");
    return;
  }

  const totalAmount = window.selectedSeats.reduce(
    (total, seat) => total + seat.price,
    0
  );
  const bookingData = {
    showtime_id: window.showtimeId,
    seats: window.selectedSeats,
    total_amount: totalAmount,
  };

  // Lưu dữ liệu đặt vé vào localStorage
  localStorage.setItem("pendingBooking", JSON.stringify(bookingData));

  // Hiển thị modal hỏi về combo
  showComboConfirmModal();
}

// Hiển thị modal xác nhận combo
function showComboConfirmModal() {
  // Tạo modal HTML
  const modalHTML = `
    <div id="combo-confirm-modal" style="
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.8);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 9999;
      font-family: Arial, sans-serif;
      padding: 20px;
      box-sizing: border-box;
    ">
      <div style="
        background: linear-gradient(135deg, #1a1a1a 0%, #2d1810 100%);
        border-radius: 15px;
        padding: 30px;
        max-width: 450px;
        width: 100%;
        text-align: center;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        border: 2px solid #e71a0f;
        max-height: 90vh;
        overflow-y: auto;
      ">
        <div style="
          background: #e71a0f;
          color: white;
          padding: 15px;
          margin: -30px -30px 20px -30px;
          border-radius: 13px 13px 0 0;
          font-size: 20px;
          font-weight: bold;
        ">
          🍿 Combo Bắp Nước
        </div>
        
        <div style="color: #fff; margin-bottom: 25px;">
          <p style="font-size: 18px; margin-bottom: 15px;">
            Bạn có muốn thêm combo bắp nước để trải nghiệm phim thêm thú vị không?
          </p>
          <div style="
            background: rgba(231, 26, 15, 0.1);
            border: 1px solid #e71a0f;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
          ">
            <p style="margin: 5px 0; font-size: 14px;">
              🎬 <strong>Ghế đã chọn:</strong> ${window.selectedSeats
                .map((seat) => seat.id)
                .join(", ")}
            </p>
            <p style="margin: 5px 0; font-size: 14px;">
              💰 <strong>Tổng tiền vé:</strong> ${formatNumber(
                window.selectedSeats.reduce(
                  (total, seat) => total + seat.price,
                  0
                )
              )} VNĐ
            </p>
          </div>
        </div>
        
        <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
          <button onclick="selectCombo()" style="
            background: linear-gradient(45deg, #e71a0f, #ff4444);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(231, 26, 15, 0.3);
            min-width: 160px;
            flex: 1;
            max-width: 200px;
          " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(231, 26, 15, 0.4)'" 
             onmouseout="this.style.transform='translateY(0px)'; this.style.boxShadow='0 4px 15px rgba(231, 26, 15, 0.3)'">
            🍿 Có, chọn combo
          </button>
          
          <button onclick="skipCombo()" style="
            background: linear-gradient(45deg, #666, #888);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            min-width: 160px;
            flex: 1;
            max-width: 200px;
          " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0, 0, 0, 0.4)'" 
             onmouseout="this.style.transform='translateY(0px)'; this.style.boxShadow='0 4px 15px rgba(0, 0, 0, 0.3)'">
            ⏭️ Không, thanh toán luôn
          </button>
        </div>
        
        <p style="
          color: #ccc;
          font-size: 12px;
          margin-top: 20px;
          margin-bottom: 0;
        ">
          💡 Bạn có thể thay đổi lựa chọn ở bước tiếp theo
        </p>
      </div>
    </div>
    
    <style>
      @media (max-width: 480px) {
        #combo-confirm-modal > div {
          padding: 20px !important;
          margin: 10px !important;
        }
        #combo-confirm-modal button {
          font-size: 14px !important;
          padding: 10px 15px !important;
          width: 100% !important;
          max-width: none !important;
          margin: 5px 0 !important;
        }
        #combo-confirm-modal > div > div:first-child {
          font-size: 18px !important;
        }
        #combo-confirm-modal p {
          font-size: 14px !important;
        }
      }
    </style>
  `;

  // Thêm modal vào body
  document.body.insertAdjacentHTML("beforeend", modalHTML);
}

// Chọn combo
function selectCombo() {
  closeComboModal();

  // Chuyển đến trang chọn combo qua routing chính (luôn dùng index.php)
  window.location.href = `index.php?quanly=chon-combo`;
}

// Bỏ qua combo, chuyển thẳng đến thanh toán
function skipCombo() {
  closeComboModal();

  // Đặt flag để checkout biết là đã bỏ qua combo
  localStorage.setItem("skipCombo", "true");

  // Chuyển đến trang checkout qua routing chính (luôn dùng index.php)
  window.location.href = `index.php?quanly=thanh-toan`;
}

// Đóng modal combo
function closeComboModal() {
  const modal = document.getElementById("combo-confirm-modal");
  if (modal) {
    modal.remove();
  }
}

// Khôi phục button đặt vé
function resetBookButton($btn, originalText) {
  $btn.text(originalText).prop("disabled", false);
}

// Utility functions
function formatNumber(number) {
  return new Intl.NumberFormat("vi-VN").format(number);
}

function formatCurrency(amount) {
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
  }).format(amount);
}

// Event handlers với jQuery
$(document).ready(function () {
  // Hover effects cho ghế
  $(document)
    .on("mouseenter", ".seat.available:not(.selected)", function () {
      $(this).css("transform", "scale(1.1)").css("border-color", "#e50914");
    })
    .on("mouseleave", ".seat.available:not(.selected)", function () {
      $(this).css("transform", "scale(1)").css("border-color", "#444");
    });

  // Hover effects cho button
  $(document)
    .on("mouseenter", ".btn-select-showtime, .btn-back", function () {
      $(this).css("transform", "scale(1.05)");
    })
    .on("mouseleave", ".btn-select-showtime, .btn-back", function () {
      $(this).css("transform", "scale(1)");
    });

  // Hover effect cho button đặt vé
  $(document)
    .on("mouseenter", ".btn-book-tickets:not(:disabled)", function () {
      $(this).css({
        "background-color": "#cc0812",
        transform: "scale(1.02)",
      });
    })
    .on("mouseleave", ".btn-book-tickets:not(:disabled)", function () {
      $(this).css({
        "background-color": "#e50914",
        transform: "scale(1)",
      });
    });
});
