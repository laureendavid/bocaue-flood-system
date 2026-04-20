/**
 * BOCAUE FLOOD INFORMATION SYSTEM — Centralized JavaScript
 *
 * Handles:
 *  1. Password toggle (eye icon)
 *  2. OTP input navigation & paste
 *  3. Password strength meter
 *  4. Profile photo preview (circular)
 *  5. Valid ID upload — drag & drop, preview, remove
 *  6. Auto-calculate age from date of birth
 *  7. Inline field validation helpers
 *  8. Alert dismiss buttons
 *  9. Leaflet map — Bocaue bounds, GPS, click-to-pin, drag, reverse geocode
 * 10. Map → auto-fill address textarea + badge
 */

document.addEventListener('DOMContentLoaded', function () {

  /* ══════════════════════════════════════════════════════════════
     1. PASSWORD TOGGLE
  ══════════════════════════════════════════════════════════════ */
  document.querySelectorAll('.toggle-pass').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = this.closest('.input-wrap').querySelector('input');
      if (!input) return;
      var isText = input.type === 'text';
      input.type = isText ? 'password' : 'text';
      var eyeOn  = this.querySelector('.icon-eye');
      var eyeOff = this.querySelector('.icon-eye-off');
      if (eyeOn)  eyeOn.style.display  = isText ? 'block' : 'none';
      if (eyeOff) eyeOff.style.display = isText ? 'none'  : 'block';
    });
  });

  /* ══════════════════════════════════════════════════════════════
     2. OTP INPUT NAVIGATION
  ══════════════════════════════════════════════════════════════ */
  var otpBoxes = document.querySelectorAll('.otp-box');

  otpBoxes.forEach(function (box, index) {
    box.addEventListener('input', function () {
      this.value = this.value.replace(/\D/g, '').slice(-1);
      if (this.value && index < otpBoxes.length - 1) {
        otpBoxes[index + 1].focus();
      }
    });
    box.addEventListener('keydown', function (e) {
      if (e.key === 'Backspace' && !this.value && index > 0) {
        otpBoxes[index - 1].focus();
      }
    });
    box.addEventListener('paste', function (e) {
      e.preventDefault();
      var pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
      if (pasted.length === otpBoxes.length) {
        otpBoxes.forEach(function (b, i) { b.value = pasted[i] || ''; });
        otpBoxes[otpBoxes.length - 1].focus();
      }
    });
  });

  /* Assemble OTP into hidden field before submit */
  var otpForm = document.getElementById('step1Form');
  if (otpForm) {
    otpForm.addEventListener('submit', function () {
      var combined = '';
      otpBoxes.forEach(function (b) { combined += b.value; });
      var hidden = document.getElementById('otp_combined');
      if (hidden) hidden.value = combined;
    });
  }

  /* ══════════════════════════════════════════════════════════════
     3. PASSWORD STRENGTH METER
  ══════════════════════════════════════════════════════════════ */
  var passInput = document.getElementById('access_key');
  if (passInput) {
    passInput.addEventListener('input', function () { updateStrength(this.value); });
  }

  function updateStrength(val) {
    var fill       = document.getElementById('strengthFill');
    var label      = document.getElementById('strengthText');
    var reqMin     = document.getElementById('req-min');
    var reqAlpha   = document.getElementById('req-alpha');
    var reqSpecial = document.getElementById('req-special');
    if (!fill) return;

    var hasMin     = val.length >= 8;
    var hasAlpha   = /[a-zA-Z]/.test(val) && /[0-9]/.test(val);
    var hasSpecial = /[^a-zA-Z0-9]/.test(val);
    var score      = (hasMin ? 1 : 0) + (hasAlpha ? 1 : 0) + (hasSpecial ? 1 : 0);

    toggleReq(reqMin,     hasMin);
    toggleReq(reqAlpha,   hasAlpha);
    toggleReq(reqSpecial, hasSpecial);

    var widths     = ['0%',     '35%',     '70%',      '100%'];
    var colors     = ['#e5e7eb','#ef4444', '#f59e0b',  '#10b981'];
    var texts      = ['',       'Weak',    'Moderate', 'Strong Authorization'];
    var textColors = ['',       '#ef4444', '#f59e0b',  '#10b981'];

    fill.style.width      = widths[score];
    fill.style.background = colors[score];
    label.textContent     = texts[score];
    label.style.color     = textColors[score];
  }

  function toggleReq(el, met) {
    if (!el) return;
    el.classList.toggle('met',   met);
    el.classList.toggle('unmet', !met);
    el.querySelector('.req-icon').innerHTML = met ? checkSVG() : circleSVG();
  }

  function checkSVG() {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>';
  }
  function circleSVG() {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/></svg>';
  }

  /* ══════════════════════════════════════════════════════════════
     4. PROFILE PHOTO PREVIEW
  ══════════════════════════════════════════════════════════════ */
  var photoInput = document.getElementById('photo_upload');
  if (photoInput) {
    photoInput.addEventListener('change', function () {
      var file = this.files[0];
      if (!file || !file.type.startsWith('image/')) return;
      readFileAsImage(file, function (src) {
        var circle = document.getElementById('photoCircle');
        if (!circle) return;
        var existing = circle.querySelector('img');
        if (existing) existing.remove();
        var img = document.createElement('img');
        img.src = src;
        img.alt = 'Profile photo preview';
        circle.appendChild(img);
        circle.classList.add('has-image');
      });
    });
  }

  /* ══════════════════════════════════════════════════════════════
     5. VALID ID UPLOAD — Drag & Drop + Preview + Remove
  ══════════════════════════════════════════════════════════════ */
  var idInput       = document.getElementById('valid_id_upload');
  var idZone        = document.getElementById('idUploadZone');
  var idPlaceholder = document.getElementById('idPlaceholder');
  var idPreviewWrap = document.getElementById('idPreviewWrap');
  var idPreviewImg  = document.getElementById('idPreviewImg');
  var idRemoveBtn   = document.getElementById('idRemoveBtn');

  if (idInput && idZone) {

    /* Click on zone triggers file input (zone is a <label>, so this is automatic) */

    /* File input change */
    idInput.addEventListener('change', function () {
      if (this.files && this.files[0]) {
        handleIdFile(this.files[0]);
      }
    });

    /* Drag & Drop */
    ['dragenter', 'dragover'].forEach(function (evt) {
      idZone.addEventListener(evt, function (e) {
        e.preventDefault(); e.stopPropagation();
        idZone.classList.add('drag-over');
      });
    });
    ['dragleave', 'dragend', 'drop'].forEach(function (evt) {
      idZone.addEventListener(evt, function (e) {
        e.preventDefault(); e.stopPropagation();
        idZone.classList.remove('drag-over');
      });
    });
    idZone.addEventListener('drop', function (e) {
      var file = e.dataTransfer.files[0];
      if (file) handleIdFile(file);
    });

    /* Remove button */
    if (idRemoveBtn) {
      idRemoveBtn.addEventListener('click', function (e) {
        e.preventDefault(); e.stopPropagation();
        clearIdPreview();
      });
    }
  }

  function handleIdFile(file) {
    var allowed = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
    if (!allowed.includes(file.type)) {
      showGlobalError('Please upload a JPG, PNG, WebP, or PDF file for your ID.');
      return;
    }
    if (file.size > 10 * 1024 * 1024) {
      showGlobalError('File is too large. Maximum size is 10MB.');
      return;
    }

    if (file.type === 'application/pdf') {
      /* Show a PDF icon placeholder instead of an image */
      showIdPreviewPdf(file.name);
    } else {
      readFileAsImage(file, function (src) {
        idPreviewImg.src = src;
        showIdPreview();
      });
    }
  }

  function showIdPreview() {
    if (idPlaceholder) idPlaceholder.style.display = 'none';
    if (idPreviewWrap) idPreviewWrap.style.display  = 'block';
  }

  function showIdPreviewPdf(filename) {
    /* Replace the img with a styled PDF label */
    if (idPreviewImg) idPreviewImg.style.display = 'none';
    var pdfLabel = idPreviewWrap.querySelector('.pdf-label');
    if (!pdfLabel) {
      pdfLabel = document.createElement('div');
      pdfLabel.className = 'pdf-label';
      pdfLabel.innerHTML =
        '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">' +
          '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>' +
          '<polyline points="14 2 14 8 20 8"/>' +
          '<line x1="16" y1="13" x2="8" y2="13"/>' +
          '<line x1="16" y1="17" x2="8" y2="17"/>' +
          '<polyline points="10 9 9 9 8 9"/>' +
        '</svg>' +
        '<span>' + filename + '</span>';
      idPreviewWrap.insertBefore(pdfLabel, idPreviewWrap.firstChild);
    } else {
      pdfLabel.querySelector('span').textContent = filename;
      pdfLabel.style.display = 'flex';
    }
    showIdPreview();
  }

  function clearIdPreview() {
    if (idInput) idInput.value = '';
    if (idPreviewImg) { idPreviewImg.src = ''; idPreviewImg.style.display = ''; }
    var pdfLabel = idPreviewWrap && idPreviewWrap.querySelector('.pdf-label');
    if (pdfLabel) pdfLabel.style.display = 'none';
    if (idPreviewWrap) idPreviewWrap.style.display  = 'none';
    if (idPlaceholder) idPlaceholder.style.display  = '';
    idZone && idZone.classList.remove('drag-over');
  }

  /* ══════════════════════════════════════════════════════════════
     6. AUTO-CALCULATE AGE FROM DATE OF BIRTH
  ══════════════════════════════════════════════════════════════ */
  var dobInput = document.getElementById('date_of_birth');
  var ageInput = document.getElementById('age_display');
  if (dobInput && ageInput) {
    dobInput.addEventListener('change', function () {
      var dob   = new Date(this.value);
      var today = new Date();
      if (isNaN(dob.getTime())) { ageInput.value = ''; return; }
      var age = today.getFullYear() - dob.getFullYear();
      var m   = today.getMonth() - dob.getMonth();
      if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
      ageInput.value = (age > 0 && age < 130) ? age : '';
    });
  }

  /* ══════════════════════════════════════════════════════════════
     7. INLINE FIELD VALIDATION HELPERS
  ══════════════════════════════════════════════════════════════ */
  window.showError = function (fieldId, message) {
    var field = document.getElementById(fieldId);
    if (!field) return;
    window.clearError(fieldId);
    var err       = document.createElement('div');
    err.className = 'field-error';
    err.id        = fieldId + '_err';
    err.innerHTML =
      '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">' +
        '<circle cx="12" cy="12" r="10"/>' +
        '<line x1="12" y1="8" x2="12" y2="12"/>' +
        '<line x1="12" y1="16" x2="12.01" y2="16"/>' +
      '</svg>' + message;
    var parent = field.closest('.form-group') || field.closest('.otp-group') || field.parentNode;
    parent.appendChild(err);
    field.style.borderColor = 'var(--danger)';
  };

  window.clearError = function (fieldId) {
    var existing = document.getElementById(fieldId + '_err');
    if (existing) existing.remove();
    var field = document.getElementById(fieldId);
    if (field) field.style.borderColor = '';
  };

  /* ══════════════════════════════════════════════════════════════
     8. ALERT DISMISS
  ══════════════════════════════════════════════════════════════ */
  document.querySelectorAll('.alert-dismiss').forEach(function (btn) {
    btn.addEventListener('click', function () {
      this.closest('.alert').remove();
    });
  });

  /* ══════════════════════════════════════════════════════════════
     UTILITY: read file as base64 image
  ══════════════════════════════════════════════════════════════ */
  function readFileAsImage(file, cb) {
    var reader = new FileReader();
    reader.onload = function (e) { cb(e.target.result); };
    reader.readAsDataURL(file);
  }

  function showGlobalError(msg) {
    /* Shows a temporary alert at the top of .reg-card */
    var card = document.querySelector('.reg-card');
    if (!card) { alert(msg); return; }
    var existing = card.querySelector('.temp-alert');
    if (existing) existing.remove();
    var el = document.createElement('div');
    el.className = 'alert alert-danger temp-alert';
    el.innerHTML =
      '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
        '<circle cx="12" cy="12" r="10"/>' +
        '<line x1="12" y1="8" x2="12" y2="12"/>' +
        '<line x1="12" y1="16" x2="12.01" y2="16"/>' +
      '</svg>' + msg +
      '<button class="alert-dismiss" type="button">&#x2715;</button>';
    card.insertBefore(el, card.firstChild);
    el.querySelector('.alert-dismiss').addEventListener('click', function () { el.remove(); });
    setTimeout(function () { if (el.parentNode) el.remove(); }, 6000);
  }

  /* ══════════════════════════════════════════════════════════════
     9 & 10. LEAFLET MAP — Bocaue bounds, GPS, pin, auto-fill address
  ══════════════════════════════════════════════════════════════ */
  var mapEl = document.getElementById('bocaueMap');
  if (!mapEl || typeof L === 'undefined') return; /* Map not on this page or Leaflet not loaded */

  var BOCAUE_CENTER = [14.7986, 120.9067];
  var DEFAULT_ZOOM  = 14;
  var MIN_ZOOM      = 13;
  var MAX_ZOOM      = 18;

  var BOCAUE_BOUNDS = L.latLngBounds(
    L.latLng(14.747, 120.865),
    L.latLng(14.845, 120.990)
  );
  var BOCAUE_POLYGON = [
    [14.844, 120.888], [14.839, 120.924], [14.831, 120.963], [14.816, 120.986],
    [14.787, 120.988], [14.764, 120.975], [14.751, 120.948], [14.748, 120.910],
    [14.757, 120.882], [14.779, 120.867], [14.809, 120.868],
  ];

  var map = L.map('bocaueMap', {
    center: BOCAUE_CENTER, zoom: DEFAULT_ZOOM,
    minZoom: MIN_ZOOM, maxZoom: MAX_ZOOM,
    maxBounds: BOCAUE_BOUNDS, maxBoundsViscosity: 1.0,
    zoomControl: true, scrollWheelZoom: true,
  });

  /* Force Leaflet to recalculate map size after CSS has painted */
  setTimeout(function () { map.invalidateSize(); }, 400);
  if (typeof ResizeObserver !== 'undefined') {
    new ResizeObserver(function () { map.invalidateSize(); }).observe(mapEl);
  }

  /* Tile layer */
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors',
    maxZoom: MAX_ZOOM,
  }).addTo(map);

  (function addBoundaryMask() {
    var worldRing = [[-90, -180], [-90, 180], [90, 180], [90, -180]];
    L.polygon([worldRing, BOCAUE_POLYGON], {
      stroke: false,
      fillColor: '#0b1f3b',
      fillOpacity: 0.35,
      interactive: false,
    }).addTo(map);
    L.polygon(BOCAUE_POLYGON, {
      color: '#2563eb',
      weight: 2,
      fillOpacity: 0.02,
      dashArray: '5,5',
      interactive: false,
    }).addTo(map);
  })();

  /* Custom pin icon */
  var sentinelIcon = L.divIcon({
    className: '',
    html: '<div class="sentinel-map-pin"><div class="pin-dot"></div></div>',
    iconSize: [30, 42], iconAnchor: [15, 42], popupAnchor: [0, -46],
  });

  var userMarker = null;

  /* DOM refs */
  var latInput   = document.getElementById('lat');
  var lngInput   = document.getElementById('lng');
  var addrInput  = document.getElementById('address');
  var pinStrip   = document.getElementById('pinInfoStrip');
  var pinAddrEl  = document.getElementById('pinAddress');
  var pinCoordsEl= document.getElementById('pinCoords');
  var pinFillBtn = document.getElementById('pinFillBtn');
  var locStatus  = document.getElementById('locationStatus');
  var locBtn     = document.getElementById('useLocationBtn');
  var locBtnText = document.getElementById('locBtnText');
  var autofillBadge = document.getElementById('addressAutofillBadge');

  /* SVG icons for status bar */
  var ICON_CHECK = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>';
  var ICON_WARN  = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';

  /* ── Helpers ── */
  function setCoords(lat, lng) {
    if (latInput) latInput.value = lat.toFixed(7);
    if (lngInput) lngInput.value = lng.toFixed(7);
  }

  function showStatus(html, type) {
    if (!locStatus) return;
    locStatus.style.display = 'flex';
    locStatus.className = 'location-status location-status--' + type;
    locStatus.innerHTML = html;
  }

  function hideStatus() {
    if (locStatus) locStatus.style.display = 'none';
  }

  function popupHTML(title, lat, lng) {
    return '<div class="map-popup"><strong>' + title + '</strong>' +
           '<span>' + lat.toFixed(6) + ', ' + lng.toFixed(6) + '</span></div>';
  }

  function pointInsidePolygon(lat, lng) {
    var x = lng;
    var y = lat;
    var inside = false;
    for (var i = 0, j = BOCAUE_POLYGON.length - 1; i < BOCAUE_POLYGON.length; j = i++) {
      var yi = BOCAUE_POLYGON[i][0];
      var xi = BOCAUE_POLYGON[i][1];
      var yj = BOCAUE_POLYGON[j][0];
      var xj = BOCAUE_POLYGON[j][1];
      var intersects = yi > y !== yj > y && x < ((xj - xi) * (y - yi)) / (yj - yi + Number.EPSILON) + xi;
      if (intersects) inside = !inside;
    }
    return inside;
  }

  /**
   * updatePinStrip — updates the strip below the map.
   * Does NOT auto-fill the address field; user must click "Use this address".
   */
  function updatePinStrip(label, lat, lng) {
    if (!pinStrip) return;
    pinStrip.style.display = 'flex';
    if (pinAddrEl)   pinAddrEl.textContent  = label;
    if (pinCoordsEl) pinCoordsEl.textContent = lat.toFixed(6) + ', ' + lng.toFixed(6);
    /* Store on the button so the fill handler can read it */
    if (pinFillBtn) pinFillBtn.dataset.address = label;
  }

  /**
   * autoFillAddress — fills the address textarea and shows the badge.
   * Called automatically on GPS; also called on manual "Use this address" click.
   */
  function autoFillAddress(address) {
    if (!addrInput) return;
    addrInput.value = address;
    /* Animate a highlight to signal auto-fill */
    addrInput.style.transition = 'border-color 0.3s ease, box-shadow 0.3s ease';
    addrInput.style.borderColor = 'var(--success)';
    addrInput.style.boxShadow  = '0 0 0 3px rgba(16,185,129,0.15)';
    setTimeout(function () {
      addrInput.style.borderColor = '';
      addrInput.style.boxShadow   = '';
    }, 1800);
    /* Show autofill badge on label */
    if (autofillBadge) autofillBadge.style.display = 'inline-flex';
  }

  /* "Use this address" button click */
  if (pinFillBtn) {
    pinFillBtn.addEventListener('click', function () {
      var addr = this.dataset.address;
      if (addr && addr !== 'Locating…') {
        autoFillAddress(addr);
      }
    });
  }

  function placeMarker(lat, lng, title) {
    if (userMarker) map.removeLayer(userMarker);
    userMarker = L.marker([lat, lng], { icon: sentinelIcon, draggable: true }).addTo(map);
    userMarker.bindPopup(popupHTML(title, lat, lng), { offset: [0, -10] }).openPopup();

    var snapLat = lat, snapLng = lng;

    userMarker.on('dragend', function (e) {
      var pos = e.target.getLatLng();
      if (!pointInsidePolygon(pos.lat, pos.lng)) {
        userMarker.setLatLng([snapLat, snapLng]);
        showStatus(ICON_WARN + '&nbsp;You are outside Bocaue, Bulacan coverage area.', 'warning');
        return;
      }
      snapLat = pos.lat; snapLng = pos.lng;
      setCoords(pos.lat, pos.lng);
      updatePinStrip('Locating address…', pos.lat, pos.lng);
      userMarker.setPopupContent(popupHTML('Dragged Location', pos.lat, pos.lng));
      reverseGeocode(pos.lat, pos.lng, function (addr) {
        updatePinStrip(addr, pos.lat, pos.lng);
        showStatus(ICON_CHECK + '&nbsp;Location updated. Click <strong>Use this address</strong> to apply it.', 'success');
      });
    });

    setCoords(lat, lng);
    updatePinStrip(title, lat, lng);
  }

  /**
   * reverseGeocode — Nominatim reverse lookup.
   * Returns a human-readable address via cb(addr).
   */
  function reverseGeocode(lat, lng, cb) {
    fetch(
      'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + lat + '&lon=' + lng + '&zoom=18&addressdetails=1',
      { headers: { 'Accept-Language': 'en' } }
    )
    .then(function (r) { return r.json(); })
    .then(function (data) {
      var a = data.address || {};
      var parts = [
        a.road || a.pedestrian || a.footway || a.path,
        a.suburb || a.village || a.quarter || a.neighbourhood,
        a.city || a.town || a.municipality || a.county,
        'Bulacan', 'Philippines',
      ].filter(Boolean);
      var display = parts.join(', ') || data.display_name || ('Lat ' + lat.toFixed(5) + ', Lng ' + lng.toFixed(5));
      if (cb) cb(display);
    })
    .catch(function () {
      var fallback = 'Lat ' + lat.toFixed(5) + ', Lng ' + lng.toFixed(5);
      if (cb) cb(fallback);
    });
  }

  /* ── Click map to pin ── */
  map.on('click', function (e) {
    if (!pointInsidePolygon(e.latlng.lat, e.latlng.lng)) {
      showStatus(ICON_WARN + '&nbsp;You are outside Bocaue, Bulacan coverage area.', 'warning');
      return;
    }
    placeMarker(e.latlng.lat, e.latlng.lng, 'Locating address…');
    showStatus(ICON_CHECK + '&nbsp;Pin placed. Fetching address…', 'success');

    reverseGeocode(e.latlng.lat, e.latlng.lng, function (addr) {
      updatePinStrip(addr, e.latlng.lat, e.latlng.lng);
      userMarker && userMarker.setPopupContent(popupHTML(addr, e.latlng.lat, e.latlng.lng));
      showStatus(ICON_CHECK + '&nbsp;Location pinned. Click <strong>Use this address</strong> or drag to adjust.', 'success');
    });
  });

  /* ── Use Current Location (GPS) ── */
  if (locBtn) {
    locBtn.addEventListener('click', function () {
      if (!navigator.geolocation) {
        showStatus(ICON_WARN + '&nbsp;Geolocation is not supported by your browser.', 'error');
        return;
      }
      locBtn.disabled = true;
      locBtnText && (locBtnText.textContent = 'Getting location\u2026');
      showStatus('<span class="loc-spinner"></span>&nbsp;Accessing your GPS location\u2026', 'loading');

      navigator.geolocation.getCurrentPosition(
        function (position) {
          var lat = position.coords.latitude;
          var lng = position.coords.longitude;
          locBtn.disabled = false;
          locBtnText && (locBtnText.textContent = 'Use My GPS Location');

          if (pointInsidePolygon(lat, lng)) {
            map.setView([lat, lng], 17);
            placeMarker(lat, lng, 'Locating address…');
            showStatus('<span class="loc-spinner"></span>&nbsp;GPS found — fetching your address…', 'loading');

            reverseGeocode(lat, lng, function (addr) {
              updatePinStrip(addr, lat, lng);
              userMarker && userMarker.setPopupContent(popupHTML(addr, lat, lng));
              /* GPS auto-fills the address field automatically */
              autoFillAddress(addr);
              showStatus(ICON_CHECK + '&nbsp;<strong>Location pinned &amp; address filled.</strong> Drag the marker to adjust.', 'success');
            });

          } else {
            map.setView(BOCAUE_CENTER, DEFAULT_ZOOM);
            showStatus(
              ICON_WARN + '&nbsp;You are outside Bocaue, Bulacan coverage area.',
              'warning'
            );
          }
        },
        function (err) {
          locBtn.disabled = false;
          locBtnText && (locBtnText.textContent = 'Use My GPS Location');
          var messages = {
            1: 'Location access denied. Please allow it in your browser settings.',
            2: 'Could not determine your position. Check your GPS signal.',
            3: 'Location request timed out. Please try again.',
          };
          showStatus(ICON_WARN + '&nbsp;' + (messages[err.code] || 'An unknown error occurred.'), 'error');
        },
        { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
      );
    });
  }

  /* ── Restore saved coords on validation re-render ── */
  var savedLat = latInput  ? parseFloat(latInput.value)  : NaN;
  var savedLng = lngInput  ? parseFloat(lngInput.value)  : NaN;
  if (!isNaN(savedLat) && !isNaN(savedLng)) {
    map.setView([savedLat, savedLng], 16);
    placeMarker(savedLat, savedLng, 'Previously selected');
    reverseGeocode(savedLat, savedLng, function (addr) {
      updatePinStrip(addr, savedLat, savedLng);
    });
  }

  /* ── PDF label style (injected dynamically) ── */
  var pdfStyle = document.createElement('style');
  pdfStyle.textContent =
    '.pdf-label {' +
      'display:flex; flex-direction:column; align-items:center; justify-content:center;' +
      'height:100%; gap:0.5rem; color:var(--text-mid); padding:1rem;' +
    '}' +
    '.pdf-label svg { color:var(--danger); }' +
    '.pdf-label span { font-size:0.78rem; font-weight:600; text-align:center; word-break:break-all; }';
  document.head.appendChild(pdfStyle);

}); /* end DOMContentLoaded */