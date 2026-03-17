function showEvacToast(message, type = "success") {
  const container = document.getElementById("toast-container");
  if (!container) return;
  const toast = document.createElement("div");
  toast.classList.add("toast", type);
  toast.innerHTML = `<span>${message}</span><span class="close-toast">&times;</span>`;
  container.appendChild(toast);
  toast
    .querySelector(".close-toast")
    .addEventListener("click", () => toast.remove());
  setTimeout(() => toast.remove(), 3000);
}

// ── Open Add Evacuee Modal ──────────────────────────────────
document.addEventListener("click", function (e) {
  const btn = e.target.closest(".btn-add-evacuee");
  if (!btn) return;
  document.getElementById("add-evacuee-center-id").value = btn.dataset.id;
  document.getElementById("evacuee-rep").value = "";
  document.getElementById("evacuee-count").value = "";
  document.getElementById("evacuee-contact").value = "";
  openModal("modal-add-evacuee");
});

// ── Confirm Add Evacuee ─────────────────────────────────────
document
  .getElementById("confirm-add-evacuee")
  ?.addEventListener("click", function () {
    const center_id = document.getElementById("add-evacuee-center-id").value;
    const rep = document.getElementById("evacuee-rep").value.trim();
    const count = document.getElementById("evacuee-count").value.trim();
    const contact = document.getElementById("evacuee-contact").value.trim();

    if (!rep || !count || !contact) {
      showEvacToast("All fields are required.", "error");
      return;
    }

    const body = new URLSearchParams({
      center_id,
      representative: rep,
      number_of_people: count,
      contact_number: contact,
    });

    fetch("../api/add_evacuee.php", { method: "POST", body })
      .then((r) => r.json())
      .then((data) => {
        closeModal("modal-add-evacuee");
        showEvacToast(data.message, data.success ? "success" : "error");
        if (data.success) setTimeout(() => location.reload(), 1500);
      })
      .catch(() => showEvacToast("Something went wrong.", "error"));
  });

// ── Open Remove Evacuee Modal ───────────────────────────────
document.addEventListener("click", function (e) {
  const btn = e.target.closest(".btn-remove-evacuee");
  if (!btn) return;
  const centerId = btn.dataset.id;
  document.getElementById("remove-evacuee-center-id").value = centerId;
  loadEvacuees(centerId);
  openModal("modal-remove-evacuee");
});

function loadEvacuees(centerId) {
  const tbody = document.getElementById("evacuee-list-tbody");
  tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;padding:24px;color:var(--text-muted);">Loading...</td></tr>`;

  fetch(`../api/get_evacuees.php?center_id=${centerId}`)
    .then((r) => r.json())
    .then((data) => {
      if (!data.evacuees.length) {
        tbody.innerHTML = `<tr class="empty-row"><td colspan="4">No evacuees found.</td></tr>`;
        return;
      }
      tbody.innerHTML = data.evacuees
        .map(
          (ev) => `
        <tr>
          <td>${ev.representative}</td>
          <td>${ev.number_of_people}</td>
          <td>${ev.contact_number}</td>
          <td>
            <button class="btn-evac-action btn-delete-center btn-remove-single" 
              data-id="${ev.evacuee_id}" data-count="${ev.number_of_people}" title="Remove">
              <span class="material-symbols-outlined">delete</span>
            </button>
          </td>
        </tr>
      `,
        )
        .join("");
    })
    .catch(() => {
      tbody.innerHTML = `<tr class="empty-row"><td colspan="4">Failed to load evacuees.</td></tr>`;
    });
}

// ── Remove Single Evacuee ───────────────────────────────────
document.addEventListener("click", function (e) {
  const btn = e.target.closest(".btn-remove-single");
  if (!btn) return;
  const evacuee_id = btn.dataset.id;
  const centerId = document.getElementById("remove-evacuee-center-id").value;

  fetch("../api/remove_evacuee.php", {
    method: "POST",
    body: new URLSearchParams({ evacuee_id }),
  })
    .then((r) => r.json())
    .then((data) => {
      showEvacToast(data.message, data.success ? "success" : "error");
      if (data.success) loadEvacuees(centerId);
    })
    .catch(() => showEvacToast("Something went wrong.", "error"));
});

// ── Remove All Evacuees ─────────────────────────────────────
document
  .getElementById("btn-remove-all-evacuees")
  ?.addEventListener("click", function () {
    const centerId = document.getElementById("remove-evacuee-center-id").value;
    if (!confirm("Remove all evacuees from this center?")) return;

    fetch("../api/remove_all_evacuees.php", {
      method: "POST",
      body: new URLSearchParams({ center_id: centerId }),
    })
      .then((r) => r.json())
      .then((data) => {
        showEvacToast(data.message, data.success ? "success" : "error");
        if (data.success) loadEvacuees(centerId);
      })
      .catch(() => showEvacToast("Something went wrong.", "error"));
  });

// ── Search Evacuees ─────────────────────────────────────────
document
  .getElementById("evacuee-search")
  ?.addEventListener("input", function () {
    const term = this.value.toLowerCase();
    document.querySelectorAll("#evacuee-list-tbody tr").forEach((row) => {
      if (row.classList.contains("empty-row")) return;
      row.style.display = row.textContent.toLowerCase().includes(term)
        ? ""
        : "none";
    });
  });

// ── Open Edit Center Modal ──────────────────────────────────
document.addEventListener("click", function (e) {
  const btn = e.target.closest(".btn-edit-center");
  if (!btn) return;
  document.getElementById("edit-center-id").value = btn.dataset.id;
  document.getElementById("edit-center-name").value = btn.dataset.name;
  document.getElementById("edit-center-address").value = btn.dataset.address;
  document.getElementById("edit-center-capacity").value = btn.dataset.capacity;
  openModal("modal-edit-center");
});

// ── Confirm Edit Center ─────────────────────────────────────
document
  .getElementById("confirm-edit-center")
  ?.addEventListener("click", function () {
    const center_id = document.getElementById("edit-center-id").value;
    const center_name = document
      .getElementById("edit-center-name")
      .value.trim();
    const address = document.getElementById("edit-center-address").value.trim();
    const capacity = document
      .getElementById("edit-center-capacity")
      .value.trim();

    if (!center_name || !capacity) {
      showEvacToast("Center name and capacity are required.", "error");
      return;
    }

    fetch("../api/edit_center.php", {
      method: "POST",
      body: new URLSearchParams({ center_id, center_name, address, capacity }),
    })
      .then((r) => r.json())
      .then((data) => {
        closeModal("modal-edit-center");
        showEvacToast(data.message, data.success ? "success" : "error");
        if (data.success) setTimeout(() => location.reload(), 1500);
      })
      .catch(() => showEvacToast("Something went wrong.", "error"));
  });

// ── Open Delete Center Modal ────────────────────────────────
document.addEventListener("click", function (e) {
  const btn = e.target.closest(".btn-delete-center");
  if (!btn || btn.classList.contains("btn-remove-single")) return;
  document.getElementById("del-center-id").value = btn.dataset.id;
  document.getElementById("del-center-name").textContent = btn.dataset.name;
  openModal("modal-delete-center");
});

// ── Confirm Delete Center ───────────────────────────────────
document
  .getElementById("confirm-delete-center")
  ?.addEventListener("click", function () {
    const center_id = document.getElementById("del-center-id").value;

    fetch("../api/delete_center.php", {
      method: "POST",
      body: new URLSearchParams({ center_id }),
    })
      .then((r) => r.json())
      .then((data) => {
        closeModal("modal-delete-center");
        showEvacToast(data.message, data.success ? "success" : "error");
        if (data.success) setTimeout(() => location.reload(), 1500);
      })
      .catch(() => showEvacToast("Something went wrong.", "error"));
  });
