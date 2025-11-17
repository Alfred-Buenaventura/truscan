<?php 
require_once __DIR__ . '/partials/header.php'; 
?>
<?php if ($isAdmin): ?>
    <!-- Admin Dashboard (unchanged) -->
    <div class="main-body">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon emerald">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div class="stat-details">
                    <p>Total Users</p>
                    <div class="stat-value emerald"><?= $totalUsers ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon yellow">
                    <i class="fa-solid fa-user-clock"></i>
                </div>
                <div class="stat-details">
                    <p>Active Today</p>
                    <div class="stat-value"><?= $activeToday ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon red">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <div class="stat-details">
                    <p>Pending Registration</p>
                    <div class="stat-value red"><?= $pendingRegistrations ?></div>
                </div>
            </div>
        </div>

        <div class="card" id="recent-activity-card">
            <div class="card-header">
                <h3>Recent Activity</h3>
            </div>
            <div class="card-body">
                <?php if (empty($activityLogs)): ?>
                    <p style="text-align: center; color: var(--gray-500); padding: 2rem;">No recent activity found.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th>Details</th>
                                <th>User</th>
                                <th>Time & Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activityLogs as $log): ?>
                                <tr>
                                    <td><?= htmlspecialchars($log['action']) ?></td>
                                    <td><?= htmlspecialchars($log['description']) ?></td>
                                    <td>
                                        <?= htmlspecialchars(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? 'System')) ?>
                                    </td>
                                    <td><?= date('M d, Y g:i A', strtotime($log['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                 <div style="text-align: right; margin-top: 1rem;">
                     <a href="activity_log.php" class="btn btn-sm btn-secondary">View All Activity &rarr;</a>
                 </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Admin Shortcuts</h3>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                    <a href="create_account.php" class="btn btn-primary" style="padding: 1rem; text-align: left; justify-content: flex-start; font-size: 1rem;">
                        <i class="fa-solid fa-user-plus" style="width: 20px; text-align: center; margin-right: 0.75rem; font-size: 1.2rem;"></i>
                        <span>Create User Accounts</span>
                    </a>
                    <a href="attendance_reports.php" class="btn btn-primary" style="padding: 1rem; text-align: left; justify-content: flex-start; font-size: 1rem;">
                        <i class="fa-solid fa-file-invoice" style="width: 20px; text-align: center; margin-right: 0.75rem; font-size: 1.2rem;"></i>
                        <span>View Attendance Reports</span>
                    </a>
                    <a href="schedule_management.php" class="btn btn-primary" style="padding: 1rem; text-align: left; justify-content: flex-start; font-size: 1rem;">
                        <i class="fa-solid fa-calendar-days" style="width: 20px; text-align: center; margin-right: 0.75rem; font-size: 1.2rem;"></i>
                        <span>Manage Schedules</span>
                    </a>
                    <a href="display.php" target="_blank" class="btn btn-primary" style="padding: 1rem; text-align: left; justify-content: flex-start; font-size: 1rem; background-color: var(--blue-600); border-color: var(--blue-600);">
                        <i class="fa-solid fa-desktop" style="width: 20px; text-align: center; margin-right: 0.75rem; font-size: 1.2rem;"></i>
                        <span>Launch Kiosk Display</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- USER DASHBOARD (Updated) -->
    <div class="main-body user-dashboard-body">
        
        <!-- Link to History Page -->
        <div style="display: flex; justify-content: flex-end; margin-bottom: 1rem;">
            <a href="attendance_history.php" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-clock-rotate-left"></i> View My Attendance History
            </a>
        </div>

        <div class="ud-grid">
            <!-- Registration Status Card -->
            <div class="ud-card">
                <h3 class="ud-card-title emerald-header">
                    <i class="fa-solid fa-clipboard-check"></i> Registration Status
                </h3>
                <div class="ud-card-content">
                    <div class="ud-card-row">
                        <span class="ud-card-label">Account Created</span>
                        <span class="ud-badge completed">Completed</span>
                    </div>
                    <div class="ud-card-row">
                        <span class="ud-card-label">Fingerprint Registered</span>
                        <?php if ($fingerprint_registered): ?>
                            <span class="ud-badge completed">Completed</span>
                        <?php else: ?>
                            <span class="ud-badge pending">Pending</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Today's Attendance Card -->
            <div class="ud-card">
                <h3 class="ud-card-title emerald-header">
                    <i class="fa-solid fa-calendar-check"></i> Today's Attendance
                </h3>
                <div class="ud-card-content">
                    <div class="ud-card-row">
                        <span class="ud-card-label">Status</span>
                        <?php
                            $status = $attendance['status'] ?? 'Not Present';
                            $statusClass = 'not-present';
                            if ($status === 'Present' || $status === 'On-time') $statusClass = 'completed';
                            if ($status === 'Late') $statusClass = 'pending';
                        ?>
                        <span class="ud-badge <?= $statusClass ?>"><?= htmlspecialchars($status) ?></span>
                    </div>
                    <div class="ud-card-row">
                        <span class="ud-card-label">Time In</span>
                        <span class="ud-card-value">
                            <?= isset($attendance['time_in']) ? date('g:i A', strtotime($attendance['time_in'])) : '------' ?>
                        </span>
                    </div>
                    <div class="ud-card-row">
                        <span class="ud-card-label">Time Out</span>
                        <span class="ud-card-value">
                            <?= isset($attendance['time_out']) ? date('g:i A', strtotime($attendance['time_out'])) : '------' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- My Recent Activity Card -->
        <div class="ud-card ud-activity-card">
            <h3 class="ud-card-title emerald-header">
                <i class="fa-solid fa-history"></i> My Recent Activity
            </h3>
            <div class="ud-card-content">
                <?php if (empty($activityLogs)): ?>
                    <div class="ud-activity-empty">
                        <i class="fa-solid fa-chart-line"></i>
                        <span>No activity recorded.</span>
                    </div>
                <?php else: ?>
                    <div class="ud-activity-list">
                        <?php foreach ($activityLogs as $log): ?>
                            <div class="ud-activity-item">
                                <div class="ud-activity-details">
                                    <strong class="ud-activity-action"><?= htmlspecialchars($log['action']) ?></strong>
                                    <span class="ud-activity-description"><?= htmlspecialchars($log['description']) ?></span>
                                </div>
                                <span class="ud-activity-time">
                                    <?= date('M d, Y g:i A', strtotime($log['created_at'])) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
             </div>
        </div>

        <!-- Note Container -->
        <div class="page-hint-card">
            <div class="page-hint-icon">
                <i class="fa-solid fa-lightbulb"></i>
            </div>
            <div class="page-hint-content">
                <h4>Note!</h4>
                <p>
                    This is your main dashboard. You can quickly see your registration status and check if your attendance for today has been recorded. Use the "View My Attendance History" button to see your complete attendance records.
                </p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/partials/footer.php'; ?>