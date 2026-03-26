<section id="page-community" class="page active" aria-labelledby="community-heading">
  <header class="page-header">
    <h2 id="community-heading">Community</h2>
  </header>

  <div class="community-grid">
    <!-- Announcements Sidebar -->
    <aside class="announcements-sidebar" aria-labelledby="comm-announce-heading">
      <h3 id="comm-announce-heading" style="
        background: #b2dede;
        color: #1e293b;
        font-weight: 700;
        padding: 14px 20px;
        margin: 0;
        border-radius: 6px 6px 0 0;
      ">Announcements</h3>
      <div class="announce-list" id="announce-list" style="max-height: calc(95vh - 200px); overflow-y: auto;">
        <?php include '../includes/fetch_commAnnouncement.php'; ?>
      </div>
    </aside>

    <!-- Posts Feed -->
    <div class="community-feed" id="reports-feed">
      <h3 id="comm-posts-heading" style="
        background: #b2dede;
        color: #1e293b;
        font-weight: 700;
        padding: 14px 20px;
        margin: 0 0 0 0;
        border-radius: 6px 6px 0 0;
      ">Community Posts</h3>
      <article class="post-card post-card--empty">
        No community posts to display.
      </article>
    </div>
  </div>
</section>