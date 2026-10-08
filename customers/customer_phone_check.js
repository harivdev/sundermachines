/**
 * Customer Phone Check Utility
 * Detects if a primary phone number already belongs to an existing customer
 * and shows a friendly, non-emergency popup with customer summary and actions.
 */

(function () {
    let activeCustomer = null;
    let onUseExistingCallback = null;
    let onTryAnotherCallback = null;
    let onNavigateDetailsCallback = null;
    let currentInputEl = null;

    function ensureModalExists() {
        if (document.getElementById('custDuplicateModal')) return;

        const style = document.createElement('style');
        style.id = 'custDuplicateModalStyles';
        style.textContent = `
            @keyframes dupPopIn {
                from { opacity: 0; transform: scale(0.96) translateY(6px); }
                to { opacity: 1; transform: scale(1) translateY(0); }
            }
            .cust-dup-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(15, 23, 42, 0.45);
                backdrop-filter: blur(2px);
                z-index: 999999;
                display: none;
                align-items: center;
                justify-content: center;
                padding: 15px;
                box-sizing: border-box;
            }
            .cust-dup-card {
                position: relative;
                background: #ffffff;
                border-radius: 14px;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.12), 0 8px 10px -6px rgba(0, 0, 0, 0.08);
                width: 100%;
                max-width: 390px;
                overflow: hidden;
                border: 1px solid #e2e8f0;
                animation: dupPopIn 0.22s cubic-bezier(0.16, 1, 0.3, 1);
                font-family: 'DM Sans', 'Inter', system-ui, -apple-system, sans-serif;
                box-sizing: border-box;
            }
            .cust-dup-top-icon-btn {
                position: absolute;
                top: 10px;
                width: 26px;
                height: 26px;
                border: none;
                background: transparent;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                line-height: 1;
                padding: 0;
                border-radius: 6px;
                transition: transform 0.15s ease, background-color 0.15s ease, color 0.15s ease;
                text-decoration: none;
                user-select: none;
                z-index: 10;
            }
            .cust-dup-btn-close-top {
                left: 12px;
                font-size: 14px;
            }
            .cust-dup-btn-close-top:hover {
                background: #fee2e2;
                transform: scale(1.15);
            }
            .cust-dup-btn-edit-top {
                right: 12px;
                font-size: 14px;
            }
            .cust-dup-btn-edit-top:hover {
                background: #f1f5f9;
                transform: scale(1.15);
            }
            .cust-dup-btn-use {
                background: #1a7a4a;
                color: #ffffff;
                border: none;
                padding: 8px 20px;
                border-radius: 6px;
                font-weight: 600;
                font-size: 13px;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                box-shadow: 0 2px 5px rgba(26, 122, 74, 0.25);
                transition: all 0.15s ease;
            }
            .cust-dup-btn-use:hover {
                background: #145f39;
                transform: translateY(-1px);
            }
            .cust-dup-btn-try {
                background: #f1f5f9;
                color: #475569;
                border: 1px solid #cbd5e1;
                padding: 8px 18px;
                border-radius: 6px;
                font-weight: 600;
                font-size: 13px;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                transition: all 0.15s ease;
            }
            .cust-dup-btn-try:hover {
                background: #e2e8f0;
                color: #0f172a;
            }

            /* Responsive / Mobile View */
            @media (max-width: 480px) {
                .cust-dup-overlay {
                    padding: 10px;
                }
                .cust-dup-card {
                    max-width: 95% !important;
                    border-radius: 12px !important;
                }
                .cust-dup-inner {
                    padding: 18px 14px 14px !important;
                }
                .cust-dup-detail-box {
                    padding: 10px 12px !important;
                    margin: 12px 0 !important;
                    font-size: 12.5px !important;
                }
                .cust-dup-btn-wrap {
                    gap: 8px !important;
                    flex-wrap: wrap !important;
                }
                .cust-dup-btn-use, .cust-dup-btn-try {
                    flex: 1 1 auto !important;
                    min-width: 120px !important;
                    padding: 8px 14px !important;
                    font-size: 12.5px !important;
                    justify-content: center !important;
                }
                .cust-dup-top-icon-btn {
                    top: 8px;
                    width: 24px;
                    height: 24px;
                }
                .cust-dup-btn-close-top {
                    left: 10px;
                    font-size: 15px;
                }
                .cust-dup-btn-edit-top {
                    right: 10px;
                    font-size: 14px;
                }
            }
        `;
        document.head.appendChild(style);

        const modalDiv = document.createElement('div');
        modalDiv.id = 'custDuplicateModal';
        modalDiv.className = 'cust-dup-overlay';
        modalDiv.innerHTML = `
            <div class="cust-dup-card" role="dialog" aria-modal="true">
                <!-- Left Top Corner: ❌ Icon to Close -->
                <button type="button" id="dupBtnCloseTop" class="cust-dup-top-icon-btn cust-dup-btn-close-top" title="Close" aria-label="Close">❌</button>

                <!-- Right Top Corner: ✏️ Pencil Icon to Navigate Customer Details -->
                <a href="#" id="dupBtnEditTop" class="cust-dup-top-icon-btn cust-dup-btn-edit-top" title="View Customer Details in List" target="_blank" aria-label="View Customer Details">✏️</a>

                <div class="cust-dup-inner" style="padding: 22px 24px 18px; text-align: center;">
                    <div style="width: 42px; height: 42px; border-radius: 50%; background: #e0f2fe; color: #0284c7; display: inline-flex; align-items: center; justify-content: center; font-size: 19px; margin-bottom: 10px;">
                        <i class="fa-solid fa-address-book"></i>
                    </div>
                    <div style="font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">Customer Already Registered</div>
                    <div style="font-size: 13px; color: #64748b; line-height: 1.4;">
                        This number "<strong id="dupPhoneDisplay" style="color: #0f172a;"></strong>" is already in our records.
                    </div>
                    
                    <div class="cust-dup-detail-box" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 16px; margin: 16px 0; text-align: left; font-size: 13px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px dashed #e2e8f0;">
                            <span style="color: #64748b; font-weight: 500;">Customer Name:</span>
                            <strong id="dupNameDisplay" style="color: #0f172a; text-align: right; max-width: 60%; word-break: break-word;"></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px dashed #e2e8f0;">
                            <span style="color: #64748b; font-weight: 500;">Primary Number:</span>
                            <strong id="dupPrimaryDisplay" style="color: #0284c7; text-align: right;"></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px dashed #e2e8f0;">
                            <span style="color: #64748b; font-weight: 500;">City:</span>
                            <span id="dupCityDisplay" style="color: #1e293b; font-weight: 600; text-align: right;"></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0;">
                            <span style="color: #64748b; font-weight: 500;">Date of Account Creation:</span>
                            <span id="dupDateDisplay" style="color: #1e293b; font-weight: 600; text-align: right;"></span>
                        </div>
                    </div>

                    <div class="cust-dup-btn-wrap" style="display: flex; justify-content: center; gap: 12px; margin-top: 14px;">
                        <button type="button" id="dupBtnUseExisting" class="cust-dup-btn-use">
                            <i class="fa fa-check"></i> Use Existing
                        </button>
                        <button type="button" id="dupBtnTryAnother" class="cust-dup-btn-try">
                            <i class="fa fa-rotate-left"></i> Try Another
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modalDiv);

        document.getElementById('dupBtnCloseTop').addEventListener('click', function () {
            hideModal();
        });

        document.getElementById('dupBtnUseExisting').addEventListener('click', function () {
            hideModal();
            if (typeof onUseExistingCallback === 'function' && activeCustomer) {
                onUseExistingCallback(activeCustomer);
            }
        });

        document.getElementById('dupBtnTryAnother').addEventListener('click', function () {
            hideModal();
            if (typeof onTryAnotherCallback === 'function') {
                onTryAnotherCallback();
            } else if (currentInputEl) {
                currentInputEl.value = '';
                currentInputEl.focus();
            }
        });

        modalDiv.addEventListener('click', function (e) {
            if (e.target === modalDiv) {
                hideModal();
            }
        });
    }

    function showModal(customer, typedPhone, onUse, onTry, onNav, inputEl) {
        ensureModalExists();
        activeCustomer = customer;
        onUseExistingCallback = onUse;
        onTryAnotherCallback = onTry;
        onNavigateDetailsCallback = onNav;
        currentInputEl = inputEl;

        document.getElementById('dupPhoneDisplay').textContent = typedPhone || customer.phoneNo1 || '';
        document.getElementById('dupNameDisplay').textContent = customer.name || '—';
        document.getElementById('dupPrimaryDisplay').textContent = customer.phoneNo1 || '—';
        document.getElementById('dupCityDisplay').textContent = customer.city || '—';
        document.getElementById('dupDateDisplay').textContent = customer.createdOnFormatted || '—';

        // Configure right top corner pencil icon link
        let targetUrl = 'manage_customers.php?phoneNo1=' + encodeURIComponent(customer.phoneNo1 || '');
        if (window.location.pathname.indexOf('/customers/') === -1) {
            targetUrl = '../customers/' + targetUrl;
        }
        const editLinkEl = document.getElementById('dupBtnEditTop');
        if (editLinkEl) {
            editLinkEl.href = targetUrl;
            editLinkEl.onclick = function (e) {
                if (typeof onNavigateDetailsCallback === 'function') {
                    e.preventDefault();
                    hideModal();
                    onNavigateDetailsCallback(activeCustomer);
                }
            };
        }

        const modal = document.getElementById('custDuplicateModal');
        modal.style.display = 'flex';
    }

    function hideModal() {
        const modal = document.getElementById('custDuplicateModal');
        if (modal) modal.style.display = 'none';
    }

    window.setupCustomerPhoneCheck = function (options) {
        ensureModalExists();

        const input = typeof options.input === 'string'
            ? document.querySelector(options.input)
            : options.input;

        if (!input) return;

        const apiPath = options.apiPath || '../customers/api_check_customer_phone.php';
        let debounceTimer = null;
        let lastCheckedPhone = '';
        let isChecking = false;

        function runCheck() {
            const rawVal = input.value.trim();
            const cleanDigits = rawVal.replace(/\D/g, '');

            // Require at least 10 digits
            if (cleanDigits.length < 10) return;

            const excludeId = typeof options.getExcludeId === 'function'
                ? (options.getExcludeId() || 0)
                : 0;

            if (cleanDigits === lastCheckedPhone) return;

            isChecking = true;
            const url = apiPath + '?phone=' + encodeURIComponent(cleanDigits) + '&exclude_id=' + encodeURIComponent(excludeId);

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    isChecking = false;
                    if (data && data.exists && data.customer) {
                        lastCheckedPhone = cleanDigits;
                        showModal(
                            data.customer,
                            cleanDigits,
                            function (cust) {
                                if (typeof options.onUseExisting === 'function') {
                                    options.onUseExisting(cust);
                                }
                            },
                            function () {
                                lastCheckedPhone = '';
                                if (typeof options.onTryAnother === 'function') {
                                    options.onTryAnother();
                                } else {
                                    input.value = '';
                                    input.focus();
                                }
                            },
                            options.onNavigateDetails || null,
                            input
                        );
                    }
                })
                .catch(() => {
                    isChecking = false;
                });
        }

        input.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const digits = this.value.replace(/\D/g, '');
            if (digits.length >= 10) {
                debounceTimer = setTimeout(runCheck, 350);
            }
        });

        input.addEventListener('blur', function () {
            clearTimeout(debounceTimer);
            const digits = this.value.replace(/\D/g, '');
            if (digits.length >= 10) {
                runCheck();
            }
        });
    };
})();
