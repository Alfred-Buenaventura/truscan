<?php 
require_once __DIR__ . '/partials/header.php'; 
?>
<div class="main-body">
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Stats Grid -->
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
                <div class="stat-icon emerald"><i class="fa-solid fa-users"></i></div>
                <div class="stat-details">
                    <p>Staff with Schedules</p>
                    <div class="stat-value emerald"><?= $stats['total_users_with_schedules'] ?? 0 ?></div>
                </div>
            </div>
            <div class="stat-card stat-card-small">
                <div class="stat-icon blue"><i class="fa-solid fa-list-check"></i></div>
                <div class="stat-details">
                    <p>Total Classes</p>
                    <div class="stat-value blue"><?= $stats['total_schedules'] ?? 0 ?></div>
                </div>
            </div>
             <div class="stat-card stat-card-small">
                <div class="stat-icon yellow"><i class="fa-solid fa-hourglass-half"></i></div>
                <div class="stat-details">
                    <p>Pending Approval</p>
                    <div class="stat-value yellow"><?= count($pendingSchedules) ?></div>
                </div>
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
        
        <!-- MANAGE TAB (Approved Schedules) -->
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
                    <!-- ADMIN VIEW: LIST OF USERS (ACCORDION) -->
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
                                                                    <th>Subject</th>
                                                                    <th>Time</th>
                                                                    <th>Room</th>
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
                                                                        <!-- NEW EDIT BUTTON -->
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
                    <!-- SINGLE USER VIEW (Standard Table) -->
                    <?php if (empty($approvedSchedules)): ?>
                         <div class="empty-state">
                            <i class="fa-solid fa-calendar-plus"></i>
                            <p>No schedules added yet.</p>
                        </div>
                    <?php else: ?>
                        <?php renderScheduleTable($approvedSchedules, false); ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- PENDING TAB -->
        <div id="pendingTab" class="tab-content <?= $activeTab === 'pending' ? 'active' : '' ?>">
            <div class="card-body">
                <?php if (empty($pendingSchedules)): ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-check-double"></i>
                        <p>No pending approvals.</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <?php if ($isAdmin): ?><th>User</th><?php endif; ?>
                                <th>Day</th><th>Subject</th><th>Time</th><th>Room</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingSchedules as $sched): ?>
                            <tr>
                                <?php if ($isAdmin): ?>
                                    <td>
                                        <strong><?= htmlspecialchars($sched['first_name'] . ' ' . $sched['last_name']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($sched['faculty_id']) ?></small>
                                    </td>
                                <?php endif; ?>
                                <td><span class="day-badge"><?= $sched['day_of_week'] ?></span></td>
                                <td><?= htmlspecialchars($sched['subject']) ?></td>
                                <td><?= date('g:i A', strtotime($sched['start_time'])) ?> - <?= date('g:i A', strtotime($sched['end_time'])) ?></td>
                                <td><?= htmlspecialchars($sched['room']) ?></td>
                                <td style="text-align: right;">
                                    <?php if ($isAdmin): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="schedule_id" value="<?= $sched['id'] ?>">
                                            <input type="hidden" name="user_id" value="<?= $sched['user_id'] ?>">
                                            <input type="hidden" name="subject" value="<?= htmlspecialchars($sched['subject']) ?>">
                                            <button type="submit" name="approve_schedule" class="btn btn-sm btn-success"><i class="fa-solid fa-check"></i></button>
                                            <button type="submit" name="decline_schedule" class="btn btn-sm btn-danger"><i class="fa-solid fa-times"></i></button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-danger" onclick="openDeleteModal(<?= $sched['id'] ?>, <?= $sched['user_id'] ?>)"><i class="fa-solid fa-trash"></i></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>

<?php
// Helper to render table rows for single user view
function renderScheduleTable($schedules, $nested) {
    echo '<table class="data-table"><thead><tr><th>Day</th><th>Subject</th><th>Time</th><th>Room</th><th>Actions</th></tr></thead><tbody>';
    
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
        echo '<td>';
        echo '<button class="btn-icon danger" onclick="openDeleteModal(' . $sched['id'] . ', ' . $sched['user_id'] . ')"><i class="fa-solid fa-trash"></i></button>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}
?>

<!-- Modals -->
<div id="addScheduleModal" class="modal">
    <div class="modal-content modal-lg">
        <form method="POST">
            <div class="modal-header"><h3><i class="fa-solid fa-plus"></i> Add Schedule</h3><button type="button" class="modal-close" onclick="closeModal('addScheduleModal')">&times;</button></div>
            <div class="modal-body">
                <div id="schedule-entry-list"></div> <button type="button" class="btn btn-secondary" onclick="addScheduleRow()">+ Add Row</button>
            </div>
            <div class="modal-footer">
                <button type="submit" name="add_schedule" class="btn btn-primary">Submit</button>
            </div>
        </form>
    </div>
</div>

<!-- NEW: Edit Schedule Modal -->
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
                    <label>Subject</label>
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
                    <label>Room</label>
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
function showScheduleTab(e, tab) {
    document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
    document.getElementById(tab + 'Tab').style.display = 'block';
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    e.currentTarget.classList.add('active');
}
document.getElementById('<?= $activeTab ?>Tab').style.display = 'block'; // Init

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

// --- NEW: Open Edit Modal ---
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
    div.innerHTML = `
        <div class="form-group">
            <label>Day</label>
            <select name="day_of_week[]" class="form-control" required>
                <option>Monday</option>
                <option>Tuesday</option>
                <option>Wednesday</option>
                <option>Thursday</option>
                <option>Friday</option>
                <option>Saturday</option>
            </select>
        </div>
        <div class="form-group">
            <label>Subject</label>
            <input type="text" name="subject[]" placeholder="Enter subject name" class="form-control" required>
        </div>
        <div class="form-group">
            <label>Start Time</label>
            <input type="time" name="start_time[]" class="form-control" required>
        </div>
        <div class="form-group">
            <label>End Time</label>
            <input type="time" name="end_time[]" class="form-control" required>
        </div>
        <div class="form-group form-group-room">
            <label>Room <span style="font-size: 0.75rem; color: var(--gray-500);">(Optional)</span></label>
            <input type="text" name="room[]" placeholder="e.g., Room 101" class="form-control">
        </div>
        <button type="button" class="btn btn-danger" onclick="this.parentElement.remove()" title="Remove">
            <i class="fa-solid fa-times"></i>
        </button>
    `;
    list.appendChild(div);
}

// Modal Helpers
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

// Close modal on outside click
window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            closeModal(modal.id);
        }
    });
};
</script>