<?php
$pageTitle = 'About Us';
$pageSubtitle = 'Learn more about the BPC Attendance System';
require_once __DIR__ . '/partials/header.php'; 
?>

<div class="main-body">
    
    <div class="card">
        <div class="card-body" style="text-align: center; padding: 40px;">
            <div style="width: 120px; height: 120px; background: var(--emerald-500); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px;">
                <i class="fa-solid fa-fingerprint" style="font-size: 64px; color: white;"></i>
            </div>
            <h2 style="font-size: 32px; font-weight: 700; color: var(--emerald-800); margin-bottom: 8px;">BPC Attendance System</h2>
            <p style="font-size: 18px; color: var(--gray-600); margin-bottom: 24px;">Staff Attendance Monitoring System</p>
            <p style="color: var(--gray-500); max-width: 600px; margin: 0 auto;">
                A comprehensive attendance tracking system designed for Bulacan Polytechnic College to efficiently monitor and manage staff attendance using biometric fingerprint authentication.
            </p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
        
        <div class="card">
            <div class="card-header">
                <h3>Key Features</h3>
            </div>
            <div class="card-body">
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div style="display: flex; gap: 12px;">
                        <div style="width: 40px; height: 40px; background: var(--emerald-100); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fa-solid fa-fingerprint" style="font-size: 20px; color: var(--emerald-600);"></i>
                        </div>
                        <div>
                            <h4 style="font-weight: 600; margin-bottom: 4px;">Biometric Authentication</h4>
                            <p style="font-size: 14px; color: var(--gray-600);">Secure fingerprint-based attendance tracking</p>
                        </div>
                    </div>

                    <div style="display: flex; gap: 12px;">
                        <div style="width: 40px; height: 40px; background: var(--emerald-100); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fa-solid fa-file-lines" style="font-size: 20px; color: var(--emerald-600);"></i>
                        </div>
                        <div>
                            <h4 style="font-weight: 600; margin-bottom: 4px;">Comprehensive Reports</h4>
                            <p style="font-size: 14px; color: var(--gray-600);">Detailed attendance reports and analytics</p>
                        </div>
                    </div>

                    <div style="display: flex; gap: 12px;">
                        <div style="width: 40px; height: 40px; background: var(--emerald-100); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fa-solid fa-calendar-days" style="font-size: 20px; color: var(--emerald-600);"></i>
                        </div>
                        <div>
                            <h4 style="font-weight: 600; margin-bottom: 4px;">Schedule Management</h4>
                            <p style="font-size: 14px; color: var(--gray-600);">Manage class schedules and working hours</p>
                        </div>
                    </div>

                    <div style="display: flex; gap: 12px;">
                        <div style="width: 40px; height: 40px; background: var(--emerald-100); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i class="fa-solid fa-user-gear" style="font-size: 20px; color: var(--emerald-600);"></i>
                        </div>
                        <div>
                            <h4 style="font-weight: 600; margin-bottom: 4px;">User Management</h4>
                            <p style="font-size: 14px; color: var(--gray-600);">Easy account creation and management</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="card">
            <div class="card-header">
                <h3>System Information</h3>
            </div>
            <div class="card-body">
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div>
                        <p style="font-size: 12px; color: var(--gray-600); margin-bottom: 4px;">Version</p>
                        <p style="font-weight: 600;">1.0.0</p>
                    </div>
                    <div>
                        <p style="font-size: 12px; color: var(--gray-600); margin-bottom: 4px;">Release Date</p>
                        <p style="font-weight: 600;">November 2025</p>
                    </div>
                    <div>
                        <p style="font-size: 12px; color: var(--gray-600); margin-bottom: 4px;">Institution</p>
                        <p style="font-weight: 600;">Bulacan Polytechnic College</p>
                    </div>
                    <div>
                        <p style="font-size: 12px; color: var(--gray-600); margin-bottom: 4px;">Technology Stack</p>
                        <p style="font-weight: 600;">PHP, MySQL, JavaScript, CSS</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="card">
        <div class="card-header">
            <h3>Development Team</h3>
        </div>
        <div class="card-body">
            <div style="text-align: center; padding: 20px;">
                <p style="color: var(--gray-600); margin-bottom: 16px;">
                    Developed and maintained by the BPC IT Department
                </p>
                <p style="font-size: 14px; color: var(--gray-500);">
                    © 2025 Bulacan Polytechnic College. All rights reserved.
                </p>
            </div>
        </div>
    </div>

    <?php if (!Helper::isAdmin()): ?>
    <div class="card">
        <div class="card-header">
            <h3>Need Help?</h3>
        </div>
        <div class="card-body">
            <div style="text-align: center; padding: 20px;">
                <p style="color: var(--gray-600); margin-bottom: 16px;">
                    For technical support or questions about the system, please contact:
                </p>
                <a href="contact.php" class="btn btn-primary">Contact Support</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/partials/footer.php'; ?>