function showCenterToast(message, type = "success") {
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

document.addEventListener("click", function (e) {
  const saveBtn = e.target.closest("#modal-center .btn-save");
  if (!saveBtn) return;

  const centerName = document.getElementById("center-name").value.trim();
  const capacity = document.getElementById("center-capacity").value.trim();
  const lat = document.getElementById("center-lat").value;
  const lng = document.getElementById("center-lng").value;
  const address = document.getElementById("center-address").value.trim();

  if (!centerName) {
    showCenterToast("Please enter a center name.", "error");
    return;
  }
  if (!capacity || capacity < 1) {
    showCenterToast("Please enter a valid capacity.", "error");
    return;
  }
  if (!lat || !lng) {
    showCenterToast("Please pin a location on the map.", "error");
    return;
  }

  saveBtn.disabled = true;
  saveBtn.textContent = "Saving...";

  fetch("../api/save_center.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `center_name=${encodeURIComponent(centerName)}&capacity=${encodeURIComponent(capacity)}&lat=${encodeURIComponent(lat)}&lng=${encodeURIComponent(lng)}&address=${encodeURIComponent(address)}`,
  })
    .then((r) => r.json())
    .then((data) => {
      if (data.success) {
        document.getElementById("center-name").value = "";
        document.getElementById("center-capacity").value = "";
        document.getElementById("center-lat").value = "";
        document.getElementById("center-lng").value = "";
        document.getElementById("center-address").value = "";
        document.getElementById("map-location-name").value = "";
        document.getElementById("location-name-group").style.display = "none";
        document.getElementById("map-coords-display").innerHTML = `
          <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle">location_on</span>
          No location selected yet.
        `;
        const modal = document.getElementById("modal-center");
        if (modal) {
          modal.classList.remove("open");
          document.body.style.overflow = "";
        }
        showCenterToast(data.message);
        setTimeout(() => location.reload(), 1500);
      } else {
        showCenterToast(data.message, "error");
      }
    })
    .catch(() =>
      showCenterToast("Something went wrong. Please try again.", "error"),
    )
    .finally(() => {
      saveBtn.disabled = false;
      saveBtn.textContent = "Save Changes";
    });
});
