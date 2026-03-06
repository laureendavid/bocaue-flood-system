// ── Toast ──────────────────────────────────────────────────
function showHotlineToast(message, type = "success") {
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

// ── Edit Hotline — open modal ──────────────────────────────
document.addEventListener("click", function (e) {
  const btn = e.target.closest(".btn-edit-hotline");
  if (!btn) return;
  document.getElementById("edit-hotline-id").value = btn.dataset.id;
  document.getElementById("edit-hotline-barangay").value = btn.dataset.barangay;
  document.getElementById("edit-hotline-name").value = btn.dataset.name;
  document.getElementById("edit-hotline-number").value = btn.dataset.number;
  openModal("modal-edit-hotline");
});

// ── Delete Hotline — open modal ────────────────────────────
document.addEventListener("click", function (e) {
  const btn = e.target.closest(".btn-delete-hotline");
  if (!btn) return;
  document.getElementById("del-hotline-id").value = btn.dataset.id;
  document.getElementById("del-hotline-name").textContent = btn.dataset.name;
  openModal("modal-delete-hotline");
});

// ── Confirm Edit ───────────────────────────────────────────
document
  .getElementById("confirm-edit-hotline")
  ?.addEventListener("click", () => {
    const id = document.getElementById("edit-hotline-id").value;
    const name = document.getElementById("edit-hotline-name").value.trim();
    const number = document.getElementById("edit-hotline-number").value.trim();

    if (!name || !number) {
      showHotlineToast("All fields are required.", "error");
      return;
    }

    fetch("../api/edit_hotline.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `id=${id}&hotline_name=${encodeURIComponent(name)}&contact_number=${encodeURIComponent(number)}`,
    })
      .then((r) => r.json())
      .then((data) => {
        closeModal("modal-edit-hotline");
        showHotlineToast(data.message, data.success ? "success" : "error");
        if (data.success) setTimeout(() => location.reload(), 1500);
      })
      .catch(() =>
        showHotlineToast("Something went wrong. Please try again.", "error"),
      );
  });

// ── Confirm Delete ─────────────────────────────────────────
document
  .getElementById("confirm-delete-hotline")
  ?.addEventListener("click", () => {
    const id = document.getElementById("del-hotline-id").value;

    fetch("../api/delete_hotline.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `id=${id}`,
    })
      .then((r) => r.json())
      .then((data) => {
        closeModal("modal-delete-hotline");
        showHotlineToast(data.message, data.success ? "success" : "error");
        if (data.success) setTimeout(() => location.reload(), 1500);
      })
      .catch(() =>
        showHotlineToast("Something went wrong. Please try again.", "error"),
      );
  });

// ── Filter & Search ────────────────────────────────────────
const hotlineFilterBtn = document.getElementById("hotline-filter-btn");
const hotlineFilterDropdown = document.getElementById(
  "hotline-filter-dropdown",
);
const hotlineSearch = document.getElementById("hotline-search");
let hotlineFiltersPopulated = false;

function populateHotlineFilters() {
  // Only build the HTML once — preserves checked state on reopen
  if (hotlineFiltersPopulated) return;
  hotlineFiltersPopulated = true;

  const rows = document.querySelectorAll("#hotline-tbody tr");
  const barangays = new Set();
  const names = new Set();

  rows.forEach((row) => {
    if (row.classList.contains("empty-row")) return;
    const cells = row.querySelectorAll("td");
    if (cells[0]) barangays.add(cells[0].textContent.trim());
    if (cells[1]) names.add(cells[1].textContent.trim());
  });

  const barangayContainer = document.getElementById("hotline-barangay-filters");
  const nameContainer = document.getElementById("hotline-name-filters");

  if (barangayContainer) {
    barangayContainer.innerHTML = `
      <label><input type="radio" name="hotline-filter-barangay" value="" checked /> All</label>
      ${[...barangays].map((b) => `<label><input type="radio" name="hotline-filter-barangay" value="${b}" /> ${b}</label>`).join("")}
    `;
  }

  if (nameContainer) {
    nameContainer.innerHTML = `
      <label><input type="radio" name="hotline-filter-name" value="" checked /> All</label>
      ${[...names].map((n) => `<label><input type="radio" name="hotline-filter-name" value="${n}" /> ${n}</label>`).join("")}
    `;
  }

  document
    .querySelectorAll(
      'input[name="hotline-filter-barangay"], input[name="hotline-filter-name"]',
    )
    .forEach((radio) => radio.addEventListener("change", filterHotlineTable));
}

function filterHotlineTable() {
  const search = hotlineSearch?.value.toLowerCase().trim() ?? "";
  const activeBarangay =
    document.querySelector('input[name="hotline-filter-barangay"]:checked')
      ?.value ?? "";
  const activeName =
    document.querySelector('input[name="hotline-filter-name"]:checked')
      ?.value ?? "";
  const rows = document.querySelectorAll("#hotline-tbody tr");
  let visible = 0;

  rows.forEach((row) => {
    if (row.classList.contains("empty-row")) return;
    const cells = row.querySelectorAll("td");
    const barangay = cells[0]?.textContent.trim() ?? "";
    const name = cells[1]?.textContent.trim() ?? "";
    const text = row.textContent.toLowerCase();

    const matchesSearch = search === "" || text.includes(search);
    const matchesBarangay =
      activeBarangay === "" || barangay === activeBarangay;
    const matchesName = activeName === "" || name === activeName;

    if (matchesSearch && matchesBarangay && matchesName) {
      row.style.display = "";
      visible++;
    } else {
      row.style.display = "none";
    }
  });

  const empty = document.getElementById("hotline-empty");
  if (empty) empty.style.display = visible === 0 ? "block" : "none";

  // Highlight filter button if any filter is active
  const anyActive = activeBarangay || activeName;
  if (hotlineFilterBtn) {
    hotlineFilterBtn.style.background = anyActive ? "var(--tab-active)" : "";
    hotlineFilterBtn.style.fontWeight = anyActive ? "700" : "";
  }
}

// Toggle dropdown
hotlineFilterBtn?.addEventListener("click", (e) => {
  e.stopPropagation();
  const isOpen = hotlineFilterDropdown.style.display === "flex";
  if (!isOpen) populateHotlineFilters();
  hotlineFilterDropdown.style.display = isOpen ? "none" : "flex";
  hotlineFilterDropdown.style.flexDirection = "column";
  hotlineFilterDropdown.style.gap = "8px";
});

// Close on outside click
document.addEventListener("click", (e) => {
  if (
    !hotlineFilterDropdown?.contains(e.target) &&
    e.target !== hotlineFilterBtn
  ) {
    if (hotlineFilterDropdown) hotlineFilterDropdown.style.display = "none";
  }
});

// Search
hotlineSearch?.addEventListener("input", filterHotlineTable);
