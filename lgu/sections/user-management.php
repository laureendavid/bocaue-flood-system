<!-- ============================================================
  USER MANAGEMENT SECTION
  ============================================================ -->
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
        Filter by Role
      </button>
      <div class="filter-dropdown" id="um-filter-dropdown">
        <p class="filter-label">Filter by Role</p>
        <label><input type="radio" name="um-role" value="" checked /> All</label>
        <label><input type="radio" name="um-role" value="LGU" /> LGU</label>
        <label><input type="radio" name="um-role" value="Rescuer" /> Rescuer</label>
        <label><input type="radio" name="um-role" value="Resident" /> Resident</label>
      </div>
    </div>
    <button class="btn-add" id="btn-add-user" aria-label="Add new user">
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


<!-- ===== ADD USER MODAL ===== -->
<div class="lgu-modal-backdrop" id="addUserModal" role="dialog" aria-modal="true" aria-labelledby="addUserModalTitle">
  <div class="add-user-modal-card">

    <!-- Header -->
    <div class="add-user-modal-header">
      <h3 id="addUserModalTitle">
        <span class="material-symbols-outlined"
          style="font-size:20px;vertical-align:middle;margin-right:6px;">person_add</span>
        Add New User
      </h3>
      <button class="add-user-close-btn" id="closeAddUserModal" aria-label="Close">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>

    <!-- Body -->
    <form id="addUserForm" novalidate>
      <div class="add-user-modal-body">

        <!-- Name Row -->
        <div class="au-field-row au-name-row">
          <div class="au-form-group">
            <label class="au-label" for="au_first_name">First Name <span class="au-required">*</span></label>
            <input type="text" id="au_first_name" name="first_name" class="au-input" placeholder="JUAN" required
              autocomplete="off" />
          </div>
          <div class="au-form-group">
            <label class="au-label" for="au_last_name">Last Name <span class="au-required">*</span></label>
            <input type="text" id="au_last_name" name="last_name" class="au-input" placeholder="DELA CRUZ" required
              autocomplete="off" />
          </div>
          <div class="au-form-group au-form-group--sm">
            <label class="au-label" for="au_suffix">Suffix</label>
            <input type="text" id="au_suffix" name="suffix" class="au-input" placeholder="Jr, Sr, III…" maxlength="10"
              autocomplete="off" />
          </div>
        </div>

        <!-- Email -->
        <div class="au-form-group">
          <label class="au-label" for="au_email">Email Address <span class="au-required">*</span></label>
          <input type="email" id="au_email" name="email" class="au-input" placeholder="juan@email.com" required
            autocomplete="off" />
        </div>

        <!-- Barangay + Role -->
        <div class="au-field-row">
          <div class="au-form-group">
            <label class="au-label">Barangay <span class="au-required">*</span></label>
            <div id="au_barangay_display"
              style="padding:10px 12px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;color:#475569;font-size:0.92rem;min-height:42px;">
              Pin the map to detect barangay
            </div>
            <input type="hidden" id="au_barangay" name="barangay_id" />
          </div>
          <div class="au-form-group">
            <label class="au-label" for="au_role">Role <span class="au-required">*</span></label>
            <select id="au_role" name="role_id" class="au-input" required>
              <option value="" disabled selected>Select role</option>
              <option value="1">LGU</option>
              <option value="2">Rescuer</option>
              <option value="3">Resident</option>
            </select>
          </div>
        </div>

        <!-- Address -->
        <div class="au-form-group">
          <label class="au-label" for="au_address">
            Address
            <span class="au-map-hint">— auto-filled when barangay is selected</span>
          </label>
          <textarea id="au_address" name="current_address" class="au-input au-textarea" rows="2"
            placeholder="Street, Barangay, Bocaue, Bulacan"></textarea>
        </div>

        <!-- MAP -->
        <div class="au-map-wrap">
          <div class="au-map-toolbar">
            <span class="au-map-status" id="au_map_status">Select a barangay or drag the pin.</span>
            <button type="button" class="au-gps-btn" id="au_gps_btn">
              <span class="material-symbols-outlined" style="font-size:15px;vertical-align:middle;">my_location</span>
              Use My Location
            </button>
          </div>
          <div id="au_map" aria-label="Select location on map"></div>
        </div>
        <input type="hidden" id="au_lat" name="latitude" />
        <input type="hidden" id="au_lng" name="longitude" />

        <!-- Password -->
        <div class="au-field-row">
          <div class="au-form-group">
            <label class="au-label" for="au_password">Password <span class="au-required">*</span></label>
            <div class="au-pw-wrap">
              <input type="password" id="au_password" name="password" class="au-input" required
                autocomplete="new-password" placeholder="Min. 8 characters" />
              <button type="button" class="au-pw-toggle" data-target="au_password">SHOW</button>
            </div>
          </div>
          <div class="au-form-group">
            <label class="au-label" for="au_confirm_password">Confirm Password <span
                class="au-required">*</span></label>
            <div class="au-pw-wrap">
              <input type="password" id="au_confirm_password" name="confirm_password" class="au-input" required
                autocomplete="new-password" placeholder="Re-enter password" />
              <button type="button" class="au-pw-toggle" data-target="au_confirm_password">SHOW</button>
            </div>
            <p class="au-match-text" id="au_match_text"></p>
          </div>
        </div>

        <!-- Error box -->
        <div class="au-error" id="au_error" style="display:none;"></div>

      </div><!-- /modal-body -->

      <!-- Footer -->
      <div class="add-user-modal-footer">
        <button type="button" class="au-btn-cancel" id="cancelAddUser">Cancel</button>
        <button type="submit" class="au-btn-save" id="au_submit_btn">
          <span class="material-symbols-outlined" style="font-size:16px;">person_add</span>
          Add User
        </button>
      </div>
    </form>

  </div>
</div>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />