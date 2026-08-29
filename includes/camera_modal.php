<!-- UNIFIED ERP CAMERA MODAL COMPONENT -->
<div id="erpCameraModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.8); z-index:999999; align-items:center; justify-content:center; padding:15px; box-sizing:border-box;">
    <div style="background:#fff; border-radius:14px; max-width:540px; width:100%; max-height:92vh; display:flex; flex-direction:column; overflow:hidden; box-shadow:0 25px 50px -12px rgba(0,0,0,0.4);">
        
        <!-- MODAL HEADER -->
        <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 18px; background:#f8fafc; border-bottom:1px solid #e2e8f0;">
            <div style="display:flex; align-items:center; gap:8px;">
                <span style="font-size:18px;">📷</span>
                <h3 style="margin:0; font-size:16px; font-weight:700; color:#0f172a;" id="erpCameraModalTitle">Take Photo</h3>
            </div>
            <button type="button" onclick="closeErpCamera()" style="background:none; border:none; font-size:24px; color:#64748b; cursor:pointer; line-height:1;" title="Close Modal">&times;</button>
        </div>

        <!-- MODAL BODY -->
        <div style="padding:14px; overflow-y:auto; flex:1; display:flex; flex-direction:column; align-items:center;">
            
            <!-- CAMERA SELECTOR BAR -->
            <div id="erpCameraControlsBar" style="display:flex; align-items:center; justify-content:space-between; gap:10px; width:100%; margin-bottom:10px;">
                <select id="erpCameraDeviceSelect" style="flex:1; padding:7px 10px; font-size:12.5px; border-radius:6px; border:1px solid #cbd5e1; background:#f8fafc;" onchange="onErpCameraDeviceChange()"></select>
                <button type="button" onclick="switchErpCamera()" style="background:#f1f5f9; color:#334155; border:1px solid #cbd5e1; padding:7px 12px; border-radius:6px; font-size:12px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:4px; white-space:nowrap;">
                    🔄 Switch Camera
                </button>
            </div>

            <!-- LIVE STREAM / PREVIEW CONTAINER -->
            <div style="position:relative; width:100%; height:320px; background:#0f172a; border-radius:10px; overflow:hidden; display:flex; align-items:center; justify-content:center;">
                <video id="erpCameraVideo" autoplay playsinline muted style="width:100%; height:100%; object-fit:cover; display:block;"></video>
                <img id="erpCameraCapturedImg" src="" alt="Captured Photo" style="width:100%; height:100%; object-fit:cover; display:none;">
                <canvas id="erpCameraCanvas" style="display:none;"></canvas>
            </div>

            <!-- STATUS NOTICE -->
            <div id="erpCameraStatus" style="margin-top:10px; width:100%; padding:8px 12px; background:#f1f5f9; border-radius:6px; font-size:12px; color:#475569; text-align:center; box-sizing:border-box;">
                Click <strong>Capture Photo</strong> to take snapshot.
            </div>

            <!-- FALLBACK CAMERA INPUT -->
            <input type="file" id="erpCameraFallbackInput" accept="image/*" capture="environment" style="display:none;" onchange="handleErpCameraFallbackSelect(this)">
        </div>

        <!-- MODAL FOOTER ACTIONS -->
        <div style="padding:12px 18px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; gap:10px;">
            <button type="button" onclick="closeErpCamera()" style="background:#cbd5e1; color:#0f172a; border:none; padding:8px 14px; border-radius:6px; font-size:12.5px; font-weight:600; cursor:pointer;">
                Cancel
            </button>

            <!-- LIVE MODE BUTTONS -->
            <div id="erpCameraLiveActions" style="display:flex; gap:8px;">
                <button type="button" id="btnErpCapturePhoto" onclick="captureErpPhoto()" style="background:#16a34a; color:#fff; border:none; padding:8px 18px; border-radius:6px; font-size:13px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                    📸 Capture Photo
                </button>
            </div>

            <!-- PREVIEW MODE BUTTONS -->
            <div id="erpCameraPreviewActions" style="display:none; gap:8px;">
                <button type="button" onclick="retakeErpPhoto()" style="background:#0284c7; color:#fff; border:none; padding:8px 14px; border-radius:6px; font-size:12.5px; font-weight:700; cursor:pointer;">
                    🔄 Retake
                </button>
                <button type="button" onclick="confirmErpPhoto()" style="background:#16a34a; color:#fff; border:none; padding:8px 18px; border-radius:6px; font-size:13px; font-weight:700; cursor:pointer;">
                    ✅ Use Photo
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let erpCameraStream = null;
let erpCameraActiveCallback = null;
let erpCameraVideoDevices = [];
let erpCameraCurrentDeviceIdx = 0;
let erpCameraCapturedDataUrl = null;

function openErpCamera(callback, options = {}) {
    erpCameraActiveCallback = callback;
    erpCameraCapturedDataUrl = null;

    const modal = document.getElementById('erpCameraModal');
    const liveActions = document.getElementById('erpCameraLiveActions');
    const previewActions = document.getElementById('erpCameraPreviewActions');
    const video = document.getElementById('erpCameraVideo');
    const img = document.getElementById('erpCameraCapturedImg');
    const status = document.getElementById('erpCameraStatus');

    if (modal) modal.style.display = 'flex';
    if (liveActions) liveActions.style.display = 'flex';
    if (previewActions) previewActions.style.display = 'none';
    if (video) video.style.display = 'block';
    if (img) img.style.display = 'none';
    if (status) status.innerHTML = '⌛ Accessing camera...';

    // If HTTP origin or unsupported WebRTC mediaDevices, fallback to native camera file picker
    const isHttpRemote = (location.protocol === 'http:' && !['localhost', '127.0.0.1'].includes(location.hostname));
    if (isHttpRemote || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        if (status) status.innerHTML = '📷 Opening native device camera picker...';
        closeErpCameraStreamOnly();
        if (modal) modal.style.display = 'none';
        const fallbackInput = document.getElementById('erpCameraFallbackInput');
        if (fallbackInput) fallbackInput.click();
        return;
    }

    initErpCameraDevices().then(() => {
        startErpCameraStream();
    });
}

async function initErpCameraDevices() {
    const select = document.getElementById('erpCameraDeviceSelect');
    if (!select) return;
    select.innerHTML = '';
    erpCameraVideoDevices = [];

    try {
        const devices = await navigator.mediaDevices.enumerateDevices();
        erpCameraVideoDevices = devices.filter(d => d.kind === 'videoinput');

        if (erpCameraVideoDevices.length > 0) {
            erpCameraVideoDevices.forEach((d, idx) => {
                const opt = document.createElement('option');
                opt.value = d.deviceId;
                let label = d.label ? d.label.trim() : `Camera ${idx + 1}`;
                if (label.toLowerCase().includes('back') || label.toLowerCase().includes('rear')) {
                    label = `📷 Back Camera (${label})`;
                } else if (label.toLowerCase().includes('front') || label.toLowerCase().includes('user')) {
                    label = `🤳 Front Camera (${label})`;
                }
                opt.text = label;
                select.appendChild(opt);
            });
            // Default to rear camera if present
            const rearIdx = erpCameraVideoDevices.findIndex(d => {
                const l = d.label.toLowerCase();
                return l.includes('back') || l.includes('rear') || l.includes('environment');
            });
            if (rearIdx !== -1) erpCameraCurrentDeviceIdx = rearIdx;
            select.value = erpCameraVideoDevices[erpCameraCurrentDeviceIdx].deviceId;
        } else {
            select.innerHTML = '<option value="">Default Camera</option>';
        }
    } catch(e) {
        select.innerHTML = '<option value="">Default Camera</option>';
    }
}

async function startErpCameraStream() {
    closeErpCameraStreamOnly();

    const video = document.getElementById('erpCameraVideo');
    const status = document.getElementById('erpCameraStatus');
    const select = document.getElementById('erpCameraDeviceSelect');
    const deviceId = select ? select.value : '';

    const constraintsList = [];
    if (deviceId) {
        constraintsList.push({ video: { deviceId: { exact: deviceId } }, audio: false });
    }
    constraintsList.push({ video: { facingMode: { ideal: "environment" }, width: { ideal: 1280 } }, audio: false });
    constraintsList.push({ video: { facingMode: { ideal: "environment" } }, audio: false });
    constraintsList.push({ video: true, audio: false });

    let stream = null;
    let lastErr = null;

    for (const c of constraintsList) {
        try {
            stream = await navigator.mediaDevices.getUserMedia(c);
            if (stream) break;
        } catch(err) {
            lastErr = err;
        }
    }

    if (!stream) {
        if (status) {
            status.innerHTML = '<span style="color:#dc2626; font-weight:700;">Please allow camera permission to take a photo.</span>';
        }
        // Fallback to native camera input
        setTimeout(() => {
            const modal = document.getElementById('erpCameraModal');
            if (modal) modal.style.display = 'none';
            document.getElementById('erpCameraFallbackInput').click();
        }, 1200);
        return;
    }

    erpCameraStream = stream;
    if (video) {
        video.srcObject = erpCameraStream;
        try { await video.play(); } catch(e) {}
    }
    if (status) status.innerHTML = '🟢 Camera live — Click <strong>Capture Photo</strong>';
}

function onErpCameraDeviceChange() {
    startErpCameraStream();
}

function switchErpCamera() {
    if (erpCameraVideoDevices.length <= 1) {
        alert("Only one camera device detected on this system.");
        return;
    }
    erpCameraCurrentDeviceIdx = (erpCameraCurrentDeviceIdx + 1) % erpCameraVideoDevices.length;
    const select = document.getElementById('erpCameraDeviceSelect');
    if (select && erpCameraVideoDevices[erpCameraCurrentDeviceIdx]) {
        select.value = erpCameraVideoDevices[erpCameraCurrentDeviceIdx].deviceId;
    }
    startErpCameraStream();
}

function captureErpPhoto() {
    const video = document.getElementById('erpCameraVideo');
    const canvas = document.getElementById('erpCameraCanvas');
    const img = document.getElementById('erpCameraCapturedImg');
    const liveActions = document.getElementById('erpCameraLiveActions');
    const previewActions = document.getElementById('erpCameraPreviewActions');
    const status = document.getElementById('erpCameraStatus');

    if (!video || video.videoWidth === 0) {
        alert("Camera feed is not active yet.");
        return;
    }

    canvas.width = video.videoWidth || 1280;
    canvas.height = video.videoHeight || 720;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    erpCameraCapturedDataUrl = canvas.toDataURL('image/jpeg', 0.92);

    video.style.display = 'none';
    img.src = erpCameraCapturedDataUrl;
    img.style.display = 'block';

    if (liveActions) liveActions.style.display = 'none';
    if (previewActions) previewActions.style.display = 'flex';
    if (status) status.innerHTML = '✨ Snapshot captured! Click <strong>Use Photo</strong> or <strong>Retake</strong>.';

    closeErpCameraStreamOnly();
}

function retakeErpPhoto() {
    const video = document.getElementById('erpCameraVideo');
    const img = document.getElementById('erpCameraCapturedImg');
    const liveActions = document.getElementById('erpCameraLiveActions');
    const previewActions = document.getElementById('erpCameraPreviewActions');

    video.style.display = 'block';
    img.style.display = 'none';
    img.src = '';
    erpCameraCapturedDataUrl = null;

    if (liveActions) liveActions.style.display = 'flex';
    if (previewActions) previewActions.style.display = 'none';

    startErpCameraStream();
}

function confirmErpPhoto() {
    if (!erpCameraCapturedDataUrl) {
        alert("No photo captured yet.");
        return;
    }

    const dataUrl = erpCameraCapturedDataUrl;
    const callback = erpCameraActiveCallback;

    closeErpCamera();

    if (typeof callback === 'function') {
        try {
            const arr = dataUrl.split(',');
            const mime = arr[0].match(/:(.*?);/)[1];
            const bstr = atob(arr[1]);
            let n = bstr.length;
            const u8arr = new Uint8Array(n);
            while (n--) {
                u8arr[n] = bstr.charCodeAt(n);
            }
            const file = new File([u8arr], 'camera_photo_' + Date.now() + '.jpg', { type: mime });
            callback(dataUrl, file);
        } catch(e) {
            callback(dataUrl, null);
        }
    }
}

function handleErpCameraFallbackSelect(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            if (typeof erpCameraActiveCallback === 'function') {
                erpCameraActiveCallback(e.target.result, file);
            }
        };
        reader.readAsDataURL(file);
    }
}

function closeErpCameraStreamOnly() {
    if (erpCameraStream) {
        try {
            erpCameraStream.getTracks().forEach(t => t.stop());
        } catch(e) {}
        erpCameraStream = null;
    }
    const video = document.getElementById('erpCameraVideo');
    if (video) video.srcObject = null;
}

function closeErpCamera() {
    closeErpCameraStreamOnly();
    const modal = document.getElementById('erpCameraModal');
    if (modal) modal.style.display = 'none';
    erpCameraActiveCallback = null;
    erpCameraCapturedDataUrl = null;
}

window.addEventListener('beforeunload', closeErpCameraStreamOnly);
window.addEventListener('pagehide', closeErpCameraStreamOnly);
</script>
