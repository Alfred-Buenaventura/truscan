<?php 
// FIX: Use __DIR__ to locate the partials folder correctly
require_once __DIR__ . '/partials/header.php'; 
?>
<div class="main-body">
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">
        <div class="card">
            <div class="card-body" style="text-align: center;">
                <div style="width: 120px; height: 120px; background: var(--emerald-500); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 48px; font-weight: 700; color: white;">
                    <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
                </div>
                <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 4px;">
                    <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                </h3>
                <p style="color: var(--gray-600); margin-bottom: 16px;"><?= htmlspecialchars($user['role']) ?></p>

                <div style="background: var(--gray-50); padding: 16px; border-radius: 12px; text-align: left;">
                    <div style="margin-bottom: 12px;">
                        <p style="font-size: 12px; color: var(--gray-600); margin-bottom: 4px;">Faculty ID</p>
                        <p style="font-weight: 600;"><?= htmlspecialchars($user['faculty_id']) ?></p>
                    </div>
                    <div style="margin-bottom: 12px;">
                        <p style="font-size: 12px; color: var(--gray-600); margin-bottom: 4px;">Username</p>
                        <p style="font-weight: 600;"><?= htmlspecialchars($user['username']) ?></p>
                    </div>
                    <div>
                        <p style="font-size: 12px; color: var(--gray-600); margin-bottom: 4px;">Member Since</p>
                        <p style="font-weight: 600;"><?= date('F Y', strtotime($user['created_at'])) ?></p>
                    </div>
                </div>

                <div style="margin-top: 16px;">
                    <a href="change_password.php" class="btn btn-secondary btn-block">Change Password</a>
                </div>

                <?php if ($user['fingerprint_registered']): ?>
                    <div style="margin-top: 16px; padding: 12px; background: var(--emerald-50); border-radius: 12px;">
                        <i class="fa-solid fa-check-circle" style="color: var(--emerald-600);"></i>
                        <p style="font-size: 12px; font-weight: 600; color: var(--emerald-700); display:inline;">Fingerprint Registered</p>
                    </div>
                <?php else: ?>
                    <div style="margin-top: 16px; padding: 12px; background: var(--yellow-100); border-radius: 12px;">
                        <i class="fa-solid fa-triangle-exclamation" style="color: #d97706;"></i>
                        <p style="font-size: 12px; font-weight: 600; color: #92400e; display:inline;">Fingerprint Not Registered</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3>Edit Profile Information</h3>
                    <p>Update your personal information</p>
                </div>
                <button type="button" id="editProfileBtn" class="btn btn-primary">
                    <i class="fa-solid fa-pen"></i> Edit Profile
                </button>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" id="firstNameInput" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>" required readonly>
                        </div>

                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name" id="lastNameInput" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>" required readonly>
                        </div>

                        <div class="form-group" style="grid-column: span 2;">
                            <label>Middle Name</label>
                            <input type="text" name="middle_name" id="middleNameInput" class="form-control" value="<?= htmlspecialchars($user['middle_name'] ?? '') ?>" readonly>
                        </div>

                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" id="emailInput" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required readonly>
                        </div>

                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" id="phoneInput" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" readonly>
                        </div>
                    </div>

                    <div style="background: var(--gray-50); padding: 16px; border-radius: 12px; margin: 24px 0;">
                        <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 12px;">Read-Only Information</h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div>
                                <p style="font-size: 12px; color: var(--gray-600); margin-bottom: 4px;">Faculty ID</p>
                                <p style="font-weight: 600;"><?= htmlspecialchars($user['faculty_id']) ?></p>
                            </div>
                            <div>
                                <p style="font-size: 12px; color: var(--gray-600); margin-bottom: 4px;">Role</p>
                                <p style="font-weight: 600;"><?= htmlspecialchars($user['role']) ?></p>
                            </div>
                        </div>
                        <p style="font-size: 12px; color: #d97706; margin-top: 8px;">
                            ⓘ Contact administrator to change Faculty ID or Role
                        </p>
                    </div>

                    <div id="editModeButtons" style="display: none; display: flex; gap: 12px;">
                        <button type="submit" name="update_profile" id="saveChangesBtn" class="btn btn-primary">Save Changes</button>
                        <button type="button" id="cancelEditBtn" class="btn btn-secondary">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    </div>

<script>
const editProfileBtn = document.getElementById('editProfileBtn');
const editModeButtons = document.getElementById('editModeButtons');
const cancelEditBtn = document.getElementById('cancelEditBtn');
const profileInputs = [
    document.getElementById('firstNameInput'),
    document.getElementById('lastNameInput'),
    document.getElementById('middleNameInput'),
    document.getElementById('emailInput'),
    document.getElementById('phoneInput')
];
let originalValues = {};

if (editProfileBtn && editModeButtons && cancelEditBtn) {
    editProfileBtn.addEventListener('click', () => {
        originalValues = {};
        profileInputs.forEach(input => {
            if (input) {
                input.removeAttribute('readonly');
                originalValues[input.id] = input.value;
            }
        });
        editProfileBtn.style.display = 'none';
        editModeButtons.style.display = 'flex';
    });

    cancelEditBtn.addEventListener('click', () => {
        profileInputs.forEach(input => {
            if (input) {
                input.setAttribute('readonly', true);
                input.value = originalValues[input.id] || input.value;
            }
        });
        editProfileBtn.style.display = 'inline-flex';
        editModeButtons.style.display = 'none';
    });
}
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>