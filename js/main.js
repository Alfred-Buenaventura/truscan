/* =========================================
   1. GLOBAL MODAL FUNCTIONS
   ========================================= */

// Function to open a modal by ID
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
    } else {
        console.error("Modal not found: " + modalId);
    }
}

// Function to close a modal by ID
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto'; // Restore scrolling
    }
}

// Global Logout Confirmation Function
// This is called by the "Log out" button in the sidebar/footer
window.showLogoutConfirm = function() {
    const modal = document.getElementById('logoutConfirmModal');
    if (modal) {
        openModal('logoutConfirmModal');
    } else {
        // Fallback: If modal is missing, use standard browser confirm
        if (confirm("Are you sure you want to log out?")) {
            window.location.href = 'logout.php';
        }
    }
};

// Close any modal if the user clicks on the dark overlay (outside the modal content)
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
};


/* =========================================
   2. ROBUST EVENT HANDLERS (DELEGATION)
   ========================================= */
// We attach a single listener to the document to handle clicks.
// This ensures buttons work even if they are added dynamically or if DOM loading is tricky.

document.addEventListener('click', function(e) {
    
    // --- A. SETTINGS MENU TOGGLE ---
    const settingsBtn = e.target.closest('#userSettingsBtn');
    const settingsMenu = document.getElementById('settings-menu');

    if (settingsBtn && settingsMenu) {
        e.preventDefault();
        e.stopPropagation();
        settingsMenu.classList.toggle('active');
        return;
    }

    // --- B. CLOSE SETTINGS MENU (WHEN CLICKING OUTSIDE) ---
    if (settingsMenu && settingsMenu.classList.contains('active')) {
        // If click is NOT on the menu AND NOT on the button, close it
        if (!e.target.closest('#settings-menu') && !e.target.closest('#userSettingsBtn')) {
            settingsMenu.classList.remove('active');
        }
    }

    // --- C. SIDEBAR TOGGLE ---
    const sidebarToggle = e.target.closest('#sidebarToggle');
    if (sidebarToggle) {
        const sidebar = document.getElementById('sidebar');
        const dashboardContainer = document.getElementById('dashboardContainer');
        
        if (sidebar && dashboardContainer) {
            sidebar.classList.toggle('collapsed');
            dashboardContainer.classList.toggle('sidebar-collapsed');
            
            // Save state to LocalStorage so it persists across refreshes
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed ? 'true' : 'false');
        }
    }
});


/* =========================================
   3. PAGE INITIALIZATION
   ========================================= */

document.addEventListener('DOMContentLoaded', function() {
    
    // --- A. RESTORE SIDEBAR STATE ---
    try {
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        const sidebar = document.getElementById('sidebar');
        const dashboardContainer = document.getElementById('dashboardContainer');
        
        if (isCollapsed && sidebar && dashboardContainer) {
            sidebar.classList.add('collapsed');
            dashboardContainer.classList.add('sidebar-collapsed');
        }
    } catch (err) {
        console.error("Sidebar restore error:", err);
    }

    // --- B. LIVE CLOCK (HEADER) ---
    try {
        const liveTimeEl = document.getElementById('live-time');
        const liveDateEl = document.getElementById('live-date');
        
        if (liveTimeEl && liveDateEl) {
            const updateTime = () => {
                const now = new Date();
                // 12-hour format with AM/PM
                liveTimeEl.textContent = now.toLocaleTimeString('en-US', { 
                    hour: 'numeric', 
                    minute: '2-digit', 
                    hour12: true 
                });
                // Full Date format
                liveDateEl.textContent = now.toLocaleDateString('en-US', { 
                    weekday: 'long', 
                    month: 'long', 
                    day: 'numeric' 
                });
            };
            
            updateTime(); // Run immediately
            setInterval(updateTime, 30000); // Update every 30 seconds
        }
    } catch (err) {
        console.error("Clock init error:", err);
    }

    // --- C. FINGERPRINT SCANNER STATUS WIDGET ---
    // Wrapped in try-catch so it doesn't break the rest of the page if missing
    try {
        const scannerWidget = document.getElementById('scanner-status-widget');
        if (scannerWidget) {
            initScannerSocket(scannerWidget);
        }
    } catch (err) {
        // Scanner widget might not exist on all pages (e.g., user dashboard), which is fine.
        // console.log("Scanner widget not active.");
    }
});


/* =========================================
   4. SCANNER WEBSOCKET LOGIC
   ========================================= */

function initScannerSocket(widget) {
    const statusText = widget.querySelector('.scanner-status-text-sub');
    const statusBadge = widget.querySelector('.scanner-status-badge');
    const statusAction = widget.querySelector('.scanner-status-action');
    const iconBadge = widget.querySelector('.scanner-icon-badge');
    
    function setStatus(online, msg) {
        if (online) {
            // Device is Connected
            widget.classList.add('online');
            widget.classList.remove('offline');
            if (statusText) statusText.textContent = 'Device Connected';
            if (statusBadge) statusBadge.textContent = 'ONLINE';
            if (statusAction) statusAction.textContent = 'Ready to scan';
            if (iconBadge) iconBadge.style.display = 'none';
        } else {
            // Device is Offline / Error
            widget.classList.remove('online');
            widget.classList.add('offline');
            if (statusText) statusText.textContent = msg || 'Check connection';
            if (statusBadge) statusBadge.textContent = 'OFFLINE';
            if (statusAction) statusAction.textContent = 'Check connection';
            if (iconBadge) iconBadge.style.display = 'flex';
        }
    }

    // Attempt to connect to the local WebSocket Bridge (C# App)
    try {
        const socket = new WebSocket("ws://127.0.0.1:8080");
        
        socket.onopen = () => {
            setStatus(true);
        };
        
        socket.onclose = () => {
            setStatus(false, "Device Not Detected");
            // Try to reconnect after 5 seconds
            setTimeout(() => initScannerSocket(widget), 5000);
        };
        
        socket.onerror = () => {
            setStatus(false, "Connection Error");
            socket.close();
        };

    } catch (e) {
        setStatus(false, "Service Error");
        // Retry logic is handled in onclose
    }
}