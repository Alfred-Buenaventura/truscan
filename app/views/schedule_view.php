<?php 
require_once __DIR__ . '/partials/header.php'; 

// Helper function to render schedule tables
// UPDATED: Added $isAdmin parameter to control visibility of Actions column
if (!function_exists('renderScheduleTable')) {
    function renderScheduleTable($schedules, $nested, $isAdmin) {
        echo '<table class="data-table"><thead><tr><th>Day</th><th>Subject / Duty</th><th>Time</th><th>Room / Department</th>';
        
        // Only show Actions header if Admin
        if ($isAdmin) {
            echo '<th>Actions</th>';
        }
        
        echo '</tr></thead><tbody>';
        
        $dayOrder = ['Monday'=>1,'Tuesday'=>2,'Wednesday'=>3,'Thursday'=>4,'Friday'=>5,'Saturday'=>6];
        usort($schedules, function($a, $b) use ($dayOrder) {
            $da = $dayOrder[$a['day_of_week']] ?? 7;
            $db = $dayOrder[$b['day_of_week']] ?? 7;
            if ($da !== $db) return $da - $db;
            return strtotime($a['start_time']) - strtotime($b['start_time']);
        });

        foreach ($schedules as $sched) {
            echo '<tr>';
            echo '<td><span class="day-badge">' . $sched['day_of_week'] . '</span></td>';
            echo '<td>' . htmlspecialchars($sched['subject']) . '</td>';
            echo '<td>' . date('g:i A', strtotime($sched['start_time'])) . ' - ' . date('g:i A', strtotime($sched['end_time'])) . '</td>';
            echo '<td>' . htmlspecialchars($sched['room']) . '</td>';
            
            // Only show Actions buttons if Admin
            if ($isAdmin) {
                echo '<td>';
                // Edit Button
                echo '<button class="btn-icon" onclick="openEditModal(' . 
                    $sched['id'] . ', ' . 
                    $sched['user_id'] . ', \'' . 
                    $sched['day_of_week'] . '\', \'' . 
                    htmlspecialchars($sched['subject'], ENT_QUOTES) . '\', \'' . 
                    date('H:i', strtotime($sched['start_time'])) . '\', \'' . 
                    date('H:i', strtotime($sched['end_time'])) . '\', \'' . 
                    htmlspecialchars($sched['room'], ENT_QUOTES) . 
                    '\')"><i class="fa-solid fa-pen"></i></button> ';
                // Delete Button
                echo '<button class="btn-icon danger" onclick="openDeleteModal(' . $sched['id'] . ', ' . $sched['user_id'] . ')"><i class="fa-solid fa-trash"></i></button>';
                echo '</td>';
            }
            
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
}
?>

<div class="main-body">
    <?php if ($error): ?> <div class="alert alert-error"><?= htmlspecialchars($error) ?></div> <?php endif; ?>
    <?php if ($success): ?> <div class="alert alert-success"><?= htmlspecialchars($success) ?></div> <?php endif; ?>

    <div class="stats-grid schedule-stats-grid">
         <?php if (!empty($selectedUserInfo) && isset($userStats)): ?>
            <?php elseif ($isAdmin): ?>
            <div class="stat-card stat-card-small">
                <div class="stat-icon yellow"><i class="fa-solid fa-hourglass-half"></i></div>
                <div class="stat-details"><p>Pending Approval</p><div class="stat-value yellow"><?= $pendingCount ?></div></div>
            </div>
        <?php endif; ?>
    </div>

    <div class="card" id="schedule-card">
        <div class="card-header card-header-flex">
            <div>
                <h3>Schedule Management</h3>
                <p>Manage and monitor class schedules</p>
            </div>
            <div class="card-header-actions">
                <?php if (!$isAdmin): ?>
                <button class="btn btn-primary btn-sm" onclick="openAddModal()">
                    <i class="fa-solid fa-plus"></i> Add Schedule
                </button>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($isAdmin): ?>
        <div style="padding: 1rem 1.5rem 0 1.5rem;">
            <form method="GET" class="schedule-filter-form" style="display: flex; gap: 10px;">
                <input type="hidden" name="tab" value="<?= $activeTab ?>"> <div class="form-group" style="flex: 1; margin: 0;">
                    <div class="input-icon-wrapper" style="position: relative;">
                        <i class="fa-solid fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af;"></i>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search by Faculty ID, Name, or User ID..." 
                               value="<?= htmlspecialchars($searchQuery) ?>" 
                               style="padding-left: 35px;">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <?php if(!empty($searchQuery)): ?>
                    <a href="schedule_management.php?tab=<?= $activeTab ?>" class="btn btn-secondary btn-sm">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab-btn <?= $activeTab === 'manage' ? 'active' : '' ?>" onclick="showScheduleTab(event, 'manage')">
                <i class="fa-solid fa-calendar-check"></i> Approved Schedules
            </button>
            <button class="tab-btn <?= $activeTab === 'pending' ? 'active' : '' ?>" onclick="showScheduleTab(event, 'pending')">
                <i class="fa-solid fa-file-circle-question"></i> Pending Approval 
                <?php if ($pendingCount > 0): ?>
                    <span class="notification-count-badge"><?= $pendingCount ?></span>
                <?php endif; ?>
            </button>
        </div>
        
        <div id="manageTab" class="tab-content <?= $activeTab === 'manage' ? 'active' : '' ?>">
            <div class="card-body">
                <?php if ($isAdmin): ?>
                    <?php if (empty($groupedApprovedSchedules)): ?>
                        <div class="empty-state">
                            <i class="fa-solid fa-calendar-xmark"></i>
                            <p>No approved schedules found matching your criteria.</p>
                        </div>
                    <?php else: ?>
                        <div class="user-schedule-accordion">
                            <?php foreach ($groupedApprovedSchedules as $uid => $userData): ?>
                                <div class="user-schedule-group">
                                    <button class="user-schedule-header" onclick="toggleScheduleGroup(this)">
                                        <div class="user-info-col">
                                            <div class="user-avatar-small"><?= strtoupper(substr($userData['user_info']['first_name'],0,1)) ?></div>
                                            <div>
                                                <span class="user-name"><?= htmlspecialchars($userData['user_info']['first_name'] . ' ' . $userData['user_info']['last_name']) ?></span>
                                                <span class="user-id">ID: <?= htmlspecialchars($userData['user_info']['faculty_id']) ?></span>
                                            </div>
                                        </div>
                                        <div class="user-stats-col">
                                            <span class="badge badge-blue"><?= number_format($userData['stats']['total_hours'], 1) ?> hrs</span>
                                        </div>
                                        <div class="user-toggle-col">
                                            <i class="fa-solid fa-chevron-down schedule-group-icon"></i>
                                        </div>
                                    </button>

                                    <div class="user-schedule-body">
                                        <?php 
                                            // Sort days/time... (Keep existing sort logic)
                                            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                            $dailySchedules = [];
                                            foreach ($userData['schedules'] as $s) { $dailySchedules[$s['day_of_week']][] = $s; }
                                        ?>
                                        <div class="daily-schedule-container">
                                            <?php foreach ($days as $day): ?>
                                                <?php if (!empty($dailySchedules[$day])): ?>
                                                    <div class="day-group">
                                                        <div class="day-header"><?= $day ?></div>
                                                        <table class="day-table">
                                                            <thead><tr><th>Subject / Duty</th><th>Time</th><th>Room</th><th style="width: 100px;">Actions</th></tr></thead>
                                                            <tbody>
                                                                <?php foreach ($dailySchedules[$day] as $sched): ?>
                                                                <tr>
                                                                    <td style="font-weight: 600;"><?= htmlspecialchars($sched['subject']) ?></td>
                                                                    <td><?= date('g:i A', strtotime($sched['start_time'])) ?> - <?= date('g:i A', strtotime($sched['end_time'])) ?></td>
                                                                    <td><?= htmlspecialchars($sched['room']) ?></td>
                                                                    <td>
                                                                        <button class="btn-icon" onclick="openEditModal(<?= $sched['id'] ?>, <?= $sched['user_id'] ?>, '<?= $sched['day_of_week'] ?>', '<?= htmlspecialchars($sched['subject'], ENT_QUOTES) ?>', '<?= date('H:i', strtotime($sched['start_time'])) ?>', '<?= date('H:i', strtotime($sched['end_time'])) ?>', '<?= htmlspecialchars($sched['room'], ENT_QUOTES) ?>')"><i class="fa-solid fa-pen"></i></button>
                                                                        <button class="btn-icon danger" onclick="openDeleteModal(<?= $sched['id'] ?>, <?= $sched['user_id'] ?>)"><i class="fa-solid fa-trash"></i></button>
                                                                    </td>
                                                                </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <?php renderScheduleTable($approvedSchedules, false, $isAdmin); ?>
                <?php endif; ?>
            </div>
        </div>

        <div id="pendingTab" class="tab-content <?= $activeTab === 'pending' ? 'active' : '' ?>">
            <div class="card-body">
                <?php if ($isAdmin): ?>
                    <?php if (empty($groupedPendingSchedules)): ?>
                        <div class="empty-state">
                            <i class="fa-solid fa-check-double"></i>
                            <p>No pending approvals found.</p>
                        </div>
                    <?php else: ?>
                        <form method="POST" id="bulkActionForm">
                            <input type="hidden" name="bulk_action_type" id="bulkActionInput">
                            
                            <div class="bulk-actions-bar" style="margin-bottom: 1rem; padding: 10px; background: #f3f4f6; border-radius: 8px; display: flex; gap: 10px; align-items: center;">
                                <strong style="margin-right: auto; color: #374151;">With Selected:</strong>
                                <button type="button" class="btn btn-success btn-sm" onclick="openBulkApproveModal()">
                                    <i class="fa-solid fa-check-double"></i> Approve
                                </button>
                                <button type="submit" onclick="document.getElementById('bulkActionInput').value='decline'; return confirm('Decline selected?');" class="btn btn-danger btn-sm">
                                    <i class="fa-solid fa-times"></i> Decline
                                </button>
                            </div>

                            <div class="user-schedule-accordion">
                                <?php foreach ($groupedPendingSchedules as $uid => $userData): ?>
                                    <div class="user-schedule-group">
                                        <div class="user-schedule-header" onclick="toggleScheduleGroup(this.parentNode.querySelector('.user-schedule-header'))">
                                            <div class="user-info-col">
                                                 <div class="user-avatar-small" style="background: #f59e0b;"><?= strtoupper(substr($userData['user_info']['first_name'],0,1)) ?></div>
                                                <div>
                                                    <span class="user-name"><?= htmlspecialchars($userData['user_info']['first_name'] . ' ' . $userData['user_info']['last_name']) ?></span>
                                                    <span class="user-id">ID: <?= htmlspecialchars($userData['user_info']['faculty_id']) ?></span>
                                                </div>
                                            </div>
                                            <div class="user-toggle-col">
                                                <button type="button" class="btn btn-xs btn-outline" onclick="event.stopPropagation(); selectAllForUser(this, '<?= $uid ?>')">Select All</button>
                                                <i class="fa-solid fa-chevron-down schedule-group-icon"></i>
                                            </div>
                                        </div>

                                        <div class="user-schedule-body">
                                            <table class="data-table" style="margin-top: 0;">
                                                <thead>
                                                    <tr>
                                                        <th style="width: 40px;"></th>
                                                        <th>Day</th>
                                                        <th>Subject / Duty</th>
                                                        <th>Time</th>
                                                        <th>Room</th>
                                                        <th style="text-align: right;">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($userData['schedules'] as $sched): ?>
                                                    <tr>
                                                        <td><input type="checkbox" name="selected_schedules[]" value="<?= $sched['id'] ?>" class="user-checkbox-<?= $uid ?>"></td>
                                                        <td><span class="day-badge"><?= $sched['day_of_week'] ?></span></td>
                                                        <td><?= htmlspecialchars($sched['subject']) ?></td>
                                                        <td><?= date('g:i A', strtotime($sched['start_time'])) ?> - <?= date('g:i A', strtotime($sched['end_time'])) ?></td>
                                                        <td><?= htmlspecialchars($sched['room']) ?></td>
                                                        <td style="text-align: right;">
                                                            <button type="button" class="btn btn-sm btn-success" onclick="submitSingleAction('approve', <?= $sched['id'] ?>)"><i class="fa-solid fa-check"></i></button>
                                                            <button type="button" class="btn btn-sm btn-danger" onclick="submitSingleAction('decline', <?= $sched['id'] ?>)"><i class="fa-solid fa-times"></i></button>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if (empty($pendingSchedules)): ?>
                        <div class="empty-state"><i class="fa-solid fa-check-double"></i><p>No pending approvals.</p></div>
                    <?php else: ?>
                        <table class="data-table">
                            <thead><tr><th>Day</th><th>Subject</th><th>Time</th><th>Room</th></tr></thead>
                            <tbody>
                                <?php foreach ($pendingSchedules as $sched): ?>
                                <tr>
                                    <td><span class="day-badge"><?= $sched['day_of_week'] ?></span></td>
                                    <td><?= htmlspecialchars($sched['subject']) ?></td>
                                    <td><?= date('g:i A', strtotime($sched['start_time'])) ?> - ...</td>
                                    <td><?= htmlspecialchars($sched['room']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function openBulkApproveModal() {
    const checkboxes = document.querySelectorAll('input[name="selected_schedules[]"]:checked');
    if (checkboxes.length === 0) {
        alert("Please select at least one schedule to approve.");
        return;
    }
    document.getElementById('bulkCountDisplay').textContent = checkboxes.length;
    openModal('bulkApproveModal');
}

function confirmBulkApprove() {
    document.getElementById('bulkActionInput').value = 'approve';
    document.getElementById('bulkActionForm').submit();
}

function submitSingleAction(type, schedId) {
    document.getElementById('single_schedule_id').value = schedId;
    if(type === 'approve') {
        document.getElementById('btn_approve').disabled = false;
        document.getElementById('btn_decline').disabled = true;
    } else {
        if(!confirm('Decline this schedule?')) return;
        document.getElementById('btn_approve').disabled = true;
        document.getElementById('btn_decline').disabled = false;
    }
    document.getElementById('singleActionForm').submit();
}

function showScheduleTab(e, tab) {
    document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
    document.getElementById(tab + 'Tab').style.display = 'block';
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    e.currentTarget.classList.add('active');
}
document.getElementById('<?= $activeTab ?>Tab').style.display = 'block';

function toggleScheduleGroup(btn) {
    const body = btn.nextElementSibling;
    const icon = btn.querySelector('.schedule-group-icon');
    if (body.style.maxHeight) { 
        body.style.maxHeight = null; 
        btn.classList.remove('active');
        icon.style.transform = 'rotate(0deg)';
    } else { 
        body.style.maxHeight = body.scrollHeight + "px"; 
        btn.classList.add('active');
        icon.style.transform = 'rotate(180deg)';
    }
}

function openDeleteModal(id, uid) {
    document.getElementById('deleteScheduleId').value = id;
    document.getElementById('deleteUserId').value = uid;
    openModal('deleteScheduleModal');
}

function openEditModal(id, uid, day, subject, start, end, room) {
    document.getElementById('editScheduleId').value = id;
    document.getElementById('editUserId').value = uid;
    document.getElementById('editDay').value = day;
    document.getElementById('editSubject').value = subject;
    document.getElementById('editStartTime').value = start;
    document.getElementById('editEndTime').value = end;
    document.getElementById('editRoom').value = room;
    openModal('editScheduleModal');
}

function openAddModal() {
    const list = document.getElementById('schedule-entry-list');
    list.innerHTML = ''; 
    addScheduleRow(); 
    openModal('addScheduleModal');
}

function addScheduleRow() {
    const list = document.getElementById('schedule-entry-list');
    const div = document.createElement('div');
    div.className = 'schedule-entry-row';
    // UPDATED LABELS AND PLACEHOLDERS BELOW
    div.innerHTML = `
        <div class="form-group">
            <label>Day</label>
            <select name="day_of_week[]" class="form-control" required>
                <option>Monday</option><option>Tuesday</option><option>Wednesday</option><option>Thursday</option><option>Friday</option><option>Saturday</option>
            </select>
        </div>
        <div class="form-group form-group-subject">
            <label>Subject / Duty</label>
            <input type="text" name="subject[]" placeholder="e.g., Math 101 or Office Duty" class="form-control" required>
        </div>
        <div class="form-group form-group-time">
            <label>Start</label>
            <input type="time" name="start_time[]" class="form-control" required>
        </div>
        <div class="form-group form-group-time">
            <label>End</label>
            <input type="time" name="end_time[]" class="form-control" required>
        </div>
        <div class="form-group form-group-room">
            <label>Room / Department</label>
            <input type="text" name="room[]" placeholder="Room or Dept" class="form-control">
        </div>
        <button type="button" class="btn btn-danger" onclick="this.parentElement.remove()" title="Remove">
            <i class="fa-solid fa-times"></i>
        </button>
    `;
    list.appendChild(div);
}

function toggleAll(source) {
    checkboxes = document.getElementsByName('selected_schedules[]');
    for(var i=0, n=checkboxes.length;i<n;i++) {
        checkboxes[i].checked = source.checked;
    }
}

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