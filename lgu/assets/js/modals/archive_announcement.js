// ── Shared Toast ───────────────────────────────────────────
function showArchiveToast(message, type = "success") {
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

// ── View Archives button ───────────────────────────────────
document.addEventListener("click", function (e) {
  if (!e.target.closest("#btn-view-archives")) return;
  openModal("modal-archived-announcements");
  loadArchives();
});

// ── Delete Archived — open confirm modal ───────────────────
document.addEventListener("click", function (e) {
  const btn = e.target.closest(".btn-delete-archived");
  if (!btn) return;
  document.getElementById("del-archived-id").value = btn.dataset.id;
  document.getElementById("del-archived-title").textContent = btn.dataset.title;
  openModal("modal-delete-archived");
});

// ── Confirm Delete Archived ────────────────────────────────
document.addEventListener("click", function (e) {
  if (!e.target.closest("#confirm-delete-archived")) return;
  const id = document.getElementById("del-archived-id").value;

  fetch("../api/delete_announcement.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `id=${id}`,
  })
    .then((r) => r.json())
    .then((data) => {
      closeModal("modal-delete-archived");
      showArchiveToast(data.message, data.success ? "success" : "error");
      if (data.success) loadArchives();
    })
    .catch(() =>
      showArchiveToast("Something went wrong. Please try again.", "error"),
    );
});

// ── Load Archives ──────────────────────────────────────────
function loadArchives() {
  const loading = document.getElementById("archive-loading");
  const empty = document.getElementById("archive-empty");
  const tableWrap = document.getElementById("archive-table-wrap");
  const tbody = document.getElementById("archive-tbody");

  loading.style.display = "block";
  empty.style.display = "none";
  tableWrap.style.display = "none";
  tbody.innerHTML = "";

  fetch("../includes/fetch_archived_announcements.php")
    .then((r) => r.json())
    .then((data) => {
      loading.style.display = "none";

      if (!data.success || data.data.length === 0) {
        empty.style.display = "block";
        return;
      }

      data.data.forEach((row) => {
        const tr = document.createElement("tr");
        const expiredDate = row.expiry_date
          ? new Date(row.expiry_date).toLocaleDateString("en-US", {
              year: "numeric",
              month: "long",
              day: "numeric",
            })
          : "—";

        tr.innerHTML = `
  <td style="padding:12px 16px; font-size:0.88rem; font-weight:500; color:var(--text-dark); border-bottom:1px solid var(--border-color, #e2e8f0); vertical-align:top;">${escapeHtml(row.title)}</td>
  <td style="padding:12px 16px; font-size:0.85rem; color:var(--text-muted); border-bottom:1px solid var(--border-color, #e2e8f0); vertical-align:top; word-break:break-word; white-space:normal; max-width:220px;">${escapeHtml(row.message)}</td>
  <td style="padding:12px 16px; font-size:0.85rem; border-bottom:1px solid var(--border-color, #e2e8f0); vertical-align:top;">${escapeHtml(row.target_area)}</td>
  <td style="padding:12px 16px; font-size:0.85rem; color:#ef4444; font-weight:500; border-bottom:1px solid var(--border-color, #e2e8f0); vertical-align:top; white-space:nowrap;">${expiredDate}</td>
  <td style="padding:12px 16px; text-align:center; border-bottom:1px solid var(--border-color, #e2e8f0); vertical-align:top;">
    <button class="btn-evac-action btn-delete-center btn-delete-archived" title="Delete Permanently"
      data-id="${row.announcement_id}"
      data-title="${escapeHtml(row.title)}">
      <span class="material-symbols-outlined">delete</span>
    </button>
  </td>`;
        tbody.appendChild(tr);
      });

      tableWrap.style.display = "block";
    })
    .catch(() => {
      loading.style.display = "none";
      empty.style.display = "block";
    });
}

// ── Escape HTML helper ─────────────────────────────────────
function escapeHtml(str) {
  const div = document.createElement("div");
  div.textContent = str;
  return div.innerHTML;
}

// ── Search Archives ────────────────────────────────────────
document.addEventListener("input", function (e) {
  if (e.target.id !== "archive-search") return;
  const term = e.target.value.toLowerCase();
  document.querySelectorAll("#archive-tbody tr").forEach((row) => {
    if (row.classList.contains("empty-row")) return;
    row.style.display = row.textContent.toLowerCase().includes(term)
      ? ""
      : "none";
  });
});
