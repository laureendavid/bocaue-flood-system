function loadEvacMonitor() {
  const tbody = document.getElementById("evac-monitor-tbody");
  if (!tbody) return;

  fetch("/soe/includes/fetch_evac_monitor.php")
    .then((res) => res.json())
    .then((json) => {
      if (!json.success || !json.data || json.data.length === 0) {
        tbody.innerHTML = `<tr class="empty-row"><td colspan="3">No evacuation centers to display.</td></tr>`;
        return;
      }

      let html = "";
      json.data.forEach((center) => {
        const pct =
          center.capacity > 0
            ? Math.round((center.occupancy / center.capacity) * 100)
            : 0;

        let barColor = "#22c55e";
        let badgeClass = "badge--available";
        let statusText = "Available";

        if (center.occupancy >= center.capacity) {
          barColor = "#ef4444";
          badgeClass = "badge--full";
          statusText = "Full";
        } else if (center.occupancy >= center.capacity * 0.8) {
          barColor = "#eab308";
          badgeClass = "badge--near-full";
          statusText = "Near Full";
        }

        html += `
          <tr>
            <td>
              <div style="font-weight:600; font-size:0.85rem;">${center.center_name}</div>
              <div class="capacity-bar-wrap" style="margin-top:6px;">
                <div class="capacity-bar" style="width:${pct}%; background:${barColor};"></div>
              </div>
            </td>
            <td style="font-size:0.85rem;">${center.occupancy}/${center.capacity}</td>
            <td><span class="badge ${badgeClass}">${statusText}</span></td>
          </tr>
        `;
      });

      tbody.innerHTML = html;
    })
    .catch((err) => {
      console.error("Evac monitor error:", err);
      if (tbody)
        tbody.innerHTML = `<tr class="empty-row"><td colspan="3">Failed to load.</td></tr>`;
    });
}

window.addEventListener("load", loadEvacMonitor);
