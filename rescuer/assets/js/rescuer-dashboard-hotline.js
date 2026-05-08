(function () {
  "use strict";

  function loadDashboardHotlines() {
    var container = document.getElementById("dash-hotlines-list");
    if (!container) return;

    // Show loading state
    container.innerHTML = "<p class='rdb-empty'>Loading hotlines...</p>";

    fetch("/soe/api/fetch_hotlines.php")
      .then(function (res) {
        return res.json();
      })
      .then(function (json) {
        if (
          !json.success ||
          !json.data ||
          Object.keys(json.data).length === 0
        ) {
          container.innerHTML =
            "<p class='rdb-empty'>No hotlines available.</p>";
          return;
        }

        var html = "";

        Object.entries(json.data).forEach(function ([barangay, entries]) {
          html += "<div class='rdb-hotline-district'>";
          html += "<p class='rdb-hotline-name'>" + escHtml(barangay) + "</p>";

          entries.forEach(function (e) {
            html +=
              "<div class='rdb-hotline-row'>" +
              "<span class='rdb-hotline-type'>" +
              escHtml(e.hotline_name) +
              "</span>" +
              "<span class='rdb-hotline-number'>" +
              escHtml(e.contact_number) +
              "</span>" +
              "</div>";
          });

          html += "</div>";
        });

        container.innerHTML = html;
      })
      .catch(function (err) {
        console.error("Failed to load hotlines:", err);
        container.innerHTML =
          "<p class='rdb-empty'>Failed to load hotlines.</p>";
      });
  }

  function escHtml(str) {
    if (!str) return "";
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
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
