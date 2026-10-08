
<div id="commonBarcodeScannerModal" class="barcode-modal-backdrop" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.75); z-index:99999; align-items:center; justify-content:center; padding:15px; box-sizing:border-box;">
    <div class="barcode-modal-card" style="background:#fff; border-radius:12px; max-width:460px; width:100%; max-height:90vh; display:flex; flex-direction:column; overflow:hidden; box-shadow:0 25px 50px -12px rgba(0,0,0,0.35); margin:0 auto;">

        <div class="barcode-modal-header" style="display:flex; align-items:center; justify-content:space-between; padding:14px 18px; background:#f8fafc; border-bottom:1px solid #e2e8f0;">
            <div style="display:flex; align-items:center; gap:10px;">
                <button type="button" class="btn-scanner-back" onclick="closeCommonBarcodeScannerModal()" style="background:#e2e8f0; border:none; padding:5px 10px; border-radius:6px; font-weight:600; font-size:13px; cursor:pointer; color:#334155;">&larr; Back</button>
                <h3 id="commonBarcodeModalTitle" style="margin:0; font-size:16px; font-weight:700; color:#0f172a;">📷 Real Web Camera Barcode Scanner</h3>
            </div>
            <button type="button" class="barcode-modal-close" onclick="closeCommonBarcodeScannerModal()" style="background:none; border:none; font-size:24px; color:#64748b; cursor:pointer; line-height:1;">&times;</button>
        </div>

        <div class="barcode-modal-body" style="padding:0; overflow-y:auto; flex:1;">

            <div class="scanner-controls-bar" style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px; padding:10px 14px; background:#f8fafc; border-bottom:1px solid #e2e8f0;">
                <div class="camera-select-wrap" style="display:flex; align-items:center; gap:8px; flex:1; min-width:220px;">
                    <label for="commonCameraSelect" style="font-size:12px; font-weight:700; color:#475569; white-space:nowrap;">Camera:</label>
                    <select id="commonCameraSelect" class="erp-input" style="padding:6px 10px; font-size:13px; width:100%; border-radius:6px; border:1px solid #cbd5e1;" onchange="onCommonCameraSelectChange()"></select>
                    <button type="button" id="commonBtnRefreshCameras" onclick="refreshCommonCameraDevices()" class="btn-erp" style="padding:6px 10px; font-size:12px; white-space:nowrap; background:#f1f5f9; color:#334155; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;" title="Refresh available cameras">🔄 Refresh</button>
                </div>
            </div>

            <div id="commonScannerCameraSection" style="padding:14px;">
                <div id="commonScannerCameraStatus" class="camera-status-info" style="margin-bottom:10px; padding:8px 12px; background:#f1f5f9; border-radius:6px; font-size:12.5px; color:#334155; text-align:center; font-weight:600;">
                    Click <strong>Start Camera</strong> to scan barcodes.
                </div>

                <div class="scanner-view-box" id="commonScannerViewBox" style="position:relative; width:100%; max-width:220px; height:200px; margin:0 auto; background:#000; border:2.5px solid #38bdf8; border-radius:10px; overflow:hidden; display:flex; align-items:center; justify-content:center; box-shadow:0 0 16px rgba(56,189,248,0.35);">
                    <video id="commonCameraPreview" autoplay playsinline muted style="width:100%; height:100%; object-fit:cover;"></video>

                    <div class="scan-overlay" style="position:absolute; top:0; left:0; width:100%; height:100%; display:flex; align-items:center; justify-content:center; pointer-events:none;">
                        <div class="scan-line" style="position:absolute; width:100%; height:2px; background:#38bdf8; top:50%; box-shadow:0 0 8px #38bdf8; animation:scanLineMove 1.8s infinite ease-in-out;"></div>
                        <div style="position:absolute; bottom:6px; width:100%; text-align:center; color:#38bdf8; font-size:10.5px; font-weight:800; letter-spacing:1px; text-shadow:0 1px 3px rgba(0,0,0,0.85);">ALIGN BARCODE HERE</div>
                    </div>

                    <!-- Floating overlay message shown in front of camera without closing it -->
                    <div id="scannerOverlayMsg" style="display:none; position:absolute; bottom:8px; left:50%; transform:translateX(-50%); z-index:20; min-width:200px; max-width:92%; pointer-events:all;">
                        <div id="scannerOverlayMsgInner" style="background:rgba(15,23,42,0.92); backdrop-filter:blur(6px); border-radius:8px; padding:8px 12px; box-shadow:0 4px 20px rgba(0,0,0,0.4); font-size:12px; color:#f1f5f9; line-height:1.4;">
                        </div>
                    </div>
                </div>

                <div class="camera-btn-wrap" style="display:flex; align-items:center; justify-content:center; gap:10px; margin-top:14px; flex-wrap:wrap;">
                    <button type="button" id="commonBtnStartCamera" onclick="startCommonBarcodeScannerCamera()" class="btn-erp btn-erp-primary" style="padding:7px 16px; font-size:13px; background:#2563eb; color:#fff; border:none; border-radius:6px; font-weight:600; cursor:pointer;">▶ Start Camera</button>
                    <button type="button" id="commonBtnStopCamera" onclick="stopCommonBarcodeScannerCamera()" class="btn-erp btn-erp-danger" style="display:none; background:#dc2626; color:#fff; padding:7px 16px; font-size:13px; border:none; border-radius:6px; font-weight:600; cursor:pointer;">⏹ Stop Camera</button>
                    <button type="button" id="commonBtnCapturePhoto" onclick="captureCommonPhotoAndScan()" class="btn-erp" style="display:none; background:#0284c7; color:#fff; padding:7px 16px; font-size:13px; border:none; border-radius:6px; font-weight:600; cursor:pointer;">📸 Capture Photo</button>
                </div>

                <div id="commonPhotoPreviewBox" style="display:none; text-align:center; padding:12px; background:#0f172a; border-radius:8px; margin:10px 0;">
                    <div style="color:#e2e8f0; font-size:13px; font-weight:600; margin-bottom:8px;">Captured Frame Preview</div>
                    <img id="commonPhotoCapturedImg" style="max-width:100%; max-height:260px; border-radius:6px; border:2px solid #38bdf8; object-fit:contain;" alt="Captured Photo">
                    <canvas id="commonPhotoCapturedCanvas" style="display:none;"></canvas>
                    <div id="commonPhotoScanStatus" style="color:#38bdf8; font-weight:700; font-size:13px; margin-top:8px;">⌛ Scanning barcode from captured photo...</div>
                </div>

                <div class="manual-search-box" style="margin-top:12px; padding:12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;">
                    <div class="manual-search-title" style="font-size:12px; font-weight:700; color:#475569; margin-bottom:6px;">Or Enter Barcode Manually:</div>
                    <div class="manual-search-form" style="display:flex; gap:8px;">
                        <input type="text" id="commonManualBarcodeInput" class="manual-search-input" placeholder="e.g. 43103325 or T1102" onkeydown="if(event.key==='Enter'){searchCommonBarcodeManual(); event.preventDefault();}" style="flex:1; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">
                        <button type="button" onclick="searchCommonBarcodeManual()" class="manual-search-btn" style="background:#0f172a; color:#fff; border:none; padding:8px 16px; border-radius:6px; font-weight:600; font-size:13px; cursor:pointer;">Search</button>
                    </div>
                </div>
            </div>

            <div id="commonScannerResultPanel" style="display:none; padding:14px;">
                <div id="commonScannerAlertHeader" class="alert-status alert-success-bg" style="padding:10px 14px; border-radius:6px; font-weight:700; font-size:14px; margin-bottom:14px;">
                    <span id="commonScannerAlertText">✓ Barcode Scanned Successfully</span>
                </div>

                <div id="commonScannerDetailsContent">

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

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@zxing/library@0.21.0/umd/index.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@ericblade/quagga2@1.8.4/dist/quagga.min.js"></script>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    let commonScannerRunning = false;
    let commonMediaStream = null;
    let commonZxingCodeReader = null;
    let commonBarcodeDetectorAnimFrame = null;
    let commonLiveScanInterval = null;
    let commonCurrentScannedData = null;
    let currentBarcodeScannerCallback = null;
    let commonContinuousMode = false;
    let commonLastScanCode = '';
    let commonLastScanTime = 0;
    let commonScanLockUntil = 0; // Absolute timestamp — no scan accepted before this time
    const COMMON_SCAN_COOLDOWN = 2500; // ms before the SAME barcode can re-trigger

    function escapeHtmlCommon(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    window.openBarcodeScanner = function(callbackOrOptions) {
        let callback = null;
        let title = "📷 Real Web Camera Barcode Scanner";
        commonContinuousMode = false;

        if (typeof callbackOrOptions === 'function') {
            callback = callbackOrOptions;
        } else if (typeof callbackOrOptions === 'object' && callbackOrOptions !== null) {
            callback = callbackOrOptions.callback || null;
            if (callbackOrOptions.title) title = callbackOrOptions.title;
            if (callbackOrOptions.continuous) commonContinuousMode = true;
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
        commonContinuousMode = false;
    };

    async function refreshCommonCameraDevices() {
        const statusEl = document.getElementById('commonScannerCameraStatus');
        if (statusEl) statusEl.innerHTML = '🔄 Scanning available camera devices...';
        await initCommonCameraDevices(true);
        if (statusEl && !commonScannerRunning) {
            statusEl.innerHTML = 'Camera list updated. Click <strong>Start Camera</strong>.';
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
            statusEl.innerHTML = '🟢 Live Camera Active — Hold Barcode steady inside box or click 📸 Capture Photo';
        }

        console.log("Camera Started");

        initCommonCameraDevices();
        initCommonMultiEngineScanner(videoEl);
    }

    // Helper: contrast enhance / threshold canvas for phone screens & low contrast
    function preprocessCanvasContrast(sourceCanvas) {
        const out = document.createElement('canvas');
        out.width = sourceCanvas.width;
        out.height = sourceCanvas.height;
        const ctx = out.getContext('2d');
        ctx.drawImage(sourceCanvas, 0, 0);

        try {
            const imgData = ctx.getImageData(0, 0, out.width, out.height);
            const d = imgData.data;
            const factor = 1.6; // High contrast
            for (let i = 0; i < d.length; i += 4) {
                // Grayscale
                const gray = 0.299 * d[i] + 0.587 * d[i+1] + 0.114 * d[i+2];
                // Contrast stretch
                const contrasted = Math.min(255, Math.max(0, factor * (gray - 128) + 128));
                d[i] = contrasted;
                d[i+1] = contrasted;
                d[i+2] = contrasted;
            }
            ctx.putImageData(imgData, 0, 0);
        } catch(e) {}
        return out;
    }

    // Helper: crop center region (where user aligns barcode in guide frame)
    function cropCenterCanvas(sourceCanvas, ratio = 0.65) {
        const out = document.createElement('canvas');
        const sw = sourceCanvas.width;
        const sh = sourceCanvas.height;
        const cw = Math.round(sw * ratio);
        const ch = Math.round(sh * ratio);
        const cx = Math.round((sw - cw) / 2);
        const cy = Math.round((sh - ch) / 2);
        out.width = cw;
        out.height = ch;
        const ctx = out.getContext('2d');
        ctx.drawImage(sourceCanvas, cx, cy, cw, ch, 0, 0, cw, ch);
        return out;
    }

    // Crop to match ONLY the blue scan-frame box (240px×150px guide)
    // The box is roughly 38% of width and 42% of height of the camera view
    // This prevents barcodes/QR codes in the background from being detected
    function cropToScanFrame(sourceCanvas) {
        const out = document.createElement('canvas');
        const sw = sourceCanvas.width;
        const sh = sourceCanvas.height;
        const cw = Math.round(sw * 0.38);
        const ch = Math.round(sh * 0.44);
        const cx = Math.round((sw - cw) / 2);
        const cy = Math.round((sh - ch) / 2);
        out.width = cw;
        out.height = ch;
        const ctx = out.getContext('2d');
        ctx.drawImage(sourceCanvas, cx, cy, cw, ch, 0, 0, cw, ch);
        return out;
    }

    let commonCachedZxingReader = null;
    let commonCachedBarcodeDetector = null;

    function getCommonBarcodeDetector() {
        if (!commonCachedBarcodeDetector && 'BarcodeDetector' in window) {
            try {
                commonCachedBarcodeDetector = new BarcodeDetector({
                    formats: ['code_128', 'code_39', 'code_93', 'ean_13', 'ean_8', 'upc_a', 'upc_e', 'itf', 'qr_code', 'codabar', 'data_matrix']
                });
            } catch(e) {}
        }
        return commonCachedBarcodeDetector;
    }

    function getCommonZxingReader() {
        if (!commonCachedZxingReader && typeof ZXing !== 'undefined') {
            try {
                const hints = new Map();
                hints.set(ZXing.DecodeHintType.POSSIBLE_FORMATS, [
                    ZXing.BarcodeFormat.CODE_128,
                    ZXing.BarcodeFormat.CODE_39,
                    ZXing.BarcodeFormat.CODE_93,
                    ZXing.BarcodeFormat.EAN_13,
                    ZXing.BarcodeFormat.EAN_8,
                    ZXing.BarcodeFormat.UPC_A,
                    ZXing.BarcodeFormat.UPC_E,
                    ZXing.BarcodeFormat.ITF,
                    ZXing.BarcodeFormat.CODABAR,
                    ZXing.BarcodeFormat.QR_CODE,
                    ZXing.BarcodeFormat.DATA_MATRIX
                ]);
                hints.set(ZXing.DecodeHintType.TRY_HARDER, true);
                commonCachedZxingReader = new ZXing.BrowserMultiFormatReader(hints);
            } catch(e) {}
        }
        return commonCachedZxingReader;
    }

    // Comprehensive multi-engine decode function for any canvas
    async function decodeBarcodeFromCanvasMultiEngine(targetCanvas) {
        // 1. Native BarcodeDetector (First! Hardware accelerated, ~3ms)
        const detector = getCommonBarcodeDetector();
        if (detector) {
            try {
                const barcodes = await detector.detect(targetCanvas);
                if (barcodes && barcodes.length > 0 && barcodes[0].rawValue) {
                    const code = barcodes[0].rawValue.trim();
                    if (code && code.length >= 2) return code;
                }
            } catch(e) {}
        }

        // 2. ZXing Reader (Cached instance, ~15ms)
        const zxingReader = getCommonZxingReader();
        if (zxingReader) {
            try {
                const img = new Image();
                img.src = targetCanvas.toDataURL('image/jpeg', 0.88);
                await new Promise(res => { img.onload = res; img.onerror = res; });
                const res = await zxingReader.decodeFromImageElement(img);
                if (res && res.getText()) {
                    const code = res.getText().trim();
                    if (code && code.length >= 2) return code;
                }
            } catch(e) {}
        }

        // 3. Quagga2 pass (~20ms)
        if (typeof Quagga !== 'undefined') {
            const quaggaPromise = new Promise(resolve => {
                try {
                    const dataUrl = targetCanvas.toDataURL('image/jpeg', 0.85);
                    Quagga.decodeSingle({
                        src: dataUrl,
                        numOfWorkers: 0,
                        inputStream: { size: Math.max(targetCanvas.width, targetCanvas.height) },
                        decoder: {
                            readers: [
                                "code_128_reader",
                                "ean_reader",
                                "ean_8_reader",
                                "code_39_reader",
                                "code_39_vin_reader",
                                "codabar_reader",
                                "upc_reader",
                                "upc_e_reader",
                                "i2of5_reader",
                                "code_93_reader"
                            ]
                        },
                        locate: true
                    }, function(res) {
                        if (res && res.codeResult && res.codeResult.code) {
                            const code = res.codeResult.code.trim();
                            resolve(code && code.length >= 2 ? code : null);
                        } else {
                            resolve(null);
                        }
                    });
                } catch(e) {
                    resolve(null);
                }
            });

            const code = await quaggaPromise;
            if (code) return code;
        }

        return null;
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
        photoStatus.innerHTML = '⌛ Scanning barcode with multi-engine decoders...';

        // In continuous mode: don't stop camera, keep scanning after decode
        if (!commonContinuousMode) {
            stopCommonBarcodeScannerCamera();
        }

        console.log("Photo Captured, analyzing frame for barcode...");

        // Pass 1: Scan frame box crop (matches live scanner area — best for aligned barcodes)
        let detectedCode = await decodeBarcodeFromCanvasMultiEngine(cropToScanFrame(photoCanvas));

        // Pass 2: Wider center crop (65%)
        if (!detectedCode) {
            detectedCode = await decodeBarcodeFromCanvasMultiEngine(cropCenterCanvas(photoCanvas, 0.65));
        }

        // Pass 3: Contrast-enhanced scan frame (handles glare and low-contrast labels)
        if (!detectedCode) {
            detectedCode = await decodeBarcodeFromCanvasMultiEngine(preprocessCanvasContrast(cropToScanFrame(photoCanvas)));
        }

        // Pass 4: Full frame (fallback for very large barcodes)
        if (!detectedCode) {
            detectedCode = await decodeBarcodeFromCanvasMultiEngine(photoCanvas);
        }

        // Pass 5: Tight crop (0.42) with contrast
        if (!detectedCode) {
            detectedCode = await decodeBarcodeFromCanvasMultiEngine(preprocessCanvasContrast(cropCenterCanvas(photoCanvas, 0.42)));
        }

        if (detectedCode) {
            console.log("Barcode Found from Photo:", detectedCode);
            photoStatus.innerHTML = `🟢 Barcode Found: <strong>${escapeHtmlCommon(detectedCode)}</strong>`;

            if (commonContinuousMode && currentBarcodeScannerCallback) {
                // Continuous mode: invoke callback directly (same path as live scan)
                // then restart the camera for the next scan
                commonScanLockUntil = Date.now() + 3000;
                const statusEl = document.getElementById('commonScannerCameraStatus');
                if (statusEl) {
                    statusEl.innerHTML = `<span style="color:#0284c7; font-weight:700; font-size:14.5px;">🔍 Found: &nbsp;<span style="font-family:monospace; font-size:13px;">${escapeHtmlCommon(detectedCode)}</span> &nbsp;— Confirm details...</span>`;
                }
                playCommonScanBeep();
                currentBarcodeScannerCallback(detectedCode);
                // Restart camera so continuous mode keeps going
                setTimeout(() => {
                    if (document.getElementById('commonBarcodeScannerModal')?.style.display !== 'none') {
                        photoBox.style.display = 'none';
                        startCommonBarcodeScannerCamera();
                        setTimeout(() => { commonScanLockUntil = 0; }, 3000);
                    }
                }, 1200);
                return;
            }

            onCommonBarcodeScanned(detectedCode);
            return;
        }

        photoStatus.innerHTML = '<span style="color:#f87171; font-weight:700;">❌ No barcode detected in captured photo. Click Start Camera to try again or enter barcode manually below.</span>';
        const manualInput = document.getElementById('commonManualBarcodeInput');
        if (manualInput) manualInput.focus();

        // In continuous mode: restart camera automatically after failed decode
        if (commonContinuousMode) {
            setTimeout(() => {
                if (document.getElementById('commonBarcodeScannerModal')?.style.display !== 'none') {
                    photoBox.style.display = 'none';
                    startCommonBarcodeScannerCamera();
                }
            }, 2000);
        }
    }


    function initCommonMultiEngineScanner(videoEl) {
        if (!commonScannerRunning) return;

        // Dedicated reusable processing canvas for continuous live frame sampling
        const scanCanvas = document.createElement('canvas');
        const sctx = scanCanvas.getContext('2d', { willReadFrequently: true });

        const detector = getCommonBarcodeDetector();
        const zxingReader = getCommonZxingReader();

        let isProcessing = false;

        // Ultra-fast 130ms live scanning ticker (120ms-150ms maintained)
        commonLiveScanInterval = setInterval(async () => {
            if (!commonScannerRunning || !commonMediaStream || isProcessing) return;
            if (Date.now() < commonScanLockUntil) return;
            if (!videoEl || videoEl.readyState < 2 || videoEl.videoWidth === 0) return;

            isProcessing = true;
            try {
                const vw = videoEl.videoWidth;
                const vh = videoEl.videoHeight;

                // Crop center 52% width and 52% height directly from video
                // strictly matching the blue label viewfinder
                const cw = Math.round(vw * 0.52);
                const ch = Math.round(vh * 0.52);
                const cx = Math.round((vw - cw) / 2);
                const cy = Math.round((vh - ch) / 2);

                scanCanvas.width = 460;
                scanCanvas.height = Math.round(460 * (ch / cw));
                sctx.drawImage(videoEl, cx, cy, cw, ch, 0, 0, scanCanvas.width, scanCanvas.height);

                let code = null;

                // Engine 1: Native BarcodeDetector (First! Hardware accelerated, 2-4ms)
                if (detector) {
                    try {
                        const barcodes = await detector.detect(scanCanvas);
                        if (barcodes && barcodes.length > 0 && barcodes[0].rawValue) {
                            const raw = barcodes[0].rawValue.trim();
                            if (raw && raw.length >= 2) code = raw;
                        }
                    } catch(e) {}
                }

                // Engine 2: ZXing Reader (Cached instance, ~15ms)
                if (!code && zxingReader) {
                    try {
                        const img = new Image();
                        img.src = scanCanvas.toDataURL('image/jpeg', 0.85);
                        await new Promise(res => { img.onload = res; img.onerror = res; });
                        const res = await zxingReader.decodeFromImageElement(img);
                        if (res && res.getText()) {
                            const raw = res.getText().trim();
                            if (raw && raw.length >= 2) code = raw;
                        }
                    } catch(e) {}
                }

                // Engine 3: Quagga2 (~20ms)
                if (!code && typeof Quagga !== 'undefined') {
                    code = await new Promise(resolve => {
                        try {
                            Quagga.decodeSingle({
                                src: scanCanvas.toDataURL('image/jpeg', 0.85),
                                numOfWorkers: 0,
                                inputStream: { size: scanCanvas.width },
                                decoder: {
                                    readers: ["code_128_reader", "ean_reader", "ean_8_reader", "code_39_reader", "upc_reader", "upc_e_reader", "i2of5_reader", "codabar_reader"]
                                },
                                locate: true
                            }, res => {
                                if (res && res.codeResult && res.codeResult.code) {
                                    const raw = res.codeResult.code.trim();
                                    resolve(raw && raw.length >= 2 ? raw : null);
                                } else {
                                    resolve(null);
                                }
                            });
                        } catch(e) {
                            resolve(null);
                        }
                    });
                }

                if (code && code.length >= 2 && commonScannerRunning) {
                    handleBarcodeDetectionEvent(code);
                }
            } catch(e) {}
            isProcessing = false;
        }, 130);
    }

    function handleBarcodeDetectionEvent(code) {
        if (!code || code.length < 2) return;
        const now = Date.now();

        // Hard lock — no scan accepted during the post-scan cooldown window
        if (now < commonScanLockUntil) return;

        // If spare details confirmation modal is visible, pause scanning until dismissed
        const confirmModal = document.getElementById('spareDetailsConfirmModal');
        if (confirmModal && confirmModal.style.display !== 'none' && confirmModal.style.display !== '') {
            return;
        }

        // Per-code cooldown — prevents the same barcode firing multiple times rapidly
        if (code === commonLastScanCode && (now - commonLastScanTime) < COMMON_SCAN_COOLDOWN) {
            return;
        }
        commonLastScanCode = code;
        commonLastScanTime = now;
        onCommonBarcodeScanned(code);
    }

    function stopCommonBarcodeScannerCamera() {
        commonScannerRunning = false;

        if (commonLiveScanInterval) {
            clearInterval(commonLiveScanInterval);
            commonLiveScanInterval = null;
        }

        // Also clear the ZXing frame-grab interval (Engine 2)
        if (window._zxingFrameInterval) {
            clearInterval(window._zxingFrameInterval);
            window._zxingFrameInterval = null;
        }

        // Reset scan lock so the scanner is fresh next time it opens
        commonScanLockUntil = 0;
        commonLastScanCode = '';
        commonLastScanTime = 0;

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
            statusEl.innerHTML = 'Camera stopped. Click <strong>Start Camera</strong>.';
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
        console.log('Barcode Found:', barcode);
        playCommonScanBeep();

        if (commonContinuousMode && currentBarcodeScannerCallback) {
            // Continuous Scan-to-Bill mode — keep camera running, but lock for 3s
            // to prevent the SAME scan from firing multiple times
            commonScanLockUntil = Date.now() + 3000;

            const statusEl = document.getElementById('commonScannerCameraStatus');
            if (statusEl) {
                statusEl.innerHTML = `<span style="color:#0284c7; font-weight:700; font-size:14.5px;">🔍 Scanned: &nbsp;<span style="font-family:monospace; font-size:13px;">${escapeHtmlCommon(barcode)}</span> &nbsp;— Confirm details...</span>`;
                setTimeout(() => {
                    if (commonScannerRunning && statusEl && (!document.getElementById('spareDetailsConfirmModal') || document.getElementById('spareDetailsConfirmModal').style.display === 'none')) {
                        statusEl.innerHTML = '🟢 Live Camera Active — Hold Barcode steady inside box or click 📸 Capture Photo';
                        commonScanLockUntil = 0; // Re-enable scanning
                    }
                }, 3000);
            }
            currentBarcodeScannerCallback(barcode);
            return;
        }

        // Single-scan mode: stop camera and look up barcode
        stopCommonBarcodeScannerCamera();
        searchCommonBarcodeAPI(barcode);
    }

    function searchCommonBarcodeManual() {
        const code = document.getElementById('commonManualBarcodeInput').value.trim();
        if (!code) {
            alert('Please enter a barcode string!');
            return;
        }
        if (commonContinuousMode && currentBarcodeScannerCallback) {
            document.getElementById('commonManualBarcodeInput').value = '';
            playCommonScanBeep();
            const statusEl = document.getElementById('commonScannerCameraStatus');
            if (statusEl) {
                statusEl.innerHTML = `<span style="color:#0284c7; font-weight:700; font-size:14.5px;">🔍 Searched: &nbsp;<span style="font-family:monospace; font-size:13px;">${escapeHtmlCommon(code)}</span> &nbsp;— Confirm details...</span>`;
            }
            currentBarcodeScannerCallback(code);
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
                if (res.success && res.data) {
                    if (currentBarcodeScannerCallback) {
                        // Let the callback page handle modal closing & UI
                        currentBarcodeScannerCallback(barcode, res.data);
                        return;
                    }
                    document.getElementById('commonScannerCameraSection').style.display = 'none';
                    document.getElementById('commonScannerResultPanel').style.display = 'block';
                    commonCurrentScannedData = res.data;
                    showCommonBarcodeResultSuccess(res.data);
                } else {
                    // Not found in stock
                    if (currentBarcodeScannerCallback) {
                        // Let the callback page show its own in-camera message
                        currentBarcodeScannerCallback(barcode, null);
                        return;
                    }
                    document.getElementById('commonScannerCameraSection').style.display = 'none';
                    document.getElementById('commonScannerResultPanel').style.display = 'block';
                    commonCurrentScannedData = null;
                    showCommonBarcodeResultNotFound(barcode);
                }
            })
            .catch(err => {
                if (currentBarcodeScannerCallback) {
                    currentBarcodeScannerCallback(barcode, null);
                    return;
                }
                alert('Error querying barcode API: ' + err);
            });
    }

    // Show a small floating message bubble in front of the camera (does NOT close camera)
    window.showScannerOverlayMessage = function(type, htmlContent, autoDismissMs) {
        const box = document.getElementById('scannerOverlayMsg');
        const inner = document.getElementById('scannerOverlayMsgInner');
        if (!box || !inner) return;

        const colors = {
            warning: { bg: 'rgba(120,53,15,0.93)', border: '#f59e0b', icon: '⚠️' },
            error:   { bg: 'rgba(127,29,29,0.93)', border: '#f87171', icon: '❌' },
            info:    { bg: 'rgba(15,23,42,0.92)',   border: '#38bdf8', icon: 'ℹ️' },
            success: { bg: 'rgba(6,78,59,0.93)',    border: '#34d399', icon: '✅' }
        };
        const c = colors[type] || colors.info;

        inner.style.background = c.bg;
        inner.style.border = '1.5px solid ' + c.border;
        inner.innerHTML = htmlContent;
        box.style.display = 'block';

        // Clear any previous auto-dismiss
        if (window._scannerOverlayTimer) clearTimeout(window._scannerOverlayTimer);
        if (autoDismissMs && autoDismissMs > 0) {
            window._scannerOverlayTimer = setTimeout(() => {
                window.hideScannerOverlayMessage();
            }, autoDismissMs);
        }
    };

    window.hideScannerOverlayMessage = function() {
        const box = document.getElementById('scannerOverlayMsg');
        if (box) box.style.display = 'none';
        if (window._scannerOverlayTimer) {
            clearTimeout(window._scannerOverlayTimer);
            window._scannerOverlayTimer = null;
        }
    };

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
        document.getElementById('commonScannerAlertText').innerHTML = '❌ Stock Not Added (Barcode Not Found)';

        const btnPrint = document.getElementById('commonBtnPrintScanned');
        if (btnPrint) btnPrint.style.display = 'none';

        const btnView = document.getElementById('commonBtnViewItem');
        if (btnView) btnView.style.display = 'none';

        const currentPath = window.location.pathname;
        const addStockUrl = (currentPath.includes('/stock/') ? '' : '../stock/') + 'add_stock.php?barcode=' + encodeURIComponent(barcode);

        const content = `
            <div style="text-align:center; padding: 20px 10px;">
                <div style="font-size:16px; font-weight:700; color:#b91c1c; margin-bottom:8px;">
                    Stock not added for barcode: <span style="font-family:monospace; text-decoration:underline;">${escapeHtmlCommon(barcode)}</span>
                </div>
                <p style="font-size:13px; color:#64748b; margin-bottom:14px;">
                    This product is not in our stock list. You can add it directly below or try entering another barcode.
                </p>
                <div style="margin-bottom:18px;">
                    <a href="${addStockUrl}" target="_blank" style="background:#2563eb; color:#ffffff; padding:8px 18px; border-radius:6px; text-decoration:none; font-size:13px; font-weight:700; display:inline-flex; align-items:center; gap:6px; box-shadow:0 2px 6px rgba(37,99,235,0.25);">
                        ➕ Add to Stock
                    </a>
                </div>
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

