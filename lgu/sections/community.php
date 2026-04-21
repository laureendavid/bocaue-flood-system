<section id="page-community" class="page active" aria-labelledby="community-heading">
  <header class="page-header">
    <h2 id="community-heading">Community</h2>
  </header>

  <div class="community-grid">
    <aside class="announcements-sidebar" aria-labelledby="comm-announce-heading"
      style="max-height:75vh; overflow-y:auto;">
      <h3 id="comm-announce-heading" class="community-section-title">Announcements</h3>
      <?php include '../includes/fetch_commAnnouncement.php'; ?>
    </aside>

    <div class="community-column">
      <h3 class="community-section-title">Community Posts</h3>
          <?php include '../includes/fetch_communityReports.php'; ?>
    </div>
  </div>
</section>