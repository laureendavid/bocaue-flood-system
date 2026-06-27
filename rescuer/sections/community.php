<section id="page-community" class="page active" aria-labelledby="community-heading">

  <style>
    .community-grid {
      display: grid;
      grid-template-columns: 320px 1fr;
      gap: 20px;
      padding: 20px;
    }

    .community-column {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .community-filter-stack {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .comm-filter-bar {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
      padding: 10px 14px;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
    }

    .comm-filter-label {
      font-size: 0.7rem;
      font-weight: 600;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      white-space: nowrap;
      margin-right: 4px;
      display: flex;
      align-items: center;
      gap: 4px;
      flex-shrink: 0;
    }

    .comm-filter-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 13px;
      border-radius: 8px;
      border: 1.5px solid #e2e8f0;
      background: #fff;
      color: #475569;
      font-size: 0.76rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.15s ease;
      white-space: nowrap;
    }

    .comm-filter-btn:hover:not(.active) {
      background: #f1f5f9;
      border-color: #94a3b8;
    }

    .comm-filter-btn.active {
      color: #fff;
    }

    .comm-filter-sep {
      color: #cbd5e1;
      font-size: 1rem;
      margin: 0 2px;
      flex-shrink: 0;
    }

    .comm-date-wrap {
      display: flex;
      align-items: center;
      gap: 6px;
      flex-wrap: wrap;
    }

    .comm-date-wrap label {
      font-size: 0.72rem;
      color: #94a3b8;
      white-space: nowrap;
    }

    .comm-date-wrap input[type="date"] {
      padding: 5px 9px;
      border-radius: 8px;
      font-size: 0.76rem;
      border: 1.5px solid #e2e8f0;
      background: #fff;
      color: #1e293b;
      cursor: pointer;
      font-family: inherit;
    }

    .comm-date-wrap input[type="date"]:focus {
      outline: none;
      border-color: #3b82f6;
    }

    .comm-apply-btn {
      padding: 5px 13px;
      border-radius: 8px;
      font-size: 0.76rem;
      font-weight: 600;
      border: 1.5px solid #2563eb;
      background: #3b82f6;
      color: #fff;
      cursor: pointer;
      transition: background 0.15s;
    }

    .comm-apply-btn:hover {
      background: #2563eb;
    }

    .comm-clear-btn {
      padding: 5px 10px;
      border-radius: 8px;
      font-size: 0.72rem;
      font-weight: 600;
      border: 1.5px solid #e2e8f0;
      background: transparent;
      color: #64748b;
      cursor: pointer;
      transition: background 0.15s;
    }

    .comm-clear-btn:hover {
      background: #f1f5f9;
    }

    .status-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      flex-shrink: 0;
    }

    .comm-active-info {
      display: none;
      align-items: center;
      gap: 6px;
      font-size: 0.72rem;
      color: #1e40af;
      padding: 5px 12px;
      background: #eff6ff;
      border: 1px solid #bfdbfe;
      border-radius: 8px;
    }

    .comm-active-info.show {
      display: inline-flex;
    }

    #comm-no-results {
      display: none;
      text-align: center;
      padding: 32px 16px;
      color: #94a3b8;
      font-size: 0.9rem;
    }

    .comm-barangay-select {
      padding: 6px 10px;
      border-radius: 8px;
      border: 1.5px solid #e2e8f0;
      background: #fff;
      color: #1e293b;
      font-size: 0.76rem;
      font-weight: 500;
      font-family: inherit;
      cursor: pointer;
      min-width: 180px;
      max-width: 260px;
      outline: none;
      transition: border-color 0.15s;
    }

    .comm-barangay-select:focus {
      border-color: #3b82f6;
    }

    .comm-barangay-select.active {
      border-color: #2563eb;
      background: #eff6ff;
      color: #1e40af;
      font-weight: 600;
    }

    /* POST CARD */
    .post-card {
      background: #fff;
      border-radius: 12px;
      padding: 15px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
      border: 1px solid #e8edf5;
      transition: box-shadow 0.2s;
    }

    .post-card:hover {
      box-shadow: 0 4px 18px rgba(0, 0, 0, 0.12);
    }

    .post-card__header {
      display: flex;
      align-items: center;
      margin-bottom: 10px;
    }

    .post-card__user {
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .post-card__avatar {
      width: 45px;
      height: 45px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #dbeafe;
    }

    .post-card__avatar--initials {
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 16px;
      font-weight: 700;
      color: #fff;
      flex-shrink: 0;
    }

    .post-card__user-info {
      display: flex;
      flex-direction: column;
      gap: 2px;
    }

    .post-card__name {
      font-weight: 600;
      font-size: 14px;
      color: #0f1f40;
    }

    .post-card__meta {
      font-size: 12px;
      color: #777;
    }

    .post-card__body {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .post-card__body--with-image {
      flex-direction: row;
      align-items: flex-start;
      gap: 14px;
    }

    .post-card__image-wrap {
      flex-shrink: 0;
      width: 160px;
      height: 110px;
      border-radius: 8px;
      overflow: hidden;
      background: #f3f4f6;
    }

    .post-card__image {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .post-card__content {
      flex: 1;
      min-width: 0;
    }

    .post-card__description {
      font-size: 0.875rem;
      color: #374151;
      line-height: 1.55;
      margin: 0 0 10px 0;
    }

    .post-card__rescue-info {
      background: #fff8f0;
      border: 1px solid #fde68a;
      border-left: 4px solid #f59e0b;
      border-radius: 8px;
      padding: 9px 12px;
      margin-bottom: 10px;
      font-size: 13px;
      color: #78350f;
    }

    .rescue-info-item {
      font-weight: 600;
      display: block;
      margin-bottom: 4px;
    }

    .rescue-info-desc {
      margin: 0;
      font-size: 12px;
      color: #92400e;
      line-height: 1.5;
    }

    .post-card__tags {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
      margin-top: 4px;
    }

    .post-tag {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      font-size: 0.78rem;
      font-weight: 500;
      padding: 3px 9px;
      border-radius: 20px;
      border: 1px solid transparent;
    }

    .post-tag--water {
      background: #eff6ff;
      color: #1d4ed8;
      border-color: #bfdbfe;
    }

    .severity--passable {
      background: #f0fdf4;
      color: #15803d;
      border-color: #bbf7d0;
    }

    .severity--limited {
      background: #fffbeb;
      color: #b45309;
      border-color: #fde68a;
    }

    .severity--impassable {
      background: #fef2f2;
      color: #b91c1c;
      border-color: #fecaca;
    }

    .severity--neutral {
      background: #f1f5f9;
      color: #475569;
    }

    .post-card__footer {
      margin-top: 14px;
      padding-top: 12px;
      border-top: 1px solid #f0f4ff;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
    }

    .post-card__map-btns {
      display: flex;
      gap: 8px;
      align-items: center;
      margin-left: auto;
    }

    .rescue-badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 5px 14px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
    }

    .rescue-badge--btn {
      border: none;
      cursor: pointer;
      transition: filter 0.15s, transform 0.12s;
    }

    .rescue-badge--btn:hover {
      filter: brightness(1.1);
      transform: scale(1.03);
    }

    .rescue-badge--btn:active {
      transform: scale(0.97);
    }

    .badge--danger {
      background: #fee2e2;
      color: #991b1b;
    }

    .badge--warning {
      background: #fef3c7;
      color: #92400e;
    }

    .badge--success {
      background: #dcfce7;
      color: #166534;
    }

    .badge--neutral {
      background: #f1f5f9;
      color: #475569;
    }

    .btn-map {
      background: #3498db;
      color: white;
      border: none;
      padding: 6px 12px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 13px;
      font-weight: 500;
      transition: background 0.18s;
    }

    .btn-map:hover {
      background: #2980b9;
    }

    .btn-gmaps {
      background: #fff;
      color: #1e40af;
      border: 1.5px solid #bfdbfe;
      padding: 5px 11px;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 500;
      text-decoration: none;
      transition: background 0.15s;
      display: inline-flex;
      align-items: center;
    }

    .btn-gmaps:hover {
      background: #eff6ff;
    }

    #feed-loading,
    #feed-end {
      text-align: center;
      padding: 10px;
      color: #777;
      font-size: 13px;
    }

    /* MAP MODAL */
    .map-modal {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.6);
      z-index: 9999;
      align-items: center;
      justify-content: center;
    }

    .map-modal-content {
      background: #fff;
      width: 60%;
      max-width: 700px;
      height: 450px;
      border-radius: 12px;
      padding: 10px;
      position: relative;
      display: flex;
      flex-direction: column;
    }

    #map {
      flex: 1;
      width: 100%;
      border-radius: 10px;
    }

    #close-map {
      position: absolute;
      right: 12px;
      top: 8px;
      font-size: 26px;
      cursor: pointer;
      color: #374151;
    }

    .full-map-modal {
      display: none;
      position: fixed;
      inset: 0;
      background: #fff;
      z-index: 10000;
    }

    .full-map-header {
      display: flex;
      justify-content: space-between;
      padding: 10px 16px;
      background: #0b1f47;
      color: white;
    }

    #full-map {
      width: 100%;
      height: calc(100% - 50px);
    }

    .close-full-map {
      cursor: pointer;
      font-size: 24px;
    }

    .btn-full-map {
      margin-top: 10px;
      background: #0b1f47;
      color: white;
      border: none;
      padding: 8px;
      border-radius: 8px;
      width: 100%;
      cursor: pointer;
      font-size: 13px;
    }

    /* CONFIRM MODAL */
    .confirm-backdrop {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.55);
      z-index: 10100;
      align-items: center;
      justify-content: center;
      padding: 16px;
    }

    .confirm-backdrop.open {
      display: flex;
    }

    .confirm-modal {
      background: #fff;
      border-radius: 16px;
      width: min(420px, 100%);
      box-shadow: 0 20px 50px rgba(15, 23, 42, 0.22);
      overflow: hidden;
      animation: modalIn 0.2s ease;
    }

    /* =============================================
   FILTER STACK — mobile-optimized
============================================= */
    .community-filter-stack {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .comm-filter-bar {
      display: flex;
      align-items: center;
      gap: 6px;
      padding: 7px 10px;
      background: #f8fafc;
      border: 0.5px solid #d1d9e6;
      border-radius: 10px;

      /* scrollable horizontally sa mobile */
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
      scrollbar-width: none;
      /* Firefox */
      white-space: nowrap;
      flex-wrap: nowrap;
      /* override sa nowrap para scroll, hindi wrap */
    }

    .comm-filter-bar::-webkit-scrollbar {
      display: none;
      /* Chrome/Safari */
    }

    .comm-filter-label {
      font-size: 0.65rem;
      font-weight: 600;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.07em;
      white-space: nowrap;
      margin-right: 2px;
      display: flex;
      align-items: center;
      gap: 3px;
      flex-shrink: 0;
    }

    .comm-filter-btn {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 4px 9px;
      border-radius: 6px;
      border: 1px solid #e2e8f0;
      background: #fff;
      color: #475569;
      font-size: 0.7rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.15s ease;
      white-space: nowrap;
      flex-shrink: 0;
    }

    .comm-filter-btn:hover:not(.active) {
      background: #f1f5f9;
      border-color: #94a3b8;
    }

    .comm-filter-btn.active {
      color: #fff;
    }

    .comm-filter-sep {
      color: #cbd5e1;
      font-size: 0.9rem;
      margin: 0 1px;
      flex-shrink: 0;
    }

    .comm-date-toggle {
      display: none;
    }

    .comm-date-wrap {
      display: flex;
      align-items: center;
      gap: 4px;
      flex-shrink: 0;
    }

    .comm-date-wrap label {
      font-size: 0.65rem;
      color: #94a3b8;
      white-space: nowrap;
    }

    .comm-date-wrap input[type="date"] {
      padding: 3px 6px;
      border-radius: 6px;
      font-size: 0.65rem;
      border: 1px solid #e2e8f0;
      background: #fff;
      color: #1e293b;
      cursor: pointer;
      font-family: inherit;
      width: 100px;
    }

    .comm-date-wrap input[type="date"]:focus {
      outline: none;
      border-color: #3b82f6;
    }

    .comm-apply-btn {
      padding: 3px 9px;
      border-radius: 6px;
      font-size: 0.7rem;
      font-weight: 600;
      border: 1px solid #2563eb;
      background: #3b82f6;
      color: #fff;
      cursor: pointer;
      flex-shrink: 0;
    }

    .comm-apply-btn:hover {
      background: #2563eb;
    }

    .comm-clear-btn {
      padding: 3px 8px;
      border-radius: 6px;
      font-size: 0.68rem;
      font-weight: 600;
      border: 1px solid #e2e8f0;
      background: transparent;
      color: #64748b;
      cursor: pointer;
      flex-shrink: 0;
    }

    .comm-clear-btn:hover {
      background: #f1f5f9;
    }

    .status-dot {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      flex-shrink: 0;
    }

    .comm-active-info {
      display: none;
      align-items: center;
      gap: 6px;
      font-size: 0.7rem;
      color: #1e40af;
      padding: 4px 10px;
      background: #eff6ff;
      border: 0.5px solid #bfdbfe;
      border-radius: 8px;
    }

    .comm-active-info.show {
      display: inline-flex;
    }

    .comm-barangay-select {
      padding: 4px 7px;
      border-radius: 6px;
      border: 1px solid #e2e8f0;
      background: #fff;
      color: #1e293b;
      font-size: 0.7rem;
      font-weight: 500;
      font-family: inherit;
      cursor: pointer;
      min-width: 130px;
      flex-shrink: 0;
      outline: none;
    }

    .comm-barangay-select:focus {
      border-color: #3b82f6;
    }

    .comm-barangay-select.active {
      border-color: #2563eb;
      background: #eff6ff;
      color: #1e40af;
      font-weight: 600;
    }

    /* warning note sa barangay bar — wrap allowed dito */
    #comm-barangay-bar span:last-child {
      font-size: 0.62rem;
      color: #94a3b8;
      font-style: italic;
      line-height: 1.4;
      max-width: 180px;
      white-space: normal;
      /* this one lang pinapayagan mag-wrap */
      flex-shrink: 0;
    }

    #comm-report-count {
      margin-left: auto;
      font-size: 0.68rem;
      color: #94a3b8;
      font-weight: 500;
      white-space: nowrap;
      flex-shrink: 0;
    }

    #comm-no-results {
      display: none;
      text-align: center;
      padding: 24px 16px;
      color: #94a3b8;
      font-size: 0.85rem;
    }

    @keyframes modalIn {
      from {
        opacity: 0;
        transform: translateY(12px) scale(0.97);
      }

      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    .confirm-modal__header {
      padding: 18px 22px 16px;
      display: flex;
      align-items: flex-start;
      gap: 14px;
    }

    .confirm-modal__header.type--start {
      background: #fff1f2;
      border-bottom: 1px solid #fecdd3;
    }

    .confirm-modal__header.type--finish {
      background: #f0fdf4;
      border-bottom: 1px solid #bbf7d0;
    }

    .confirm-modal__icon {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      flex-shrink: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
    }

    .type--start .confirm-modal__icon {
      background: #fee2e2;
    }

    .type--finish .confirm-modal__icon {
      background: #dcfce7;
    }

    .confirm-modal__header-text h3 {
      margin: 0 0 3px;
      font-size: 1rem;
      font-weight: 700;
    }

    .type--start .confirm-modal__header-text h3 {
      color: #991b1b;
    }

    .type--finish .confirm-modal__header-text h3 {
      color: #166534;
    }

    .confirm-modal__header-text p {
      margin: 0;
      font-size: 0.78rem;
      color: #64748b;
    }

    .confirm-modal__body {
      padding: 18px 22px;
    }

    .confirm-modal__body p {
      font-size: 14px;
      color: #1e293b;
      line-height: 1.6;
      margin: 0;
    }

    .confirm-modal__body strong {
      color: #0f1f40;
    }

    .confirm-modal__footer {
      padding: 12px 22px 18px;
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      border-top: 1px solid #f0f4ff;
    }

    .btn-modal-cancel {
      padding: 9px 20px;
      border-radius: 9px;
      border: 1.5px solid #e2e8f0;
      background: #fff;
      color: #475569;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
    }

    .btn-modal-cancel:hover {
      background: #f8fafc;
    }

    .btn-modal-confirm {
      padding: 9px 22px;
      border-radius: 9px;
      border: none;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      min-width: 140px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      transition: filter 0.15s, transform 0.12s;
      color: #fff;
    }

    .btn-modal-confirm.type--start {
      background: #dc2626;
    }

    .btn-modal-confirm.type--finish {
      background: #16a34a;
    }

    .btn-modal-confirm:hover {
      filter: brightness(1.08);
    }

    .btn-modal-confirm:active {
      transform: scale(0.97);
    }

    .btn-modal-confirm:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      filter: none;
    }

    /* TOAST */
    .rescue-toast {
      position: fixed;
      bottom: 24px;
      right: 24px;
      background: #0f1f40;
      color: #fff;
      padding: 12px 20px;
      border-radius: 10px;
      font-size: 13px;
      font-weight: 500;
      z-index: 10200;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
      display: flex;
      align-items: center;
      gap: 8px;
      opacity: 0;
      transform: translateY(10px);
      transition: opacity 0.25s, transform 0.25s;
      pointer-events: none;
    }

    .rescue-toast.show {
      opacity: 1;
      transform: translateY(0);
    }

    .rescue-toast.toast--success {
      border-left: 4px solid #22c55e;
    }

    .rescue-toast.toast--error {
      border-left: 4px solid #ef4444;
    }

    @media (max-width: 768px) {
      .community-grid {
        grid-template-columns: 1fr;
      }

      .map-modal-content {
        width: 90%;
        height: 400px;
      }

      /* FILTER BARS — wrap na lang, hindi scroll */
      .comm-filter-bar {
        overflow-x: visible;
        flex-wrap: wrap;
        white-space: normal;
        gap: 5px;
        padding: 8px 10px;
      }

      /* Label — full row sarili niya */
      .comm-filter-label {
        width: 100%;
        margin-bottom: 2px;
      }

      /* Buttons — mas maliit para maraming kasya */
      .comm-filter-btn {
        padding: 4px 8px;
        font-size: 0.68rem;
      }

      /* Date inputs — hidden by default, toggle */
      .comm-filter-sep {
        display: none;
      }

      .comm-date-wrap {
        display: none;
        width: 100%;
        flex-wrap: wrap;
        gap: 4px;
        margin-top: 4px;
        padding-top: 6px;
        border-top: 1px dashed #e2e8f0;
      }

      .comm-date-wrap.open {
        display: flex;
      }

      .comm-date-wrap input[type="date"] {
        flex: 1;
        min-width: 120px;
        width: auto;
        font-size: 0.68rem;
        box-sizing: border-box;
      }

      .comm-date-toggle {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 8px;
        border-radius: 6px;
        border: 1px dashed #cbd5e1;
        background: #fff;
        color: #64748b;
        font-size: 0.68rem;
        font-weight: 600;
        cursor: pointer;
      }

      /* Barangay select — full width */
      .comm-barangay-select {
        min-width: 0;
        width: 100%;
        max-width: 100%;
      }

      /* Warning note — full width din */
      #comm-barangay-bar span:last-child {
        width: 100%;
        max-width: 100%;
      }

      /* Post card fixes */
      .post-card__body--with-image {
        flex-direction: column;
      }

      .post-card__image-wrap {
        width: 100%;
        height: 200px;
      }

      .post-card__map-btns {
        flex-direction: column;
        width: 100%;
        margin-left: 0;
      }

      .btn-map,
      .btn-gmaps {
        width: 100%;
        justify-content: center;
        text-align: center;
      }
    }
  </style>

  <header class="page-header">
    <h2 id="community-heading">Community</h2>
  </header>

  <div class="community-grid">

    <!-- LEFT: Announcements -->
    <aside class="announcements-sidebar" style="max-height:75vh; overflow-y:auto;">
      <h3 class="community-section-title">Announcements</h3>
      <?php include '../includes/fetch_commAnnouncement.php'; ?>
    </aside>

    <!-- RIGHT: Feed -->
    <div class="community-column">
      <h3 class="community-section-title">Community Posts</h3>

      <!-- ── FILTER STACK ── -->
      <div class="community-filter-stack">
        <!-- Search bar -->
        <div class="comm-filter-bar" id="comm-search-bar">
          <span class="comm-filter-label">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8" />
              <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            Search
          </span>
          <div style="display:flex; align-items:center; gap:6px; flex:1; min-width:0;">
            <input type="text" id="comm-search-input" placeholder="Search by resident name…" style="
                flex:1; min-width:0; padding:4px 10px;
                border-radius:6px; border:1px solid #e2e8f0;
                background:#fff; color:#1e293b;
                font-size:0.76rem; font-family:inherit;
                outline:none; transition:border-color 0.15s;
            " />
            <button id="comm-search-clear" style="
                display:none; padding:3px 8px;
                border-radius:6px; font-size:0.72rem; font-weight:600;
                border:1px solid #e2e8f0; background:transparent;
                color:#64748b; cursor:pointer;
            ">✕ Clear</button>
          </div>
          <span id="comm-search-active-pill" style="
        display:none; font-size:0.7rem; color:#1e40af;
        background:#eff6ff; border:0.5px solid #bfdbfe;
        border-radius:6px; padding:3px 9px; white-space:nowrap;
    "></span>
        </div>
        <!-- Date filter bar -->
        <div class="comm-filter-bar" id="comm-date-bar">
          <span class="comm-filter-label">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
              <line x1="16" y1="2" x2="16" y2="6" />
              <line x1="8" y1="2" x2="8" y2="6" />
              <line x1="3" y1="10" x2="21" y2="10" />
            </svg>
            Date
          </span>
          <button class="comm-filter-btn active" data-date-preset="all"
            style="background:#3b82f6;border-color:#2563eb;color:#fff;">All time</button>
          <button class="comm-filter-btn" data-date-preset="today">Today</button>
          <button class="comm-filter-btn" data-date-preset="7">Last 7 days</button>
          <button class="comm-filter-btn" data-date-preset="30">Last 30 days</button>
          <span class="comm-filter-sep">|</span>
          <button class="comm-date-toggle" id="comm-date-toggle-btn">📅 Custom range</button>
          <div class="comm-date-wrap" id="comm-date-wrap-el">
            <label for="comm-date-from">From</label>
            <input type="date" id="comm-date-from" />
            <label for="comm-date-to">to</label>
            <input type="date" id="comm-date-to" />
            <button class="comm-apply-btn" id="comm-date-apply">Apply</button>
            <button class="comm-clear-btn" id="comm-date-clear" style="display:none;">Clear</button>
          </div>
        </div>

        <!-- Active date pill -->
        <div class="comm-active-info" id="comm-date-info"></div>

        <!-- Status filter bar -->
        <div class="comm-filter-bar" id="comm-status-bar">
          <span class="comm-filter-label">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10" />
              <line x1="12" y1="8" x2="12" y2="12" />
              <line x1="12" y1="16" x2="12.01" y2="16" />
            </svg>
            Status
          </span>
          <button class="comm-filter-btn active" data-status="all"
            style="background:#3b82f6;border-color:#2563eb;color:#fff;">
            <span style="font-size:14px;line-height:1;">⊞</span> All
          </button>
          <button class="comm-filter-btn" data-status="Rescue Needed">
            <span class="status-dot" style="background:#ef4444;"></span> Rescue Needed
          </button>
          <button class="comm-filter-btn" data-status="Being Rescued">
            <span class="status-dot" style="background:#eab308;"></span> Being Rescued
          </button>
          <button class="comm-filter-btn" data-status="Rescued">
            <span class="status-dot" style="background:#22c55e;"></span> Rescued
          </button>
          <button class="comm-filter-btn" data-status="Not Required">
            <span class="status-dot" style="background:#94a3b8;"></span> Not Required
          </button>
          <span class="comm-filter-sep">|</span>
          <button class="comm-filter-btn" data-status="my-inprogress" id="comm-my-inprogress-btn"
            style="border-color:#fde68a;color:#b45309;">
            <span class="status-dot" style="background:#eab308;"></span>
            Your Rescue In Progress
          </button>
          <button class="comm-filter-btn" data-status="my-rescued" id="comm-my-rescued-btn"
            style="border-color:#bbf7d0;color:#166534;">
            <span class="status-dot" style="background:#22c55e;"></span>
            Your Rescues
          </button>
          <span id="comm-report-count"
            style="margin-left:auto;font-size:0.73rem;color:#94a3b8;font-weight:500;white-space:nowrap;"></span>
        </div>

        <!-- Barangay filter bar -->
        <div class="comm-filter-bar" id="comm-barangay-bar">
          <span class="comm-filter-label">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
              <polyline points="9 22 9 12 15 12 15 22" />
            </svg>
            Barangay
          </span>
          <select id="comm-barangay-select" class="comm-barangay-select">
            <option value="">All Barangays</option>
          </select>
          <span style="font-size:0.68rem;color:#94a3b8;font-style:italic;line-height:1.4;max-width:220px;">
            ⚠️ Results may include nearby areas that mention this barangay in their address.
          </span>
        </div>

      </div>
      <!-- ── END FILTER STACK ── -->

      <div id="comm-no-results">
        <p>No reports match the selected filters.</p>
      </div>

      <div id="feed-container"></div>
      <div id="feed-loading">Loading...</div>
      <div id="feed-end" style="display:none;">No more posts</div>

    </div>
    <!-- closes .community-column -->

  </div>
  <!-- closes .community-grid -->

  <!-- MAP MODAL -->
  <div id="map-modal" class="map-modal">
    <div class="map-modal-content">
      <span id="close-map">&times;</span>
      <h3>Report Location</h3>
      <div id="map"></div>
      <button id="open-full-map" class="btn-full-map">Open Full Screen Map 🗺️</button>
    </div>
  </div>

  <!-- FULL MAP -->
  <div id="full-map-modal" class="full-map-modal">
    <div class="full-map-header">
      <h3>Flood Location Map</h3>
      <span class="close-full-map" id="close-full-map">&times;</span>
    </div>
    <div id="full-map"></div>
  </div>

  <!-- CONFIRMATION MODAL -->
  <div id="confirm-backdrop" class="confirm-backdrop" role="dialog" aria-modal="true"
    aria-labelledby="confirm-modal-title">
    <div class="confirm-modal">
      <div class="confirm-modal__header" id="confirm-modal-header">
        <div class="confirm-modal__icon" id="confirm-modal-icon"></div>
        <div class="confirm-modal__header-text">
          <h3 id="confirm-modal-title"></h3>
          <p id="confirm-modal-subtitle"></p>
        </div>
      </div>
      <div class="confirm-modal__body">
        <p id="confirm-modal-body"></p>
      </div>
      <div class="confirm-modal__footer">
        <button type="button" class="btn-modal-cancel" id="confirm-cancel">Cancel</button>
        <button type="button" class="btn-modal-confirm" id="confirm-ok"></button>
      </div>
    </div>
  </div>

  <!-- TOAST -->
  <div id="rescue-toast" class="rescue-toast" role="alert" aria-live="polite"></div>

  <!-- LEAFLET -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

  <script>
    (function () {

      /* ═══════════════════════════════════════════════
         FILTER STATE
      ═══════════════════════════════════════════════ */
      var activeDatePreset = 'all';
      var activeDateFrom = null;
      var activeDateTo = null;
      var activeStatus = 'all';
      var activeBarangayId = '';
      var activeSearch = '';

      /* ═══════════════════════════════════════════════
         DATE HELPERS
      ═══════════════════════════════════════════════ */
      function fmtDate(d) {
        if (!d) return '';
        var parts = d.split('-');
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return months[parseInt(parts[1]) - 1] + ' ' + parseInt(parts[2]) + ', ' + parts[0];
      }

      function cardPassesDate(card) {
        if (activeDatePreset === 'all' && !activeDateFrom && !activeDateTo) return true;
        var raw = card.getAttribute('data-created-at');
        if (!raw) return false;
        var cardDate = new Date(raw + 'T00:00:00');
        var today = new Date(); today.setHours(0, 0, 0, 0);
        if (activeDatePreset === 'today') return cardDate.getTime() === today.getTime();
        if (activeDatePreset === '7') { var c7 = new Date(today); c7.setDate(c7.getDate() - 6); return cardDate >= c7; }
        if (activeDatePreset === '30') { var c30 = new Date(today); c30.setDate(c30.getDate() - 29); return cardDate >= c30; }
        if (activeDateFrom && cardDate < new Date(activeDateFrom + 'T00:00:00')) return false;
        if (activeDateTo && cardDate > new Date(activeDateTo + 'T00:00:00')) return false;
        return true;
      }

      function cardPassesStatus(card) {
        if (activeStatus === 'all') return true;
        var rescueStatus = card.getAttribute('data-rescue-status');
        var assignedToMe = card.getAttribute('data-assigned-to-me') === '1';
        if (activeStatus === 'my-inprogress') return rescueStatus === 'Being Rescued' && assignedToMe;
        if (activeStatus === 'my-rescued') return rescueStatus === 'Rescued' && assignedToMe;
        return rescueStatus === activeStatus;
      }

      function applyFilters() {
        var cards = document.querySelectorAll('#feed-container .post-card');
        var visible = 0;
        cards.forEach(function (card) {
          var show = cardPassesDate(card) && cardPassesStatus(card);
          card.style.display = show ? '' : 'none';
          if (show) visible++;
        });
        var noResults = document.getElementById('comm-no-results');
        noResults.style.display = visible === 0 && !hasMore ? 'block' : 'none';
        var countEl = document.getElementById('comm-report-count');
        if (countEl) countEl.textContent = visible + ' report' + (visible !== 1 ? 's' : '');
      }

      /* ═══════════════════════════════════════════════
         DATE BAR LOGIC
      ═══════════════════════════════════════════════ */
      var dateBar = document.getElementById('comm-date-bar');
      var dateInfo = document.getElementById('comm-date-info');

      function updateDateInfo() {
        if (activeDatePreset === 'all') { dateInfo.className = 'comm-active-info'; return; }
        var msg = '';
        if (activeDatePreset === 'today') msg = "Showing today's reports only";
        else if (activeDatePreset === '7') msg = 'Showing reports from the last 7 days';
        else if (activeDatePreset === '30') msg = 'Showing reports from the last 30 days';
        else if (activeDateFrom && activeDateTo) msg = fmtDate(activeDateFrom) + ' – ' + fmtDate(activeDateTo);
        else if (activeDateFrom) msg = 'From ' + fmtDate(activeDateFrom) + ' onwards';
        else if (activeDateTo) msg = 'Up to ' + fmtDate(activeDateTo);
        dateInfo.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> ' + msg;
        dateInfo.className = 'comm-active-info show';
      }

      function setDatePresetUI(preset) {
        dateBar.querySelectorAll('[data-date-preset]').forEach(function (btn) {
          var isActive = btn.getAttribute('data-date-preset') === preset;
          btn.classList.toggle('active', isActive);
          btn.style.background = isActive ? '#3b82f6' : '#fff';
          btn.style.borderColor = isActive ? '#2563eb' : '#e2e8f0';
          btn.style.color = isActive ? '#fff' : '#475569';
        });
      }

      dateBar.querySelectorAll('[data-date-preset]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          activeDatePreset = this.getAttribute('data-date-preset');
          activeDateFrom = null; activeDateTo = null;
          document.getElementById('comm-date-from').value = '';
          document.getElementById('comm-date-to').value = '';
          document.getElementById('comm-date-clear').style.display = 'none';
          setDatePresetUI(activeDatePreset);
          updateDateInfo();
          applyFilters();
        });
      });

      document.getElementById('comm-date-apply').addEventListener('click', function () {
        var from = document.getElementById('comm-date-from').value;
        var to = document.getElementById('comm-date-to').value;
        if (!from && !to) return;
        activeDatePreset = 'custom';
        activeDateFrom = from || null;
        activeDateTo = to || null;
        setDatePresetUI('');
        document.getElementById('comm-date-clear').style.display = '';
        updateDateInfo();
        applyFilters();
      });

      document.getElementById('comm-date-clear').addEventListener('click', function () {
        activeDatePreset = 'all'; activeDateFrom = null; activeDateTo = null;
        document.getElementById('comm-date-from').value = '';
        document.getElementById('comm-date-to').value = '';
        this.style.display = 'none';
        setDatePresetUI('all');
        updateDateInfo();
        applyFilters();
      });

      /* ═══════════════════════════════════════════════
         STATUS BAR LOGIC
      ═══════════════════════════════════════════════ */
      var statusColors = {
        'all': { bg: '#3b82f6', border: '#2563eb' },
        'Rescue Needed': { bg: '#ef4444', border: '#dc2626' },
        'Being Rescued': { bg: '#eab308', border: '#ca8a04' },
        'Rescued': { bg: '#22c55e', border: '#16a34a' },
        'Not Required': { bg: '#94a3b8', border: '#64748b' },
        'my-inprogress': { bg: '#eab308', border: '#ca8a04' },
        'my-rescued': { bg: '#22c55e', border: '#16a34a' },
      };

      var statusBar = document.getElementById('comm-status-bar');

      function setStatusUI(status) {
        statusBar.querySelectorAll('[data-status]').forEach(function (btn) {
          var isActive = btn.getAttribute('data-status') === status;
          btn.classList.toggle('active', isActive);
          var colors = statusColors[btn.getAttribute('data-status')] || statusColors['all'];
          btn.style.background = isActive ? colors.bg : '#fff';
          btn.style.borderColor = isActive ? colors.border : '#e2e8f0';
          btn.style.color = isActive ? '#fff' : '#475569';
          var dot = btn.querySelector('.status-dot');
          if (dot) dot.style.background = isActive ? 'rgba(255,255,255,0.85)' : colors.bg;
        });
      }

      statusBar.querySelectorAll('[data-status]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          activeStatus = this.getAttribute('data-status');
          setStatusUI(activeStatus);
          if (activeStatus === 'my-inprogress' || activeStatus === 'my-rescued') {
            applyFilters();
          } else {
            feedPage = 1; hasMore = true; loading = false;
            feed.innerHTML = '';
            loadingEl.style.display = 'block';
            endEl.style.display = 'none';
            loadFeed();
          }
        });
      });

      /* ═══════════════════════════════════════════════
         BARANGAY FILTER
      ═══════════════════════════════════════════════ */
      var barangaySelect = document.getElementById('comm-barangay-select');

      fetch('../includes/fetch_barangays.php')
        .then(function (r) { return r.json(); })
        .then(function (barangays) {
          barangays.forEach(function (b) {
            var opt = document.createElement('option');
            opt.value = b.barangay_id;
            opt.textContent = b.barangay_name;
            barangaySelect.appendChild(opt);
          });
        });

      barangaySelect.addEventListener('change', function () {
        activeBarangayId = this.value;
        barangaySelect.classList.toggle('active', !!activeBarangayId);
        feedPage = 1; hasMore = true; loading = false;
        feed.innerHTML = '';
        loadingEl.style.display = 'block';
        endEl.style.display = 'none';
        loadFeed();
      });

      /* ═══════════════════════════════════════════════
   SEARCH FILTER
═══════════════════════════════════════════════ */
      var searchInput = document.getElementById('comm-search-input');
      var searchClearBtn = document.getElementById('comm-search-clear');
      var searchPill = document.getElementById('comm-search-active-pill');
      var searchDebounce = null;

      function setSearchUI(val) {
        searchClearBtn.style.display = val ? '' : 'none';
        if (val) {
          searchPill.style.display = '';
          searchPill.textContent = '🔍 "' + val + '"';
        } else {
          searchPill.style.display = 'none';
        }
        searchInput.style.borderColor = val ? '#3b82f6' : '#e2e8f0';
      }

      function triggerSearch(val) {
        activeSearch = val.trim();
        setSearchUI(activeSearch);
        feedPage = 1; hasMore = true; loading = false;
        feed.innerHTML = '';
        loadingEl.style.display = 'block';
        endEl.style.display = 'none';
        loadFeed();
      }

      searchInput.addEventListener('input', function () {
        clearTimeout(searchDebounce);
        searchDebounce = setTimeout(function () {
          triggerSearch(searchInput.value);
        }, 400);
      });

      searchInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
          clearTimeout(searchDebounce);
          triggerSearch(searchInput.value);
        }
      });

      searchClearBtn.addEventListener('click', function () {
        searchInput.value = '';
        triggerSearch('');
        searchInput.focus();
      });

      /* Clickable profile trigger */
      document.addEventListener('click', function (e) {
        var trigger = e.target.closest('.profile-trigger');
        if (!trigger) return;
        var name = trigger.dataset.reporterName;
        if (!name) return;
        searchInput.value = name;
        triggerSearch(name);
        feed.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });

      /* ═══════════════════════════════════════════════
         INFINITE SCROLL FEED
      ═══════════════════════════════════════════════ */
      var feedPage = 1;
      var loading = false;
      var hasMore = true;
      var feed = document.getElementById('feed-container');
      var loadingEl = document.getElementById('feed-loading');
      var endEl = document.getElementById('feed-end');

      function loadFeed() {
        if (loading || !hasMore) return;
        loading = true;

        var url = '../includes/fetch_rescuerReports.php?page=' + feedPage;
        var backendStatus = activeStatus;
        if (activeStatus === 'my-inprogress' || activeStatus === 'my-rescued') backendStatus = 'all';
        if (backendStatus !== 'all') url += '&status=' + encodeURIComponent(backendStatus);
        if (activeBarangayId) url += '&barangay_id=' + encodeURIComponent(activeBarangayId);
        if (activeSearch) url += '&search=' + encodeURIComponent(activeSearch);

        fetch(url)
          .then(function (r) { return r.text(); })
          .then(function (html) {
            if (!html.trim()) {
              hasMore = false;
              loadingEl.style.display = 'none';
              endEl.style.display = 'block';
              applyFilters();
              return;
            }
            feed.insertAdjacentHTML('beforeend', html);
            feedPage++;
            loading = false;
            applyFilters();
          });
      }

      loadFeed();

      new IntersectionObserver(
        function (e) { if (e[0].isIntersecting) loadFeed(); },
        { rootMargin: '200px' }
      ).observe(loadingEl);

      /* ═══════════════════════════════════════════════
         MAP
      ═══════════════════════════════════════════════ */
      var mapInstance = null, fullMapInstance = null;
      var lastLat, lastLng, lastName, lastReportData = null;

      function rpmEscHtml(str) {
        if (!str) return '';
        return String(str)
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;');
      }

      var rpmSeverityColors = {
        'Passable': { bg: '#22c55e', border: '#16a34a' },
        'Limited Access': { bg: '#eab308', border: '#ca8a04' },
        'Impassable': { bg: '#ef4444', border: '#dc2626' },
      };

      var rpmRescueBadgeColors = {
        'Rescue Needed': { bg: '#fee2e2', text: '#991b1b' },
        'Being Rescued': { bg: '#fef3c7', text: '#92400e' },
        'Rescued': { bg: '#dcfce7', text: '#166534' },
        'Not Required': { bg: '#f1f5f9', text: '#475569' },
      };

      function buildReportPopup(d) {
        var sev = rpmSeverityColors[d.severity] || { bg: '#3b82f6', border: '#2563eb' };
        var rb = rpmRescueBadgeColors[d.rescueStatus] || rpmRescueBadgeColors['Not Required'];
        var gmapsUrl = 'https://www.google.com/maps?q=' + d.lat + ',' + d.lng;

        var html = '<div class="rpm-popup">';

        html += '<div class="rpm-popup__header" style="background:' + sev.bg + ';">';
        html += '<div class="rpm-popup__eyebrow">Flood Report</div>';
        html += '<div class="rpm-popup__title">' + rpmEscHtml(d.name) + '</div>';
        html += '</div>';

        if (d.image) {
          html += '<div class="rpm-popup__image"><img src="' + rpmEscHtml(d.image) + '" alt="Report photo"></div>';
        }

        html += '<div class="rpm-popup__body">';

        html += '<div class="rpm-popup__row">📍 ' + rpmEscHtml(d.address) + '</div>';
        html += '<div class="rpm-popup__row rpm-popup__row--muted">🕒 ' + rpmEscHtml(d.date) + '</div>';

        if (d.description) {
          html += '<p class="rpm-popup__desc">' + rpmEscHtml(d.description) + '</p>';
        }

        if (d.people > 0) {
          html += '<div class="rpm-popup__alert">👥 ' + d.people + ' person(s) need rescue</div>';
        }

        html += '<div class="rpm-popup__tags">';
        if (d.water) {
          html += '<span class="rpm-popup__tag rpm-popup__tag--water">💧 ' + rpmEscHtml(d.water) + '</span>';
        }
        if (d.severity) {
          html += '<span class="rpm-popup__tag" style="background:' + sev.bg + '22;color:' + sev.border + ';border-color:' + sev.bg + '55;">⚠️ ' + rpmEscHtml(d.severity) + '</span>';
        }
        html += '</div>';

        html += '<span class="rpm-popup__badge" style="background:' + rb.bg + ';color:' + rb.text + ';">' + rpmEscHtml(d.rescueStatus) + '</span>';

        html += '<a class="rpm-popup__gmaps" href="' + gmapsUrl + '" target="_blank" rel="noopener noreferrer">Open in Google Maps</a>';

        html += '</div></div>';

        return html;
      }

      document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-map');
        if (!btn) return;
        lastLat = parseFloat(btn.dataset.lat);
        lastLng = parseFloat(btn.dataset.lng);
        lastName = btn.dataset.name;
        lastReportData = {
          lat: lastLat,
          lng: lastLng,
          name: btn.dataset.name,
          address: btn.dataset.address,
          date: btn.dataset.date,
          description: btn.dataset.description,
          image: btn.dataset.image,
          water: btn.dataset.water,
          severity: btn.dataset.severity,
          rescueStatus: btn.dataset.rescueStatus,
          people: parseInt(btn.dataset.people, 10) || 0,
        };
        document.getElementById('map-modal').style.display = 'flex';
        setTimeout(function () {
          if (mapInstance) mapInstance.remove();
          mapInstance = L.map('map').setView([lastLat, lastLng], 15);
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            { attribution: '&copy; OpenStreetMap' }).addTo(mapInstance);
          L.marker([lastLat, lastLng]).addTo(mapInstance)
            .bindPopup(buildReportPopup(lastReportData), { maxWidth: 280, minWidth: 240, className: 'rpm-popup-wrap' })
            .openPopup();
        }, 150);
      });

      document.getElementById('open-full-map').onclick = function () {
        document.getElementById('map-modal').style.display = 'none';
        document.getElementById('full-map-modal').style.display = 'block';
        setTimeout(function () {
          if (fullMapInstance) fullMapInstance.remove();
          fullMapInstance = L.map('full-map').setView([lastLat, lastLng], 15);
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            { attribution: '&copy; OpenStreetMap' }).addTo(fullMapInstance);
          L.marker([lastLat, lastLng]).addTo(fullMapInstance)
            .bindPopup(buildReportPopup(lastReportData), { maxWidth: 280, minWidth: 240, className: 'rpm-popup-wrap' })
            .openPopup();
        }, 150);
      };

      document.getElementById('close-map').onclick = function () {
        document.getElementById('map-modal').style.display = 'none';
      };
      document.getElementById('close-full-map').onclick = function () {
        document.getElementById('full-map-modal').style.display = 'none';
      };

      /* ═══════════════════════════════════════════════
         RESCUE CONFIRMATION MODAL
      ═══════════════════════════════════════════════ */
      var MODAL_CONTENT = {
        start: {
          icon: '🚨', title: 'Start Rescue Operation',
          subtitle: 'Rescue Needed → Being Rescued',
          bodyFn: function (reporter) {
            return 'Are you sure you want to mark <strong>' + reporter + '</strong>\'s report as <strong>Being Rescued</strong>? This means a rescue team is now on the way.';
          },
          btnLabel: '🚑 Yes, Start Rescue',
          btnClass: 'type--start', headerClass: 'type--start',
        },
        finish: {
          icon: '✅', title: 'Complete Rescue',
          subtitle: 'Being Rescued → Rescued',
          bodyFn: function (reporter) {
            return 'Confirm that <strong>' + reporter + '</strong> has been successfully <strong>Rescued</strong>. This action cannot be undone.';
          },
          btnLabel: '✅ Yes, Mark as Rescued',
          btnClass: 'type--finish', headerClass: 'type--finish',
        },
      };

      var backdrop = document.getElementById('confirm-backdrop');
      var header = document.getElementById('confirm-modal-header');
      var iconEl = document.getElementById('confirm-modal-icon');
      var titleEl = document.getElementById('confirm-modal-title');
      var subtitleEl = document.getElementById('confirm-modal-subtitle');
      var bodyEl = document.getElementById('confirm-modal-body');
      var cancelBtn = document.getElementById('confirm-cancel');
      var okBtn = document.getElementById('confirm-ok');
      var toast = document.getElementById('rescue-toast');

      var activeReportId = null, activeNextId = null, activeBadgeBtn = null;

      document.addEventListener('click', function (e) {
        var badge = e.target.closest('.rescue-badge--btn');
        if (!badge) return;
        var modalType = badge.dataset.modalType;
        var content = MODAL_CONTENT[modalType];
        if (!content) return;
        activeReportId = badge.dataset.reportId;
        activeNextId = badge.dataset.nextStatusId;
        activeBadgeBtn = badge;
        header.className = 'confirm-modal__header ' + content.headerClass;
        iconEl.textContent = content.icon;
        titleEl.textContent = content.title;
        subtitleEl.textContent = content.subtitle;
        bodyEl.innerHTML = content.bodyFn(badge.dataset.reporter);
        okBtn.textContent = content.btnLabel;
        okBtn.className = 'btn-modal-confirm ' + content.btnClass;
        okBtn.disabled = false;
        backdrop.classList.add('open');
      });

      function closeModal() {
        backdrop.classList.remove('open');
        activeReportId = activeNextId = activeBadgeBtn = null;
      }

      cancelBtn.onclick = closeModal;
      backdrop.addEventListener('click', function (e) { if (e.target === backdrop) closeModal(); });
      document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });

      okBtn.onclick = function () {
        okBtn.disabled = true;
        okBtn.textContent = 'Updating…';
        var body = new FormData();
        body.append('report_id', activeReportId);
        body.append('new_status_id', activeNextId);
        fetch('../api/update_rescue_status.php', { method: 'POST', body: body })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (!data.success) throw new Error(data.message || 'Failed');
            var newStatus = data.status_name;
            var classMap = {
              'Rescue Needed': 'badge--danger',
              'Being Rescued': 'badge--warning',
              'Rescued': 'badge--success',
              'Not Required': 'badge--neutral',
            };
            var nextMap = { 'Being Rescued': { nextId: 4, modalType: 'finish' } };
            var stillClickable = nextMap[newStatus] !== undefined;
            if (stillClickable) {
              var next = nextMap[newStatus];
              activeBadgeBtn.classList.remove('badge--danger', 'badge--warning', 'badge--success', 'badge--neutral');
              activeBadgeBtn.classList.add(classMap[newStatus]);
              activeBadgeBtn.textContent = newStatus;
              activeBadgeBtn.dataset.nextStatusId = next.nextId;
              activeBadgeBtn.dataset.modalType = next.modalType;
            } else {
              var span = document.createElement('span');
              span.className = 'rescue-badge ' + (classMap[newStatus] || 'badge--neutral');
              span.textContent = newStatus;
              activeBadgeBtn.replaceWith(span);
            }
            var card = activeBadgeBtn
              ? activeBadgeBtn.closest('.post-card')
              : (span ? span.closest('.post-card') : null);
            if (card) card.setAttribute('data-rescue-status', newStatus);
            showToast('✅ Status updated to: ' + newStatus, 'success');
            closeModal();
            applyFilters();
          })
          .catch(function (err) {
            showToast('❌ ' + err.message, 'error');
            okBtn.disabled = false;
            okBtn.textContent = MODAL_CONTENT[
              activeBadgeBtn ? activeBadgeBtn.dataset.modalType : 'start'
            ].btnLabel;
          });
      };

      /* ═══════════════════════════════════════════════
         TOAST
      ═══════════════════════════════════════════════ */
      var toastTimer = null;
      function showToast(msg, type) {
        toast.textContent = msg;
        toast.className = 'rescue-toast toast--' + (type || 'success') + ' show';
        if (toastTimer) clearTimeout(toastTimer);
        toastTimer = setTimeout(function () { toast.classList.remove('show'); }, 3500);
      }
      var dateToggleBtn = document.getElementById('comm-date-toggle-btn');
      var dateWrapEl = document.getElementById('comm-date-wrap-el');
      if (dateToggleBtn) {
        dateToggleBtn.addEventListener('click', function () {
          dateWrapEl.classList.toggle('open');
          dateToggleBtn.textContent = dateWrapEl.classList.contains('open')
            ? '✕ Close'
            : '📅 Custom range';
        });
      }
      /* ═══════════════════════════════════════════════
         INITIAL UI STATE
      ═══════════════════════════════════════════════ */
      setStatusUI('all');

    })();
  </script>
  <!-- Back to Top Button -->
  <button id="back-to-top-community" onclick="document.querySelector('.page.active').scrollTop = 0" style="
  position: fixed;
  bottom: 28px;
  right: 28px;
  z-index: 9999;
  display: none;
  align-items: center;
  justify-content: center;
  width: 42px;
  height: 42px;
  border-radius: 50%;
  background: #0b1f47;
  color: #fff;
  border: none;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(0,0,0,0.2);
  transition: opacity 0.2s, transform 0.2s;
" title="Back to top">
    <span class="material-symbols-outlined" style="font-size:20px;">arrow_upward</span>
  </button>

  <script>
    (function () {
      var btn = document.getElementById('back-to-top-community');
      var page = document.querySelector('.page.active');

      page.addEventListener('scroll', function () {
        btn.style.display = page.scrollTop > 300 ? 'flex' : 'none';
      });
    })();
  </script>
</section>