$(document).ready(function () {
  // Auto focus vào email field
  $('input[name="email"]').focus();

  // Form validation
  $(".login-form").on("submit", function (e) {
    const $email = $('input[name="email"]');
    const $password = $('input[name="password"]');
    const $submitBtn = $('button[type="submit"]');

    const email = $email.val().trim();
    const password = $password.val();

    let hasError = false;

    // Reset previous errors
    $email.removeClass("error");
    $password.removeClass("error");

    // Validate email
    if (!email) {
      $email.addClass("error");
      hasError = true;
    } else if (!validateEmail(email)) {
      $email.addClass("error");
      hasError = true;
    }

    // Validate password
    if (!password) {
      $password.addClass("error");
      hasError = true;
    }

    if (hasError) {
      e.preventDefault();
      alert("Vui lòng kiểm tra lại thông tin đăng nhập!");
      return;
    }

    // Show loading state
    const originalText = $submitBtn.text();
    $submitBtn.prop("disabled", true).text("Đang đăng nhập...");

    // Reset sau 10 giây nếu có lỗi
    setTimeout(function () {
      $submitBtn.prop("disabled", false).text(originalText);
    }, 10000);
  });

  // Real-time email validation
  $('input[name="email"]')
    .on("blur", function () {
      const email = $(this).val().trim();
      if (email && !validateEmail(email)) {
        $(this).addClass("error");
      } else {
        $(this).removeClass("error");
      }
    })
    .on("input", function () {
      $(this).removeClass("error");
    });

  // Remove error class when typing
  $('input[name="password"]').on("input", function () {
    $(this).removeClass("error");
  });

  // Enter key navigation
  $('input[name="email"]').on("keypress", function (e) {
    if (e.which === 13) {
      $('input[name="password"]').focus();
    }
  });

  $('input[name="password"]').on("keypress", function (e) {
    if (e.which === 13) {
      $(".login-form").submit();
    }
  });

  // Show/hide password toggle (nếu muốn thêm)
  function addPasswordToggle() {
    const $passwordField = $('input[name="password"]');
    const $formGroup = $passwordField.parent();

    const $toggleBtn = $(
      '<button type="button" class="password-toggle">👁️</button>'
    );
    $formGroup.css("position", "relative");
    $formGroup.append($toggleBtn);

    $toggleBtn.on("click", function () {
      const type =
        $passwordField.attr("type") === "password" ? "text" : "password";
      $passwordField.attr("type", type);
      $(this).text(type === "password" ? "👁️" : "🙈");
    });
  }

  // Email validation function
  function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }

  // Auto dismiss error/success messages
  setTimeout(function () {
    $(".error, .success").fadeOut();
  }, 5000);

  // Uncomment để thêm password toggle
  // addPasswordToggle();
});
