$(document).ready(function () {
  // Password strength checker
  function checkPasswordStrength(password) {
    let strength = 0;
    let feedback = "";

    if (password.length >= 6) strength += 1;
    if (password.length >= 8) strength += 1;
    if (/[a-z]/.test(password)) strength += 1;
    if (/[A-Z]/.test(password)) strength += 1;
    if (/[0-9]/.test(password)) strength += 1;
    if (/[^A-Za-z0-9]/.test(password)) strength += 1;

    const $strengthEl = $("#passwordStrength");
    if (!$strengthEl.length) return;

    const $strengthText = $strengthEl.find(".strength-text");

    $strengthEl.removeClass("strength-weak strength-medium strength-strong");

    if (password.length === 0) {
      $strengthEl.hide();
      return;
    }

    $strengthEl.show();

    if (strength <= 2) {
      $strengthEl.addClass("strength-weak");
      feedback = "Mật khẩu yếu";
    } else if (strength <= 4) {
      $strengthEl.addClass("strength-medium");
      feedback = "Mật khẩu trung bình";
    } else {
      $strengthEl.addClass("strength-strong");
      feedback = "Mật khẩu mạnh";
    }

    if ($strengthText.length) {
      $strengthText.text(feedback);
    }
  }

  // Password confirmation checker
  function checkPasswordMatch() {
    const $password = $("#password");
    const $confirmPassword = $("#confirmPassword");

    if (!$password.length || !$confirmPassword.length) return;

    const passwordValue = $password.val();
    const confirmPasswordValue = $confirmPassword.val();

    if (confirmPasswordValue.length === 0) {
      $confirmPassword.removeClass("error").css("borderColor", "");
      return;
    }

    if (passwordValue === confirmPasswordValue) {
      $confirmPassword
        .removeClass("error")
        .css("borderColor", "rgba(76, 175, 80, 0.6)");
    } else {
      $confirmPassword.addClass("error").css("borderColor", "#f44336");
    }
  }

  // Email validation
  function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  }

  // Event listeners với jQuery
  $("#password").on("input", function () {
    checkPasswordStrength($(this).val());
    checkPasswordMatch();
  });

  $("#confirmPassword").on("input", checkPasswordMatch);

  $("#email")
    .on("blur", function () {
      const email = $(this).val();
      if (email && !validateEmail(email)) {
        $(this).addClass("error").css("borderColor", "#f44336");
      } else if (email) {
        $(this)
          .removeClass("error")
          .css("borderColor", "rgba(76, 175, 80, 0.6)");
      }
    })
    .on("input", function () {
      $(this).removeClass("error").css("borderColor", "");
    });

  $("#phone")
    .on("blur", function () {
      const phone = $(this).val();
      if (phone && !validatePhone(phone)) {
        $(this).addClass("error");
      } else if (phone) {
        $(this).removeClass("error");
      }
    })
    .on("input", function () {
      $(this).removeClass("error");
    });

  // Form submission
  $("#registerForm").on("submit", function (e) {
    const $form = $(this);
    const $name = $("#name");
    const $email = $("#email");
    const $phone = $("#phone");
    const $password = $("#password");
    const $confirmPassword = $("#confirmPassword");

    const name = $name.val().trim();
    const email = $email.val();
    const phone = $phone.val();
    const password = $password.val();
    const confirmPassword = $confirmPassword.val();

    let hasError = false;

    // Validate all fields
    if (!name) {
      $name.addClass("error");
      hasError = true;
    }

    if (!validateEmail(email)) {
      $email.addClass("error");
      hasError = true;
    }

    if (!validatePhone(phone)) {
      $phone.addClass("error");
      hasError = true;
    }

    if (password.length < 6) {
      $password.addClass("error");
      hasError = true;
    }

    if (password !== confirmPassword) {
      $confirmPassword.addClass("error");
      hasError = true;
    }

    if (hasError) {
      e.preventDefault();
      alert("Vui lòng kiểm tra lại thông tin đã nhập!");
      return;
    }

    // Show loading state
    const $btn = $("#registerBtn");
    const $btnText = $btn.find(".btn-text");

    if ($btn.length && $btnText.length) {
      $btn.prop("disabled", true).addClass("loading");
      $btnText.text("Đang tạo tài khoản...");

      // Reset sau 10 giây nếu có lỗi
      setTimeout(function () {
        $btn.prop("disabled", false).removeClass("loading");
        $btnText.text("Đăng ký");
      }, 10000);
    }
  });

  // Auto focus vào name field
  $("#name").focus();
});
