<!-- COMMON REUSABLE BARCODE SCANNER MODAL FOR ERP -->
<div id="commonBarcodeScannerModal" class="barcode-modal-backdrop" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.75); z-index:99999; align-items:center; justify-content:center; padding:15px; box-sizing:border-box;">
    <div class="barcode-modal-card" style="background:#fff; border-radius:12px; max-width:680px; width:100%; max-height:90vh; display:flex; flex-direction:column; overflow:hidden; box-shadow:0 25px 50px -12px rgba(0,0,0,0.35);">
        
        <!-- HEADER -->
        <div class="barcode-modal-header" style="display:flex; align-items:center; justify-content:space-between; padding:14px 18px; background:#f8fafc; border-bottom:1px solid #e2e8f0;">
            <div style="display:flex; align-items:center; gap:10px;">
                <button type="button" class="btn-scanner-back" onclick="closeCommonBarcodeScannerModal()" style="background:#e2e8f0; border:none; padding:5px 10px; border-radius:6px; font-weight:600; font-size:13px; cursor:pointer; color:#334155;">&larr; Back</button>
                <h3 id="commonBarcodeModalTitle" style="margin:0; font-size:16px; font-weight:700; color:#0f172a;">📷 Real Web Camera Barcode Scanner</h3>
            </div>
            <button type="button" class="barcode-modal-close" onclick="closeCommonBarcodeScannerModal()" style="background:none; border:none; font-size:24px; color:#64748b; cursor:pointer; line-height:1;">&times;</button>
        </div>

        <div class="barcode-modal-body" style="padding:0; overflow-y:auto; flex:1;">
            <!-- CAMERA CONTROLS BAR -->
            <div class="scanner-controls-bar" style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px; padding:10px 14px; background:#f8fafc; border-bottom:1px solid #e2e8f0;">
                <div class="camera-select-wrap" style="display:flex; align-items:center; gap:8px; flex:1; min-width:220px;">
                    <label for="commonCameraSelect" style="font-size:12px; font-weight:700; color:#475569; white-space:nowrap;">Camera:</label>
                    <select id="commonCameraSelect" class="erp-input" style="padding:6px 10px; font-size:13px; width:100%; border-radius:6px; border:1px solid #cbd5e1;" onchange="onCommonCameraSelectChange()"></select>
                    <button type="button" id="commonBtnRefreshCameras" onclick="refreshCommonCameraDevices()" class="btn-erp" style="padding:6px 10px; font-size:12px; white-space:nowrap; background:#f1f5f9; color:#334155; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;" title="Refresh available cameras">🔄 Refresh</button>
                </div>
                <div class="camera-btn-wrap" style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    <button type="button" id="commonBtnStartCamera" onclick="startCommonBarcodeScannerCamera()" class="btn-erp btn-erp-primary" style="padding:6px 12px; font-size:13px; background:#2563eb; color:#fff; border:none; border-radius:6px; font-weight:600; cursor:pointer;">▶ Start Camera</button>
                    <button type="button" id="commonBtnStopCamera" onclick="stopCommonBarcodeScannerCamera()" class="btn-erp btn-erp-danger" style="display:none; background:#dc2626; color:#fff; padding:6px 12px; font-size:13px; border:none; border-radius:6px; font-weight:600; cursor:pointer;">⏹ Stop Camera</button>
                    <button type="button" id="commonBtnCapturePhoto" onclick="captureCommonPhotoAndScan()" class="btn-erp" style="display:none; background:#0284c7; color:#fff; padding:6px 12px; font-size:13px; border:none; border-radius:6px; font-weight:600; cursor:pointer;">📸 Capture Photo</button>
                </div>
            </div>

            <!-- CAMERA SCANNER SECTION -->
            <div id="commonScannerCameraSection" style="padding:14px;">
                <div class="scanner-view-box" style="position:relative; width:100%; max-height:360px; background:#000; border-radius:8px; overflow:hidden; display:flex; align-items:center; justify-content:center;">
                    <video id="commonCameraPreview" autoplay playsinline muted style="width:100%; height:100%; max-height:360px; object-fit:cover;"></video>
                    
                    <div class="scan-overlay" style="position:absolute; top:0; left:0; width:100%; height:100%; display:flex; align-items:center; justify-content:center; pointer-events:none;">
                        <div class="scan-frame" style="width:240px; height:150px; border:2px solid #38bdf8; box-shadow:0 0 0 4000px rgba(0,0,0,0.5); position:relative; border-radius:8px;">
                            <div class="scan-line" style="position:absolute; width:100%; height:2px; background:#38bdf8; top:50%; box-shadow:0 0 8px #38bdf8; animation:scanLineMove 2s infinite ease-in-out;"></div>
                            <div style="position:absolute; bottom:6px; width:100%; text-align:center; color:#38bdf8; font-size:10px; font-weight:800; letter-spacing:1px;">ALIGN BARCODE HERE</div>
                        </div>
                    </div>
                </div>

                <!-- CAPTURED PHOTO PREVIEW BOX -->
                <div id="commonPhotoPreviewBox" style="display:none; text-align:center; padding:12px; background:#0f172a; border-radius:8px; margin:10px 0;">
                    <div style="color:#e2e8f0; font-size:13px; font-weight:600; margin-bottom:8px;">Captured Frame Preview</div>
                    <img id="commonPhotoCapturedImg" style="max-width:100%; max-height:260px; border-radius:6px; border:2px solid #38bdf8; object-fit:contain;" alt="Captured Photo">
                    <canvas id="commonPhotoCapturedCanvas" style="display:none;"></canvas>
                    <div id="commonPhotoScanStatus" style="color:#38bdf8; font-weight:700; font-size:13px; margin-top:8px;">⌛ Scanning barcode from captured photo...</div>
                </div>

                <div id="commonScannerCameraStatus" class="camera-status-info" style="margin-top:10px; padding:10px 12px; background:#f1f5f9; border-radius:6px; font-size:12.5px; color:#334155; text-align:center;">
                    Click <strong>Start Camera</strong> to grant permission and open real device webcam.
                </div>

                <!-- MANUAL BARCODE ENTRY -->
                <div class="manual-search-box" style="margin-top:12px; padding:12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;">
                    <div class="manual-search-title" style="font-size:12px; font-weight:700; color:#475569; margin-bottom:6px;">Or Enter Barcode Manually:</div>
                    <div class="manual-search-form" style="display:flex; gap:8px;">
                        <input type="text" id="commonManualBarcodeInput" class="manual-search-input" placeholder="e.g. 43103325 or T1102" onkeydown="if(event.key==='Enter'){searchCommonBarcodeManual(); event.preventDefault();}" style="flex:1; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">
                        <button type="button" onclick="searchCommonBarcodeManual()" class="manual-search-btn" style="background:#0f172a; color:#fff; border:none; padding:8px 16px; border-radius:6px; font-weight:600; font-size:13px; cursor:pointer;">Search</button>
                    </div>
                </div>
            </div>

            <!-- RESULT DETAILS PANEL -->
            <div id="commonScannerResultPanel" style="display:none; padding:14px;">
                <div id="commonScannerAlertHeader" class="alert-status alert-success-bg" style="padding:10px 14px; border-radius:6px; font-weight:700; font-size:14px; margin-bottom:14px;">
                    <span id="commonScannerAlertText">✓ Barcode Scanned Successfully</span>
                </div>

                <div id="commonScannerDetailsContent">
                    <!-- Dynamic details populated here -->
                </div>
            </div>
        </div>

        <div class="modal-actions" id="commonScannerModalActions" style="padding:12px 18px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:10px;">
            <a href="#" id="commonBtnViewItem" class="btn-top btn-dark" style="display:none; background:#2563eb; color:#fff; text-decoration:none; padding:8px 14px; border-radius:6px; font-size:13px; font-weight:600;">👁️ View Item</a>
            <button type="button" id="commonBtnPrintScanned" onclick="printCommonCurrentScannedLabel()" class="btn-top btn-dark" style="display:none; background:#212529; color:#fff; border:none; padding:8px 14px; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer;">🖨️ Print Barcode</button>
            <button type="button" id="commonBtnScanAgain" onclick="resetAndScanCommonAgain()" class="btn-top btn-light-gray" style="background:#e2e8f0; color:#334155; border:none; padding:8px 14px; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer;">📷 Scan Again</button>
            <button type="button" onclick="closeCommonBarcodeScannerModal()" class="btn-top btn-light-gray" style="background:#cbd5e1; color:#0f172a; border:none; padding:8px 14px; border-radius:6px; font-size:13px; font-weight:600; cursor:pointer;">Close</button>
        </div>
    </div>
</div>

<style>
@keyframes scanLineMove {
    0% { top: 5%; }
    50% { top: 90%; }
    100% { top: 5%; }
}
.alert-success-bg { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
.alert-danger-bg { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
</style>

<!-- COMMON JS LIBRARIES & SCANNER LOGIC -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@zxing/library@0.21.0/umd/index.min.js"></script>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    let commonScannerRunning = false;
    let commonMediaStream = null;
    let commonZxingCodeReader = null;
    let commonBarcodeDetectorAnimFrame = null;
    let commonCurrentScannedData = null;
    let currentBarcodeScannerCallback = null;

    function escapeHtmlCommon(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // PUBLIC GLOBAL API FOR ALL ERP PAGES
    window.openBarcodeScanner = function(callbackOrOptions) {
        let callback = null;
        let title = "📷 Real Web Camera Barcode Scanner";

        if (typeof callbackOrOptions === 'function') {
            callback = callbackOrOptions;
        } else if (typeof callbackOrOptions === 'object' && callbackOrOptions !== null) {
            callback = callbackOrOptions.callback || null;
            if (callbackOrOptions.title) title = callbackOrOptions.title;
        }

        currentBarcodeScannerCallback = callback;
        const titleEl = document.getElementById('commonBarcodeModalTitle');
        if (titleEl) titleEl.innerHTML = escapeHtmlCommon(title);

        const modal = document.getElementById('commonBarcodeScannerModal');
        if (modal) modal.style.display = 'flex';

        initCommonCameraDevices();
        resetAndScanCommonAgain();
    };

    window.openBarcodeScannerModal = function() {
        window.openBarcodeScanner();
    };

    window.closeCommonBarcodeScannerModal = function() {
        stopCommonBarcodeScannerCamera();
        const modal = document.getElementById('commonBarcodeScannerModal');
        if (modal) modal.style.display = 'none';
        currentBarcodeScannerCallback = null;
    };

    async function refreshCommonCameraDevices() {
        const statusEl = document.getElementById('commonScannerCameraStatus');
        if (statusEl) statusEl.innerHTML = '🔄 Scanning available camera devices...';
        await initCommonCameraDevices(true);
        if (statusEl && !commonScannerRunning) {
            statusEl.innerHTML = 'Camera list updated. Click <strong>Start Camera</strong> to open webcam.';
        }
    }

    async function initCommonCameraDevices(forceRefresh = false) {
        const select = document.getElementById('commonCameraSelect');
        if (!select) return;
        
        const previousVal = select.value;

        if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) {
            select.innerHTML = '<option value="">Default Camera</option>';
            return;
        }

        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const videoDevices = devices.filter(d => d.kind === 'videoinput');

            if (videoDevices.length > 0) {
                select.innerHTML = '';
                videoDevices.forEach((device, index) => {
                    const option = document.createElement('option');
                    option.value = device.deviceId;

                    let label = device.label ? device.label.trim() : '';
                    if (!label) {
                        label = `Camera ${index + 1}`;
                    }

                    const lower = label.toLowerCase();
                    if (lower.includes('back') || lower.includes('rear') || lower.includes('environment')) {
                        label = `Back Camera 📷 (${label})`;
                    } else if (lower.includes('front') || lower.includes('user') || lower.includes('facing') || lower.includes('selfie')) {
                        label = `Front Camera 🤳 (${label})`;
                    }

                    option.text = label;
                    select.appendChild(option);
                });

                if (videoDevices.length === 1) {
                    select.value = videoDevices[0].deviceId;
                } else if (previousVal && Array.from(select.options).some(o => o.value === previousVal)) {
                    select.value = previousVal;
                } else {
                    const rearDev = videoDevices.find(d => {
                        const l = d.label.toLowerCase();
                        return l.includes('back') || l.includes('rear') || l.includes('environment');
                    });
                    if (rearDev) select.value = rearDev.deviceId;
                }
            } else {
                select.innerHTML = '<option value="">No Camera Found</option>';
            }
        } catch(e) {
            console.warn("[BarcodeScanner] Could not enumerate camera devices:", e);
            select.innerHTML = '<option value="">Default Camera</option>';
        }
    }

    function onCommonCameraSelectChange() {
        stopCommonBarcodeScannerCamera();
        startCommonBarcodeScannerCamera();
    }

    function resetAndScanCommonAgain() {
        stopCommonBarcodeScannerCamera();

        document.getElementById('commonScannerResultPanel').style.display = 'none';
        document.getElementById('commonScannerCameraSection').style.display = 'block';
        document.getElementById('commonManualBarcodeInput').value = '';

        const photoBox = document.getElementById('commonPhotoPreviewBox');
        if (photoBox) photoBox.style.display = 'none';
        
        const btnPrint = document.getElementById('commonBtnPrintScanned');
        if (btnPrint) btnPrint.style.display = 'none';

        const btnView = document.getElementById('commonBtnViewItem');
        if (btnView) btnView.style.display = 'none';

        startCommonBarcodeScannerCamera();
    }

    async function startCommonBarcodeScannerCamera() {
        if (commonScannerRunning) {
            console.log("Scanner already running, skipping duplicate start.");
            return;
        }

        stopCommonBarcodeScannerCamera();

        const statusEl = document.getElementById('commonScannerCameraStatus');
        const videoEl = document.getElementById('commonCameraPreview');
        const btnStart = document.getElementById('commonBtnStartCamera');
        const btnStop = document.getElementById('commonBtnStopCamera');
        const btnCapture = document.getElementById('commonBtnCapturePhoto');
        const select = document.getElementById('commonCameraSelect');

        const photoBox = document.getElementById('commonPhotoPreviewBox');
        if (photoBox) photoBox.style.display = 'none';

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            if (statusEl) {
                statusEl.innerHTML = '<span style="color:#b91c1c; font-weight:700;">❌ Camera API is not supported or restricted (requires HTTPS or localhost).</span>';
            }
            console.error("getUserMedia not available.");
            return;
        }

        commonScannerRunning = true;
        console.log("Scanner Started");

        if (statusEl) {
            statusEl.innerHTML = '⌛ Requesting camera permission...';
        }

        const selectedDeviceId = select ? select.value : '';
        
        const constraintOptions = [];

        if (selectedDeviceId) {
            constraintOptions.push({
                video: { deviceId: { exact: selectedDeviceId }, width: { ideal: 1280 }, height: { ideal: 720 } },
                audio: false
            });
            constraintOptions.push({
                video: { deviceId: { exact: selectedDeviceId } },
                audio: false
            });
        }

        constraintOptions.push({
            video: { facingMode: { ideal: "environment" }, width: { ideal: 1280 }, height: { ideal: 720 } },
            audio: false
        });

        constraintOptions.push({
            video: { facingMode: { ideal: "environment" } },
            audio: false
        });

        constraintOptions.push({
            video: true,
            audio: false
        });

        let stream = null;
        let lastError = null;

        for (const constraints of constraintOptions) {
            try {
                stream = await navigator.mediaDevices.getUserMedia(constraints);
                if (stream) break;
            } catch(err) {
                lastError = err;
            }
        }

        if (!stream) {
            commonScannerRunning = false;
            if (btnStart) btnStart.style.display = 'inline-flex';
            if (btnStop) btnStop.style.display = 'none';
            if (btnCapture) btnCapture.style.display = 'none';

            let msg = 'Unable to open device camera.';
            if (lastError) {
                if (lastError.name === 'NotAllowedError' || lastError.name === 'PermissionDeniedError') {
                    msg = 'Camera access was denied. Please allow camera permission in browser settings.';
                } else if (lastError.name === 'NotFoundError' || lastError.name === 'DevicesNotFoundError') {
                    msg = 'No camera device detected on this system.';
                } else if (lastError.name === 'NotReadableError' || lastError.name === 'TrackStartError') {
                    msg = 'Camera is currently in use by another application.';
                } else {
                    msg = lastError.message || msg;
                }
            }
            if (statusEl) {
                statusEl.innerHTML = `<span style="color:#b91c1c; font-weight:700;">❌ ${escapeHtmlCommon(msg)}</span>`;
            }
            return;
        }

        commonMediaStream = stream;
        videoEl.srcObject = commonMediaStream;
        
        if (videoEl.paused) {
            try {
                await videoEl.play();
            } catch(e) {
                console.warn("video.play() warning:", e);
            }
        }

        if (btnStart) btnStart.style.display = 'none';
        if (btnStop) btnStop.style.display = 'inline-flex';
        if (btnCapture) btnCapture.style.display = 'inline-flex';

        if (statusEl) {
            statusEl.innerHTML = '🟢 Live Camera Active — Point camera at Barcode or click 📸 Capture Photo';
        }

        console.log("Camera Started");

        initCommonCameraDevices();
        initCommonMultiEngineScanner(videoEl);
    }

    async function captureCommonPhotoAndScan() {
        const videoEl = document.getElementById('commonCameraPreview');
        const photoBox = document.getElementById('commonPhotoPreviewBox');
        const photoImg = document.getElementById('commonPhotoCapturedImg');
        const photoCanvas = document.getElementById('commonPhotoCapturedCanvas');
        const photoStatus = document.getElementById('commonPhotoScanStatus');

        if (!videoEl || !commonMediaStream || videoEl.videoWidth === 0) {
            alert("Camera feed is not active yet.");
            return;
        }

        photoCanvas.width = videoEl.videoWidth || 1280;
        photoCanvas.height = videoEl.videoHeight || 720;
        const ctx = photoCanvas.getContext('2d');
        ctx.drawImage(videoEl, 0, 0, photoCanvas.width, photoCanvas.height);

        const dataUrl = photoCanvas.toDataURL('image/png');
        photoImg.src = dataUrl;
        photoBox.style.display = 'block';
        photoStatus.innerHTML = '⌛ Scanning barcode from captured photo...';

        stopCommonBarcodeScannerCamera();

        console.log("Photo Captured, analyzing frame for barcode...");

        if ('BarcodeDetector' in window) {
            try {
                const detector = new BarcodeDetector({ formats: ['code_128', 'code_39', 'ean_13', 'ean_8', 'upc_a', 'upc_e', 'qr_code'] });
                const barcodes = await detector.detect(photoCanvas);
                if (barcodes && barcodes.length > 0 && barcodes[0].rawValue) {
                    const code = barcodes[0].rawValue.trim();
                    if (code) {
                        console.log("Barcode Found from Photo (Native):", code);
                        photoStatus.innerHTML = `🟢 Barcode Found: <strong>${escapeHtmlCommon(code)}</strong>`;
                        onCommonBarcodeScanned(code);
                        return;
                    }
                }
            } catch(e) {
                console.warn("Native BarcodeDetector photo error:", e);
            }
        }

        if (typeof ZXing !== 'undefined') {
            try {
                const hints = new Map();
                hints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS, [
                    ZXing.BarcodeFormat.CODE_128,
                    ZXing.BarcodeFormat.CODE_39,
                    ZXing.BarcodeFormat.EAN_13,
                    ZXing.BarcodeFormat.EAN_8,
                    ZXing.BarcodeFormat.UPC_A,
                    ZXing.BarcodeFormat.UPC_E,
                    ZXing.BarcodeFormat.QR_CODE
                ]);
                const reader = new ZXing.BrowserMultiFormatReader(hints);
                const result = await reader.decodeFromImageElement(photoImg);
                if (result && result.getText()) {
                    const code = result.getText().trim();
                    if (code) {
                        console.log("Barcode Found from Photo (ZXing):", code);
                        photoStatus.innerHTML = `🟢 Barcode Found: <strong>${escapeHtmlCommon(code)}</strong>`;
                        onCommonBarcodeScanned(code);
                        return;
                    }
                }
            } catch(e) {
                console.warn("ZXing photo decode warning:", e);
            }
        }

        photoStatus.innerHTML = '<span style="color:#f87171; font-weight:700;">❌ No barcode detected in captured photo. Click Start Camera to try again or enter barcode manually below.</span>';
    }

    function initCommonMultiEngineScanner(videoEl) {
        if (!commonScannerRunning) return;

        if ('BarcodeDetector' in window) {
            try {
                const supportedFormats = ['code_128', 'code_39', 'ean_13', 'ean_8', 'upc_a', 'upc_e', 'qr_code'];
                const detector = new BarcodeDetector({ formats: supportedFormats });

                const detectFrame = async () => {
                    if (!commonScannerRunning || !commonMediaStream) return;
                    try {
                        if (videoEl && videoEl.readyState >= 2 && videoEl.videoWidth > 0) {
                            const barcodes = await detector.detect(videoEl);
                            if (barcodes && barcodes.length > 0 && barcodes[0].rawValue) {
                                const code = barcodes[0].rawValue.trim();
                                if (code && commonScannerRunning) {
                                    console.log("Barcode Found:", code);
                                    onCommonBarcodeScanned(code);
                                    return;
                                }
                            }
                        }
                    } catch(e) {}

                    if (commonScannerRunning) {
                        commonBarcodeDetectorAnimFrame = requestAnimationFrame(detectFrame);
                    }
                };

                commonBarcodeDetectorAnimFrame = requestAnimationFrame(detectFrame);
                return;
            } catch(e) {
                console.warn("Native BarcodeDetector setup error:", e);
            }
        }

        if (typeof ZXing !== 'undefined') {
            try {
                if (commonZxingCodeReader) {
                    try { commonZxingCodeReader.reset(); } catch(e) {}
                    commonZxingCodeReader = null;
                }

                const hints = new Map();
                hints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS, [
                    ZXing.BarcodeFormat.CODE_128,
                    ZXing.BarcodeFormat.CODE_39,
                    ZXing.BarcodeFormat.EAN_13,
                    ZXing.BarcodeFormat.EAN_8,
                    ZXing.BarcodeFormat.UPC_A,
                    ZXing.BarcodeFormat.UPC_E,
                    ZXing.BarcodeFormat.QR_CODE
                ]);
                commonZxingCodeReader = new ZXing.BrowserMultiFormatReader(hints);

                commonZxingCodeReader.decodeFromVideoElement(videoEl, (result, error) => {
                    if (result && commonScannerRunning) {
                        const code = result.getText().trim();
                        if (code) {
                            console.log("Barcode Found:", code);
                            onCommonBarcodeScanned(code);
                        }
                    }
                    if (error) {
                        if (error instanceof ZXing.NotFoundException || (error.name && error.name.includes('NotFoundException'))) {
                            return;
                        }
                    }
                }).catch(err => {
                    if (err && (err.name === 'NotFoundException' || (err.message && err.message.includes('stream has ended')))) {
                        return;
                    }
                });

                return;
            } catch(e) {
                console.warn("ZXing setup error:", e);
            }
        }
    }

    function stopCommonBarcodeScannerCamera() {
        commonScannerRunning = false;

        if (commonBarcodeDetectorAnimFrame) {
            cancelAnimationFrame(commonBarcodeDetectorAnimFrame);
            commonBarcodeDetectorAnimFrame = null;
        }

        if (commonZxingCodeReader) {
            try { commonZxingCodeReader.reset(); } catch(e) {}
            commonZxingCodeReader = null;
        }

        if (commonMediaStream) {
            try {
                commonMediaStream.getTracks().forEach(track => track.stop());
            } catch(e) {}
            commonMediaStream = null;
        }

        const videoEl = document.getElementById('commonCameraPreview');
        if (videoEl) {
            videoEl.srcObject = null;
        }

        const btnStart = document.getElementById('commonBtnStartCamera');
        const btnStop = document.getElementById('commonBtnStopCamera');
        const btnCapture = document.getElementById('commonBtnCapturePhoto');
        const statusEl = document.getElementById('commonScannerCameraStatus');

        if (btnStart) btnStart.style.display = 'inline-flex';
        if (btnStop) btnStop.style.display = 'none';
        if (btnCapture) btnCapture.style.display = 'none';
        if (statusEl && !statusEl.innerHTML.includes('❌')) {
            statusEl.innerHTML = 'Camera stopped. Click <strong>Start Camera</strong> to scan barcodes.';
        }

        console.log("Scanner Stopped");
    }

    function playCommonScanBeep() {
        try {
            const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(880, audioCtx.currentTime);
            gain.gain.setValueAtTime(0.25, audioCtx.currentTime);
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.start();
            osc.stop(audioCtx.currentTime + 0.12);
        } catch(e) {}
    }

    function onCommonBarcodeScanned(barcode) {
        if (!barcode) return;
        console.log("Barcode Found:", barcode);
        playCommonScanBeep();
        stopCommonBarcodeScannerCamera();
        searchCommonBarcodeAPI(barcode);
    }

    function searchCommonBarcodeManual() {
        const code = document.getElementById('commonManualBarcodeInput').value.trim();
        if (!code) {
            alert('Please enter a barcode string!');
            return;
        }
        stopCommonBarcodeScannerCamera();
        searchCommonBarcodeAPI(code);
    }

    function searchCommonBarcodeAPI(barcode) {
        const currentPath = window.location.pathname;
        let apiUrl = '../stock/get_by_barcode.php?barcode=' + encodeURIComponent(barcode);
        if (currentPath.includes('/stock/')) {
            apiUrl = 'get_by_barcode.php?barcode=' + encodeURIComponent(barcode);
        }

        fetch(apiUrl)
            .then(response => response.json())
            .then(res => {
                if (currentBarcodeScannerCallback) {
                    const callback = currentBarcodeScannerCallback;
                    closeCommonBarcodeScannerModal();
                    callback(barcode, res.success ? res.data : null);
                    return;
                }

                document.getElementById('commonScannerCameraSection').style.display = 'none';
                document.getElementById('commonScannerResultPanel').style.display = 'block';

                if (res.success && res.data) {
                    commonCurrentScannedData = res.data;
                    showCommonBarcodeResultSuccess(res.data);
                } else {
                    commonCurrentScannedData = null;
                    showCommonBarcodeResultNotFound(barcode);
                }
            })
            .catch(err => {
                if (currentBarcodeScannerCallback) {
                    const callback = currentBarcodeScannerCallback;
                    closeCommonBarcodeScannerModal();
                    callback(barcode, null);
                    return;
                }
                alert('Error querying barcode API: ' + err);
            });
    }

    function showCommonBarcodeResultSuccess(data) {
        const alertHeader = document.getElementById('commonScannerAlertHeader');
        alertHeader.className = 'alert-status alert-success-bg';
        document.getElementById('commonScannerAlertText').innerHTML = '✓ Barcode Scanned Successfully';
        
        const btnPrint = document.getElementById('commonBtnPrintScanned');
        if (btnPrint) btnPrint.style.display = 'inline-flex';

        const btnView = document.getElementById('commonBtnViewItem');
        if (btnView) {
            const currentPath = window.location.pathname;
            btnView.href = (currentPath.includes('/stock/') ? '' : '../stock/') + 'edit_stock.php?id=' + encodeURIComponent(data.id);
            btnView.style.display = 'inline-flex';
        }

        const content = `
            <div class="detail-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div class="barcode-display-box" style="grid-column:span 2; text-align:center; padding:12px; background:#f8fafc; border-radius:8px;">
                    <svg id="commonModalBarcodeSvg"></svg>
                    <div style="font-family:monospace; font-weight:700; font-size:15px; letter-spacing:1px; margin-top:2px;">${escapeHtmlCommon(data.barCode)}</div>
                </div>

                <div class="detail-item">
                    <span class="detail-label" style="display:block; font-size:11px; font-weight:700; color:#64748b;">Barcode</span>
                    <span class="detail-value" style="font-size:13px; font-weight:600; color:#0f172a;">${escapeHtmlCommon(data.barCode)}</span>
                </div>

                <div class="detail-item">
                    <span class="detail-label" style="display:block; font-size:11px; font-weight:700; color:#64748b;">Serial No</span>
                    <span class="detail-value" style="font-size:13px; font-weight:600; color:#0f172a;">${escapeHtmlCommon(data.serialNo)}</span>
                </div>

                <div class="detail-item" style="grid-column: span 2;">
                    <span class="detail-label" style="display:block; font-size:11px; font-weight:700; color:#64748b;">Item Name</span>
                    <span class="detail-value" style="font-size:15px; font-weight:700; color:#2563eb;">${escapeHtmlCommon(data.spareName)}</span>
                </div>

                <div class="detail-item">
                    <span class="detail-label" style="display:block; font-size:11px; font-weight:700; color:#64748b;">Part Number</span>
                    <span class="detail-value" style="font-size:13px; font-weight:600; color:#0f172a;">${escapeHtmlCommon(data.partNo)}</span>
                </div>

                <div class="detail-item">
                    <span class="detail-label" style="display:block; font-size:11px; font-weight:700; color:#64748b;">Rack Number</span>
                    <span class="detail-value" style="font-size:13px; font-weight:600; color:#0f172a;">${escapeHtmlCommon(data.rackNumber)}</span>
                </div>

                <div class="detail-item">
                    <span class="detail-label" style="display:block; font-size:11px; font-weight:700; color:#64748b;">Brand</span>
                    <span class="detail-value" style="font-size:13px; font-weight:600; color:#0f172a;">${escapeHtmlCommon(data.brandName)}</span>
                </div>

                <div class="detail-item">
                    <span class="detail-label" style="display:block; font-size:11px; font-weight:700; color:#64748b;">Model</span>
                    <span class="detail-value" style="font-size:13px; font-weight:600; color:#0f172a;">${escapeHtmlCommon(data.modelName)}</span>
                </div>

                <div class="detail-item">
                    <span class="detail-label" style="display:block; font-size:11px; font-weight:700; color:#64748b;">Available Quantity</span>
                    <span class="detail-value" style="color:#084298; font-weight:700; font-size:14px;">${data.availableQty}</span>
                </div>

                <div class="detail-item">
                    <span class="detail-label" style="display:block; font-size:11px; font-weight:700; color:#64748b;">GST %</span>
                    <span class="detail-value" style="font-size:13px; font-weight:600; color:#0f172a;">${data.gstPercentage}%</span>
                </div>

                <div class="detail-item">
                    <span class="detail-label" style="display:block; font-size:11px; font-weight:700; color:#64748b;">Selling Price</span>
                    <span class="detail-value" style="color:#15803d; font-weight:700; font-size:14px;">₹${data.sellingPricePerUnit.toFixed(2)}</span>
                </div>

                <div class="detail-item">
                    <span class="detail-label" style="display:block; font-size:11px; font-weight:700; color:#64748b;">Sold Price</span>
                    <span class="detail-value" style="color:#15803d; font-weight:700; font-size:14px;">₹${data.selledPricePerUnit.toFixed(2)}</span>
                </div>
            </div>
        `;

        document.getElementById('commonScannerDetailsContent').innerHTML = content;

        try {
            JsBarcode("#commonModalBarcodeSvg", data.barCode, {
                format: "CODE128",
                width: 1.8,
                height: 44,
                displayValue: false
            });
        } catch(e) {}
    }

    function showCommonBarcodeResultNotFound(barcode) {
        const alertHeader = document.getElementById('commonScannerAlertHeader');
        alertHeader.className = 'alert-status alert-danger-bg';
        document.getElementById('commonScannerAlertText').innerHTML = '❌ Barcode Not Found';
        
        const btnPrint = document.getElementById('commonBtnPrintScanned');
        if (btnPrint) btnPrint.style.display = 'none';

        const btnView = document.getElementById('commonBtnViewItem');
        if (btnView) btnView.style.display = 'none';

        const content = `
            <div style="text-align:center; padding: 20px 10px;">
                <div style="font-size:16px; font-weight:700; color:#b91c1c; margin-bottom:8px;">
                    No spare / item matches barcode: <span style="font-family:monospace; text-decoration:underline;">${escapeHtmlCommon(barcode)}</span>
                </div>
                <p style="font-size:13px; color:#64748b; margin-bottom:16px;">
                    Please check the barcode sticker or try entering the barcode manually below.
                </p>
                <div class="manual-search-box" style="max-width:400px; margin:0 auto; padding:12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;">
                    <div class="manual-search-title" style="font-size:12px; font-weight:700; color:#475569; margin-bottom:6px;">Enter Barcode Manually</div>
                    <div class="manual-search-form" style="display:flex; gap:8px;">
                        <input type="text" id="commonNotFoundManualInput" value="${escapeHtmlCommon(barcode)}" class="manual-search-input" onkeydown="if(event.key==='Enter'){searchCommonBarcodeNotFound(); event.preventDefault();}" style="flex:1; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">
                        <button type="button" onclick="searchCommonBarcodeNotFound()" class="manual-search-btn" style="background:#0f172a; color:#fff; border:none; padding:8px 16px; border-radius:6px; font-weight:600; font-size:13px; cursor:pointer;">Search</button>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('commonScannerDetailsContent').innerHTML = content;
    }

    function searchCommonBarcodeNotFound() {
        const val = document.getElementById('commonNotFoundManualInput').value.trim();
        if (val) {
            searchCommonBarcodeAPI(val);
        }
    }

    window.addEventListener('beforeunload', stopCommonBarcodeScannerCamera);
    window.addEventListener('pagehide', stopCommonBarcodeScannerCamera);
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stopCommonBarcodeScannerCamera();
        }
    });

    function printCommonCurrentScannedLabel() {
        if (!commonCurrentScannedData || !commonCurrentScannedData.barCode) return;
        
        let w = window.open('', '_blank');
        let html = `
        <html>
        <head>
            <title>Print Barcode - ${escapeHtmlCommon(commonCurrentScannedData.barCode)}</title>
            <style>
                @page { size: auto; margin: 0; }
                body { font-family: sans-serif; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #fff; }
                .label-card { border: 2px dashed #000; padding: 15px 25px; text-align: center; border-radius: 8px; max-width: 300px; }
                .title { font-size: 14px; font-weight: bold; margin-bottom: 6px; }
                .code { font-family: monospace; font-size: 16px; font-weight: bold; margin-top: 4px; letter-spacing: 2px; }
            </style>
        </head>
        <body>
            <div class="label-card">
                <div class="title">${escapeHtmlCommon(commonCurrentScannedData.spareName)}</div>
                <svg id="printSvg"></svg>
                <div class="code">${escapeHtmlCommon(commonCurrentScannedData.barCode)}</div>
            </div>
            <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"><\/script>
            <script>
                JsBarcode("#printSvg", "${escapeHtmlCommon(commonCurrentScannedData.barCode)}", { format: "CODE128", width: 2, height: 50, displayValue: false });
                setTimeout(() => { window.print(); window.close(); }, 500);
            <\/script>
        </body>
        </html>`;
        w.document.write(html);
        w.document.close();
    }
</script>
