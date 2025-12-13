<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <div class="sidebar-logo-icon">
                <i class="fa-solid fa-fingerprint"></i>
            </div>
            <div class="sidebar-title">
                <h1>BPC Attendance</h1>
                <p><?= Helper::isAdmin() ? 'Admin Panel' : 'Staff Dashboard' ?></p>
            </div>
        </div>
        <button class="btn sidebar-toggle-btn" id="sidebarToggle" title="Toggle Sidebar">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <a href="index.php" class="nav-item">
            <i class="fa-solid fa-house nav-icon"></i>
            <span class="nav-text">Home</span>
        </a>
        
        <?php if (Helper::isAdmin()): ?>
        <a href="create_account.php" class="nav-item">
            <i class="fa-solid fa-user-plus nav-icon"></i>
            <span class="nav-text">Create User Account</span>
        </a>
        <a href="create_admin.php" class="nav-item">
            <i class="fa-solid fa-user-shield nav-icon"></i>
            <span class="nav-text">Create Admin Account</span>
        </a>
        <a href="complete_registration.php" class="nav-item">
             <i class="fa-solid fa-fingerprint nav-icon"></i>
            <span class="nav-text">Fingerprint Registration</span>
        </a>
        <?php endif; ?>
        
        <a href="attendance_reports.php" class="nav-item">
            <i class="fa-solid fa-clipboard-list nav-icon"></i>
            <span class="nav-text">Attendance Reports</span>
        </a>
        <a href="schedule_management.php" class="nav-item">
            <i class="fa-solid fa-calendar-days nav-icon"></i>
            <span class="nav-text">Schedule Management</span>
        </a>
        
        <button type="button" class="nav-item nav-item-button" onclick="openModal('notificationsModal')" id="notificationsBtn">
            <i class="fa-solid fa-bell nav-icon"></i>
            <span class="nav-text">Notifications</span>
            <?php
            // FIX: Use Singleton Instance
            $db = Database::getInstance();
            $stmt = $db->query("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0", [$_SESSION['user_id']], "i");
            $unreadCount = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
            if ($unreadCount > 0):
            ?>
            <span class="notification-badge"><?= $unreadCount ?></span>
            <?php endif; ?>
        </button>
    </nav>

    <div class="sidebar-footer">
    
    <div id="settings-menu">
        <a href="profile.php" class="settings-menu-item">
            <i class="fa-solid fa-user"></i>
            <span>My Profile</span>
        </a>
        <a href="about.php" class="settings-menu-item">
            <i class="fa-solid fa-circle-info"></i>
            <span>About Us</span>
        </a>
        <?php if (!Helper::isAdmin()): ?>
        <a href="contact.php" class="settings-menu-item">
            <i class="fa-solid fa-envelope"></i>
            <span>Contact Support</span>
        </a>
        <?php endif; ?>
    </div>
    
    <div class="user-info">
        <div class="user-info-inner">
            <div class="user-avatar">
                <?= strtoupper(substr($_SESSION['first_name'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="user-details">
                <p>Logged in as</p>
                <div class="user-name"><?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?></div>
            </div>

            <button class="btn user-settings-btn" id="userSettingsBtn" type="button">
                <i class="fa-solid fa-gear"></i>
            </button>
        </div>
    </div>

    <button class="btn logout-btn" onclick="showLogoutConfirm()">
         <i class="fa-solid fa-right-from-bracket logout-icon"></i>
        <span class="logout-text">Log out</span>
    </button>
</div>

</aside>

<div id="notificationsModal" class="modal">
    <div class="modal-content modal-small">
        <div class="modal-header">
            <h3><i class="fa-solid fa-bell"></i> Notifications</h3>
            <button type="button" class="modal-close" onclick="closeModal('notificationsModal')">
                <i class="fa-solid fa-times"></i>
            </button>
        </div>
        <div class="modal-body" style="max-height: 60vh; overflow-y: auto;">
            <?php
            // FIX: Use Singleton Instance
            $db = Database::getInstance();
            $stmt = $db->query(
                "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 20", 
                [$_SESSION['user_id']], 
                "i"
            );
            $notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            if (empty($notifications)):
            ?>
                <div style="text-align: center; padding: 3rem; color: var(--gray-500);">
                    <i class="fa-solid fa-bell-slash" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                    <p>No notifications yet</p>
                </div>
            <?php else: ?>
                <div class="notifications-list">
                    <?php foreach ($notifications as $notif): ?>
                        <div class="notification-item <?= $notif['is_read'] ? 'read' : 'unread' ?>" 
                             data-id="<?= $notif['id'] ?>">
                            <div class="notification-icon <?= $notif['type'] ?>">
                                <?php
                                $icon = 'fa-info-circle';
                                if ($notif['type'] === 'success') $icon = 'fa-check-circle';
                                if ($notif['type'] === 'warning') $icon = 'fa-exclamation-triangle';
                                if ($notif['type'] === 'error') $icon = 'fa-times-circle';
                                ?>
                                <i class="fa-solid <?= $icon ?>"></i>
                            </div>
                            <div class="notification-content">
                                <p><?= htmlspecialchars($notif['message']) ?></p>
                                <span class="notification-time">
                                    <?= date('M d, Y g:i A', strtotime($notif['created_at'])) ?>
                                </span>
                            </div>
                            <?php if (!$notif['is_read']): ?>
                            <button class="notification-mark-read" onclick="markAsRead(<?= $notif['id'] ?>)" title="Mark as read">
                                <i class="fa-solid fa-check"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="modal-footer">
            <?php if (!empty($notifications)): ?>
            <button type="button" class="btn btn-primary" onclick="markAllAsRead()">
                <i class="fa-solid fa-check-double"></i> Mark All as Read
            </button>
            <?php endif; ?>
            <button type="button" class="btn btn-secondary" onclick="closeModal('notificationsModal')">Close</button>
        </div>
    </div>
</div>

<script>
function markAsRead(notificationId) {
    fetch('api.php?action=mark_notification_read', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ notification_id: notificationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const notifItem = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
            if (notifItem) {
                notifItem.classList.remove('unread');
                notifItem.classList.add('read');
                const markBtn = notifItem.querySelector('.notification-mark-read');
                if (markBtn) markBtn.remove();
            }
            const badge = document.querySelector('#notificationsBtn .notification-badge');
            if (badge) {
                let count = parseInt(badge.textContent) - 1;
                if (count <= 0) badge.remove();
                else badge.textContent = count;
            }
        }
    });
}

function markAllAsRead() {
    fetch('api.php?action=mark_all_notifications_read', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.querySelectorAll('.notification-item.unread').forEach(item => {
                item.classList.remove('unread');
                item.classList.add('read');
                const markBtn = item.querySelector('.notification-mark-read');
                if (markBtn) markBtn.remove();
            });
            const badge = document.querySelector('#notificationsBtn .notification-badge');
            if (badge) badge.remove();
            closeModal('notificationsModal');
        }
    });
}
</script>