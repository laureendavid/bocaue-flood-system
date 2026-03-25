(function () {
  "use strict";

  function loadDashboardHotlines() {
    var container = document.getElementById("dash-hotlines-list");
    if (!container) return;

    fetch("/soe/api/fetch_hotlines.php")
      .then(function (res) {
        return res.json();
      })
      .then(function (json) {
        if (!json.success || Object.keys(json.data).length === 0) {
          container.innerHTML =
            "<li class='empty-state-inline'>No hotlines available.</li>";
          return;
        }

        var html = "";
        Object.entries(json.data).forEach(function ([barangay, entries]) {
          html += "<li class='hotline-district'>";
          html += "<div class='hotline-district-name'>" + barangay + "</div>";
          entries.forEach(function (e) {
            html +=
              "<div class='hotline-row'>" +
              "<span class='hotline-type'>" +
              e.hotline_name +
              "</span>" +
              "<span class='hotline-number' style='color:#1a1a2e;font-family:monospace;font-weight:500;'>" +
              e.contact_number +
              "</span>" +
              "</div>";
          });
          html += "</li>";
        });

        container.innerHTML = html;
      })
      .catch(function () {
        container.innerHTML =
          "<li class='empty-state-inline'>Failed to load hotlines.</li>";
      });
  }

  function init() {
    if (!document.getElementById("dash-hotlines-list")) return;
    loadDashboardHotlines();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
