<?php
/**
 * Hotlines page — data loaded from flood_information.hotlines via $conn.
 * Included by main.php when ?page=hotlines
 */

if (!isset($conn)) {
    require_once __DIR__ . '/../../config/db.php';
}

/**
 * @param string $name
 * @return array{category: string, icon: string, iconClass: string, tagClass: string}
 */
function resident_hotline_display_meta(string $name): array
{
    $n = strtolower($name);

    if (preg_match('/police|pnp|bantay/i', $n)) {
        return [
            'category' => 'police',
            'icon' => 'local_police',
            'iconClass' => 'icon-police',
            'tagClass' => 'tag-police',
        ];
    }

    if (preg_match('/medical|health|hospital|ambulance|red cross/i', $n)) {
        return [
            'category' => 'medical',
            'icon' => 'local_hospital',
            'iconClass' => 'icon-medical',
            'tagClass' => 'tag-medical',
        ];
    }

    if (preg_match('/rescue|fire|search|coast guard/i', $n)) {
        return [
            'category' => 'rescue',
            'icon' => 'medical_services',
            'iconClass' => 'icon-rescue',
            'tagClass' => 'tag-rescue',
        ];
    }

    if (preg_match('/emergency|mdrrmo|ndrrmc|911/i', $n)) {
        return [
            'category' => 'emergency',
            'icon' => 'emergency',
            'iconClass' => 'icon-emergency',
            'tagClass' => 'tag-emergency',
        ];
    }

    return [
        'category' => 'lgu',
        'icon' => 'account_balance',
        'iconClass' => 'icon-lgu',
        'tagClass' => 'tag-lgu',
    ];
}

$hlItems = [];
$hlDbError = '';

$sql = "
    SELECT
        h.hotline_id,
        h.hotline_name,
        h.contact_number,
        b.barangay_name AS barangay
    FROM hotlines h
    INNER JOIN barangays b ON h.barangay_id = b.barangay_id
    ORDER BY b.barangay_name ASC, h.hotline_name ASC, h.hotline_id ASC
";

$result = isset($conn) ? $conn->query($sql) : false;

if ($result === false) {
    $hlDbError = 'Could not load hotlines: ' . ($conn->error ?? 'database error');
} elseif ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $meta = resident_hotline_display_meta((string) $row['hotline_name']);
        $hlItems[] = [
            'id' => (int) $row['hotline_id'],
            'name' => (string) $row['hotline_name'],
            'number' => (string) $row['contact_number'],
            'barangay' => (string) $row['barangay'],
            'category' => $meta['category'],
            'icon' => $meta['icon'],
            'iconClass' => $meta['iconClass'],
            'tagClass' => $meta['tagClass'],
        ];
    }
}

$hlPayload = [
    'success' => $hlDbError === '' && count($hlItems) > 0,
    'items' => $hlItems,
    'error' => $hlDbError,
    'count' => count($hlItems),
];
?>

<section id="page-hotlines" class="page active">

  <div id="page-content-hotlines">

    <div class="hl-title-area">
      <div class="hl-page-title">Hotlines</div>
    </div>

    <div class="hl-tagline">
      <div class="tagline-icon-wrap">
        <span class="material-symbols-outlined">wifi_calling_3</span>
      </div>
      <div class="tagline-text">
        <div class="tagline-eyebrow">Bocaue, Bulacan</div>
        <div class="tagline-heading">Call trusted emergency contacts instantly</div>
      </div>
    </div>

    <div class="hl-body">
      <div class="hl-inner">

        <div class="hl-error-banner" id="hl-error"<?= $hlDbError !== '' ? '' : ' style="display:none;"' ?>>
          <?= $hlDbError !== '' ? htmlspecialchars($hlDbError) : '' ?>
        </div>

        <div class="hl-search">
          <span class="material-symbols-outlined">search</span>
          <input
            type="text"
            id="hl-search-input"
            placeholder="Search hotlines or barangay…"
            autocomplete="off"
          />
        </div>

        <div class="hl-tabs">
          <button class="hl-tab active" data-cat="all">All</button>
          <button class="hl-tab" data-cat="emergency">Emergency</button>
          <button class="hl-tab" data-cat="medical">Medical</button>
          <button class="hl-tab" data-cat="police">Police</button>
          <button class="hl-tab" data-cat="lgu">LGU / Barangay</button>
          <button class="hl-tab" data-cat="rescue">Rescue</button>
        </div>

        <div class="hl-loading" id="hl-loading" style="display:none;">
          <div class="hl-spinner"></div>
          Loading hotlines…
        </div>

        <script type="application/json" id="hl-db-json"><?= json_encode(
            $hlPayload,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
        ) ?></script>

        <div class="hl-list" id="hl-list"></div>

        <div class="hl-no-results" id="hl-no-results" style="display:none;">
          No hotlines found in the database.
        </div>

      </div>
    </div>

  </div>

</section>
