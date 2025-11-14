// Auto Logout System - Tự động đăng xuất khi đóng tab/browser
(function () {
  "use strict";

  // Kiểm tra nếu user đã đăng nhập
  function isLoggedIn() {
    const body = document.body;
    return body && body.getAttribute("data-logged-in") === "true";
  }

  // Gửi request đăng xuất
  function sendLogoutRequest() {
    if (!isLoggedIn()) return;

    // Sử dụng sendBeacon để đảm bảo request được gửi ngay cả khi tab đóng
    const logoutUrl = "pages/actions/logout_process.php";

    if (navigator.sendBeacon) {
      navigator.sendBeacon(logoutUrl);
    } else {
      // Fallback cho browser cũ
      const xhr = new XMLHttpRequest();
      xhr.open("POST", logoutUrl, false); // Synchronous request
      xhr.send();
    }
  }

  // Biến để track navigation nội bộ
  let isInternalNavigation = false;

  // Detect navigation nội bộ
  document.addEventListener("click", function (event) {
    const target = event.target.closest("a");
    if (target && target.href) {
      const currentDomain = window.location.hostname;
      const linkDomain = new URL(target.href).hostname;

      // Nếu là link nội bộ (cùng domain), đánh dấu là internal navigation
      if (linkDomain === currentDomain || linkDomain === "") {
        isInternalNavigation = true;
        // Reset flag sau 100ms để đảm bảo nó hoạt động cho beforeunload
        setTimeout(() => {
          isInternalNavigation = false;
        }, 100);
      }
    }
  });

  // Detect form submit nội bộ
  document.addEventListener("submit", function (event) {
    isInternalNavigation = true;
    setTimeout(() => {
      isInternalNavigation = false;
    }, 100);
  });

  // TEMPORARILY DISABLED - Detect khi user đóng tab/browser
  /*
  window.addEventListener("beforeunload", function (event) {
    if (isLoggedIn() && !isInternalNavigation) {
      // Chỉ logout khi thực sự đóng tab/browser
      sendLogoutRequest();
    }
  });
  */

  // TEMPORARILY DISABLED - Auto logout after inactivity
  let inactiveTimer;
  const INACTIVE_TIME = 60 * 60 * 1000; // 60 phút (tăng lên)

  function resetInactiveTimer() {
    clearTimeout(inactiveTimer);
    // TEMPORARILY DISABLED
    /*
    if (isLoggedIn()) {
      inactiveTimer = setTimeout(function () {
        // Redirect đến trang đăng xuất
        alert("Phiên đăng nhập đã hết hạn do không hoạt động!");
        window.location.href = "pages/actions/logout_process.php";
      }, INACTIVE_TIME);
    }
    */
  }

  // Track user activity
  [
    "mousedown",
    "mousemove",
    "keypress",
    "scroll",
    "touchstart",
    "click",
  ].forEach(function (event) {
    document.addEventListener(event, resetInactiveTimer, true);
  });

  // Khởi tạo timer khi load trang
  document.addEventListener("DOMContentLoaded", function () {
    if (isLoggedIn()) {
      resetInactiveTimer();
      console.log("🔐 Auto logout system activated");
      console.log("📍 Current page:", window.location.href);
    } else {
      console.log("👤 User not logged in, auto logout disabled");
    }
  });

  // TEMPORARILY DISABLED - Tab focus detection
  /*
  let tabFocusTime = Date.now();

  window.addEventListener("blur", function () {
    tabFocusTime = Date.now();
  });

  window.addEventListener("focus", function () {
    if (!isLoggedIn()) return;

    const timeDiff = Date.now() - tabFocusTime;
    // Nếu tab mất focus quá 1 giờ, đăng xuất
    if (timeDiff > 60 * 60 * 1000) {
      alert("Phiên đăng nhập đã hết hạn!");
      window.location.href = "pages/actions/logout_process.php";
    }
  });
  */
})();
