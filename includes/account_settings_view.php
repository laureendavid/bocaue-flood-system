<?php

/**
 * Shared account settings page markup.
 *
 * Expected variables:
 * - $portalEyebrow, $cancelUrl, $formAction
 * - $profile, $flash, $avatarUrl, $defaultAvatarUrl, $hasProfilePhoto
 * - $barangayLabel, $locationAddress, $locationCoords, $dobValue
 * - $accountSettingsEmbedded (bool)
 */

$wrapperTag = !empty($accountSettingsEmbedded) ? 'section' : 'div';
$wrapperClass = !empty($accountSettingsEmbedded) ? 'page active' : 'as-standalone-page';
$roleLabel = htmlspecialchars($profile['full_name'] ?? 'user');
?>

<<?= $wrapperTag ?> id="page-account-settings" class="<?= $wrapperClass ?>">
  <div class="as-page">

    <header class="as-egov-header">
      <p class="as-egov-eyebrow"><?= htmlspecialchars($portalEyebrow ?? 'My Account') ?></p>
      <h1 class="as-egov-title">Account Settings</h1>
      <p class="as-egov-lead">
        Review and update your registered profile information. You may change only the fields you need;
        unchanged fields keep their current values. Verified location data from registration cannot be modified.
      </p>
    </header>

    <?php if (is_array($flash) && !empty($flash['message'])): ?>
      <?php
        $flashType = $flash['type'] ?? 'info';
        if ($flashType === 'success') {
            $flashIcon = 'check_circle';
        } elseif ($flashType === 'error') {
            $flashIcon = 'error';
        } else {
            $flashIcon = 'info';
        }
      ?>
      <div class="as-alert as-alert--<?= htmlspecialchars($flashType) ?>" role="alert">
        <span class="material-symbols-outlined" aria-hidden="true"><?= $flashIcon ?></span>
        <span><?= htmlspecialchars($flash['message']) ?></span>
      </div>
    <?php endif; ?>

    <div class="as-grid">
      <div class="as-card">
        <div class="as-card-head">
          <span class="material-symbols-outlined" aria-hidden="true">badge</span>
          <div>
            <h2 class="as-card-title">Profile Information</h2>
            <p class="as-card-desc">Update your photo, name, and contact details on record.</p>
          </div>
        </div>

        <form
          id="account-profile-form"
          class="as-form"
          method="POST"
          action="<?= htmlspecialchars($formAction) ?>"
          enctype="multipart/form-data"
          novalidate
        >
          <input type="hidden" name="form_action" value="profile">

          <fieldset class="as-egov-section as-egov-section--photo">
            <legend class="as-section-label">Profile Photo</legend>

            <div class="as-profile-photo-panel">
              <div class="as-profile-current">
                <div class="as-avatar-preview-wrap as-avatar-preview-wrap--lg">
                  <img
                    id="as-avatar-preview"
                    class="as-avatar-preview"
                    src="<?= htmlspecialchars($avatarUrl) ?>"
                    alt="Current profile photo for <?= $roleLabel ?>"
                    data-default-src="<?= htmlspecialchars($defaultAvatarUrl) ?>"
                    data-current-src="<?= htmlspecialchars($avatarUrl) ?>"
                    onerror="this.onerror=null;this.src=this.dataset.defaultSrc||'';"
                  >
                </div>
                <p class="as-profile-current-label">Current Profile Photo</p>
                <p class="as-profile-current-name"><?= htmlspecialchars($profile['full_name'] ?? '') ?></p>
                <p class="as-profile-current-status">
                  <?php if ($hasProfilePhoto): ?>
                    Saved on your account from registration or your last update.
                  <?php else: ?>
                    No profile photo on file yet. Upload one below.
                  <?php endif; ?>
                </p>
              </div>

              <div class="as-profile-upload">
                <p class="as-profile-upload-title">Change Profile Photo</p>
                <p class="as-form-hint">Optional. Leave this empty to keep your current photo.</p>

                <div class="as-file-wrap">
                  <label class="as-file-trigger" for="profile_picture">
                    <span class="material-symbols-outlined" aria-hidden="true">upload</span>
                    Upload New Photo
                  </label>
                  <input
                    type="file"
                    id="profile_picture"
                    name="profile_picture"
                    class="as-file-input"
                    accept="image/jpeg,image/png,.jpg,.jpeg,.png"
                  >
                  <p class="as-file-name" id="as-file-name">No new file selected</p>
                  <p class="as-form-hint">Accepted formats: JPG or PNG. Maximum file size: 5 MB.</p>
                </div>
              </div>
            </div>
          </fieldset>

          <fieldset class="as-egov-section">
            <legend class="as-section-label">Personal Details</legend>
            <p class="as-required-note">
              Fields marked with <abbr title="Required">*</abbr> must remain valid on your account.
              Leave a field as shown to keep its current value.
            </p>

            <div class="as-form-fields">
              <div class="as-form-row as-form-row--2">
                <div class="as-form-group">
                  <label class="as-form-label" for="first_name">First Name <abbr title="Required">*</abbr></label>
                  <input type="text" id="first_name" name="first_name" class="as-form-input"
                    value="<?= htmlspecialchars($profile['first_name'] ?? '') ?>"
                    data-original-value="<?= htmlspecialchars($profile['first_name'] ?? '') ?>"
                    maxlength="80" autocomplete="given-name">
                </div>
                <div class="as-form-group">
                  <label class="as-form-label" for="last_name">Last Name <abbr title="Required">*</abbr></label>
                  <input type="text" id="last_name" name="last_name" class="as-form-input"
                    value="<?= htmlspecialchars($profile['last_name'] ?? '') ?>"
                    data-original-value="<?= htmlspecialchars($profile['last_name'] ?? '') ?>"
                    maxlength="80" autocomplete="family-name">
                </div>
              </div>

              <div class="as-form-row as-form-row--2">
                <div class="as-form-group">
                  <label class="as-form-label" for="suffix">Suffix <span class="as-optional">(Optional)</span></label>
                  <input type="text" id="suffix" name="suffix" class="as-form-input"
                    value="<?= htmlspecialchars($profile['suffix'] ?? '') ?>"
                    data-original-value="<?= htmlspecialchars($profile['suffix'] ?? '') ?>"
                    maxlength="20" placeholder="e.g. JR., SR., III">
                </div>
                <div class="as-form-group">
                  <label class="as-form-label" for="date_of_birth">Date of Birth <abbr title="Required">*</abbr></label>
                  <input type="date" id="date_of_birth" name="date_of_birth" class="as-form-input"
                    value="<?= htmlspecialchars($dobValue) ?>"
                    data-original-value="<?= htmlspecialchars($dobValue) ?>">
                </div>
              </div>
            </div>
          </fieldset>

          <fieldset class="as-egov-section">
            <legend class="as-section-label">Contact Information</legend>

            <div class="as-form-fields">
              <div class="as-form-row as-form-row--2">
                <div class="as-form-group">
                  <label class="as-form-label" for="email">Email Address <abbr title="Required">*</abbr></label>
                  <input type="email" id="email" name="email" class="as-form-input"
                    value="<?= htmlspecialchars($profile['email'] ?? '') ?>"
                    data-original-value="<?= htmlspecialchars($profile['email'] ?? '') ?>"
                    autocomplete="email">
                </div>
                <div class="as-form-group">
                  <label class="as-form-label" for="phone">Mobile Number <abbr title="Required">*</abbr></label>
                  <input type="tel" id="phone" name="phone" class="as-form-input"
                    value="<?= htmlspecialchars($profile['phone'] ?? '') ?>"
                    data-original-value="<?= htmlspecialchars($profile['phone'] ?? '') ?>"
                    pattern="09[0-9]{9}"
                    maxlength="11" placeholder="09XXXXXXXXX" autocomplete="tel" inputmode="numeric">
                  <p class="as-form-hint">Philippine mobile format: 09XXXXXXXXX</p>
                </div>
              </div>
            </div>
          </fieldset>

          <fieldset class="as-egov-section as-egov-section--locked">
            <legend class="as-section-label as-section-label--locked">
              <span class="material-symbols-outlined" aria-hidden="true">verified_user</span>
              Verified Registration Data
            </legend>

            <div class="as-form-fields">
              <div class="as-form-group">
                <label class="as-form-label" for="verified_location">Registered Barangay / Location</label>
                <input type="text" id="verified_location" class="as-form-input as-form-input--readonly"
                  value="<?= htmlspecialchars($barangayLabel) ?>" readonly tabindex="-1" aria-readonly="true">
              </div>

              <div class="as-form-group">
                <label class="as-form-label" for="verified_address">Registered Address</label>
                <textarea id="verified_address" class="as-form-input as-form-input--readonly" rows="3"
                  readonly tabindex="-1" aria-readonly="true"><?= htmlspecialchars($locationAddress) ?></textarea>
                <?php if ($locationCoords !== ''): ?>
                  <p class="as-form-hint">Registered coordinates: <?= htmlspecialchars($locationCoords) ?></p>
                <?php endif; ?>
                <p class="as-form-hint as-form-hint--lock">
                  <span class="material-symbols-outlined" aria-hidden="true">lock</span>
                  Location is verified during registration and cannot be changed.
                </p>
              </div>
            </div>
          </fieldset>

          <div class="as-form-actions">
            <a href="<?= htmlspecialchars($cancelUrl) ?>" class="as-btn as-btn--ghost">Cancel</a>
            <button type="submit" class="as-btn as-btn--primary">Save Changes</button>
          </div>
        </form>
      </div>

      <div class="as-card">
        <div class="as-card-head">
          <span class="material-symbols-outlined" aria-hidden="true">lock_reset</span>
          <div>
            <h2 class="as-card-title">Security Settings</h2>
            <p class="as-card-desc">Change your account password to keep your profile secure.</p>
          </div>
        </div>

        <form id="account-password-form" class="as-form" method="POST" action="<?= htmlspecialchars($formAction) ?>" novalidate>
          <input type="hidden" name="form_action" value="password">

          <fieldset class="as-egov-section">
            <legend class="as-section-label">Change Password</legend>
            <p class="as-required-note">Password must be at least 12 characters and include letters and numbers.</p>

            <div class="as-form-fields">
              <div class="as-form-group">
                <label class="as-form-label" for="current_password">Current Password <abbr title="Required">*</abbr></label>
                <div class="as-password-wrap">
                  <input type="password" id="current_password" name="current_password" class="as-form-input" required autocomplete="current-password">
                  <button type="button" class="as-password-toggle" data-target="current_password" aria-label="Show current password">
                    <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
                  </button>
                </div>
              </div>

              <div class="as-form-group">
                <label class="as-form-label" for="new_password">New Password <abbr title="Required">*</abbr></label>
                <div class="as-password-wrap">
                  <input type="password" id="new_password" name="new_password" class="as-form-input" required minlength="12" autocomplete="new-password">
                  <button type="button" class="as-password-toggle" data-target="new_password" aria-label="Show new password">
                    <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
                  </button>
                </div>
              </div>

              <div class="as-form-group">
                <label class="as-form-label" for="confirm_password">Confirm New Password <abbr title="Required">*</abbr></label>
                <div class="as-password-wrap">
                  <input type="password" id="confirm_password" name="confirm_password" class="as-form-input" required minlength="12" autocomplete="new-password">
                  <button type="button" class="as-password-toggle" data-target="confirm_password" aria-label="Show confirm password">
                    <span class="material-symbols-outlined" aria-hidden="true">visibility</span>
                  </button>
                </div>
              </div>
            </div>
          </fieldset>

          <div class="as-form-actions">
            <button type="reset" class="as-btn as-btn--ghost">Cancel</button>
            <button type="submit" class="as-btn as-btn--primary">Update Password</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</<?= $wrapperTag ?>>
