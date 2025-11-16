/**
 * ==================== ADMIN JAVASCRIPT - CGV BOOKING SYSTEM ====================
 * Modern Admin Panel JavaScript with enhanced functionality
 */

// ==================== GLOBAL VARIABLES ====================
let currentTheme = localStorage.getItem("admin-theme") || "light";
let sidebarCollapsed = localStorage.getItem("sidebar-collapsed") === "true";
let notifications = [];

// ==================== DOM READY ====================
document.addEventListener("DOMContentLoaded", function () {
  initializeAdmin();
  setupEventListeners();
  loadAnimations();
  setupMobileMenu();
  applyTheme();
  loadNotifications();
});

// ==================== INITIALIZATION ====================
function initializeAdmin() {
  console.log("🎬 CGV Admin Panel Initialized");

  // Add loading class to body
  document.body.classList.add("loading");

  // Remove loading after DOM is ready
  setTimeout(() => {
    document.body.classList.remove("loading");
    document.body.classList.add("fade-in");
  }, 500);

  // Initialize tooltips
  initializeTooltips();

  // Setup search functionality
  setupSearch();

  // Setup form validations
  setupFormValidations();

  // Auto-save forms
  setupAutoSave();
}

// ==================== EVENT LISTENERS ====================
function setupEventListeners() {
  // Sidebar toggle
  const sidebarToggle = document.querySelector(".sidebar-toggle");
  if (sidebarToggle) {
    sidebarToggle.addEventListener("click", toggleSidebar);
  }

  // Theme toggle
  const themeToggle = document.querySelector(".theme-toggle");
  if (themeToggle) {
    themeToggle.addEventListener("click", toggleTheme);
  }

  // Form submissions
  const forms = document.querySelectorAll("form");
  forms.forEach((form) => {
    form.addEventListener("submit", handleFormSubmit);
  });

  // Table sorting
  const sortableHeaders = document.querySelectorAll("th[data-sort]");
  sortableHeaders.forEach((header) => {
    header.addEventListener("click", sortTable);
    header.style.cursor = "pointer";
    header.innerHTML += ' <span class="sort-icon">⇅</span>';
  });

  // Delete confirmations
  const deleteButtons = document.querySelectorAll('[data-action="delete"]');
  deleteButtons.forEach((button) => {
    button.addEventListener("click", confirmDelete);
  });

  // Auto-refresh data
  setupAutoRefresh();
}

// ==================== MOBILE MENU ====================
function setupMobileMenu() {
  const mobileMenuBtn = document.querySelector(".mobile-menu-btn");
  const sidebar = document.querySelector(".admin-sidebar");
  const overlay = document.createElement("div");
  overlay.className = "sidebar-overlay";

  if (mobileMenuBtn && sidebar) {
    mobileMenuBtn.addEventListener("click", () => {
      sidebar.classList.toggle("open");
      if (sidebar.classList.contains("open")) {
        document.body.appendChild(overlay);
        overlay.addEventListener("click", closeMobileMenu);
      } else {
        closeMobileMenu();
      }
    });
  }

  function closeMobileMenu() {
    sidebar.classList.remove("open");
    if (overlay.parentNode) {
      overlay.parentNode.removeChild(overlay);
    }
  }
}

// ==================== SEARCH FUNCTIONALITY ====================
function setupSearch() {
  const searchInputs = document.querySelectorAll(
    ".search-input, [data-search]"
  );

  searchInputs.forEach((input) => {
    input.addEventListener("input", debounce(performSearch, 300));
    input.addEventListener("keypress", function (e) {
      if (e.key === "Enter") {
        performSearch.call(this);
      }
    });
  });
}

function performSearch() {
  const searchTerm = this.value.toLowerCase();
  const targetTable = this.dataset.target || "table";
  const table = document.querySelector(`#${targetTable}, .table`);

  if (!table) return;

  const rows = table.querySelectorAll("tbody tr");
  let visibleCount = 0;

  rows.forEach((row) => {
    const text = row.textContent.toLowerCase();
    const shouldShow = text.includes(searchTerm);

    row.style.display = shouldShow ? "" : "none";
    if (shouldShow) visibleCount++;

    // Add highlight animation
    if (shouldShow && searchTerm) {
      row.classList.add("search-highlight");
      setTimeout(() => row.classList.remove("search-highlight"), 1000);
    }
  });

  // Update search results count
  updateSearchResultsCount(visibleCount, rows.length);
}

function updateSearchResultsCount(visible, total) {
  let counter = document.querySelector(".search-results-count");
  if (!counter) {
    counter = document.createElement("div");
    counter.className = "search-results-count";
    const searchContainer = document.querySelector(
      ".search-container, .search-input"
    ).parentNode;
    if (searchContainer) {
      searchContainer.appendChild(counter);
    }
  }

  counter.textContent = `Hiển thị ${visible} / ${total} kết quả`;
  counter.style.cssText = `
        padding: 8px 12px;
        background: linear-gradient(135deg, #3498db, #2980b9);
        color: white;
        border-radius: 20px;
        font-size: 0.85rem;
        margin-left: 10px;
    `;
}

// ==================== TABLE FUNCTIONALITY ====================
function sortTable() {
  const header = this;
  const table = header.closest("table");
  const tbody = table.querySelector("tbody");
  const rows = Array.from(tbody.querySelectorAll("tr"));
  const columnIndex = Array.from(header.parentNode.children).indexOf(header);
  const sortDirection = header.dataset.sortDirection === "asc" ? "desc" : "asc";

  // Clear all sort icons
  table.querySelectorAll("th .sort-icon").forEach((icon) => {
    icon.textContent = "⇅";
  });

  // Update current sort icon
  const sortIcon = header.querySelector(".sort-icon");
  sortIcon.textContent = sortDirection === "asc" ? "↑" : "↓";
  header.dataset.sortDirection = sortDirection;

  rows.sort((a, b) => {
    const aVal = a.children[columnIndex].textContent.trim();
    const bVal = b.children[columnIndex].textContent.trim();

    // Try to parse as numbers
    const aNum = parseFloat(aVal.replace(/[^\d.-]/g, ""));
    const bNum = parseFloat(bVal.replace(/[^\d.-]/g, ""));

    if (!isNaN(aNum) && !isNaN(bNum)) {
      return sortDirection === "asc" ? aNum - bNum : bNum - aNum;
    }

    return sortDirection === "asc"
      ? aVal.localeCompare(bVal)
      : bVal.localeCompare(aVal);
  });

  // Animate row reordering
  rows.forEach((row, index) => {
    row.style.transform = "translateX(-100%)";
    row.style.opacity = "0";
    setTimeout(() => {
      tbody.appendChild(row);
      row.style.transform = "translateX(0)";
      row.style.opacity = "1";
    }, index * 50);
  });
}

// ==================== FORM HANDLING ====================
function setupFormValidations() {
  const forms = document.querySelectorAll("form");

  forms.forEach((form) => {
    const inputs = form.querySelectorAll("input, select, textarea");

    inputs.forEach((input) => {
      input.addEventListener("blur", validateField);
      input.addEventListener("input", clearFieldError);
    });
  });
}

function validateField() {
  const field = this;
  const value = field.value.trim();
  const isRequired = field.hasAttribute("required");

  clearFieldError.call(field);

  if (isRequired && !value) {
    showFieldError(field, "Trường này là bắt buộc");
    return false;
  }

  // Email validation
  if (field.type === "email" && value) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(value)) {
      showFieldError(field, "Email không hợp lệ");
      return false;
    }
  }

  // Phone validation
  if (field.type === "tel" && value) {
    const phoneRegex = /^[0-9]{10,11}$/;
    if (!phoneRegex.test(value.replace(/\D/g, ""))) {
      showFieldError(field, "Số điện thoại không hợp lệ");
      return false;
    }
  }

  // Password validation
  if (field.type === "password" && value && value.length < 6) {
    showFieldError(field, "Mật khẩu phải có ít nhất 6 ký tự");
    return false;
  }

  showFieldSuccess(field);
  return true;
}

function showFieldError(field, message) {
  field.classList.add("error");
  field.classList.remove("success");

  let errorDiv = field.parentNode.querySelector(".field-error");
  if (!errorDiv) {
    errorDiv = document.createElement("div");
    errorDiv.className = "field-error";
    field.parentNode.appendChild(errorDiv);
  }

  errorDiv.textContent = message;
  errorDiv.style.cssText = `
        color: #e74c3c;
        font-size: 0.85rem;
        margin-top: 5px;
        animation: slideIn 0.3s ease;
    `;
}

function showFieldSuccess(field) {
  field.classList.add("success");
  field.classList.remove("error");
  clearFieldError.call(field);
}

function clearFieldError() {
  this.classList.remove("error");
  const errorDiv = this.parentNode.querySelector(".field-error");
  if (errorDiv) {
    errorDiv.remove();
  }
}

function handleFormSubmit(e) {
  const form = e.target;
  const submitButton = form.querySelector('button[type="submit"]');

  // Validate all fields
  const fields = form.querySelectorAll(
    "input[required], select[required], textarea[required]"
  );
  let isValid = true;

  fields.forEach((field) => {
    if (!validateField.call(field)) {
      isValid = false;
    }
  });

  if (!isValid) {
    e.preventDefault();
    showNotification("Vui lòng kiểm tra lại thông tin", "error");
    return;
  }

  // Show loading state
  if (submitButton) {
    const originalText = submitButton.textContent;
    submitButton.disabled = true;
    submitButton.innerHTML = '<span class="loading"></span> Đang xử lý...';

    // Restore button after 3 seconds if form doesn't redirect
    setTimeout(() => {
      submitButton.disabled = false;
      submitButton.textContent = originalText;
    }, 3000);
  }
}

// ==================== AUTO SAVE ====================
function setupAutoSave() {
  const forms = document.querySelectorAll("form[data-autosave]");

  forms.forEach((form) => {
    const formId = form.id || "form-" + Date.now();
    const inputs = form.querySelectorAll("input, select, textarea");

    // Load saved data
    loadFormData(form, formId);

    inputs.forEach((input) => {
      input.addEventListener(
        "input",
        debounce(() => {
          saveFormData(form, formId);
        }, 1000)
      );
    });
  });
}

function saveFormData(form, formId) {
  const data = new FormData(form);
  const obj = {};

  for (let [key, value] of data.entries()) {
    obj[key] = value;
  }

  localStorage.setItem(`autosave-${formId}`, JSON.stringify(obj));
  showNotification("Đã tự động lưu", "info", 2000);
}

function loadFormData(form, formId) {
  const saved = localStorage.getItem(`autosave-${formId}`);
  if (!saved) return;

  try {
    const data = JSON.parse(saved);
    Object.keys(data).forEach((key) => {
      const field = form.querySelector(`[name="${key}"]`);
      if (field && field.type !== "password") {
        field.value = data[key];
      }
    });
  } catch (e) {
    console.error("Error loading form data:", e);
  }
}

// ==================== NOTIFICATIONS ====================
function showNotification(message, type = "info", duration = 5000) {
  const notification = document.createElement("div");
  notification.className = `notification notification-${type}`;
  notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-icon">${getNotificationIcon(type)}</span>
            <span class="notification-message">${message}</span>
            <button class="notification-close" onclick="this.parentNode.parentNode.remove()">×</button>
        </div>
    `;

  notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${getNotificationColor(type)};
        color: white;
        padding: 15px 20px;
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        z-index: 10000;
        animation: slideInRight 0.3s ease;
        max-width: 400px;
    `;

  document.body.appendChild(notification);

  if (duration > 0) {
    setTimeout(() => {
      notification.style.animation = "slideOutRight 0.3s ease";
      setTimeout(() => notification.remove(), 300);
    }, duration);
  }

  notifications.push(notification);
}

function getNotificationIcon(type) {
  const icons = {
    success: "✅",
    error: "❌",
    warning: "⚠️",
    info: "ℹ️",
  };
  return icons[type] || icons.info;
}

function getNotificationColor(type) {
  const colors = {
    success: "linear-gradient(135deg, #27ae60, #229954)",
    error: "linear-gradient(135deg, #e74c3c, #c0392b)",
    warning: "linear-gradient(135deg, #f39c12, #e67e22)",
    info: "linear-gradient(135deg, #3498db, #2980b9)",
  };
  return colors[type] || colors.info;
}

function loadNotifications() {
  // Add notification container styles
  const style = document.createElement("style");
  style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        .notification-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .notification-close {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            margin-left: auto;
        }
    `;
  document.head.appendChild(style);
}

// ==================== THEME MANAGEMENT ====================
function toggleTheme() {
  currentTheme = currentTheme === "light" ? "dark" : "light";
  localStorage.setItem("admin-theme", currentTheme);
  applyTheme();
}

function applyTheme() {
  document.body.setAttribute("data-theme", currentTheme);

  const themeToggle = document.querySelector(".theme-toggle");
  if (themeToggle) {
    themeToggle.textContent = currentTheme === "light" ? "🌙" : "☀️";
  }
}

// ==================== SIDEBAR MANAGEMENT ====================
function toggleSidebar() {
  const sidebar = document.querySelector(".admin-sidebar");
  const content = document.querySelector(".admin-content");

  sidebarCollapsed = !sidebarCollapsed;
  localStorage.setItem("sidebar-collapsed", sidebarCollapsed);

  if (sidebarCollapsed) {
    sidebar.classList.add("collapsed");
    content.classList.add("expanded");
  } else {
    sidebar.classList.remove("collapsed");
    content.classList.remove("expanded");
  }
}

// ==================== ANIMATIONS ====================
function loadAnimations() {
  // Add fade-in animation to cards
  const cards = document.querySelectorAll(".card");
  cards.forEach((card, index) => {
    card.style.animationDelay = `${index * 0.1}s`;
    card.classList.add("fade-in");
  });

  // Add slide-in animation to menu items
  const menuItems = document.querySelectorAll(".sidebar-menu a");
  menuItems.forEach((item, index) => {
    item.style.animationDelay = `${index * 0.05}s`;
    item.classList.add("slide-in");
  });

  // Animate stats cards
  animateStatsCards();
}

function animateStatsCards() {
  const statNumbers = document.querySelectorAll(".stat-number");

  statNumbers.forEach((stat) => {
    const finalValue = parseInt(stat.textContent.replace(/\D/g, ""));
    if (isNaN(finalValue)) return;

    let currentValue = 0;
    const increment = finalValue / 50;
    const timer = setInterval(() => {
      currentValue += increment;
      if (currentValue >= finalValue) {
        stat.textContent = stat.textContent.replace(/\d+/, finalValue);
        clearInterval(timer);
      } else {
        stat.textContent = stat.textContent.replace(
          /\d+/,
          Math.floor(currentValue)
        );
      }
    }, 30);
  });
}

// ==================== TOOLTIPS ====================
function initializeTooltips() {
  const elementsWithTooltips = document.querySelectorAll(
    "[title], [data-tooltip]"
  );

  elementsWithTooltips.forEach((element) => {
    const tooltipText =
      element.getAttribute("title") || element.getAttribute("data-tooltip");
    if (!tooltipText) return;

    element.removeAttribute("title"); // Remove default tooltip

    const tooltip = document.createElement("div");
    tooltip.className = "custom-tooltip";
    tooltip.textContent = tooltipText;
    tooltip.style.cssText = `
            position: absolute;
            background: rgba(0,0,0,0.9);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            pointer-events: none;
            z-index: 10000;
            opacity: 0;
            transition: opacity 0.3s ease;
            white-space: nowrap;
        `;

    element.addEventListener("mouseenter", (e) => {
      document.body.appendChild(tooltip);
      const rect = element.getBoundingClientRect();
      tooltip.style.left =
        rect.left + rect.width / 2 - tooltip.offsetWidth / 2 + "px";
      tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + "px";
      tooltip.style.opacity = "1";
    });

    element.addEventListener("mouseleave", () => {
      tooltip.style.opacity = "0";
      setTimeout(() => {
        if (tooltip.parentNode) tooltip.remove();
      }, 300);
    });
  });
}

// ==================== DELETE CONFIRMATION ====================
function confirmDelete(e) {
  e.preventDefault();

  const button = this;
  const itemName = button.dataset.itemName || "mục này";

  const modal = createConfirmModal(
    "Xác nhận xóa",
    `Bạn có chắc chắn muốn xóa ${itemName}? Hành động này không thể hoàn tác.`,
    () => {
      // Proceed with deletion
      window.location.href = button.href;
    }
  );

  document.body.appendChild(modal);
}

function createConfirmModal(title, message, onConfirm) {
  const modal = document.createElement("div");
  modal.className = "confirm-modal";
  modal.innerHTML = `
        <div class="modal-overlay">
            <div class="modal-content">
                <h3>${title}</h3>
                <p>${message}</p>
                <div class="modal-buttons">
                    <button class="btn btn-secondary cancel-btn">Hủy</button>
                    <button class="btn btn-danger confirm-btn">Xóa</button>
                </div>
            </div>
        </div>
    `;

  modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 10000;
    `;

  const overlay = modal.querySelector(".modal-overlay");
  overlay.style.cssText = `
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        animation: fadeIn 0.3s ease;
    `;

  const content = modal.querySelector(".modal-content");
  content.style.cssText = `
        background: white;
        padding: 2rem;
        border-radius: 15px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        max-width: 400px;
        animation: slideIn 0.3s ease;
    `;

  const buttons = modal.querySelector(".modal-buttons");
  buttons.style.cssText = `
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
        justify-content: flex-end;
    `;

  // Event listeners
  modal
    .querySelector(".cancel-btn")
    .addEventListener("click", () => modal.remove());
  modal.querySelector(".confirm-btn").addEventListener("click", () => {
    onConfirm();
    modal.remove();
  });

  overlay.addEventListener("click", (e) => {
    if (e.target === overlay) modal.remove();
  });

  return modal;
}

// ==================== AUTO REFRESH ====================
function setupAutoRefresh() {
  const refreshInterval = 300000; // 5 minutes

  setInterval(() => {
    const activeElements = document.querySelectorAll(
      ".stat-card, [data-auto-refresh]"
    );
    if (activeElements.length > 0) {
      refreshData();
    }
  }, refreshInterval);
}

function refreshData() {
  // This would typically make AJAX calls to refresh data
  console.log("Refreshing data...");
  showNotification("Dữ liệu đã được cập nhật", "info", 3000);
}

// ==================== EXPORT FUNCTIONS ====================
function exportTableToCSV(tableId, filename = "export") {
  const table = document.getElementById(tableId);
  if (!table) return;

  let csv = [];
  const rows = table.querySelectorAll("tr");

  rows.forEach((row) => {
    const cols = row.querySelectorAll("td, th");
    const rowData = [];
    cols.forEach((col) => {
      rowData.push('"' + col.textContent.replace(/"/g, '""') + '"');
    });
    csv.push(rowData.join(","));
  });

  downloadCSV(csv.join("\n"), filename + ".csv");
}

function downloadCSV(csv, filename) {
  const blob = new Blob([csv], { type: "text/csv" });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = filename;
  a.click();
  window.URL.revokeObjectURL(url);

  showNotification("Đã xuất file thành công", "success");
}

function printTable(tableId) {
  const table = document.getElementById(tableId);
  if (!table) return;

  const printWindow = window.open("", "_blank");
  printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>In báo cáo</title>
            <style>
                body { font-family: Arial, sans-serif; }
                table { border-collapse: collapse; width: 100%; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                @media print { .no-print { display: none; } }
            </style>
        </head>
        <body>
            <h2>Báo cáo CGV Admin</h2>
            <p>Ngày in: ${new Date().toLocaleDateString("vi-VN")}</p>
            ${table.outerHTML}
        </body>
        </html>
    `);

  printWindow.document.close();
  printWindow.focus();
  printWindow.print();
  printWindow.close();
}

// ==================== UTILITY FUNCTIONS ====================
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

function formatNumber(num) {
  return new Intl.NumberFormat("vi-VN").format(num);
}

function formatCurrency(amount) {
  return new Intl.NumberFormat("vi-VN", {
    style: "currency",
    currency: "VND",
  }).format(amount);
}

function formatDate(date) {
  return new Intl.DateTimeFormat("vi-VN").format(new Date(date));
}

// ==================== GLOBAL FUNCTIONS FOR INLINE CALLS ====================
window.searchTable = function (input, tableId) {
  performSearch.call(input);
};

window.filterTable = function (value, tableId) {
  const table = document.getElementById(tableId);
  if (!table) return;

  const rows = table.querySelectorAll("tbody tr");
  rows.forEach((row) => {
    const shouldShow =
      !value || row.dataset.status === value || row.dataset.role === value;
    row.style.display = shouldShow ? "" : "none";
  });
};

window.exportUsers = function () {
  exportTableToCSV("users-table", "danh-sach-nguoi-dung");
};

window.exportMovies = function () {
  exportTableToCSV("movies-table", "danh-sach-phim");
};

window.printUsers = function () {
  printTable("users-table");
};

window.printMovies = function () {
  printTable("movies-table");
};

// Console welcome message
console.log(
  `
%c🎬 CGV Admin Panel
%cVersion 2.0 - Modern Admin Interface
%cLoaded successfully!
`,
  "color: #e74c3c; font-size: 20px; font-weight: bold;",
  "color: #3498db; font-size: 14px;",
  "color: #27ae60; font-size: 12px;"
);
