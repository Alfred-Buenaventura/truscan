<?php require_once __DIR__ . '/partials/header.php'; ?>

<div class="main-body">

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="card" style="max-width: 800px; margin: 0 auto;">
        <div class="card-body">
            <div class="user-creation-header">
                <i class="fa-solid fa-user-shield" style="color: var(--emerald-600); font-size: 1.5rem;"></i>
                <h3>Create New Admin Account</h3>
            </div>
            <p class="user-creation-subtitle">
                Create a single administrator account. The default password will be <strong>Admin_2025!</strong>
            </p>
            
            <form method="POST" style="margin-top: 1.5rem;">
                <div class="user-creation-form-grid">
                    <div class="form-group">
                        <label>Admin ID Number <span class="required">*</span></label>
                        <input type="text" name="faculty_id" class="form-control" placeholder="e.g., ADMIN001" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address <span class="required">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="e.g., admin@bpc.edu.ph" required>
                    </div>
                    <div class="form-group">
                        <label>First Name <span class="required">*</span></label>
                        <input type="text" name="first_name" class="form-control" placeholder="Enter first name" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name <span class="required">*</span></label>
                        <input type="text" name="last_name" class="form-control" placeholder="Enter last name" required>
                    </div>
                    <div class="form-group">
                        <label>Middle Name</label>
                        <input type="text" name="middle_name" class="form-control" placeholder="Enter middle name (optional)">
                    </div>
                    <div class="form-group">
                        <label>Phone Number <span class="required">*</span></label>
                        <input type="text" name="phone" class="form-control" placeholder="e.g., 09171234567" required>
                    </div>
                     <div class="form-group form-group-full">
                        <label>Role</label>
                        <input type="text" name="role_display" class="form-control" value="Admin" readonly style="background-color: var(--gray-100);">
                    </div>
                </div>

                <div class="password-info-box" style="margin-top: 1.5rem;">
    <i class="fa-solid fa-circle-info"></i>
    <div>
        <strong>Security Notice:</strong> The new admin will be assigned the secure default password <strong>Admin_2025!</strong>. They must change it immediately.
    </div>
</div>

                <button type="submit" name="create_admin" class="btn btn-primary btn-full-width" style="margin-top: 1.5rem;">
                    <i class="fa-solid fa-user-plus"></i>
                    Create Admin Account
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>