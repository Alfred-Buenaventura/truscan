<?php 
// FIX: Use __DIR__ to locate the partials folder correctly
require_once __DIR__ . '/partials/header.php'; 
?>

<main class="main-content" style="position: relative; z-index: 1; padding: 20px;">
    <div class="main-body">

        <div id="toastContainer" class="toast-container"></div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon emerald">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="stat-details">
                    <p>Total Accounts</p>
                    <div class="stat-value emerald"><?= $stats['total_active'] ?? 0 ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon emerald">
                    <i class="fa-solid fa-briefcase"></i>
                </div>
                <div class="stat-details">
                    <p>Non-Admin Users</p>
                    <div class="stat-value emerald"><?= $stats['non_admin_active'] ?? 0 ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon emerald">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <div class="stat-details">
                    <p>Admin Users</p>
                    <div class="stat-value emerald"><?= $stats['admin_active'] ?? 0 ?></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="tabs">
                <button class="tab-btn <?= $activeTab === 'csv' ? 'active' : '' ?>" onclick="showTab(event, 'csv')">
                    <i class="fa-solid fa-file-csv"></i> CSV Bulk Import
                </button>
                <button class="tab-btn <?= $activeTab === 'create' ? 'active' : '' ?>" onclick="showTab(event, 'create')">
                    <i class="fa-solid fa-user-plus"></i> Account Creation
                </button>
                <button class="tab-btn <?= $activeTab === 'view' ? 'active' : '' ?>" onclick="showTab(event, 'view')">
                    <i class="fa-solid fa-list"></i> View All Accounts
                </button>
            </div>

            <div id="csvTab" class="tab-content <?= $activeTab === 'csv' ? 'active' : '' ?>">
                <div class="card-body">
                    <div class="csv-section-header">
                        <i class="fa-solid fa-file-arrow-up"></i>
                        <h3>Bulk User Import (CSV)</h3>
                    </div>
                    <p class="csv-subtitle">Import multiple user accounts from a CSV file</p>

                    <div class="download-template-box">
                        <div class="download-template-inner">
                            <div class="step-badge">1</div>
                            <div class="download-template-content">
                                <h4>Download the CSV template first <span>to ensure correct format.</span></h4>
                                <button type="button" class="btn btn-primary download-template-link" onclick="confirmDownload()">
                                    <i class="fa-solid fa-download"></i>
                                    Download Template
                                </button>
                            </div>
                        </div>
                    </div>

                    <form method="POST" enctype="multipart/form-data" id="csvUploadForm">
                        <div style="margin-bottom: 1.5rem;">
                            <label class="csv-upload-label">Upload CSV File</label>
                            <div class="csv-dropzone" id="csvDropzone" onclick="document.getElementById('csvFileInput').click()">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <p class="csv-dropzone-text"><strong>Click to choose a CSV file</strong></p>
                                <p id="csvFileStatus" class="csv-file-status">No file chosen...</p>
                            </div>
                            <input type="file" name="csvFile" id="csvFileInput" accept=".csv" style="display: none;" required>
                        </div>

                        <button type="submit" class="btn btn-primary btn-full-width">
                            <i class="fa-solid fa-upload"></i>
                            Import Users from CSV
                        </button>
                    </form>

                    <div class="csv-requirements">
                        <h4>CSV Format Requirements:</h4>
                        <ul>
                            <li>Columns (in order): Faculty ID, Last Name, First Name, Middle Name, Username, Role, Email, Phone</li>
                            <li>All users will be created with default password: <strong>DefaultPass123!</strong></li>
                            <li>Users must change password on first login</li>
                            <li>Duplicate Faculty IDs will be skipped</li>
                            <li>Rows with 'Admin' role will be skipped</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div id="createTab" class="tab-content <?= $activeTab === 'create' ? 'active' : '' ?>">
                <div class="card-body">
                    <div class="user-creation-header">
                        <i class="fa-solid fa-user-plus"></i>
                        <h3>Create New User Account</h3>
                    </div>
                    <p class="user-creation-subtitle">Create a single user account with default password: <strong>DefaultPass123!</strong></p>

                    <form method="POST" style="margin-top: 1.5rem;">
                        <div class="user-creation-form-grid">
                            <div class="form-group">
                                <label>Faculty/ID Number <span class="required">*</span></label>
                                <input type="text" name="faculty_id" class="form-control" placeholder="e.g., STAFF001" required>
                            </div>
                            <div class="form-group">
                                <label>Email Address <span class="required">*</span></label>
                                <input type="email" name="email" class="form-control" placeholder="e.g., staff@bulacan.edu.ph" required>
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
                                <label>Role/Position <span class="required">*</span></label>
                                <select name="role" class="form-control" required>
                                    <option value="">Select a role</option>
                                    <option value="Full Time Teacher">Full Time Teacher</option>
                                    <option value="Part Time Teacher">Part Time Teacher</option>
                                    <option value="Registrar">Registrar</option>
                                    <option value="Admission">Admission</option>
                                    <option value="OPRE">OPRE</option>
                                    <option value="Scholarship Office">Scholarship Office</option>
                                    <option value="Doctor">Doctor</option>
                                    <option value="Nurse">Nurse</option>
                                    <option value="Guidance Office">Guidance Office</option>
                                    <option value="Library">Library</option>
                                    <option value="Finance">Finance</option>
                                    <option value="Student Affair">Student Affair</option>
                                    <option value="Security Personnel and Facility Operator">Security Personnel and Facility Operator</option>
                                    <option value="OVPA">OVPA</option>
                                    <option value="MIS">MIS</option>
                                </select>
                            </div>
                        </div>

                        <div class="password-info-box">
                            <i class="fa-solid fa-circle-info"></i>
                            <div>
                                <strong>Note:</strong> User will be assigned the default password <strong>DefaultPass123!</strong> and will be prompted to change it on first login.
                            </div>
                        </div>

                        <button type="submit" name="create_user" class="btn btn-primary btn-full-width">
                            <i class="fa-solid fa-user-plus"></i>
                            Create Account
                        </button>
                    </form>
                </div>
            </div>

            <div id="viewTab" class="tab-content <?= $activeTab === 'view' ? 'active' : '' ?>">
                <div class="card-body">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h3 style="margin: 0;">All Active Accounts</h3>
                        <button class="btn btn-secondary" onclick="openArchivedModal()">
                            <i class="fa-solid fa-archive"></i>
                            View Archived Accounts (<?= count($archivedUsers) ?>)
                        </button>
                    </div>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Faculty ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($activeUsers) > 0): ?>
                                <?php foreach ($activeUsers as $user): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['faculty_id']) ?></td>
                                        <td><?= htmlspecialchars($user['first_name']) ?> <?= htmlspecialchars($user['last_name']) ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td><?= htmlspecialchars($user['phone']) ?></td>
                                        <td><span class="role-badge"><?= htmlspecialchars($user['role']) ?></span></td>
                                        <td>
                                            <?php 
                                            // Helper to safe encode data for JS function calls
                                            $name = $user['first_name'] . ' ' . $user['last_name'];
                                            $jsData = [
                                                'id' => $user['id'],
                                                'first' => $user['first_name'],
                                                'last' => $user['last_name'],
                                                'middle' => $user['middle_name'],
                                                'email' => $user['email'],
                                                'phone' => $user['phone']
                                            ];
                                            ?>
                                            <button class="btn btn-sm" onclick="editUser(
                                                <?= $user['id'] ?>, 
                                                '<?= htmlspecialchars($user['first_name'], ENT_QUOTES) ?>', 
                                                '<?= htmlspecialchars($user['last_name'], ENT_QUOTES) ?>', 
                                                '<?= htmlspecialchars($user['middle_name'] ?? '', ENT_QUOTES) ?>', 
                                                '<?= htmlspecialchars($user['email'], ENT_QUOTES) ?>', 
                                                '<?= htmlspecialchars($user['phone'] ?? '', ENT_QUOTES) ?>'
                                            )">
                                                <i class="fa-solid fa-pen"></i> Edit
                                            </button>
                                            <button class="btn btn-sm btn-warning" onclick="confirmArchive(<?= $user['id'] ?>, '<?= htmlspecialchars($name, ENT_QUOTES) ?>')">
                                                <i class="fa-solid fa-archive"></i> Archive
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center" style="padding: 2rem; color: var(--gray-600);">
                                        <i class="fa-solid fa-users-slash" style="font-size: 2rem; margin-bottom: 0.5rem; display: block;"></i>
                                        No active accounts found
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <div id="editForm" style="display: none; margin-top: 2rem; border-top: 2px solid var(--gray-200); padding-top: 2rem;">
                        <h4><i class="fa-solid fa-user-pen"></i> Edit User Information</h4>
                        <form method="POST" style="margin-top: 1rem;">
                            <input type="hidden" name="user_id" id="editUserId">
                            <div class="form-grid">
                                <div class="form-group"><label>First Name <span class="required">*</span></label><input type="text" name="first_name" id="editFirstName" class="form-control" required></div>
                                <div class="form-group"><label>Last Name <span class="required">*</span></label><input type="text" name="last_name" id="editLastName" class="form-control" required></div>
                                <div class="form-group"><label>Middle Name</label><input type="text" name="middle_name" id="editMiddleName" class="form-control"></div>
                                <div class="form-group"><label>Email <span class="required">*</span></label><input type="email" name="email" id="editEmail" class="form-control" required></div>
                                <div class="form-group"><label>Phone</label><input type="text" name="phone" id="editPhone" class="form-control"></div>
                            </div>
                            <div style="margin-top: 1.5rem; display: flex; gap: 0.75rem;">
                                <button type="submit" name="edit_user" class="btn btn-primary">
                                    <i class="fa-solid fa-save"></i> Update User
                                </button>
                                <button type="button" onclick="hideEditForm()" class="btn btn-secondary">
                                    <i class="fa-solid fa-times"></i> Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div id="archivedModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3><i class="fa-solid fa-archive"></i> Archived Accounts</h3>
                    <button class="modal-close" onclick="closeArchivedModal()">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Faculty ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($archivedUsers) > 0): ?>
                                <?php foreach ($archivedUsers as $user): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['faculty_id']) ?></td>
                                        <td><?= htmlspecialchars($user['first_name']) ?> <?= htmlspecialchars($user['last_name']) ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td><span class="role-badge"><?= htmlspecialchars($user['role']) ?></span></td>
                                        <td>
                                            <?php $name = htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES); ?>
                                            <button class="btn btn-sm btn-success" onclick="confirmRestore(<?= $user['id'] ?>, '<?= $name ?>')">
                                                <i class="fa-solid fa-rotate-left"></i> Restore
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $user['id'] ?>, '<?= $name ?>')">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center" style="padding: 2rem; color: var(--gray-600);">
                                        <i class="fa-solid fa-inbox" style="font-size: 2rem; margin-bottom: 0.5rem; display: block;"></i>
                                        No archived accounts
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="confirmModal" class="modal">
            <div class="modal-content modal-small">
                <div class="modal-header">
                    <h3 id="confirmTitle"><i class="fa-solid fa-circle-exclamation"></i> Confirm Action</h3>
                </div>
                <div class="modal-body">
                    <p id="confirmMessage" style="font-size: 1rem; color: var(--gray-700);"></p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeConfirmModal()">
                        <i class="fa-solid fa-times"></i> Cancel
                    </button>
                    <button class="btn btn-primary" id="confirmActionBtn" onclick="executeConfirmedAction()">
                        <i class="fa-solid fa-check"></i> Confirm
                    </button>
                </div>
            </div>
        </div>

        <div id="duplicateUserModal" class="modal">
            <div class="modal-content modal-small">
                <div class="modal-header" style="background-color: var(--yellow-50);">
                    <h3 style="color: var(--yellow-700);"><i class="fa-solid fa-triangle-exclamation"></i> Duplicate Account</h3>
                </div>
                <div class="modal-body">
                    <p class="fs-large" style="color: var(--gray-700);">
                        An account with this Faculty ID already exists in the system.
                    </p>
                    <p class="fs-small" style="color: var(--gray-600); margin-top: 1rem;">
                        Please check the "View All Accounts" tab to find the existing user. Duplicate accounts cannot be created.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="closeModal('duplicateUserModal')">OK</button>
                </div>
            </div>
        </div>

        <div id="doubleConfirmModal" class="modal">
            <div class="modal-content modal-small">
                <div class="modal-header" style="background: var(--red-50);">
                    <h3 style="color: var(--red-600);"><i class="fa-solid fa-triangle-exclamation"></i> Final Confirmation</h3>
                </div>
                <div class="modal-body">
                    <div style="background: var(--red-50); padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border: 1px solid var(--red-600);">
                        <p style="color: var(--red-600); font-weight: 600; margin-bottom: 0.5rem;">
                            <i class="fa-solid fa-exclamation-triangle"></i> WARNING: This action cannot be undone!
                        </p>
                        <p style="color: var(--gray-700); font-size: 0.9rem; margin: 0;">
                            All user data will be permanently deleted from the system.
                        </p>
                    </div>
                    <p id="doubleConfirmMessage" style="font-size: 1rem; color: var(--gray-700);"></p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeDoubleConfirmModal()">
                        <i class="fa-solid fa-times"></i> Cancel
                    </button>
                    <button class="btn btn-danger" onclick="executeDeleteAction()">
                        <i class="fa-solid fa-trash"></i> Yes, Delete Permanently
                    </button>
                </div>
            </div>
        </div>
        
        <div id="operationStatusModal" class="modal">
            <div class="modal-content modal-small">
                <div class="modal-header" id="statusModalHeader">
                    <h3 id="statusModalTitle"><i class="fa-solid fa-circle-check"></i> Success</h3>
                </div>
                <div class="modal-body">
                    <p id="statusModalMessage" style="font-size: 1rem; color: var(--gray-700); line-height: 1.6;"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="closeModal('operationStatusModal')">OK</button>
                </div>
            </div>
        </div>

    </div>
</main>

<script>
/* Global Action Variables */
let pendingAction = null;
let deleteUserId = null;
let deleteUserName = null;

/* Modal Helpers */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

document.addEventListener('DOMContentLoaded', function() {
    // --- Handle Flash Messages passed from Controller ---
    const flashMessage = <?= json_encode($flashMessage) ?>;
    const flashType = <?= json_encode($flashType) ?>;

    if (flashMessage) {
        if (flashType === 'duplicate') {
            openModal('duplicateUserModal');
        } else {
            const modalHeader = document.getElementById('statusModalHeader');
            const modalTitle = document.getElementById('statusModalTitle');
            const modalMessage = document.getElementById('statusModalMessage');

            if (flashType === 'success') {
                modalHeader.style.background = 'var(--emerald-50)';
                modalTitle.innerHTML = '<i class="fa-solid fa-circle-check"></i> Success';
                modalTitle.style.color = 'var(--emerald-800)';
            } else if (flashType === 'error') {
                modalHeader.style.background = 'var(--red-50)';
                modalTitle.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Error';
                modalTitle.style.color = 'var(--red-700)';
            } else {
                modalHeader.style.background = 'var(--blue-50)';
                modalTitle.innerHTML = '<i class="fa-solid fa-circle-info"></i> Notice';
                modalTitle.style.color = 'var(--blue-700)';
            }
            
            modalMessage.textContent = flashMessage;
            openModal('operationStatusModal');
        }
    }

    // --- CSV Upload Drag & Drop Logic ---
    const csvFileInput = document.getElementById('csvFileInput');
    const csvDropzone = document.getElementById('csvDropzone');
    const csvFileStatus = document.getElementById('csvFileStatus');

    if (csvFileInput && csvDropzone && csvFileStatus) {
        csvFileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                csvFileStatus.textContent = this.files[0].name;
                csvFileStatus.classList.add('has-file');
            } else {
                csvFileStatus.textContent = 'No file chosen...';
                csvFileStatus.classList.remove('has-file');
            }
        });

        csvDropzone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });

        csvDropzone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });

        csvDropzone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            if (e.dataTransfer.files.length > 0) {
                csvFileInput.files = e.dataTransfer.files;
                csvFileStatus.textContent = e.dataTransfer.files[0].name;
                csvFileStatus.classList.add('has-file');
            }
        });
    }
});

/* Tab Switching */
window.showTab = function(event, tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    const el = document.getElementById(tab + 'Tab');
    if (el) el.classList.add('active');
    if (event && event.target) {
        const btn = event.target.closest('.tab-btn');
        if (btn) btn.classList.add('active');
    }
};

/* Edit User Functions */
function editUser(id, firstName, lastName, middleName, email, phone) {
    document.getElementById('editUserId').value = id;
    document.getElementById('editFirstName').value = firstName || '';
    document.getElementById('editLastName').value = lastName || '';
    document.getElementById('editMiddleName').value = middleName || '';
    document.getElementById('editEmail').value = email || '';
    document.getElementById('editPhone').value = phone || '';
    
    const form = document.getElementById('editForm');
    if (form) {
        form.style.display = 'block';
        form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

function hideEditForm() {
    const form = document.getElementById('editForm');
    if (form) form.style.display = 'none';
}

/* Archive/Restore/Delete Logic */
function openArchivedModal() {
    openModal('archivedModal');
}

function closeArchivedModal() {
    closeModal('archivedModal');
}

function confirmDownload() {
    document.getElementById('confirmTitle').innerHTML = '<i class="fa-solid fa-download"></i> Download Template';
    document.getElementById('confirmMessage').textContent = 'Download the CSV template file? This template shows the correct format for bulk user import.';
    const btn = document.getElementById('confirmActionBtn');
    btn.className = 'btn btn-primary';
    btn.innerHTML = '<i class="fa-solid fa-download"></i> Download';

    pendingAction = function() {
        window.location.href = 'download_template.php';
    };
    openModal('confirmModal');
}

function confirmArchive(userId, userName) {
    document.getElementById('confirmTitle').innerHTML = '<i class="fa-solid fa-archive"></i> Confirm Archive';
    document.getElementById('confirmMessage').textContent = `Are you sure you want to archive ${userName}? They will no longer have access to the system.`;
    const btn = document.getElementById('confirmActionBtn');
    btn.className = 'btn btn-warning';
    btn.innerHTML = '<i class="fa-solid fa-archive"></i> Archive';

    pendingAction = function() {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="user_id" value="${userId}"><input type="hidden" name="archive_user" value="1">`;
        document.body.appendChild(form);
        form.submit();
    };
    openModal('confirmModal');
}

function confirmRestore(userId, userName) {
    document.getElementById('confirmTitle').innerHTML = '<i class="fa-solid fa-rotate-left"></i> Confirm Restore';
    document.getElementById('confirmMessage').textContent = `Are you sure you want to restore ${userName}? They will regain access to the system.`;
    const btn = document.getElementById('confirmActionBtn');
    btn.className = 'btn btn-success';
    btn.innerHTML = '<i class="fa-solid fa-check"></i> Restore';

    pendingAction = function() {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="user_id" value="${userId}"><input type="hidden" name="restore_user" value="1">`;
        document.body.appendChild(form);
        form.submit();
    };
    openModal('confirmModal');
}

function confirmDelete(userId, userName) {
    document.getElementById('confirmTitle').innerHTML = '<i class="fa-solid fa-trash"></i> Confirm Delete';
    document.getElementById('confirmMessage').textContent = `Are you sure you want to permanently delete ${userName}? This action cannot be undone.`;
    const btn = document.getElementById('confirmActionBtn');
    btn.className = 'btn btn-danger';
    btn.innerHTML = '<i class="fa-solid fa-trash"></i> Delete';

    deleteUserId = userId;
    deleteUserName = userName;

    pendingAction = function() {
        closeConfirmModal();
        setTimeout(() => {
            const doubleMsg = document.getElementById('doubleConfirmMessage');
            if (doubleMsg) doubleMsg.textContent = `Type confirmation: Are you absolutely sure you want to delete ${userName}?`;
            openModal('doubleConfirmModal');
        }, 300);
    };
    openModal('confirmModal');
}

function closeConfirmModal() {
    closeModal('confirmModal');
    pendingAction = null;
}

function closeDoubleConfirmModal() {
    closeModal('doubleConfirmModal');
    deleteUserId = null;
    deleteUserName = null;
}

function executeConfirmedAction() {
    if (pendingAction) pendingAction();
    closeConfirmModal();
}

function executeDeleteAction() {
    if (deleteUserId) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="user_id" value="${deleteUserId}"><input type="hidden" name="delete_user" value="1">`;
        document.body.appendChild(form);
        form.submit();
    }
    closeDoubleConfirmModal();
}

window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            closeModal(modal.id);
        }
    });
};
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>