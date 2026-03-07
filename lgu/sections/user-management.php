<section id="page-user-management" class="page active" aria-labelledby="um-heading">
  <header class="page-header">
    <h2 id="um-heading">User Management</h2>
  </header>

  <div class="toolbar">
    <div class="search-box">
      <span class="material-symbols-outlined">search</span>
      <input type="search" id="um-search" placeholder="Search users" aria-label="Search users" />
    </div>
    <div class="filter-wrapper">
      <button class="btn-filter" id="um-filter-btn" aria-label="Filter users">
        <span class="material-symbols-outlined" style="font-size:16px">filter_list</span>
        Filter
      </button>
      <div class="filter-dropdown" id="um-filter-dropdown">
        <p class="filter-label">Filter by Role</p>
        <label><input type="radio" name="um-role" value="" checked /> All</label>
        <label><input type="radio" name="um-role" value="LGU" /> LGU</label>
        <label><input type="radio" name="um-role" value="Rescuer" /> Rescuer</label>
        <label><input type="radio" name="um-role" value="Resident" /> Resident</label>
      </div>
    </div>
    <button class="btn-add" aria-label="Add new user">
      <span class="material-symbols-outlined">add</span>
      Add User
    </button>
  </div>

  <div class="table-wrap">
    <table aria-label="User management table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Barangay</th>
          <th>Role</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody id="um-tbody">
        <?php include('../includes/fetch_users.php'); ?>
      </tbody>
    </table>
    <div id="um-empty" style="display:none;" class="empty-row">
      <p>No users match your search.</p>
    </div>
  </div>
</section>