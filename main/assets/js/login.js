/* ================================================================
   login.js — Bocaue Community Flood Information System
   ================================================================ */

// ===== TOGGLE PASSWORD VISIBILITY =====
const pwInput = document.getElementById("password");
const pwIcon = document.getElementById("pw-icon");

document.getElementById("toggle-pw").addEventListener("click", () => {
  if (pwInput.type === "password") {
    pwInput.type = "text";
    pwIcon.textContent = "visibility";
  } else {
    pwInput.type = "password";
    pwIcon.textContent = "visibility_off";
  }
});

// ===== FORM SUBMIT =====
document.getElementById("login-form").addEventListener("submit", function (e) {
  const email = document.getElementById("email");
  const password = document.getElementById("password");
  let valid = true;

  // Clear previous errors
  email.classList.remove("input-error");
  password.classList.remove("input-error");

  if (!email.value.trim()) {
    email.classList.add("input-error");
    valid = false;
  }

  if (!password.value.trim()) {
    password.classList.add("input-error");
    valid = false;
  }

  if (!valid) {
    e.preventDefault();
    return;
  }

  // Disable button to prevent double submit
  const btn = document.getElementById("btn-login");
  btn.disabled = true;
  btn.textContent = "Logging in...";
});
