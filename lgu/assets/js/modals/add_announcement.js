(function () {
  if (window.__announcementInitialized) return;
  window.__announcementInitialized = true;

  const toastContainer = document.getElementById("toast-container");

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

  document.addEventListener("click", function (e) {
    const btn = e.target.closest("#announcement-save");
    if (!btn) return;

    const title = document.getElementById("announce-title").value.trim();
    const message = document.getElementById("announce-message").value.trim();
    const targetArea = document.getElementById("announce-area").value;
    const expiry = document.getElementById("announce-expiry").value;

    if (!title || !message || !targetArea || !expiry) {
      showToast("All fields are required.", "error");
      return;
    }

    btn.disabled = true;

    fetch("../api/add_announcement.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `title=${encodeURIComponent(title)}&message=${encodeURIComponent(message)}&target_area=${encodeURIComponent(targetArea)}&expiry_date=${encodeURIComponent(expiry)}`,
    })
      .then((r) => r.json())
      .then((data) => {
        showToast(data.message, data.success ? "success" : "error");
        if (data.success) {
          closeModal("modal-announcement");
          document.getElementById("announce-title").value = "";
          document.getElementById("announce-message").value = "";
          document.getElementById("announce-area").selectedIndex = 0;
          document.getElementById("announce-expiry").value = "";
          setTimeout(() => location.reload(), 1500);
        }
      })
      .catch(() =>
        showToast("Something went wrong. Please try again.", "error"),
      )
      .finally(() => {
        btn.disabled = false;
      });
  });
})();
