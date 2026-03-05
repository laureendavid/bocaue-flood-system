// add_hotlines.js
(function () {
  // Guard: only initialize once even if script is included multiple times
  if (window.__hotlinesInitialized) return;
  window.__hotlinesInitialized = true;

  const modal = document.getElementById("modal-hotline");
  const toastContainer = document.getElementById("toast-container");

  // --- Toast ---
  function showToast(message, type = "success") {
    if (!toastContainer) return;
    const toast = document.createElement("div");
    toast.classList.add("toast", type);
    toast.innerHTML = `<span>${message}</span><span class="close-toast">&times;</span>`;
    toastContainer.appendChild(toast);
    toast
      .querySelector(".close-toast")
      .addEventListener("click", () => toast.remove());
    setTimeout(() => toast.remove(), 3000);
  }

  // --- Save handler ---
  function handleHotlineSave() {
    const barangay_id = document.querySelector("#hotline-barangay").value;
    const name = document.querySelector("#hotline-name").value.trim();
    const contact = document.querySelector("#hotline-contact").value.trim();

    if (!barangay_id || !name || !contact) {
      showToast("All fields are required!", "error");
      return;
    }

    // Disable button to prevent double submit
    const saveBtn = document.getElementById("hotline-save");
    saveBtn.disabled = true;

    const formData = new FormData();
    formData.append("barangay_id", barangay_id);
    formData.append("hotline_name", name);
    formData.append("contact_number", contact);

    fetch("/soe/api/add_hotlines.php", { method: "POST", body: formData })
      .then((res) => res.text())
      .then((data) => {
        if (data.trim() === "success") {
          showToast("Hotline added successfully!", "success");
          closeModal(modal);
          // Clear form
          document.querySelector("#hotline-name").value = "";
          document.querySelector("#hotline-contact").value = "";
          document.querySelector("#hotline-barangay").selectedIndex = 0;
        } else {
          showToast("Error: " + data, "error");
        }
      })
      .catch((err) => {
        console.error(err);
        showToast("Unexpected error occurred.", "error");
      })
      .finally(() => {
        // Re-enable after request finishes
        saveBtn.disabled = false;
      });
  }

  // --- Open/close helpers ---
  function openModal(m) {
    if (m) m.style.display = "flex"; // use flex so overlay centers the dialog
  }

  function closeModal(m) {
    if (m) m.style.display = "none";
  }

  // --- Single delegated listener for everything ---
  document.addEventListener("click", function (e) {
    const target = e.target;

    // Save hotline
    if (target.closest("#hotline-save")) {
      handleHotlineSave();
      return;
    }

    // Open: "Add Hotline" button
    if (target.closest("#btn-add-hotline")) {
      openModal(modal);
      return;
    }

    // Close buttons inside modal-hotline
    if (
      target.closest("#modal-hotline .modal-close") ||
      target.closest("#modal-hotline .btn-cancel")
    ) {
      closeModal(modal);
      return;
    }

    // Click on overlay backdrop
    if (target === modal) {
      closeModal(modal);
      return;
    }
  });
})();
