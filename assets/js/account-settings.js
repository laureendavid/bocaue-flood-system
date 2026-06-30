(function initAccountSettings() {
  const page = document.getElementById("page-account-settings");
  if (!page) return;

  const avatarPreview = document.getElementById("as-avatar-preview");
  const profileInput = document.getElementById("profile_picture");
  const fileNameLabel = document.getElementById("as-file-name");
  const profileForm = document.getElementById("account-profile-form");
  const passwordForm = document.getElementById("account-password-form");

  if (profileInput && avatarPreview) {
    profileInput.addEventListener("change", function () {
      const file = profileInput.files && profileInput.files[0];
      const currentSrc = avatarPreview.dataset.currentSrc || avatarPreview.src;
      const defaultSrc = avatarPreview.dataset.defaultSrc || "";

      if (!file) {
        if (fileNameLabel) fileNameLabel.textContent = "No new file selected";
        avatarPreview.src = currentSrc || defaultSrc;
        return;
      }

      if (!/^image\/(jpeg|png)$/i.test(file.type)) {
        alert("Profile picture must be a JPG or PNG image.");
        profileInput.value = "";
        if (fileNameLabel) fileNameLabel.textContent = "No new file selected";
        avatarPreview.src = currentSrc || defaultSrc;
        return;
      }

      if (file.size > 5 * 1024 * 1024) {
        alert("Profile picture must be 5 MB or smaller.");
        profileInput.value = "";
        if (fileNameLabel) fileNameLabel.textContent = "No new file selected";
        avatarPreview.src = currentSrc || defaultSrc;
        return;
      }

      if (fileNameLabel) fileNameLabel.textContent = file.name;

      const reader = new FileReader();
      reader.onload = function (event) {
        avatarPreview.src = event.target?.result || currentSrc;
      };
      reader.readAsDataURL(file);
    });
  }

  page.querySelectorAll(".as-password-toggle").forEach(function (toggleBtn) {
    toggleBtn.addEventListener("click", function () {
      const targetId = toggleBtn.getAttribute("data-target");
      const input = targetId ? document.getElementById(targetId) : null;
      const icon = toggleBtn.querySelector(".material-symbols-outlined");
      if (!input || !icon) return;

      const isHidden = input.type === "password";
      input.type = isHidden ? "text" : "password";
      icon.textContent = isHidden ? "visibility_off" : "visibility";
      toggleBtn.setAttribute(
        "aria-label",
        isHidden ? "Hide password" : "Show password",
      );
    });
  });

  function fieldValue(id) {
    return document.getElementById(id)?.value.trim() || "";
  }

  function fieldOriginal(id) {
    const input = document.getElementById(id);
    if (!input) return "";
    return (input.dataset.originalValue || "").trim();
  }

  function fieldChanged(id) {
    return fieldValue(id) !== fieldOriginal(id);
  }

  function resolveProfileValue(id) {
    const current = fieldValue(id);
    const original = fieldOriginal(id);
    if (current === "" && original !== "" && id !== "suffix") {
      return original;
    }
    return current;
  }

  function validatePhone(phone) {
    return /^09\d{9}$/.test(phone);
  }

  function validatePassword(password) {
    return (
      password.length >= 12 &&
      /[A-Za-z]/.test(password) &&
      /[0-9]/.test(password)
    );
  }

  function profileFieldHasChanges() {
    return (
      ["first_name", "last_name", "suffix", "email", "phone", "date_of_birth"].some(fieldChanged)
      || Boolean(profileInput && profileInput.files && profileInput.files.length)
    );
  }

  if (profileForm) {
    profileForm.addEventListener("submit", function (event) {
      if (!profileFieldHasChanges()) {
        return;
      }

      const firstName = resolveProfileValue("first_name");
      const lastName = resolveProfileValue("last_name");
      const email = resolveProfileValue("email");
      const phone = resolveProfileValue("phone");
      const dob = resolveProfileValue("date_of_birth");

      if (fieldChanged("first_name") && firstName === "") {
        event.preventDefault();
        alert("First name cannot be empty.");
        return;
      }

      if (fieldChanged("last_name") && lastName === "") {
        event.preventDefault();
        alert("Last name cannot be empty.");
        return;
      }

      if (firstName === "" || lastName === "") {
        event.preventDefault();
        alert("First name and last name are required on your account.");
        return;
      }

      if (fieldChanged("email")) {
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
          event.preventDefault();
          alert("Please enter a valid email address.");
          return;
        }
      }

      if (email === "" || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        event.preventDefault();
        alert("Please enter a valid email address.");
        return;
      }

      if (fieldChanged("phone")) {
        if (!validatePhone(phone)) {
          event.preventDefault();
          alert("Please enter a valid PH phone number (e.g. 09XXXXXXXXX).");
          return;
        }
      }

      if (phone === "" || !validatePhone(phone)) {
        event.preventDefault();
        alert("Please enter a valid PH phone number (e.g. 09XXXXXXXXX).");
        return;
      }

      if (fieldChanged("date_of_birth")) {
        if (!dob) {
          event.preventDefault();
          alert("Date of birth is required.");
          return;
        }
      }

      if (dob === "") {
        event.preventDefault();
        alert("Date of birth is required.");
      }
    });
  }

  if (passwordForm) {
    passwordForm.addEventListener("submit", function (event) {
      const currentPassword = document.getElementById("current_password")?.value || "";
      const newPassword = document.getElementById("new_password")?.value || "";
      const confirmPassword = document.getElementById("confirm_password")?.value || "";

      if (!currentPassword || !newPassword || !confirmPassword) {
        event.preventDefault();
        alert("Please fill in all password fields.");
        return;
      }

      if (!validatePassword(newPassword)) {
        event.preventDefault();
        alert("New password must be at least 12 characters and include letters and numbers.");
        return;
      }

      if (newPassword !== confirmPassword) {
        event.preventDefault();
        alert("New password and confirmation do not match.");
      }
    });

    passwordForm.addEventListener("reset", function () {
      window.setTimeout(function () {
        passwordForm.querySelectorAll(".as-form-input").forEach(function (input) {
          input.value = "";
          input.type = "password";
        });
        passwordForm.querySelectorAll(".as-password-toggle .material-symbols-outlined").forEach(function (icon) {
          icon.textContent = "visibility";
        });
      }, 0);
    });
  }
})();
