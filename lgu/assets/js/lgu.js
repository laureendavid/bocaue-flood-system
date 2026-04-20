const SIDEBAR_DESKTOP_BREAKPOINT = 1024;
const STORAGE_KEYS = {
  sidebarCollapsed: "lgu_sidebar_collapsed",
  sidebarOpenMobile: "lgu_sidebar_open_mobile",
};

function isDesktopViewport() {
  return window.innerWidth > SIDEBAR_DESKTOP_BREAKPOINT;
}

function setSidebarMobileState(isOpen) {
  const sidebar = document.getElementById("sidebar");
  const overlay = document.getElementById("sidebar-overlay");
  if (!sidebar || !overlay) {
    return;
  }

  sidebar.classList.toggle("open", isOpen);
  overlay.classList.toggle("visible", isOpen);
  document.body.classList.toggle("sidebar-mobile-open", isOpen);
  localStorage.setItem(STORAGE_KEYS.sidebarOpenMobile, isOpen ? "1" : "0");
}

function applySidebarDesktopState() {
  const collapsed = localStorage.getItem(STORAGE_KEYS.sidebarCollapsed) === "1";
  document.body.classList.toggle("sidebar-collapsed", collapsed);
}

function syncSidebarByViewport() {
  if (isDesktopViewport()) {
    setSidebarMobileState(false);
    applySidebarDesktopState();
  } else {
    document.body.classList.remove("sidebar-collapsed");
    const shouldOpen = localStorage.getItem(STORAGE_KEYS.sidebarOpenMobile) === "1";
    setSidebarMobileState(shouldOpen);
  }
}

function switchDMTab(tabName) {
  document.querySelectorAll(".dm-tab").forEach((btn) => {
    const isActive = btn.dataset.tab === tabName;
    btn.classList.toggle("active", isActive);
    btn.setAttribute("aria-selected", isActive ? "true" : "false");
  });

  document.querySelectorAll(".dm-panel").forEach((panel) => {
    panel.style.display = panel.dataset.panel === tabName ? "block" : "none";
  });
}

function initActiveNavHighlight() {
  const currentPage = document.body.dataset.page || "";
  document.querySelectorAll(".sidebar .nav-link").forEach((link) => {
    const isActive = link.dataset.page === currentPage;
    link.classList.toggle("active", isActive);
    if (isActive) {
      link.setAttribute("aria-current", "page");
    } else {
      link.removeAttribute("aria-current");
    }
  });
}

function initSidebarBehavior() {
  const hamburgerBtn = document.getElementById("hamburger-btn");
  const closeBtn = document.getElementById("sidebar-close-btn");
  const overlay = document.getElementById("sidebar-overlay");
  const desktopToggleBtn = document.getElementById("sidebar-toggle-btn");

  if (hamburgerBtn) {
    hamburgerBtn.addEventListener("click", (event) => {
      event.stopPropagation();
      setSidebarMobileState(true);
    });
  }

  if (closeBtn) {
    closeBtn.addEventListener("click", (event) => {
      event.stopPropagation();
      setSidebarMobileState(false);
    });
  }

  if (overlay) {
    overlay.addEventListener("click", () => setSidebarMobileState(false));
  }

  if (desktopToggleBtn) {
    desktopToggleBtn.addEventListener("click", () => {
      const nextCollapsed = !document.body.classList.contains("sidebar-collapsed");
      document.body.classList.toggle("sidebar-collapsed", nextCollapsed);
      localStorage.setItem(STORAGE_KEYS.sidebarCollapsed, nextCollapsed ? "1" : "0");
    });
  }

  document.querySelectorAll(".sidebar .nav-link").forEach((link) => {
    link.addEventListener("click", () => {
      if (!isDesktopViewport()) {
        setSidebarMobileState(false);
      }
    });
  });

  syncSidebarByViewport();
  window.addEventListener("resize", syncSidebarByViewport);
}

function initProfileDropdown() {
  const profileBtn = document.getElementById("profile-btn");
  const profileDropdown = document.getElementById("profile-dropdown");
  if (!profileBtn || !profileDropdown) {
    return;
  }

  profileBtn.addEventListener("click", (event) => {
    event.stopPropagation();
    const isOpen = profileDropdown.classList.contains("open");
    profileDropdown.classList.toggle("open", !isOpen);
  });

  document.addEventListener("click", (event) => {
    if (!event.target.closest("#profile-wrapper")) {
      profileDropdown.classList.remove("open");
    }
  });
}

function initReportVerificationActions() {
  const reportTableBody = document.getElementById("report-verification-body");
  const reportSearch = document.getElementById("report-search");
  const statusFilter = document.getElementById("status-filter");
  const actionModal = document.getElementById("report-action-modal");
  const actionModalMessage = document.getElementById("report-action-modal-message");
  const actionModalCancel = document.getElementById("report-action-cancel");
  const actionModalConfirm = document.getElementById("report-action-confirm");
  const lightbox = document.getElementById("report-image-lightbox");
  const lightboxPreview = document.getElementById("report-image-preview");
  const lightboxClose = document.getElementById("report-image-close");

  let pendingActionButton = null;

  if (!reportTableBody) {
    return;
  }

  const statusAlias = {
    pending: ["pending"],
    verified: ["verified", "approved"],
    approved: ["approved", "verified"],
    rejected: ["rejected"],
  };

  function normalizeStatus(status) {
    return String(status || "").trim().toLowerCase();
  }

  function statusMatches(selectedStatus, rowStatus) {
    if (selectedStatus === "all") {
      return true;
    }

    const aliases = statusAlias[selectedStatus] || [selectedStatus];
    return aliases.includes(rowStatus);
  }

  function filterReportRows() {
    const rows = Array.from(reportTableBody.querySelectorAll("tr[data-report-id]"));
    const searchTerm = (reportSearch?.value || "").trim().toLowerCase();
    const selectedStatus = normalizeStatus(statusFilter?.value || "all");
    let visibleCount = 0;

    rows.forEach((row) => {
      const rowStatus = normalizeStatus(row.dataset.status);
      const rowSearch = normalizeStatus(row.dataset.search);
      const visible = statusMatches(selectedStatus, rowStatus) && (searchTerm === "" || rowSearch.includes(searchTerm));
      row.style.display = visible ? "" : "none";
      if (visible) {
        visibleCount += 1;
      }
    });

    const existingEmpty = reportTableBody.querySelector(".empty-row");
    if (visibleCount > 0 && existingEmpty) {
      existingEmpty.remove();
      return;
    }

    if (visibleCount === 0 && !existingEmpty) {
      const tr = document.createElement("tr");
      tr.className = "empty-row";
      tr.innerHTML = "<td colspan='9'>No reports match your filter.</td>";
      reportTableBody.appendChild(tr);
    }
  }

  function closeActionModal() {
    if (!actionModal) {
      return;
    }
    actionModal.classList.remove("open");
    actionModal.setAttribute("aria-hidden", "true");
    pendingActionButton = null;
  }

  function openActionModal(button) {
    if (!actionModal || !actionModalMessage) {
      return false;
    }

    pendingActionButton = button;
    const action = String(button.dataset.action || "").toLowerCase();
    actionModalMessage.textContent = action === "approved"
      ? "Approve this report and notify the resident?"
      : "Reject this report and notify the resident?";
    actionModal.classList.add("open");
    actionModal.setAttribute("aria-hidden", "false");
    return true;
  }

  function closeLightbox() {
    if (!lightbox || !lightboxPreview) {
      return;
    }
    lightbox.classList.remove("open");
    lightbox.setAttribute("aria-hidden", "true");
    lightboxPreview.src = "";
  }

  function openLightbox(imageSrc, altText) {
    if (!lightbox || !lightboxPreview) {
      return;
    }
    lightboxPreview.src = imageSrc;
    lightboxPreview.alt = altText || "Report image preview";
    lightbox.classList.add("open");
    lightbox.setAttribute("aria-hidden", "false");
  }

  async function updateReportStatus(button) {
    const reportId = button.dataset.reportId;
    const action = button.dataset.action;
    if (!reportId || !action) {
      return;
    }

    button.disabled = true;
    button.classList.add("is-loading");

    try {
      const body = new URLSearchParams();
      body.append("report_id", reportId);
      body.append("status", action);

      const response = await fetch("../includes/update_report_status.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: body.toString(),
      });

      const data = await response.json();
      if (!response.ok || !data.success) {
        throw new Error(data.message || "Failed to update report.");
      }

      const row = button.closest("tr[data-report-id]");
      if (!row) {
        return;
      }

      row.dataset.status = data.status;
      const badge = row.querySelector(".js-report-status");
      if (badge) {
        const nextStatus = String(data.status || "Pending");
        const normalized = normalizeStatus(nextStatus);
        badge.textContent = nextStatus;
        badge.classList.remove("badge-pending", "badge-verified", "badge-approved", "badge-rejected");
        if (normalized === "approved" || normalized === "verified") {
          badge.classList.add("badge-approved");
        } else if (normalized === "rejected") {
          badge.classList.add("badge-rejected");
        } else {
          badge.classList.add("badge-pending");
        }
      }

      row.querySelectorAll(".btn-report-action").forEach((btn) => {
        btn.disabled = true;
        btn.classList.remove("is-loading");
      });

      filterReportRows();
    } catch (error) {
      button.disabled = false;
      button.classList.remove("is-loading");
      alert(error.message || "Something went wrong while updating status.");
    }
  }

  reportTableBody.addEventListener("click", (event) => {
    const imageTrigger = event.target.closest(".report-image-trigger");
    if (imageTrigger) {
      openLightbox(imageTrigger.dataset.imageSrc || "", imageTrigger.dataset.imageAlt || "Report image preview");
      return;
    }

    const actionBtn = event.target.closest(".btn-report-action");
    if (!actionBtn || actionBtn.disabled) {
      return;
    }

    const modalOpened = openActionModal(actionBtn);
    if (!modalOpened) {
      const confirmed = window.confirm("Are you sure you want to continue?");
      if (confirmed) {
        updateReportStatus(actionBtn);
      }
    }
  });

  if (reportSearch) {
    reportSearch.addEventListener("input", filterReportRows);
  }
  if (statusFilter) {
    statusFilter.addEventListener("change", filterReportRows);
  }

  if (actionModalCancel) {
    actionModalCancel.addEventListener("click", closeActionModal);
  }
  if (actionModalConfirm) {
    actionModalConfirm.addEventListener("click", () => {
      if (!pendingActionButton) {
        closeActionModal();
        return;
      }

      const targetButton = pendingActionButton;
      closeActionModal();
      updateReportStatus(targetButton);
    });
  }
  if (actionModal) {
    actionModal.addEventListener("click", (event) => {
      if (event.target === actionModal) {
        closeActionModal();
      }
    });
  }

  if (lightboxClose) {
    lightboxClose.addEventListener("click", closeLightbox);
  }
  if (lightbox) {
    lightbox.addEventListener("click", (event) => {
      if (event.target === lightbox) {
        closeLightbox();
      }
    });
  }

  document.addEventListener("keydown", (event) => {
    if (event.key !== "Escape") {
      return;
    }
    closeActionModal();
    closeLightbox();
  });

  filterReportRows();
}

document.addEventListener("DOMContentLoaded", () => {
  initActiveNavHighlight();
  initSidebarBehavior();
  initProfileDropdown();
  initReportVerificationActions();

  document.querySelectorAll(".dm-tab").forEach((btn) => {
    btn.addEventListener("click", function () {
      switchDMTab(this.dataset.tab);
    });
  });
});

window.addEventListener("pageshow", (event) => {
  const navEntries = performance.getEntriesByType("navigation");
  if (event.persisted || (navEntries.length && navEntries[0].type === "back_forward")) {
    window.location.href = "../main/login.php";
  }
});
