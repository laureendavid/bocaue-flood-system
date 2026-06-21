function loadHotlines() {
  fetch("../api/fetch_hotlines.php")
    .then((res) => res.json())
    .then((json) => {
      const container = document.getElementById("hotlines-list");
      if (!json.success || Object.keys(json.data).length === 0) {
        container.innerHTML =
          '<p class="placeholder-text placeholder-text--padded">No hotlines to display.</p>';
        return;
      }

      let html = "";
      for (const [barangay, entries] of Object.entries(json.data)) {
        html += `<div class="hotline-district">
          <h4>${barangay}</h4>`;
        entries.forEach((e) => {
          html += `<div class="hotline-row">
            <span>${e.hotline_name}</span>
            <span>${e.contact_number}</span>
          </div>`;
        });
        html += `</div>`;
      }
      container.innerHTML = html;
    })
    .catch(() => {
      document.getElementById("hotlines-list").innerHTML =
        '<p class="placeholder-text placeholder-text--padded">Failed to load hotlines.</p>';
    });
}

document.addEventListener("DOMContentLoaded", loadHotlines);
