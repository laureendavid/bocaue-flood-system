/* ===== SIDEBAR TOGGLE ===== */
function openSidebar() {
  const sidebar = document.getElementById("sidebar");
  const overlay = document.getElementById("sidebar-overlay");
  if (!sidebar || !overlay) return;
  sidebar.classList.add("open");
  overlay.classList.add("visible");
  document.body.style.overflow = "hidden";
}

function closeSidebar() {
  const sidebar = document.getElementById("sidebar");
  const overlay = document.getElementById("sidebar-overlay");
  if (!sidebar || !overlay) return;
  sidebar.classList.remove("open");
  overlay.classList.remove("visible");
  document.body.style.overflow = "";
}

/* ===== DARK MODE ===== */
function toggleDarkMode() {
  document.documentElement.classList.toggle("dark");
}

/* ===== DATA MANAGEMENT TABS ===== */
function switchDMTab(tabName) {
  document.querySelectorAll(".dm-tab").forEach((btn) => {
    btn.classList.toggle("active", btn.dataset.tab === tabName);
    btn.setAttribute("aria-selected", btn.dataset.tab === tabName);
  });
  document.querySelectorAll(".dm-panel").forEach((panel) => {
    panel.style.display = panel.dataset.panel === tabName ? "block" : "none";
  });
}

/* ===== INIT ===== */
document.addEventListener("DOMContentLoaded", () => {
  /* Hamburger open */
  const hamburgerBtn = document.getElementById("hamburger-btn");
  if (hamburgerBtn) {
    hamburgerBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      openSidebar();
    });
  }

  /* Sidebar close (X) */
  const closeBtn = document.getElementById("sidebar-close-btn");
  if (closeBtn) {
    closeBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      closeSidebar();
    });
  }

  /* Overlay closes sidebar */
  const overlay = document.getElementById("sidebar-overlay");
  if (overlay) {
    overlay.addEventListener("click", closeSidebar);
  }

  /* Data Management tabs */
  document.querySelectorAll(".dm-tab").forEach((btn) => {
    btn.addEventListener("click", function () {
      switchDMTab(this.dataset.tab);
    });
  });

  /* Profile dropdown */
  const profileBtn = document.getElementById("profile-btn");
  const profileDropdown = document.getElementById("profile-dropdown");

  if (profileBtn && profileDropdown) {
    profileBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      profileDropdown.classList.toggle("open");
    });

    document.addEventListener("click", () => {
      profileDropdown.classList.remove("open");
    });
  }
});

/* prevent back button when logged out (cant go back without logging in again) */
window.addEventListener("pageshow", function (event) {
  if (
    event.persisted ||
    performance.getEntriesByType("navigation")[0].type === "back_forward"
  ) {
    window.location.href = "../main/login.php";
  }
});
