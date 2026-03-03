<section id="page-user-management" class="page active" aria-labelledby="um-heading">
  <header class="page-header">
    <h2 id="um-heading">User Management</h2>
  </header>

  <div class="toolbar">
    <div class="search-box">
      <span class="material-symbols-outlined">search</span>
      <input type="search" placeholder="Search users" aria-label="Search users" />
    </div>
    <button class="btn-filter" aria-label="Filter users">
      <span class="material-symbols-outlined" style="font-size:16px">filter_list</span>
      Filter
    </button>
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
      <tbody>
        <tr class="empty-row">
          <td colspan="6">No users to display.</td>
        </tr>
      </tbody>
    </table>
  </div>
</section>
