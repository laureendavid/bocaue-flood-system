<!-- ================================================================
     MODALS — included once in main.php, always in DOM but hidden
     ================================================================ -->

<!-- ===== MODAL: Add Hotline ===== -->
<div id="modal-hotline" class="modal-overlay" aria-modal="true" role="dialog">
  <div class="modal">
    <div class="modal-header">
      <h3 id="modal-hotline-title">Add Hotline</h3>
      <button class="modal-close" data-modal="modal-hotline" aria-label="Close">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>

    <div class="modal-body">
      <div class="form-group">
        <label>Barangay</label>
        <select id="hotline-barangay" class="form-select">
          <option value="" disabled selected>Select Barangay</option>
          <option value="1">Antipona</option>
          <option value="2">Bagumbayan</option>
          <option value="3">Bambang</option>
          <option value="4">Batia</option>
          <option value="5">Biñang 1st</option>
          <option value="6">Biñang 2nd</option>
          <option value="7">Bolacan</option>
          <option value="8">Bundukan</option>
          <option value="9">Bunlo</option>
          <option value="10">Caingin</option>
          <option value="11">Duhat</option>
          <option value="12">Igulot</option>
          <option value="13">Lolomboy</option>
          <option value="14">Poblacion</option>
          <option value="15">Sulucan</option>
          <option value="16">Taal</option>
          <option value="17">Tambobong</option>
          <option value="18">Turo</option>
          <option value="19">Wakas</option>
        </select>
      </div>

      <div class="form-group">
        <label>Hotline Name</label>
        <input type="text" id="hotline-name" class="form-input" placeholder="e.g. MDRRMO Hotline" />
      </div>

      <div class="form-group">
        <label>Contact Number</label>
        <input type="text" id="hotline-contact" class="form-input" placeholder="e.g. 09XX-XXX-XXXX" />
      </div>
    </div>

    <div class="modal-footer">
      <button class="btn-cancel modal-close" data-modal="modal-hotline">Cancel</button>
      <button class="btn-save" id="hotline-save">Save Changes</button>
    </div>
  </div>
</div>

<!-- Toast container -->
<div id="toast-container"></div>
<script src="/soe/lgu/assets/js/add_hotlines.js"></script>


<!-- ===== MODAL: Add Center ===== -->
<div id="modal-center" class="modal-overlay" aria-modal="true" role="dialog" aria-labelledby="modal-center-title">
  <div class="modal modal--wide">
    <div class="modal-header">
      <h3 id="modal-center-title">Add Center</h3>
      <button class="modal-close" data-modal="modal-center" aria-label="Close">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label for="center-name">Center Name</label>
        <input type="text" id="center-name" class="form-input" placeholder="e.g. Barangay Hall Evacuation Center" />
      </div>
      <div class="form-group">
        <label for="center-capacity">Capacity</label>
        <input type="number" id="center-capacity" class="form-input" placeholder="e.g. 500" min="1" />
      </div>
      <div class="form-group">
        <label>Pin Location on Map</label>
        <p class="form-hint">
          Click <strong>Use My Location</strong> to automatically detect your location,
          then <strong>click anywhere on the map</strong> to adjust the pin if needed.
          You can also <strong>drag the pin</strong> to fine-tune the exact position.
        </p>
        <div id="center-map" class="map-picker"></div>

        <!-- Coordinates display -->
        <div class="map-coords" id="map-coords-display">
          <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle">location_on</span>
          No location selected yet.
        </div>
      </div>

      <!-- Address field -->
      <div class="form-group" id="location-name-group" style="display:none;">
        <label for="map-location-name">Address</label>
        <p class="form-hint">The detected address may be incorrect or missing. Please enter the correct address
          manually.</p>
        <input type="text" id="map-location-name" class="form-input"
          placeholder="Address not detected? Type it manually here..." style="color:#1e293b; background:#fff;" />
      </div>

      <input type="hidden" id="center-lat" />
      <input type="hidden" id="center-lng" />
      <input type="hidden" id="center-address" />
    </div>
    <div class="modal-footer">
      <button class="btn-cancel modal-close" data-modal="modal-center">Cancel</button>
      <button class="btn-save">Save Changes</button>
    </div>
  </div>
</div>

<!-- ===== MODAL: Add Announcement ===== -->
<div id="modal-announcement" class="modal-overlay" aria-modal="true" role="dialog"
  aria-labelledby="modal-announcement-title">
  <div class="modal">
    <div class="modal-header">
      <h3 id="modal-announcement-title">Add Announcement</h3>
      <button class="modal-close" data-modal="modal-announcement" aria-label="Close">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label for="announce-title">Title</label>
        <input type="text" id="announce-title" class="form-input" placeholder="Announcement title" />
      </div>
      <div class="form-group">
        <label for="announce-message">Message</label>
        <textarea id="announce-message" class="form-textarea" placeholder="Write your announcement here..."></textarea>
      </div>
      <div class="form-group">
        <label for="announce-area">Target Area</label>
        <select id="announce-area" class="form-select">
          <option value="" disabled selected>Select Target Area</option>
          <option>All Barangays</option>
          <option>Antipona</option>
          <option>Bagumbayan</option>
          <option>Bambang</option>
          <option>Batia</option>
          <option>Biñang 1st</option>
          <option>Biñang 2nd</option>
          <option>Bolacan</option>
          <option>Bundukan</option>
          <option>Bunlo</option>
          <option>Caingin</option>
          <option>Duhat</option>
          <option>Igulot</option>
          <option>Lolomboy</option>
          <option>Poblacion</option>
          <option>Sulucan</option>
          <option>Taal</option>
          <option>Tambobong</option>
          <option>Turo</option>
          <option>Wakas</option>
        </select>
      </div>
      <div class="form-group">
        <label for="announce-expiry">Expiry Date</label>
        <input type="date" id="announce-expiry" class="form-input" />
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn-cancel modal-close" data-modal="modal-announcement">Cancel</button>
      <button class="btn-save" id="announcement-save">Save Changes</button>
    </div>
  </div>
</div>