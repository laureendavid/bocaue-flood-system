(function () {
  "use strict";

  function loadRescueStats() {
    var needing = document.getElementById("stat-needing");
    var inprogress = document.getElementById("stat-inprogress");
    var rescued = document.getElementById("stat-rescued");

    if (!needing && !inprogress && !rescued) return;

    fetch("/soe/includes/fetch_rescue_stats.php")
      .then(function (res) {
        return res.json();
      })
      .then(function (json) {
        if (!json.success) return;

        var d = json.data;

        if (needing) animateCount(needing, d.needing);
        if (inprogress) animateCount(inprogress, d.inprogress);
        if (rescued) animateCount(rescued, d.rescued);
      })
      .catch(function (err) {
        console.error("Failed to load rescue stats:", err);
      });
  }

  /* Smooth count-up animation */
  function animateCount(el, target) {
    var start = 0;
    var duration = 800;
    var startTime = null;

    function step(timestamp) {
      if (!startTime) startTime = timestamp;
      var progress = Math.min((timestamp - startTime) / duration, 1);
      // ease out
      var eased = 1 - Math.pow(1 - progress, 3);
      el.textContent = Math.round(eased * target);
      if (progress < 1) requestAnimationFrame(step);
      else el.textContent = target;
    }

    requestAnimationFrame(step);
  }

  function init() {
    if (!document.getElementById("stat-needing")) return;
    loadRescueStats();

    // Auto-refresh every 60 seconds
    setInterval(loadRescueStats, 60000);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
