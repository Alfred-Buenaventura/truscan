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
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="stats-grid schedule-stats-grid">
        <?php if (!empty($selectedUserInfo) && isset($userStats)): ?>
            <div class="stat-card stat-card-small">
                <div class="stat-icon emerald"><i class="fa-solid fa-user"></i></div>
                <div class="stat-details">
                    <p>Viewing Schedule</p>
                    <div class="stat-value-name"><?= htmlspecialchars($selectedUserInfo['first_name'] . ' ' . $selectedUserInfo['last_name']) ?></div>
                </div>
            </div>
            <div class="stat-card stat-card-small">
                <div class="stat-icon emerald"><i class="fa-solid fa-clock"></i></div>
                <div class="stat-details">
                    <p>Total Hours</p>
                    <div class="stat-value emerald"><?= number_format($userStats['total_hours'], 1) ?>h</div>
                </div>
            </div>
            <div class="stat-card stat-card-small">
                <div class="stat-icon blue"><i class="fa-solid fa-calendar-week"></i></div>
                <div class="stat-details">
                    <p>Duty Span</p>
                    <div class="stat-value blue"><?= number_format($userStats['duty_span'], 1) ?>h</div>
                </div>
            </div>
        <?php elseif ($isAdmin): ?>
            <div class="stat-card stat-card-small">
                <div class="stat-icon emerald"><i class="fa-solid fa-calendar-check"></i></div>
                <div class="stat-details"><p>Approved Schedules</p><div class="stat-value emerald"><?= $stats['total_schedules'] ?? 0 ?></div></div>
            </div>
            <div class="stat-card stat-card-small">
                <div class="stat-icon blue"><i class="fa-solid fa-book-open"></i></div>
                <div class="stat-details"><p>Total Classes</p><div class="stat-value blue"><?= $stats['total_subjects'] ?? 0 ?></div></div>
            </div>
            <div class="stat-card stat-card-small">
                <div class="stat-icon" style="background: #e0e7ff; color: #4f46e5;"><i class="fa-solid fa-users"></i></div>
                <div class="stat-details"><p>Total Staff</p><div class="stat-value" style="color: #4338ca;"><?= count($users) ?></div></div>
            </div>
             <div class="stat-card stat-card-small">
                <div class="stat-icon yellow"><i class="fa-solid fa-hourglass-half"></i></div>
                <div class="stat-details"><p>Pending Approval</p><div class="stat-value yellow"><?= count($pendingSchedules) ?></div></div>
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

        <div class="tabs">
            <button class="tab-btn <?= $activeTab === 'manage' ? 'active' : '' ?>" onclick="showScheduleTab(event, 'manage')">
                <i class="fa-solid fa-calendar-check"></i> Approved Schedules
            </button>
            <button class="tab-btn <?= $activeTab === 'pending' ? 'active' : '' ?>" onclick="showScheduleTab(event, 'pending')">
                <i class="fa-solid fa-file-circle-question"></i> Pending Approval 
                <?php if (count($pendingSchedules) > 0): ?>
                    <span class="notification-count-badge"><?= count($pendingSchedules) ?></span>
                <?php endif; ?>
            </button>
        </div>
        
        <div id="manageTab" class="tab-content <?= $activeTab === 'manage' ? 'active' : '' ?>">
            <div class="card-body">
                
                <form method="GET" class="schedule-filter-form">
                    <div class="schedule-filter-grid">
                        <?php if ($isAdmin): ?>
                        <div class="form-group">
                            <label>Filter by User</label>
                            <select name="user_id" class="form-control" onchange="this.form.submit()">
                                <option value="">View All Users</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>" <?= $selectedUserId == $user['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                </form>

                <?php if ($isAdmin && empty($filters['user_id'])): ?>
                    <?php if (empty($groupedApprovedSchedules)): ?>
                        <div class="empty-state">
                            <i class="fa-solid fa-calendar-xmark"></i>
                            <p>No approved schedules found.</p>
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
                                                <span class="user-id"><?= htmlspecialchars($userData['user_info']['faculty_id']) ?></span>
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
                                            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                            $dailySchedules = [];
                                            foreach ($userData['schedules'] as $s) {
                                                $dailySchedules[$s['day_of_week']][] = $s;
                                            }
                                        ?>
                                        
                                        <div class="daily-schedule-container">
                                            <?php foreach ($days as $day): ?>
                                                <?php if (!empty($dailySchedules[$day])): ?>
                                                    <div class="day-group">
                                                        <div class="day-header"><?= $day ?></div>
                                                        <table class="day-table">
                                                            <thead>
                                                                <tr>
                                                                    <th>Subject / Duty</th>
                                                                    <th>Time</th>
                                                                    <th>Room / Department</th>
                                                                    <th style="width: 100px;">Actions</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php 
                                                                usort($dailySchedules[$day], function($a, $b) {
                                                                    return strtotime($a['start_time']) - strtotime($b['start_time']);
                                                                });
                                                                foreach ($dailySchedules[$day] as $sched): 
                                                                ?>
                                                                <tr>
                                                                    <td style="font-weight: 600;"><?= htmlspecialchars($sched['subject']) ?></td>
                                                                    <td>
                                                                        <div class="time-pill">
                                                                            <?= date('g:i A', strtotime($sched['start_time'])) ?> - <?= date('g:i A', strtotime($sched['end_time'])) ?>
                                                                        </div>
                                                                    </td>
                                                                    <td><?= htmlspecialchars($sched['room']) ?></td>
                                                                    <td>
                                                                        <button class="btn-icon" onclick="openEditModal(
                                                                            <?= $sched['id'] ?>,
                                                                            <?= $sched['user_id'] ?>,
                                                                            '<?= $sched['day_of_week'] ?>',
                                                                            '<?= htmlspecialchars($sched['subject'], ENT_QUOTES) ?>',
                                                                            '<?= date('H:i', strtotime($sched['start_time'])) ?>',
                                                                            '<?= date('H:i', strtotime($sched['end_time'])) ?>',
                                                                            '<?= htmlspecialchars($sched['room'], ENT_QUOTES) ?>'
                                                                        )" title="Edit">
                                                                            <i class="fa-solid fa-pen"></i>
                                                                        </button>
                                                                        <button class="btn-icon danger" onclick="openDeleteModal(<?= $sched['id'] ?>, <?= $sched['user_id'] ?>)" title="Delete">
                                                                            <i class="fa-solid fa-trash"></i>
                                                                        </button>
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
                    <?php if (empty($approvedSchedules)): ?>
                         <div class="empty-state">
                            <i class="fa-solid fa-calendar-plus"></i>
                            <p>No schedules added yet.</p>
                        </div>
                    <?php else: ?>
                        <?php renderScheduleTable($approvedSchedules, false, $isAdmin); ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <div id="pendingTab" class="tab-content <?= $activeTab === 'pending' ? 'active' : '' ?>">
            <div class="card-body">
                <?php if (empty($pendingSchedules)): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-check-double"></i>
                        <p>No pending approvals.</p>
                    </div>
                <?php else: ?>
                    
                    <form method="POST" id="bulkActionForm">
                        <input type="hidden" name="bulk_action_type" id="bulkActionInput">

                        <?php if ($isAdmin): ?>
                        <div class="bulk-actions-bar" style="margin-bottom: 1rem; display: flex; gap: 10px; align-items: center;">
                            <strong style="margin-right: auto;">Bulk Actions:</strong>
                            
                            <button type="button" class="btn btn-success btn-sm" onclick="openBulkApproveModal()">
                                <i class="fa-solid fa-check-double"></i> Approve Selected
                            </button>
                            
                            <button type="submit" onclick="document.getElementById('bulkActionInput').value='decline'; return confirm('Decline selected?');" class="btn btn-danger btn-sm">
                                <i class="fa-solid fa-times"></i> Decline Selected
                            </button>
                        </div>
                        <?php endif; ?>

                        <table class="data-table">
                            <thead>
                                <tr>
                                    <?php if ($isAdmin): ?>
                                        <th style="width: 40px;"><input type="checkbox" onclick="toggleAll(this)"></th>
                                        <th>User</th>
                                    <?php endif; ?>
                                    <th>Day</th>
                                    <th>Subject / Duty</th>
                                    <th>Time</th>
                                    <th>Room / Department</th>
                                    <?php if ($isAdmin): ?>
                                        <th style="text-align: right;">Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingSchedules as $sched): ?>
                                <tr>
                                    <?php if ($isAdmin): ?>
                                        <td><input type="checkbox" name="selected_schedules[]" value="<?= $sched['id'] ?>"></td>
                                        <td><strong><?= htmlspecialchars($sched['first_name'] . ' ' . $sched['last_name']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($sched['faculty_id']) ?></small></td>
                                    <?php endif; ?>
                                    <td><span class="day-badge"><?= $sched['day_of_week'] ?></span></td>
                                    <td><?= htmlspecialchars($sched['subject']) ?></td>
                                    <td><?= date('g:i A', strtotime($sched['start_time'])) ?> - <?= date('g:i A', strtotime($sched['end_time'])) ?></td>
                                    <td><?= htmlspecialchars($sched['room']) ?></td>
                                    
                                    <?php if ($isAdmin): ?>
                                    <td style="text-align: right;">
                                        <button type="button" class="btn btn-sm btn-success" onclick="submitSingleAction('approve', <?= $sched['id'] ?>)"><i class="fa-solid fa-check"></i></button>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="submitSingleAction('decline', <?= $sched['id'] ?>)"><i class="fa-solid fa-times"></i></button>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </form>
                    
                    <form method="POST" id="singleActionForm" style="display:none;">
                        <input type="hidden" name="schedule_id" id="single_schedule_id">
                        <input type="hidden" name="approve_schedule" id="btn_approve" disabled>
                        <input type="hidden" name="decline_schedule" id="btn_decline" disabled>
                    </form>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div id="bulkApproveModal" class="modal">
    <div class="modal-content modal-small">
        <div class="modal-header">
            <h3><i class="fa-solid fa-circle-check"></i> Confirm Approval</h3>
            <button type="button" class="modal-close" onclick="closeModal('bulkApproveModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p style="font-size: 1.1rem; color: var(--gray-800); margin-bottom: 0.5rem;">Are you sure you want to approve the selected schedules?</p>
            <p style="font-size: 0.9rem; color: var(--gray-600);"><span id="bulkCountDisplay">0</span> schedules will be added to the official calendar.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('bulkApproveModal')">Cancel</button>
            <button type="button" class="btn btn-success" onclick="confirmBulkApprove()"><i class="fa-solid fa-check"></i> Yes, Approve</button>
        </div>
    </div>
</div>

<div id="deleteScheduleModal" class="modal">
    <div class="modal-content modal-small">
        <form method="POST">
            <div class="modal-header">
                <h3><i class="fa-solid fa-trash"></i> Delete?</h3>
                <button type="button" class="modal-close" onclick="closeModal('deleteScheduleModal')">&times;</button>
            </div>
            <div class="modal-body"><p>Are you sure you want to delete this schedule?</p>
                <input type="hidden" name="schedule_id_delete" id="deleteScheduleId">
                <input type="hidden" name="user_id_delete" id="deleteUserId">
            </div>
            <div class="modal-footer"><button type="submit" name="delete_schedule" class="btn btn-danger">Delete</button></div>
        </form>
    </div>
</div>

<div id="editScheduleModal" class="modal">
    <div class="modal-content">
        <form method="POST">
            <input type="hidden" name="schedule_id_edit" id="editScheduleId">
            <input type="hidden" name="user_id_edit" id="editUserId">
            
            <div class="modal-header"><h3><i class="fa-solid fa-pen-to-square"></i> Edit Schedule</h3><button type="button" class="modal-close" onclick="closeModal('editScheduleModal')">&times;</button></div>
            <div class="modal-body">
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Day of Week</label>
                    <select name="day_of_week_edit" id="editDay" class="form-control">
                        <option>Monday</option><option>Tuesday</option><option>Wednesday</option><option>Thursday</option><option>Friday</option><option>Saturday</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Subject / Duty</label>
                    <input type="text" name="subject_edit" id="editSubject" class="form-control" required>
                </div>
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>Start Time</label>
                    <input type="time" name="start_time_edit" id="editStartTime" class="form-control" required>
                </div>
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label>End Time</label>
                    <input type="time" name="end_time_edit" id="editEndTime" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Room / Department</label>
                    <input type="text" name="room_edit" id="editRoom" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editScheduleModal')">Cancel</button>
                <button type="submit" name="edit_schedule" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<div id="deleteScheduleModal" class="modal">
    <div class="modal-content modal-small">
        <form method="POST">
            <div class="modal-header danger"><h3>Delete?</h3><button type="button" class="modal-close" onclick="closeModal('deleteScheduleModal')">&times;</button></div>
            <div class="modal-body"><p>Are you sure you want to delete this schedule?</p>
                <input type="hidden" name="schedule_id_delete" id="deleteScheduleId">
                <input type="hidden" name="user_id_delete" id="deleteUserId">
            </div>
            <div class="modal-footer"><button type="submit" name="delete_schedule" class="btn btn-danger">Delete</button></div>
        </form>
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