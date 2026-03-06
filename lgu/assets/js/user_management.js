// --- Toast ---
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

// Change Role — open modal
document.addEventListener("click", function (e) {
  const btn = e.target.closest(".btn-change-role");
  if (!btn) return;
  document.getElementById("cr-user-id").value = btn.dataset.id;
  document.getElementById("cr-name").value = btn.dataset.name;
  document.getElementById("cr-email").value = btn.dataset.email;
  document.getElementById("cr-role").value = btn.dataset.role;
  openModal("modal-change-role");
});

// Delete — open modal
document.addEventListener("click", function (e) {
  const btn = e.target.closest(".btn-delete");
  if (!btn) return;
  document.getElementById("del-user-id").value = btn.dataset.id;
  document.getElementById("del-user-name").textContent = btn.dataset.name;
  openModal("modal-delete");
});

// Save role change
document
  .getElementById("confirm-change-role")
  ?.addEventListener("click", () => {
    const userId = document.getElementById("cr-user-id").value;
    const newRole = document.getElementById("cr-role").value;

    fetch("../api/change_role.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `user_id=${userId}&role=${encodeURIComponent(newRole)}`,
    })
      .then((r) => r.json())
      .then((data) => {
        closeModal("modal-change-role");
        showToast(data.message, data.success ? "success" : "error");
        if (data.success) setTimeout(() => location.reload(), 1500);
      })
      .catch(() =>
        showToast("Something went wrong. Please try again.", "error"),
      );
  });

// Confirm delete
document.getElementById("confirm-delete")?.addEventListener("click", () => {
  const userId = document.getElementById("del-user-id").value;

  fetch("../api/delete_user.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `user_id=${userId}`,
  })
    .then((r) => r.json())
    .then((data) => {
      closeModal("modal-delete");
      showToast(data.message, data.success ? "success" : "error");
      if (data.success) setTimeout(() => location.reload(), 1500);
    })
    .catch(() => showToast("Something went wrong. Please try again.", "error"));
});

// Close on outside click
document.querySelectorAll(".modal-overlay").forEach((overlay) => {
  overlay.addEventListener("click", function (e) {
    if (e.target === this) closeModal(this.id);
  });
});

// ── Search & Filter ────────────────────────────────────────
const umSearch = document.getElementById("um-search");
const umFilterBtn = document.getElementById("um-filter-btn");
const umFilterDropdown = document.getElementById("um-filter-dropdown");
const umTbody = document.getElementById("um-tbody");
const umEmpty = document.getElementById("um-empty");

// Toggle filter dropdown
umFilterBtn?.addEventListener("click", (e) => {
  e.stopPropagation();
  const isOpen = umFilterDropdown.style.display === "flex";
  umFilterDropdown.style.display = isOpen ? "none" : "flex";
  umFilterDropdown.style.flexDirection = "column";
  umFilterDropdown.style.gap = "8px";
});
// Close filter dropdown when clicking outside
document.addEventListener("click", (e) => {
  if (!umFilterDropdown?.contains(e.target) && e.target !== umFilterBtn) {
    if (umFilterDropdown) umFilterDropdown.style.display = "none";
  }
});

function filterTable() {
  if (!umTbody) return;

  const search = umSearch?.value.toLowerCase().trim() ?? "";
  const activeRole =
    document.querySelector('input[name="um-role"]:checked')?.value ?? "";
  const rows = umTbody.querySelectorAll("tr");
  let visibleCount = 0;

  rows.forEach((row) => {
    if (row.classList.contains("empty-row")) return;

    const text = row.textContent.toLowerCase();
    const role = row.querySelector(".badge")?.textContent.trim() ?? "";

    const matchesSearch = search === "" || text.includes(search);
    const matchesRole = activeRole === "" || role === activeRole;

    if (matchesSearch && matchesRole) {
      row.style.display = "";
      visibleCount++;
    } else {
      row.style.display = "none";
    }
  });

  // Show/hide empty state
  if (umEmpty) umEmpty.style.display = visibleCount === 0 ? "block" : "none";

  // Update filter button to show active state
  if (umFilterBtn) {
    const activeRole = document.querySelector(
      'input[name="um-role"]:checked',
    )?.value;
    umFilterBtn.style.background = activeRole ? "var(--tab-active)" : "";
    umFilterBtn.style.fontWeight = activeRole ? "700" : "";
  }
}

// Listen for search input
umSearch?.addEventListener("input", filterTable);

// Listen for role filter change
document.querySelectorAll('input[name="um-role"]').forEach((radio) => {
  radio.addEventListener("change", () => {
    if (umFilterDropdown) umFilterDropdown.style.display = "none";
    filterTable();
  });
});
