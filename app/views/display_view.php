<?php
$currentYear = date("Y");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BPC Attendance Display</title>
    
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/display.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="display-body">

    <div class="time-date-bar">
        <div id="currentDate"><i class="fa-regular fa-calendar-days"></i>---</div>
        <div id="currentTime"><i class="fa-regular fa-clock"></i>--:-- --</div>
    </div>

    <div class="slideshow-bg" id="slideshowBg"></div>

    <div class="display-container">
        
        <div class="default-state" id="defaultState">
            <div class="logo-icon">
                <i class="fa-solid fa-fingerprint"></i>
            </div>
            <h1>Welcome to BPC</h1>
            <p>Please scan your fingerprint</p>
        </div>

        <div class="scan-card" id="scanCard">
            <div class="icon-badge" id="scanIcon"></div>
            <div class="user-name" id="scanName">---</div>
            <div class="scan-status" id="scanStatus">---</div>
            <div class="time-date">
                <span id="scanTime">--:-- --</span> | <span id="scanDate">---</span>
            </div>
        </div>

    </div>

    <footer class="display-footer">
        &copy; Bulacan Polytechnic College (<?php echo $currentYear; ?>)
    </footer>
    
    <script>
        const scanCard = document.getElementById('scanCard');
        const scanIcon = document.getElementById('scanIcon');
        const scanName = document.getElementById('scanName');
        const scanStatus = document.getElementById('scanStatus');
        const scanTime = document.getElementById('scanTime');
        const scanDate = document.getElementById('scanDate');
        const defaultStateP = document.getElementById('defaultState').querySelector('p');

        const currentTimeDisplay = document.getElementById('currentTime');
        const currentDateDisplay = document.getElementById('currentDate');
        
        let hideCardTimer;

        // Clock update function
        function updateClock() {
            const now = new Date();
            const timeOptions = { hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true };
            const dateOptions = { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' };

            currentTimeDisplay.innerHTML = `<i class="fa-regular fa-clock"></i>${now.toLocaleTimeString('en-US', timeOptions)}`;
            currentDateDisplay.innerHTML = `<i class="fa-regular fa-calendar-days"></i>${now.toLocaleDateString('en-US', dateOptions)}`;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Slideshow functions
        const slideshowBg = document.getElementById('slideshowBg');
        const slideshowImages = [
            'img/background.png',
            'img/bpc.jpg',
            'img/bpc1.jpg',
            'img/bpc2.jpg',
            'img/bpc3.jpg',
            'img/bpc4.jpg',
            'img/bpc5.jpg'
        ];
        let currentSlideIndex = 0;
        const slideDuration = 5000;

        function loadSlideshow() {
            slideshowImages.forEach((imgSrc, index) => {
                const imgDiv = document.createElement('div');
                imgDiv.className = 'slideshow-image';
                imgDiv.style.backgroundImage = `url(${imgSrc})`;
                imgDiv.setAttribute('data-index', index);
                slideshowBg.appendChild(imgDiv);
            });
            if (slideshowImages.length > 0) {
                document.querySelector('.slideshow-image[data-index="0"]').classList.add('active');
            }
        }

        function nextSlide() {
            const currentSlide = document.querySelector(`.slideshow-image.active`);
            if (currentSlide) {
                currentSlide.classList.remove('active');
            }

            currentSlideIndex = (currentSlideIndex + 1) % slideshowImages.length;
            const nextSlide = document.querySelector(`.slideshow-image[data-index="${currentSlideIndex}"]`);
            if (nextSlide) {
                nextSlide.classList.add('active');
            }
        }
        
        if (slideshowImages.length > 1) {
            setInterval(nextSlide, slideDuration);
        }
        loadSlideshow();

        function showScanEvent(data) {
            clearTimeout(hideCardTimer);

            scanName.textContent = data.name;
            scanStatus.textContent = data.status;
            
            const scanTimeObj = new Date(data.full_timestamp);
            scanTime.textContent = scanTimeObj.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
            scanDate.textContent = scanTimeObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

            if (data.status.toLowerCase().includes('time in')) {
                scanIcon.innerHTML = '<i class="fa-solid fa-arrow-right-to-bracket"></i>';
                scanIcon.className = 'icon-badge time-in';
                scanStatus.className = 'scan-status time-in';
            } else if (data.status.toLowerCase().includes('time out')) {
                scanIcon.innerHTML = '<i class="fa-solid fa-arrow-right-from-bracket"></i>';
                scanIcon.className = 'icon-badge time-badge time-out';
                scanStatus.className = 'scan-status time-out';
            } else {
                scanIcon.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i>';
                scanIcon.className = 'icon-badge error';
                scanStatus.className = 'scan-status error';
            }

            scanCard.classList.add('show');

            hideCardTimer = setTimeout(() => {
                scanCard.classList.remove('show');
            }, 7000);
        }

        function recordAttendance(userId) {
            console.log("📤 Recording attendance for user ID:", userId);
            
            fetch("api/record_attendance.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ user_id: userId })
            })
            .then(response => {
                console.log("📥 Response status:", response.status);
                return response.json();
            })
            .then(data => {
                console.log("📊 Backend response:", data);
                
                if (data.success && data.data) {
                    showScanEvent(data.data);
                } else {
                    console.error("❌ Backend error:", data.message);
                    const now = new Date();
                    showScanEvent({
                        name: "System Error",
                        status: data.message || "Contact Admin",
                        full_timestamp: now.toISOString(),
                        time: now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }),
                        date: now.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' })
                    });
                }
            })
            .catch(err => {
                console.error("❌ Network error:", err);
                const now = new Date();
                showScanEvent({
                    name: "Network Error",
                    status: "Check Connection",
                    full_timestamp: now.toISOString(),
                    time: now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }),
                    date: now.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' })
                });
            });
        }

        // WebSocket Connection
        function connectWebSocket() {
            const socket = new WebSocket("ws://127.0.0.1:8080/");
            
            let reconnectTimeout;
            let isConnected = false;

            socket.onopen = () => {
                console.log("✅ Display connected to bridge successfully");
                isConnected = true;
                defaultStateP.textContent = "Please scan your fingerprint";
                
                if (reconnectTimeout) {
                    clearTimeout(reconnectTimeout);
                }
                
                socket.send(JSON.stringify({ command: "verify_start" }));
            };

            socket.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    console.log("📨 Message received:", data);
                    
                    if (data.type === "verification_success") {
                        console.log("✅ Verification success, User ID:", data.user_id);
                        recordAttendance(data.user_id);
                    }
                    else if (data.type === "verification_fail") {
                        console.warn("❌ Verification failed:", data.message);
                        const now = new Date();
                        showScanEvent({
                            name: "Scan Failed",
                            status: "Finger not recognized",
                            full_timestamp: now.toISOString(),
                            time: now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }),
                            date: now.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' })
                        });
                    }
                    else if (data.status === "info") {
                        console.log("ℹ️ Bridge info:", data.message);
                        if (data.message && data.message.includes("Verification active")) {
                            defaultStateP.textContent = "Please scan your fingerprint";
                        }
                    }
                    else if (data.status === "error") {
                        console.error("⚠️ Bridge error:", data.message);
                        defaultStateP.textContent = "Scanner Error: " + data.message;
                    }

                } catch (e) {
                    console.error("❌ Error parsing WebSocket message:", e);
                }
            };

            socket.onerror = (err) => {
                console.error("❌ WebSocket error. Is ZKTecoBridge.exe running?", err);
                defaultStateP.textContent = "Scanner service disconnected";
                isConnected = false;
            };

            socket.onclose = (event) => {
                console.log("🔌 WebSocket closed. Code:", event.code, "Reason:", event.reason);
                isConnected = false;
                defaultStateP.textContent = "Connection lost. Retrying...";
                
                reconnectTimeout = setTimeout(() => {
                    console.log("🔄 Attempting to reconnect...");
                    connectWebSocket();
                }, 5000);
            };
            
            return socket;
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', () => {
            console.log("🚀 Display page loaded, connecting to scanner...");
            connectWebSocket();
        });
    </script>
</body>
</html>