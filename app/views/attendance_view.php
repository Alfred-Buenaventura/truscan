<?php 
// FIX: Use __DIR__ to locate the partials folder correctly
require_once __DIR__ . '/partials/header.php'; 
?>
<div class="main-body attendance-reports-page"> 
    
    <?php if ($isAdmin): ?>
    <div class="report-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div></div> <a href="attendance_history.php" class="btn btn-secondary">
            <i class="fa-solid fa-clock-rotate-left"></i> View Full History
        </a>
    </div>
    <?php endif; ?>

    <div class="report-stats-grid">
        <div class="report-stat-card">
            <div class="stat-icon-bg bg-emerald-100 text-emerald-600">
                 <i class="fa-solid fa-arrow-right-to-bracket"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Today's Entries</span>
                <span class="stat-value"><?= $stats['entries'] ?></span>
            </div>
        </div>
         <div class="report-stat-card">
            <div class="stat-icon-bg bg-red-100 text-red-600">
                <i class="fa-solid fa-arrow-right-from-bracket"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">Today's Exits</span>
                <span class="stat-value"><?= $stats['exits'] ?></span>
            </div>
        </div>
         <div class="report-stat-card">
            <div class="stat-icon-bg bg-blue-100 text-blue-600">
                 <i class="fa-solid fa-user-check"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label"><?= $isAdmin ? 'Users Present' : 'Total Days Present' ?></span>
                <span class="stat-value"><?= $stats['present_total'] ?></span>
            </div>
        </div>
         <div class="report-stat-card">
            <div class="stat-icon-bg bg-gray-100 text-gray-600">
                 <i class="fa-solid fa-list-alt"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label"><?= $isAdmin ? 'Total Records' : 'My Records (Filtered)' ?></span>
                <span class="stat-value"><?= $totalRecords ?></span> 
            </div>
        </div>
    </div>

    <div class="filter-export-section card <?= $isAdmin ? 'report-filter-card-admin' : 'report-filter-card-user' ?>">
        <div class="card-header">
            <h3><i class="fa-solid fa-filter"></i> Filter & Export</h3>
            <p><?= $isAdmin ? 'Filter and export attendance records for all users' : 'Filter your attendance records by date and export' ?></p>
        </div>
        <div class="card-body">
            <form method="GET" action="attendance_reports.php" class="filter-controls-new">
                <div class="filter-inputs" <?= !$isAdmin ? 'style="grid-template-columns: 1fr;"' : '' ?>>
                    
                    <?php if ($isAdmin): ?>
                    <div class="form-group filter-item">
                        <label for="searchFilter">Search</label>
                        <div class="search-wrapper">
                            <i class="fa-solid fa-search search-icon-filter"></i>
                            <input type="text" id="searchFilter" name="search" class="form-control search-input-filter" placeholder="Search users..." value="<?= htmlspecialchars($filters['search']) ?>">
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="form-group filter-item">
                        <label for="dateRangeStartFilter">Date Range</label>
                         <div style="display: flex; gap: 0.5rem;">
                             <input type="date" id="dateRangeStartFilter" name="start_date" class="form-control report-date-input" value="<?= htmlspecialchars($filters['start_date']) ?>">
                             <input type="date" id="dateRangeEndFilter" name="end_date" class="form-control report-date-input" value="<?= htmlspecialchars($filters['end_date']) ?>">
                         </div>
                    </div>
                    
                    <?php if ($isAdmin): ?>
                    <div class="form-group filter-item">
                        <label for="userFilter">Select User</label>
                        <select id="userFilter" name="user_id" class="form-control">
                            <option value="">All Users</option>
                            <?php foreach ($allUsers as $user): ?>
                                <option value="<?= $user['id'] ?>" <?= $filters['user_id'] == $user['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?> (<?= htmlspecialchars($user['faculty_id']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="filter-actions-new">
                    <button type="submit" class="btn btn-primary btn-sm apply-filter-btn">
                        <i class="fa-solid fa-check"></i> Apply Filters
                    </button>
                    <a href="export_attendance.php?start_date=<?= $filters['start_date'] ?>&end_date=<?= $filters['end_date'] ?><?= $isAdmin ? '&search=' . urlencode($filters['search']) . '&user_id=' . $filters['user_id'] : '' ?>"
                        class="btn btn-danger btn-sm export-csv-btn"
                        id="exportCsvBtn">
                        <i class="fa-solid fa-file-csv"></i> Export CSV
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card attendance-table-card">
         <div class="card-body" style="padding: 0;"> 
            <?php if (!empty($error)): ?>
                <div class="alert alert-error" style="margin: 1rem;"><?= htmlspecialchars($error) ?></div>
            <?php elseif (empty($records)): ?>
                <p style="text-align: center; color: var(--gray-500); padding: 40px; font-size: 1.1rem;">No records found matching the selected filters.</p>
            <?php else: ?>
                <table class="attendance-table-new">
                    <thead>
                        <tr>
                            <?php if ($isAdmin): ?>
                                <th>User</th>
                                <th>Department</th>
                            <?php else: ?>
                                <th>Name</th>
                            <?php endif; ?>
                            <th>Date</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $record): ?>
                        <tr class="clickable-row"
                                onclick="openDtrModal('print_dtr.php?user_id=<?= $record['user_id'] ?>&start_date=<?= htmlspecialchars($filters['start_date']) ?>&end_date=<?= htmlspecialchars($filters['end_date']) ?>&preview=1', '<?= htmlspecialchars($record['first_name'] . ' ' . $record['last_name']) ?>')"
                                title="Click to preview DTR">
                            <?php if ($isAdmin): ?>
                            <td>
                                <div class="user-cell">
                                    <span class="user-name">
                                        <?= htmlspecialchars($record['first_name'] . ' ' . $record['last_name']) ?>
                                    </span>
                                    <span class="user-id"><?= htmlspecialchars($record['faculty_id']) ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="department-cell"><?= htmlspecialchars($record['role']) ?></span> 
                            </td>
                            <?php else: ?>
                            <td>
                                <div class="user-cell">
                                    <span class="user-name">
                                        <?= htmlspecialchars($record['first_name'] . ' ' . $record['last_name']) ?>
                                    </span>
                                    <span class="user-id"><?= htmlspecialchars($record['faculty_id']) ?></span>
                                </div>
                            </td>
                            <?php endif; ?>
                            
                            <td>
                                <span class="date-cell"><?= date('m/d/Y', strtotime($record['date'])) ?></span>
                            </td>
                            <td>
                                <?php if ($record['time_in']): ?>
                                    <div class="time-cell time-in">
                                        <i class="fa-solid fa-arrow-right-to-bracket"></i>
                                        <span><?= date('h:i A', strtotime($record['time_in'])) ?></span>
                                        <span class="status-label <?= ($record['status'] == 'Late') ? 'status-late' : '' ?>">
                                            <?= htmlspecialchars($record['status']) ?>
                                        </span>
                                    </div>
                                <?php elseif (isset($record['status']) && $record['status'] == 'Absent'): ?>
                                    <div class="time-cell no-time">
                                        <span class="status-label status-absent">Absent</span>
                                    </div>
                                <?php else: ?>
                                    <div class="time-cell no-time">-</div>
                                <?php endif; ?>
                            </td>
                             <td>
                                <?php if ($record['time_out']): ?>
                                    <div class="time-cell time-out">
                                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                                        <span><?= date('h:i A', strtotime($record['time_out'])) ?></span>
                                    </div>
                                <?php elseif (isset($record['status']) && $record['status'] == 'Absent'): ?>
                                    <div class="time-cell no-time">-</div>
                                <?php else: ?>
                                    <div class="time-cell no-time">-</div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div id="dtrPreviewModal" class="modal modal-dtr-preview">
        <div class="modal-content">
            <div class="modal-header" style="justify-content: space-between; display: flex; width: 100%;">
                <div>
                    <h3 id="dtrModalTitle" style="color: var(--emerald-800);"><i class="fa-solid fa-file-invoice"></i> DTR Preview</h3>
                    <p id="dtrModalSubtitle" style="color: var(--gray-600); font-size: 0.9rem; margin-top: 4px;"></p>
                </div>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <button type="button" class="btn btn-primary btn-sm" onclick="printDtrFromModal()">
                        <i class="fa-solid fa-print"></i> Print
                    </button>
                    <button type="button" class="modal-close" onclick="closeDtrModal()">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
            </div>
            <div class="modal-body">
                <iframe id="dtrFrame" src="about:blank" frameborder="0"></iframe>
            </div>
        </div>
    </div>

</div>

<script>
function openDtrModal(url, userName) {
    const iframe = document.getElementById('dtrFrame');
    const modal = document.getElementById('dtrPreviewModal');
    const subtitle = document.getElementById('dtrModalSubtitle');
    
    if (iframe && modal) {
        if (subtitle && userName) {
            subtitle.textContent = "Previewing for: " + userName;
        }
        iframe.src = url;
        openModal('dtrPreviewModal'); 
    }
}

function closeDtrModal() {
    const iframe = document.getElementById('dtrFrame');
    if (iframe) iframe.src = 'about:blank';
    closeModal('dtrPreviewModal');
}

function printDtrFromModal() {
    const iframe = document.getElementById('dtrFrame');
    if (iframe && iframe.contentWindow) {
        iframe.contentWindow.print();
    }
}
// Modal helper function if not already in footer
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'flex';
}
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>