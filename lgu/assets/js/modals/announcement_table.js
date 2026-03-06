(function () {
  if (window.__announcementTableInitialized) return;
  window.__announcementTableInitialized = true;

  function showToast(message, type = "success") {
    const toastContainer = document.getElementById("toast-container");
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

  // ── Edit — open modal ──────────────────────────────────────
  document.addEventListener("click", function (e) {
    const btn = e.target.closest(".btn-edit-announcement");
    if (!btn) return;
    document.getElementById("edit-announcement-id").value = btn.dataset.id;
    document.getElementById("edit-announce-title").value = btn.dataset.title;
    document.getElementById("edit-announce-message").value =
      btn.dataset.message;
    document.getElementById("edit-announce-area").value = btn.dataset.area;
    document.getElementById("edit-announce-expiry").value = btn.dataset.expiry;
    openModal("modal-edit-announcement");
  });

  // ── Delete — open modal ────────────────────────────────────
  document.addEventListener("click", function (e) {
    const btn = e.target.closest(".btn-delete-announcement");
    if (!btn) return;
    document.getElementById("del-announcement-id").value = btn.dataset.id;
    document.getElementById("del-announcement-title").textContent =
      btn.dataset.title;
    openModal("modal-delete-announcement");
  });

  // ── Confirm Edit ───────────────────────────────────────────
  document.addEventListener("click", function (e) {
    const btn = e.target.closest("#confirm-edit-announcement");
    if (!btn) return;

    const id = document.getElementById("edit-announcement-id").value;
    const title = document.getElementById("edit-announce-title").value.trim();
    const message = document
      .getElementById("edit-announce-message")
      .value.trim();
    const targetArea = document.getElementById("edit-announce-area").value;
    const expiry = document.getElementById("edit-announce-expiry").value;

    if (!title || !message || !targetArea || !expiry) {
      showToast("All fields are required.", "error");
      return;
    }

    btn.disabled = true;

    fetch("../api/edit_announcement.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `id=${id}&title=${encodeURIComponent(title)}&message=${encodeURIComponent(message)}&target_area=${encodeURIComponent(targetArea)}&expiry_date=${encodeURIComponent(expiry)}`,
    })
      .then((r) => r.json())
      .then((data) => {
        showToast(data.message, data.success ? "success" : "error");
        if (data.success) {
          closeModal("modal-edit-announcement");
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

  // ── Confirm Delete ─────────────────────────────────────────
  document.addEventListener("click", function (e) {
    const btn = e.target.closest("#confirm-delete-announcement");
    if (!btn) return;

    const id = document.getElementById("del-announcement-id").value;
    btn.disabled = true;

    fetch("../api/delete_announcement.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `id=${id}`,
    })
      .then((r) => r.json())
      .then((data) => {
        showToast(data.message, data.success ? "success" : "error");
        if (data.success) {
          closeModal("modal-delete-announcement");
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

  // ── Search ─────────────────────────────────────────────────
  const announcementSearch = document.getElementById("announcement-search");
  announcementSearch?.addEventListener("input", function () {
    const search = this.value.toLowerCase().trim();
    const rows = document.querySelectorAll("#announcement-tbody tr");
    let visible = 0;

    rows.forEach((row) => {
      if (row.classList.contains("empty-row")) return;
      const match = row.textContent.toLowerCase().includes(search);
      row.style.display = match ? "" : "none";
      if (match) visible++;
    });

    const existing = document.querySelector(
      "#announcement-tbody .no-results-row",
    );
    if (existing) existing.remove();

    if (visible === 0) {
      const tr = document.createElement("tr");
      tr.classList.add("empty-row", "no-results-row");
      tr.innerHTML = `<td colspan="5">No announcements match your search.</td>`;
      document.getElementById("announcement-tbody").appendChild(tr);
    }
  });
})();
