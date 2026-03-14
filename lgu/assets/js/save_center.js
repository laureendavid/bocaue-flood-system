document.addEventListener("DOMContentLoaded", function () {
  const saveBtn = document.querySelector("#modal-center .btn-save");
  if (!saveBtn) return;

  saveBtn.addEventListener("click", function () {
    const centerName = document.getElementById("center-name").value.trim();
    const capacity = document.getElementById("center-capacity").value.trim();
    const lat = document.getElementById("center-lat").value;
    const lng = document.getElementById("center-lng").value;
    const address = document.getElementById("center-address").value.trim();

    if (!centerName) {
      alert("Please enter a center name.");
      return;
    }
    if (!capacity || capacity < 1) {
      alert("Please enter a valid capacity.");
      return;
    }
    if (!lat || !lng) {
      alert("Please pin a location on the map.");
      return;
    }

    saveBtn.disabled = true;
    saveBtn.textContent = "Saving...";

    const formData = new FormData();
    formData.append("center_name", centerName);
    formData.append("capacity", capacity);
    formData.append("lat", lat);
    formData.append("lng", lng);
    formData.append("address", address);

    fetch("../api/save_center.php", {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          // Reset form
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

          // Close modal
          const modal = document.getElementById("modal-center");
          if (modal) {
            modal.classList.remove("open");
            document.body.style.overflow = "";
          }

          alert(data.message);
          location.reload();
        } else {
          alert(data.message);
        }
      })
      .catch(() => alert("Something went wrong. Please try again."))
      .finally(() => {
        saveBtn.disabled = false;
        saveBtn.textContent = "Save Changes";
      });
  });
});
