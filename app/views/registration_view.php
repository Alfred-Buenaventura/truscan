<?php 
// FIX: Use __DIR__ to locate the partials folder correctly
require_once __DIR__ . '/partials/header.php'; 
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
.scan-step { transition: all 0.3s ease; }
.scan-step.bg-emerald-500 {
    transform: scale(1.1);
    box-shadow: 0 0 6px rgba(16, 185, 129, 0.5);
}
@keyframes pulse {
  0%, 100% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.05); opacity: 0.7; }
}
.pulse { animation: pulse 1s infinite ease-in-out; }
.btn:disabled { opacity: 0.5; cursor: not-allowed; }
</style>

<div class="main-body flex items-center justify-center">
  <div class="w-full max-w-2xl bg-white p-8 rounded-2xl shadow-lg border border-gray-200" style="margin: 0 auto;">
    <h2 class="text-center text-2xl font-bold text-gray-800 mb-2" style="text-align:center; font-size:1.5rem; font-weight:bold; margin-bottom:0.5rem;">Fingerprint Registration</h2>
    <p class="text-center text-gray-600 mb-6" style="text-align:center; color:#4b5563; margin-bottom:1.5rem;">
      Registering Fingerprint for
      <span class="font-semibold text-emerald-700" style="color:#047857; font-weight:600;">
          <?= htmlspecialchars($targetUser['first_name'] . ' ' . $targetUser['last_name']) ?>
      </span><br>
      Faculty ID: <span class="text-gray-500"><?= htmlspecialchars($targetUser['faculty_id']) ?></span>
    </p>

    <div id="deviceStatusContainer" class="border border-gray-200 bg-gray-50 text-gray-600 py-3 px-4 rounded-lg flex items-center justify-center gap-3 mb-6" style="display:flex; align-items:center; justify-content:center; gap:12px; padding:12px; border-radius:8px; border:1px solid #e5e7eb; background-color:#f9fafb; margin-bottom:1.5rem;">
      <i id="deviceStatusIcon" class="fa fa-spinner fa-spin"></i>
      <span id="deviceStatusText" class="font-medium">Connecting to device...</span>
    </div>

    <div class="flex flex-col items-center text-center border border-emerald-100 rounded-xl p-8 mb-6" style="display:flex; flex-direction:column; align-items:center; border:1px solid #d1fae5; border-radius:12px; padding:2rem; margin-bottom:1.5rem;">
      <div class="w-40 h-40 rounded-full border-4 border-emerald-100 flex items-center justify-center mb-4" style="width:10rem; height:10rem; border-radius:50%; border:4px solid #d1fae5; display:flex; align-items:center; justify-content:center; margin-bottom:1rem;">
        <i class="fa fa-fingerprint fa-4x text-emerald-600" id="fingerIcon" style="font-size:4em; color:#059669;"></i>
      </div>
      <p class="text-gray-700 font-medium mb-4" id="scanStatus" style="margin-bottom:1rem; font-weight:500;">Ready to scan fingerprint...</p>
      
      <div class="flex gap-3 mb-6" style="display:flex; gap:0.75rem; margin-bottom:1.5rem;">
        <?php for ($i = 1; $i <= 3; $i++): ?>
          <div id="scanStep<?= $i ?>" class="scan-step w-8 h-8 rounded-full border border-gray-300 flex items-center justify-center font-bold text-gray-500" style="width:2rem; height:2rem; border-radius:50%; border:1px solid #d1d5db; display:flex; align-items:center; justify-content:center; font-weight:bold; color:#6b7280;">
              <?= $i ?>
          </div>
        <?php endfor; ?>
      </div>

      <form method="POST" id="fingerprintForm" class="flex items-center gap-3" style="display:flex; gap:0.75rem;">
        <input type="hidden" name="fingerprint_data" id="fingerprintData">
        <button type="button" id="scanBtn" class="btn btn-primary" disabled>
          <i class="fa fa-fingerprint"></i> Scan
        </button>
        <a href="complete_registration.php" class="btn btn-secondary">Cancel</a>
      </form>
    </div>

    <div class="bg-blue-50 border border-blue-200 text-blue-800 p-4 rounded-lg text-sm" style="background-color:#eff6ff; border:1px solid #bfdbfe; color:#1e40af; padding:1rem; border-radius:0.5rem; font-size:0.875rem;">
      <strong>Instructions:</strong>
      <ul class="list-disc list-inside mt-2 text-blue-700 space-y-1" style="list-style-type:disc; padding-left:1.5rem; margin-top:0.5rem;">
        <li>Ensure finger is clean.</li>
        <li>Place finger firmly on scanner.</li>
        <li>Scan **3 times** to complete.</li>
      </ul>
    </div>
  </div>
</div>

<div id="deviceErrorModal" class="modal">
    <div class="modal-content modal-small">
        <div class="modal-header">
            <h3><i class="fa-solid fa-triangle-exclamation"></i> Device Error</h3>
            <button type="button" class="modal-close" onclick="closeModal('deviceErrorModal')"><i class="fa-solid fa-times"></i></button>
        </div>
        <div class="modal-body">
            <p>Scanner not detected. Please check connection.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" id="deviceErrorOkBtn">OK</button>
        </div>
    </div>
</div>

<div id="retryConnectionModal" class="modal">
    <div class="modal-content modal-small">
        <div class="modal-header">
            <h3>Retry Connection?</h3>
            <button type="button" class="modal-close" onclick="closeModal('retryConnectionModal')"><i class="fa-solid fa-times"></i></button>
        </div>
        <div class="modal-body"><p>Device still not detected.</p></div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="window.location.href='complete_registration.php'">Back</button>
            <button type="button" class="btn btn-primary" id="retryConnectBtn">Retry</button>
        </div>
    </div>
</div>

<div id="scanNoticeModal" class="modal">
    <div class="modal-content modal-small">
        <div class="modal-header">
            <h3>Ready to Scan</h3>
            <button type="button" class="modal-close" onclick="closeModal('scanNoticeModal')"><i class="fa-solid fa-times"></i></button>
        </div>
        <div class="modal-body"><p>Press Proceed to start the 3-step scan.</p></div>
        <div class="modal-footer">
             <button type="button" class="btn btn-secondary" onclick="closeModal('scanNoticeModal')">Cancel</button>
             <button type="button" id="proceedScanBtn" class="btn btn-primary">Proceed</button>
        </div>
    </div>
</div>

<div id="scanFailedModal" class="modal">
    <div class="modal-content modal-small">
        <div class="modal-header">
            <h3>Scan Failed</h3>
            <button type="button" class="modal-close" onclick="closeModal('scanFailedModal')"><i class="fa-solid fa-times"></i></button>
        </div>
        <div class="modal-body">
            <p>Scan unsuccessful. Please try again.</p>
            <p id="scanFailedAttempts" style="font-size: 0.9rem; margin-top: 0.5rem;"></p>
        </div>
        <div class="modal-footer">
             <button type="button" class="btn btn-secondary" onclick="window.location.href='complete_registration.php'">Cancel</button>
             <button type="button" id="retryScanBtn" class="btn btn-primary">Try Again</button>
        </div>
    </div>
</div>

<script>
function openModal(modalId) { const m = document.getElementById(modalId); if(m) m.style.display='flex'; }
function closeModal(modalId) { const m = document.getElementById(modalId); if(m) m.style.display='none'; }

let socket;
let isDeviceConnected = false;

const deviceStatusContainer = document.getElementById('deviceStatusContainer');
const deviceStatusIcon = document.getElementById('deviceStatusIcon');
const deviceStatusText = document.getElementById('deviceStatusText');
const scanBtn = document.getElementById('scanBtn');
const fingerIcon = document.getElementById('fingerIcon');
const scanStatus = document.getElementById('scanStatus');
const fingerprintForm = document.getElementById('fingerprintForm');

function resetScanUI() {
    for (let i = 1; i <= 3; i++) {
        const stepEl = document.getElementById(`scanStep${i}`);
        if (stepEl) {
            stepEl.classList.remove('bg-emerald-500', 'border-emerald-500', 'text-white');
            stepEl.classList.add('text-gray-500');
            stepEl.style.backgroundColor = ''; stepEl.style.borderColor = ''; stepEl.style.color = '';
        }
    }
    fingerIcon.classList.remove('pulse');
    scanStatus.textContent = "Ready to scan fingerprint...";
    scanBtn.disabled = false; 
    scanBtn.innerHTML = '<i class="fa fa-fingerprint"></i> Scan';
}

function startEnrollment() {
    if (!socket || socket.readyState !== WebSocket.OPEN) {
        scanStatus.textContent = "Device not ready.";
        return;
    }
    resetScanUI();
    scanStatus.textContent = "Place finger for scan 1 of 3...";
    fingerIcon.classList.add('pulse');
    scanBtn.disabled = true; 
    scanBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Scanning...';
    socket.send(JSON.stringify({ command: "enroll_start" }));
}

function connectWebSocket() {
    socket = new WebSocket("ws://127.0.0.1:8080");
    socket.onopen = () => {
        isDeviceConnected = true;
        deviceStatusContainer.style.borderColor = '#a7f3d0'; 
        deviceStatusContainer.style.backgroundColor = '#ecfdf5'; 
        deviceStatusContainer.style.color = '#047857'; 
        deviceStatusIcon.className = 'fa fa-check-circle'; 
        deviceStatusText.textContent = "Device Connected";
        scanBtn.disabled = false; 
    };
    socket.onmessage = (event) => {
        try {
            const data = JSON.parse(event.data);
            if (data.status === "progress") {
                const step = data.step; 
                const stepEl = document.getElementById(`scanStep${step}`);
                if (stepEl) {
                    stepEl.style.backgroundColor = '#10b981'; 
                    stepEl.style.borderColor = '#10b981';
                    stepEl.style.color = 'white';
                }
                scanStatus.textContent = data.message; 
                if (step < 3) setTimeout(() => { scanStatus.textContent = `Place finger for scan ${step + 1} of 3...`; }, 1000);
            }
            else if (data.status === "success") {
                fingerIcon.classList.remove('pulse');
                scanStatus.textContent = "All scans complete! Saving...";
                document.getElementById('fingerprintData').value = data.template; 
                setTimeout(() => fingerprintForm.submit(), 1500);
            }
            else if (data.status === "error") {
                fingerIcon.classList.remove('pulse');
                scanStatus.textContent = `Scan failed: ${data.message}`;
                document.getElementById('scanFailedAttempts').textContent = "Process aborted. Try again.";
                openModal('scanFailedModal');
            }
        } catch (e) { console.error(e); }
    };
    socket.onerror = () => {
        isDeviceConnected = false;
        deviceStatusContainer.style.borderColor = '#fecaca'; 
        deviceStatusContainer.style.backgroundColor = '#fef2f2'; 
        deviceStatusContainer.style.color = '#dc2626'; 
        deviceStatusIcon.className = 'fa fa-times-circle';
        deviceStatusText.textContent = "Device Not Detected";
        scanBtn.disabled = true; 
        openModal('deviceErrorModal'); 
    };
    socket.onclose = () => {
        if (isDeviceConnected) { 
            isDeviceConnected = false;
            deviceStatusIcon.className = 'fa fa-times-circle';
            deviceStatusText.textContent = "Connection Lost";
            scanBtn.disabled = true;
        }
    };
}

function retryConnection() {
    closeModal('retryConnectionModal');
    deviceStatusIcon.className = 'fa fa-spinner fa-spin';
    deviceStatusText.textContent = "Connecting...";
    scanBtn.disabled = true;
    connectWebSocket(); 
}

document.addEventListener('DOMContentLoaded', () => {
    connectWebSocket(); 
    scanBtn.addEventListener('click', () => { isDeviceConnected ? openModal('scanNoticeModal') : openModal('deviceErrorModal'); });
    document.getElementById('proceedScanBtn').addEventListener('click', () => { closeModal('scanNoticeModal'); startEnrollment(); });
    document.getElementById('retryScanBtn').addEventListener('click', () => { closeModal('scanFailedModal'); resetScanUI(); });
    document.getElementById('deviceErrorOkBtn').addEventListener('click', () => { closeModal('deviceErrorModal'); openModal('retryConnectionModal'); });
    document.getElementById('retryConnectBtn').addEventListener('click', () => { retryConnection(); });
});
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>