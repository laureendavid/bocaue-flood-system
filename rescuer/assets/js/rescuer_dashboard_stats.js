(function () {
  "use strict";

  function loadRescueStats() {
    fetch("/soe/includes/fetch_rescue_stats.php")
      .then(function (res) {
        return res.json();
      })
      .then(function (json) {
        if (!json.success) return;

        var o = json.overall;
        var p = json.personal;

        /* overall */
        animateCount("stat-needing", o.needing);
        animateCount("stat-inprogress", o.inprogress);
        animateCount("stat-rescued", o.rescued);

        /* personal */
        animateCount("stat-my-inprogress", p.inprogress);
        animateCount("stat-my-rescued", p.rescued);
      })
      .catch(function (err) {
        console.error("Failed to load rescue stats:", err);
      });
  }

  function animateCount(id, target) {
    var el = document.getElementById(id);
    if (!el) return;

    var duration = 800;
    var startTime = null;

    function step(timestamp) {
      if (!startTime) startTime = timestamp;
      var progress = Math.min((timestamp - startTime) / duration, 1);
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
    setInterval(loadRescueStats, 60000);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
