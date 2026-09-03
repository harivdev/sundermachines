<?php
require_once(__DIR__ . '/auth.php');
requireLogin();

$current_page = basename($_SERVER['PHP_SELF']);
$showQuickAccess = (isset($_COOKIE['showQuickAccess']) && $_COOKIE['showQuickAccess'] === 'true');

// Dynamic Breadcrumb Calculation
$current_path = strtolower($_SERVER['PHP_SELF']);
$current_page_name = strtolower(basename($current_path));

$current_module_title = 'Dashboard';
$current_page_title = 'Overview';

if (strpos($current_path, '/jobcard/') !== false) {
    $current_module_title = 'Job Card';
    if ($current_page_name === 'create.php') $current_page_title = 'Create Job Card';
    elseif ($current_page_name === 'edit.php') $current_page_title = 'Edit Job Card';
    elseif ($current_page_name === 'list.php') $current_page_title = 'Job Card List';
    elseif ($current_page_name === 'spares_list.php') $current_page_title = 'Job Card Spares';
    else $current_page_title = 'Job Card Management';
} elseif (strpos($current_path, '/sales/') !== false) {
    $current_module_title = 'Sales';
    if ($current_page_name === 'create.php') $current_page_title = 'Create Sales Order';
    elseif ($current_page_name === 'edit.php') $current_page_title = 'Edit Sales Order';
    elseif ($current_page_name === 'view.php') $current_page_title = 'View Sales Invoice';
    elseif ($current_page_name === 'list.php') $current_page_title = 'Sales List';
    else $current_page_title = 'Sales Management';
} elseif (strpos($current_path, '/purchase/') !== false) {
    $current_module_title = 'Purchase';
    if ($current_page_name === 'create.php') $current_page_title = 'Create Purchase Order';
    elseif ($current_page_name === 'edit_purchase.php') $current_page_title = 'Edit Purchase Order';
    elseif ($current_page_name === 'purchase_list.php') $current_page_title = 'Purchase List';
    else $current_page_title = 'Purchase Management';
} elseif (strpos($current_path, '/stock/') !== false) {
    $current_module_title = 'Stock';
    if ($current_page_name === 'add_stock.php') $current_page_title = 'Add Stock';
    elseif ($current_page_name === 'list.php') $current_page_title = 'Stock Inventory List';
    else $current_page_title = 'Stock Management';
} elseif (strpos($current_path, '/brand/') !== false) {
    $current_module_title = 'Master';
    $current_page_title = 'Brand List';
} elseif (strpos($current_path, '/model/') !== false) {
    $current_module_title = 'Master';
    $current_page_title = 'Model List';
} elseif (strpos($current_path, '/machine/') !== false) {
    $current_module_title = 'Master';
    $current_page_title = 'Machine List';
} elseif (strpos($current_path, '/spares/') !== false) {
    $current_module_title = 'Master';
    if ($current_page_name === 'add_spare.php') $current_page_title = 'Add Spare';
    else $current_page_title = 'Spares List';
} elseif (strpos($current_path, '/customers/') !== false) {
    $current_module_title = 'Master';
    $current_page_title = 'Customer List';
} elseif (strpos($current_path, '/supplier/') !== false) {
    $current_module_title = 'Master';
    $current_page_title = 'Supplier List';
} elseif (strpos($current_path, '/employee/') !== false) {
    $current_module_title = 'Master';
    if ($current_page_name === 'add.php') $current_page_title = 'Add Employee';
    elseif ($current_page_name === 'edit.php') $current_page_title = 'Edit Employee';
    else $current_page_title = 'Employee List';
} elseif (strpos($current_path, '/users/') !== false) {
    $current_module_title = 'Users';
    $current_page_title = 'User Management';
} elseif (strpos($current_path, '/report/') !== false) {
    $current_module_title = 'Reports';
    if ($current_page_name === 'daily_sales.php') $current_page_title = 'Daily Sales Report';
    elseif ($current_page_name === 'monthly_sales.php') $current_page_title = 'Monthly Sales Report';
    else $current_page_title = 'Reports Overview';
} else {
    $current_module_title = 'Dashboard';
    $current_page_title = 'Main Dashboard';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Sunder Billing</title>

    <!-- INLINE SCRIPT: Instant non-flickering Quick Access preference sync before first paint -->
    <script>
    (function() {
        var m = document.cookie.match(/(?:^|; )showQuickAccess=([^;]*)/);
        var pref = m ? m[1] : localStorage.getItem('showQuickAccess');
        if (pref === 'true') {
            document.documentElement.classList.add('has-quick-access');
        } else {
            document.documentElement.classList.remove('has-quick-access');
        }
    })();
    </script>

    <!-- JsBarcode library -->
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../includes/common_erp.css">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', Arial, sans-serif;
            background: #f4f6f9;
            padding-top: 125px;
            /* Offset for fixed topbar (44px) + menu (41px) + breadcrumb (38px) + borders */
            padding-bottom: 20px;
        }

        /* TOP BAR */
        .topbar {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 44px;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.25);
        }

        .brand {
            font-weight: 700;
            color: #fff;
            font-size: 16px;
            letter-spacing: 0.4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .brand .brand-accent {
            color: #FDD017;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
        }

        .user-pill small {
            color: rgba(255, 255, 255, 0.75);
            font-size: 11px;
            text-transform: uppercase;
        }

        .btn-logout {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
            padding: 7px 16px;
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            font-family: 'Inter', Arial, sans-serif;
            transition: all 0.2s;
        }

        .btn-logout:hover {
            background: #ef4444;
            color: #fff;
            border-color: #ef4444;
        }

        /* MENU CONTAINER */
        .menu-container {
            background: #fff;
            position: fixed;
            width: 100%;
            top: 44px;
            z-index: 999;
            border-bottom: 1px solid #e5e7eb;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
        }

        .menu-bar {
            display: flex;
            margin: 0;
            padding: 0 16px;
            list-style: none;
        }

        .menu-item {
            position: relative;
        }

        .menu-item>a,
        .menu-item>.menu-link {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 10px 14px;
            text-decoration: none;
            color: #374151;
            font-weight: 600;
            font-size: 13.5px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
            white-space: nowrap;
            user-select: none;
        }

        .nav-icon {
            font-size: 13.5px;
            color: inherit;
            transition: color 0.15s ease;
            margin-right: 5px;
        }

        .nav-shortcut {
            display: inline-block;
            font-size: 11px;
            font-weight: 600;
            font-family: inherit;
            color: #000000 !important; /* Black font color */
            border: none !important; /* No border around shortcut */
            background: transparent !important;
            padding: 0;
            margin-left: auto;
            letter-spacing: 0.3px;
            vertical-align: middle;
            transition: color 0.15s ease;
        }

        .dropdown a:hover .nav-shortcut,
        .dropdown a:focus .nav-shortcut {
            color: #b45309 !important;
        }

        .menu-item>a:hover,
        .menu-item>.menu-link:hover,
        .menu-item>a:focus,
        .menu-item>.menu-link:focus,
        .menu-item>a:focus-visible,
        .menu-item>.menu-link:focus-visible {
            color: #b45309;
            background: transparent !important;
            border-bottom: none !important;
            outline: none;
        }

        .menu-item>a.active-link,
        .menu-item>.menu-link.active-link {
            color: #b45309;
            background: transparent !important;
            border-bottom: none !important;
            font-weight: 600;
        }

        .arrow {
            font-size: 9px;
            display: inline-block;
            margin-left: 3px;
            transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            transform: rotate(-90deg);
        }

        .menu-item:hover > a .arrow,
        .menu-item:hover > .menu-link .arrow,
        .menu-item:focus-within > a .arrow,
        .menu-item:focus-within > .menu-link .arrow,
        .submenu:hover > .submenu-link .arrow,
        .submenu:focus-within > .submenu-link .arrow {
            transform: rotate(0deg);
        }

        /* DROPDOWN */
        .dropdown {
            display: none;
            position: absolute;
            background: #fff;
            min-width: 200px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            top: 100%;
            left: 0;
            z-index: 1001;
            border-radius: 0 0 10px 10px;
            border: 1px solid #e5e7eb;
            border-top: none;
            overflow: visible;
        }

        .menu-item:hover > .dropdown,
        .menu-item:focus-within > .dropdown {
            display: block;
        }

        .submenu:hover > .submenu-dropdown,
        .submenu:focus-within > .submenu-dropdown {
            display: block;
        }

        .dropdown>a {
            padding: 10px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #374151;
            font-size: 15px; /* Increased from 13px by 2px */
            font-weight: 500;
            text-decoration: none;
            transition: all 0.15s;
            border-left: 3px solid transparent;
        }

        .dropdown>a:hover,
        .dropdown>a:focus,
        .dropdown>a:focus-visible {
            background: #fefce8;
            color: #b45309;
            border-left-color: #FDD017;
            outline: none;
        }

        .dropdown>a.active-link {
            background: #fefce8;
            color: #b45309;
            border-left-color: #FDD017;
            font-weight: 600;
        }

        .dropdown-divider {
            border: none;
            border-top: 1px solid #f3f4f6;
            margin: 4px 0;
        }

        /* SUBMENU */
        .submenu {
            position: relative;
        }

        .submenu-link {
            padding: 10px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #374151;
            font-size: 15px; /* Increased from 13px by 2px */
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s;
            border-left: 3px solid transparent;
        }

        .submenu:hover > .submenu-link,
        .submenu-link:hover,
        .submenu-link:focus,
        .submenu-link:focus-visible {
            background: #fefce8;
            color: #b45309;
            border-left-color: #FDD017;
            outline: none;
        }

        .submenu-dropdown {
            display: none;
            position: absolute;
            left: 100%;
            top: -4px;
            background: #fff;
            min-width: 190px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            z-index: 1002;
        }

        .submenu-dropdown a {
            padding: 10px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #374151;
            font-size: 15px; /* Increased from 13px by 2px */
            text-decoration: none;
            transition: all 0.15s;
            border-left: 3px solid transparent;
        }

        .submenu-dropdown a:hover,
        .submenu-dropdown a:focus,
        .submenu-dropdown a:focus-visible {
            background: #fefce8;
            color: #b45309;
            border-left-color: #FDD017;
            outline: none;
        }

        .topbar-actions-desktop {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .mobile-action-btn {
            display: none;
        }

        .mobile-action-panel {
            display: none;
        }

        /* MOBILE TOGGLE */
        #menuToggle {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            color: #fff;
            font-size: 22px;
            padding: 4px 8px;
        }

        /* MOBILE RESPONSIVE NAV */
        @media (max-width: 768px) {
            body {
                padding-top: 65px !important;
            }

            .topbar {
                padding: 0 12px;
                height: 56px;
            }

            .topbar-right {
                display: flex;
                align-items: center;
                gap: 8px;
                margin-left: auto;
            }

            .topbar-actions-desktop {
                display: none !important;
            }

            .brand {
                font-size: 16px;
            }

            /* Mobile Action button - placed before hamburger */
            .mobile-action-btn {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                background: rgba(255, 255, 255, 0.12);
                color: #ffffff;
                border: 1px solid rgba(255, 255, 255, 0.25);
                border-radius: 8px;
                padding: 6px 12px;
                font-size: 12px;
                font-weight: 600;
                cursor: pointer;
                transition: background 0.15s ease;
                order: 90;
            }

            .mobile-action-btn:hover {
                background: rgba(255, 255, 255, 0.22);
            }

            /* Mobile Action Dropdown Panel (Exact width matching Action label button) */
            .mobile-action-panel {
                position: fixed;
                top: 58px;
                background: #0f172a;
                border: 1px solid #334155;
                border-radius: 10px;
                padding: 8px 6px;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
                z-index: 10002;
                display: none;
                flex-direction: column;
                gap: 8px;
                box-sizing: border-box !important;
            }

            .mobile-action-panel.open {
                display: flex !important;
            }

            .mobile-action-item {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 100%;
                box-sizing: border-box !important;
                padding-bottom: 6px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }

            /* Hamburger icon - LAST TO CORNER (Far Right) */
            #menuToggle {
                display: flex !important;
                align-items: center;
                justify-content: center;
                order: 99 !important;
                margin-left: 2px !important;
                font-size: 24px;
                padding: 4px 6px;
            }

            /* MOBILE MENU DRAWER (Max width 290px - does not cover full page) */
            .menu-container {
                display: none !important;
                position: fixed !important;
                top: 56px !important;
                left: 0 !important;
                width: 85% !important;
                max-width: 290px !important;
                background: #ffffff !important;
                z-index: 10005 !important;
                border-right: 2px solid #cbd5e1;
                border-bottom: 2px solid #cbd5e1;
                box-shadow: 6px 0 25px rgba(0, 0, 0, 0.3) !important;
                max-height: calc(100vh - 56px);
                overflow-y: auto !important;
                -webkit-overflow-scrolling: touch;
            }

            .menu-container.open {
                display: block !important;
            }

            .menu-bar {
                flex-direction: column !important;
                display: flex !important;
                padding: 0 !important;
                margin: 0 !important;
                background: #ffffff !important;
                border-top: none;
            }

            .menu-item {
                width: 100% !important;
                border-bottom: 1px solid #f1f5f9;
            }

            .menu-item > a,
            .menu-item > .menu-link {
                display: flex !important;
                align-items: center !important;
                justify-content: flex-start !important;
                gap: 10px !important;
                padding: 14px 20px !important;
                color: #0f172a !important;
                font-size: 15px !important;
                font-weight: 600 !important;
                text-decoration: none !important;
                background: #ffffff !important;
                border: none !important;
                cursor: pointer !important;
            }

            .menu-item > a .arrow,
            .menu-item > .menu-link .arrow {
                margin-left: auto !important;
            }

            .menu-item > a:hover,
            .menu-item > .menu-link:hover,
            .menu-item > a:active,
            .menu-item > .menu-link:active {
                background: #f8fafc !important;
                color: #f97316 !important;
            }

            /* MOBILE INLINE ACCORDION DROPDOWNS */
            .dropdown {
                display: none !important;
                position: static !important;
                width: 100% !important;
                background: #f8fafc !important;
                box-shadow: none !important;
                border: none !important;
                border-top: 1px solid #e2e8f0 !important;
                border-bottom: 1px solid #e2e8f0 !important;
                padding: 0 !important;
                border-radius: 0 !important;
            }

            .dropdown.open {
                display: block !important;
            }

            .dropdown > a {
                display: flex !important;
                align-items: center !important;
                gap: 10px !important;
                padding: 12px 24px !important;
                color: #334155 !important;
                font-size: 14px !important;
                font-weight: 500 !important;
                text-decoration: none !important;
                background: transparent !important;
                border-bottom: 1px solid #f1f5f9 !important;
            }

            .dropdown > a:hover,
            .dropdown > a:active {
                background: #fff7ed !important;
                color: #f97316 !important;
            }

            /* MOBILE INLINE ACCORDION SUBMENUS */
            .submenu {
                width: 100% !important;
            }

            .submenu-link {
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                padding: 12px 24px !important;
                color: #334155 !important;
                font-size: 14px !important;
                font-weight: 500 !important;
                background: transparent !important;
                border-bottom: 1px solid #f1f5f9 !important;
                cursor: pointer !important;
            }

            .submenu-dropdown {
                display: none !important;
                position: static !important;
                width: 100% !important;
                background: #f1f5f9 !important;
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                border-radius: 0 !important;
            }

            .submenu-dropdown.open {
                display: block !important;
            }

            .submenu-dropdown a {
                display: flex !important;
                align-items: center !important;
                gap: 10px !important;
                padding: 10px 10px 10px 40px !important;
                color: #475569 !important;
                font-size: 13.5px !important;
                font-weight: 500 !important;
                text-decoration: none !important;
                border-bottom: 1px solid #e2e8f0 !important;
            }

            .submenu-dropdown a:hover,
            .submenu-dropdown a:active {
                background: #fff7ed !important;
                color: #ea580c !important;
            }

            /* MOBILE LIST HEADER BAR ALIGNMENT (Fix for Image 2) */
            .list-header-bar {
                flex-direction: column !important;
                align-items: flex-start !important;
                padding: 12px 15px !important;
                gap: 12px !important;
            }

            .list-header-title {
                width: 100% !important;
                font-size: 18px !important;
                white-space: normal !important;
            }

            .list-header-actions {
                width: 100% !important;
                display: flex !important;
                flex-wrap: wrap !important;
                gap: 8px !important;
                justify-content: flex-start !important;
            }

            .list-header-actions a,
            .list-header-actions button {
                font-size: 12px !important;
                padding: 6px 10px !important;
                white-space: nowrap !important;
            }

            /* MOBILE PAGINATION FOOTER ALIGNMENT (Fix for Image 3) */
            .list-pagination-bar {
                flex-direction: column !important;
                align-items: center !important;
                text-align: center !important;
                gap: 10px !important;
                margin-top: 15px !important;
            }

            .pagination-info {
                width: 100% !important;
                font-size: 12.5px !important;
                text-align: center !important;
                color: #475569 !important;
            }

            .pagination-buttons {
                width: 100% !important;
                justify-content: center !important;
                flex-wrap: wrap !important;
                gap: 4px !important;
            }

            .pagination-buttons a,
            .pagination-buttons span {
                font-size: 12px !important;
                padding: 5px 8px !important;
            }

            /* Prevent iOS auto-zoom on form elements */
            input,
            select,
            textarea {
                font-size: 16px !important;
            }
        }

        /* Global light orange tone input focus & autofill styling across all pages */
        .erp-input:focus, .erp-select:focus, .erp-textarea:focus, 
        .form-control:focus, .form-select:focus, 
        input:focus, select:focus, textarea:focus,
        input:active, select:active, textarea:active {
            outline: none !important;
            background-color: #fff7ed !important;
            border-color: #f59e0b !important;
            box-shadow: 0 0 0 3.5px rgba(245, 158, 11, 0.22) !important;
        }

        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus, 
        input:-webkit-autofill:active,
        select:-webkit-autofill,
        textarea:-webkit-autofill {
            -webkit-box-shadow: 0 0 0 30px #fff7ed inset !important;
            -webkit-text-fill-color: #1e293b !important;
            border-color: #f59e0b !important;
            transition: background-color 5000s ease-in-out 0s;
        }

        /* MINI-SIZED QUICK ACCESS TOGGLE SWITCH ON NAV BAR */
        .quick-access-toggle {
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
            white-space: nowrap !important;
            color: #c2410c !important;
            font-size: 12.5px !important;
            font-weight: 700 !important;
            cursor: pointer !important;
            user-select: none !important;
            margin: 0 !important;
            padding: 4px 12px !important;
            background: #fff7ed !important;
            border: 1.5px solid #fed7aa !important;
            border-radius: 20px !important;
            flex-shrink: 0 !important;
            height: 28px !important;
            line-height: 1 !important;
        }

        .quick-access-toggle span {
            white-space: nowrap !important;
            display: inline-block !important;
        }

        .quick-access-toggle input {
            display: none;
        }

        .quick-access-toggle .toggle-slider {
            width: 28px;
            height: 15px;
            background-color: #cbd5e1;
            border-radius: 15px;
            position: relative;
            flex-shrink: 0;
            transition: background-color 0.2s ease;
        }

        .quick-access-toggle .toggle-slider::before {
            content: '';
            position: absolute;
            width: 11px;
            height: 11px;
            border-radius: 50%;
            background-color: #ffffff;
            top: 2px;
            left: 2px;
            transition: transform 0.2s ease;
        }

        .quick-access-toggle input:checked + .toggle-slider {
            background-color: #ea580c;
        }

        .quick-access-toggle input:checked + .toggle-slider::before {
            transform: translateX(13px);
        }

        /* LEFT SIDE QUICK ACCESS SIDEBAR (LIGHT ORANGE THEME) */
        .quick-access-sidebar {
            position: fixed;
            top: 44px; /* Directly below dark topbar, aligning banner with menu-container */
            left: 0;
            width: 220px;
            bottom: 0;
            background: #fff7ed; /* Light orange background */
            border-right: 1px solid #fed7aa;
            box-shadow: 3px 0 12px rgba(249, 115, 22, 0.12);
            z-index: 9999;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .quick-access-sidebar.hidden {
            transform: translateX(-220px) !important;
        }

        .quick-access-banner {
            background: #f97316; /* Warm light orange header banner box */
            color: #ffffff;
            padding: 0 16px;
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            letter-spacing: 0.3px;
            box-shadow: 0 2px 4px rgba(249, 115, 22, 0.15);
            height: 41px; /* Exact match to menu-container height (41px) */
            box-sizing: border-box;
        }

        .quick-access-menu-group {
            padding: 6px 0;
            display: flex;
            flex-direction: column;
        }

        .quick-access-nav-link {
            padding: 10px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #7c2d12; /* Dark warm amber text */
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            transition: background 0.15s ease, color 0.15s ease;
        }

        .quick-access-nav-link:hover {
            background: #ffedd5; /* Soft light orange highlight */
            color: #c2410c;
        }

        .quick-access-nav-link .nav-icon {
            font-size: 16px;
            color: #ea580c;
        }

        .quick-access-task-banner {
            background: #f97316; /* Warm light orange Task header banner box */
            color: #ffffff;
            padding: 8px 14px;
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 4px;
            box-shadow: 0 2px 4px rgba(249, 115, 22, 0.15);
        }

        .quick-access-task-container {
            flex: 1;
            overflow-y: auto;
            padding: 6px 0;
        }

        .quick-access-task-link {
            padding: 6px 16px;
            display: block;
            color: #7c2d12; /* Dark warm amber text */
            font-size: 13px;
            font-weight: 700;
            font-family: inherit;
            text-decoration: none;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            transition: background 0.15s ease, color 0.15s ease;
        }

        /* BREADCRUMB PAGE BANNER STRIP */
        .erp-breadcrumb-banner {
            position: fixed;
            top: 85px; /* Fixed offset: topbar 44px + menu-container 41px = 85px */
            left: 0;
            width: 100%;
            z-index: 998;
            background: linear-gradient(135deg, #15803d 0%, #166534 100%);
            color: #ffffff;
            height: 38px;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1.5px solid #14532d;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            box-sizing: border-box;
            transition: margin-left 0.35s cubic-bezier(0.4, 0, 0.2, 1), width 0.35s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        html.has-quick-access .erp-breadcrumb-banner,
        body.has-quick-access .erp-breadcrumb-banner {
            margin-left: 220px !important;
            width: calc(100% - 220px) !important;
        }

        .breadcrumb-inner {
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: 'Inter', sans-serif;
        }

        .bc-brand {
            background: rgba(255, 255, 255, 0.2);
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 800;
            font-size: 11.5px;
            letter-spacing: 0.4px;
            color: #ffffff;
        }

        .bc-sep {
            opacity: 0.6;
            font-size: 11px;
            color: #ffffff;
        }

        .bc-module {
            color: #a7f3d0;
            font-weight: 600;
        }

        .bc-page {
            color: #ffffff;
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        /* HEADER MENU BAR: left edge shifts, right edge stays fixed */
        .menu-container {
            transition: margin-left 0.35s cubic-bezier(0.4, 0, 0.2, 1),
                        width 0.35s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        html.has-quick-access .menu-container,
        body.has-quick-access .menu-container {
            margin-left: 220px !important;
            width: calc(100% - 220px) !important;
        }

        /* MAIN PAGE CONTENT: only left edge moves, right edge stays fixed at viewport right */
        .erp-container,
        .container,
        main,
        .main-content,
        .page-wrapper,
        .page-main-container {
            transition: margin-left 0.35s cubic-bezier(0.4, 0, 0.2, 1),
                        width 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        html.has-quick-access .container,
        html.has-quick-access .erp-container,
        html.has-quick-access main,
        html.has-quick-access .main-content,
        html.has-quick-access .page-wrapper,
        html.has-quick-access .page-main-container,
        body.has-quick-access .container,
        body.has-quick-access .erp-container,
        body.has-quick-access main,
        body.has-quick-access .main-content,
        body.has-quick-access .page-wrapper,
        body.has-quick-access .page-main-container {
            margin-left: 220px !important;
            width: calc(100% - 220px) !important;
            box-sizing: border-box !important;
        }

        /* PREVENT TABLE COLUMNS FROM SQUISHING/SHRINKING WHEN QUICK ACCESS IS OPEN */
        .erp-table-box,
        .table-wrap,
        .table-responsive,
        div[style*="overflow-x: auto"] {
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch;
        }

        body.has-quick-access .erp-table th,
        body.has-quick-access .erp-table td,
        body.has-quick-access table th,
        body.has-quick-access table td,
        html.has-quick-access .erp-table th,
        html.has-quick-access .erp-table td,
        html.has-quick-access table th,
        html.has-quick-access table td {
            white-space: nowrap;
        }

        @media (max-width: 768px) {
            .quick-access-sidebar {
                top: 56px;
            }
            html.has-quick-access .menu-container,
            html.has-quick-access .container,
            html.has-quick-access .erp-container,
            html.has-quick-access .page-main-container,
            body.has-quick-access .menu-container,
            body.has-quick-access .container,
            body.has-quick-access .erp-container,
            body.has-quick-access .page-main-container {
                margin-left: 0 !important;
                width: 100% !important;
            }
        }
    </style>
</head>

<body class="<?php echo $showQuickAccess ? 'has-quick-access' : ''; ?>">

    <!-- TOP BAR -->
    <div class="topbar">
        <div class="brand">
            <span class="brand-accent">Sunder</span>&nbsp;Billing
        </div>
        <div class="topbar-right">
            <!-- Mobile Action Button (Placed BEFORE Hamburger) -->
            <button type="button" id="mobileActionBtn" class="mobile-action-btn" onclick="toggleMobileActionPanel(event)" title="Actions">
                <i class="fa-solid fa-sliders"></i> Action <i class="fa-solid fa-chevron-down" style="font-size: 10px;"></i>
            </button>

            <!-- Hamburger menu icon (LAST TO CORNER - Far Right) -->
            <button id="menuToggle" type="button" onclick="toggleMobileMenu(event)" title="Toggle Menu">&#9776;</button>
        </div>
    </div>

    <!-- MOBILE ACTION PANEL DROPDOWN (Contains 3 options matching exact Action label width) -->
    <div id="mobileActionPanel" class="mobile-action-panel">
        <!-- Option 1: Quick Access toggle switch (no text names) -->
        <div class="mobile-action-item">
            <label class="quick-access-toggle" style="margin: 0; padding: 0;" title="Toggle Left Quick Access Sidebar">
                <input type="checkbox" id="toggleQuickAccessCheckboxMobile" <?php echo $showQuickAccess ? 'checked' : ''; ?> onchange="toggleQuickAccessSidebar(this.checked)">
                <span class="toggle-slider"></span>
            </label>
        </div>

        <!-- Option 2: Admin role pill (no "USER ACCOUNT" label, no doubled text) -->
        <?php if (isset($_SESSION['employee_name']) || isset($_SESSION['username'])): ?>
            <div class="mobile-action-item">
                <div class="user-pill" style="margin: 0; width: 100%; justify-content: center; text-align: center; box-sizing: border-box; padding: 4px 6px;">
                    <?php if (isset($_SESSION['employee_name'])): ?>
                        <span><?php echo htmlspecialchars($_SESSION['employee_name']); ?></span>
                    <?php else: ?>
                        <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <?php 
                        $roleDisplay = $_SESSION['role'] ?? 'Admin';
                        if (strcasecmp($_SESSION['username'], $roleDisplay) !== 0): 
                        ?>
                            <small><?php echo htmlspecialchars($roleDisplay); ?></small>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Option 3: Logout button (same width as Action label box) -->
        <div class="mobile-action-item" style="border-bottom: none; padding-bottom: 0;">
            <button class="btn-logout" style="width: 100%; box-sizing: border-box; justify-content: center; text-align: center; margin: 0; padding: 6px 0; font-size: 12px;" onclick="window.location.href='../login/logout.php'">
                Logout &#x2192;
            </button>
        </div>
    </div>

    <!-- MENU -->
    <div class="menu-container">
        <ul class="menu-bar" id="mainMenu">

            <!-- 1. DASHBOARD -->
            <li class="menu-item">
                <a href="../login/dashboard.php" <?php echo ($current_page == 'dashboard.php') ? 'class="active-link"' : ''; ?>>
                    <i class="fa-solid fa-gauge-high nav-icon"></i> Dashboard
                </a>
            </li>

            <!-- 2. JOB CARD (Moved to 2nd position from left) -->
            <li class="menu-item has-dropdown">
                <span class="menu-link <?php echo (strpos($_SERVER['PHP_SELF'], 'jobcard') !== false) ? 'active-link' : ''; ?>" tabindex="0" role="button" aria-haspopup="true">
                    <i class="fa-solid fa-clipboard-list nav-icon"></i> Job Card <span class="arrow">&#9660;</span>
                </span>
                <div class="dropdown">
                    <a href="../jobcard/create.php" <?php echo ($current_page == 'create.php' && strpos($_SERVER['PHP_SELF'], 'jobcard') !== false) ? 'class="active-link"' : ''; ?>>
                        <i class="fa-solid fa-file-circle-plus nav-icon"></i> Create Job <kbd class="nav-shortcut">Ctrl+J</kbd>
                    </a>
                    <a href="../jobcard/list.php" <?php echo ($current_page == 'list.php' && strpos($_SERVER['PHP_SELF'], 'jobcard') !== false) ? 'class="active-link"' : ''; ?>>
                        <i class="fa-solid fa-list-check nav-icon"></i> Job Card List
                    </a>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN'): ?>
                        <a href="../jobcard/spares_list.php" <?php echo ($current_page == 'spares_list.php' && strpos($_SERVER['PHP_SELF'], 'jobcard') !== false) ? 'class="active-link"' : ''; ?>>
                            <i class="fa-solid fa-gears nav-icon"></i> Job Card Spares
                        </a>
                    <?php endif; ?>
                </div>
            </li>

            <!-- 3. STOCK -->
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN'): ?>
                <li class="menu-item has-dropdown">
                    <span class="menu-link <?php echo (strpos($_SERVER['PHP_SELF'], 'stock') !== false) ? 'active-link' : ''; ?>" tabindex="0" role="button" aria-haspopup="true">
                        <i class="fa-solid fa-boxes-stacked nav-icon"></i> Stock <span class="arrow">&#9660;</span>
                    </span>
                    <div class="dropdown">
                        <a href="../stock/add_stock.php" <?php echo ($current_page == 'add_stock.php') ? 'class="active-link"' : ''; ?>>
                            <i class="fa-solid fa-plus nav-icon"></i> Add Stock
                        </a>
                        <a href="../stock/list.php" <?php echo ($current_page == 'list.php' && strpos($_SERVER['PHP_SELF'], 'stock') !== false) ? 'class="active-link"' : ''; ?>>
                            <i class="fa-solid fa-boxes-stacked nav-icon"></i> Stock List
                        </a>
                    </div>
                </li>
            <?php endif; ?>

            <!-- 4. SALES -->
            <li class="menu-item has-dropdown">
                <span class="menu-link <?php echo (strpos($_SERVER['PHP_SELF'], 'sales') !== false) ? 'active-link' : ''; ?>" tabindex="0" role="button" aria-haspopup="true">
                    <i class="fa-solid fa-cart-shopping nav-icon"></i> Sales <span class="arrow">&#9660;</span>
                </span>
                <div class="dropdown">
                    <a href="../sales/create.php" <?php echo ($current_page == 'create.php' && strpos($_SERVER['PHP_SELF'], 'sales') !== false) ? 'class="active-link"' : ''; ?>>
                        <i class="fa-solid fa-cart-plus nav-icon"></i> Create Sale <kbd class="nav-shortcut">Ctrl+S</kbd>
                    </a>
                    <a href="../sales/list.php" <?php echo ($current_page == 'list.php' && strpos($_SERVER['PHP_SELF'], 'sales') !== false) ? 'class="active-link"' : ''; ?>>
                        <i class="fa-solid fa-receipt nav-icon"></i> Sales List
                    </a>
                </div>
            </li>

            <!-- 5. PURCHASE -->
            <?php if (isAdmin()): ?>
                <li class="menu-item has-dropdown">
                    <span class="menu-link <?php echo (strpos($_SERVER['PHP_SELF'], 'purchase') !== false) ? 'active-link' : ''; ?>" tabindex="0" role="button" aria-haspopup="true">
                        <i class="fa-solid fa-bag-shopping nav-icon"></i> Purchase <span class="arrow">&#9660;</span>
                    </span>
                    <div class="dropdown">
                        <a href="../purchase/create.php" <?php echo ($current_page == 'create.php' && strpos($_SERVER['PHP_SELF'], 'purchase') !== false) ? 'class="active-link"' : ''; ?>>
                            <i class="fa-solid fa-plus nav-icon"></i> Create Purchase <kbd class="nav-shortcut">Ctrl+P</kbd>
                        </a>
                        <a href="../purchase/purchase_list.php" <?php echo ($current_page == 'purchase_list.php' && strpos($_SERVER['PHP_SELF'], 'purchase') !== false) ? 'class="active-link"' : ''; ?>>
                            <i class="fa-solid fa-truck-field nav-icon"></i> Purchase List
                        </a>
                    </div>
                </li>
            <?php endif; ?>

            <!-- 6. MASTER -->
            <?php 
            $is_master_active = (
                strpos($_SERVER['PHP_SELF'], 'brand') !== false ||
                strpos($_SERVER['PHP_SELF'], 'model') !== false ||
                strpos($_SERVER['PHP_SELF'], 'machine') !== false ||
                strpos($_SERVER['PHP_SELF'], 'spares') !== false ||
                strpos($_SERVER['PHP_SELF'], 'customers') !== false ||
                strpos($_SERVER['PHP_SELF'], 'supplier') !== false ||
                strpos($_SERVER['PHP_SELF'], 'employee') !== false
            );
            ?>
            <li class="menu-item has-dropdown">
                <span class="menu-link <?php echo $is_master_active ? 'active-link' : ''; ?>" tabindex="0" role="button" aria-haspopup="true">
                    <i class="fa-solid fa-layer-group nav-icon"></i> Master <span class="arrow">&#9660;</span>
                </span>
                <div class="dropdown">

                    <?php if (isAdmin()): ?>
                        <div class="submenu <?php echo (strpos($_SERVER['PHP_SELF'], 'brand') !== false) ? 'active' : ''; ?>">
                            <span class="submenu-link" tabindex="0" role="button" aria-haspopup="true">
                                <span><i class="fa-solid fa-tags nav-icon"></i> Brand</span>
                                <span class="arrow">&#9660;</span>
                            </span>
                            <div class="submenu-dropdown">
                                <a href="../brand/add.php" <?php echo ($current_page == 'add.php' && strpos($_SERVER['PHP_SELF'], 'brand') !== false) ? 'class="active-link"' : ''; ?>>
                                    <i class="fa-solid fa-plus nav-icon"></i> Add Brand
                                </a>
                                <a href="../brand/list.php" <?php echo ($current_page == 'list.php' && strpos($_SERVER['PHP_SELF'], 'brand') !== false) ? 'class="active-link"' : ''; ?>>
                                    <i class="fa-solid fa-list nav-icon"></i> Brand List
                                </a>
                            </div>
                        </div>

                        <hr class="dropdown-divider">

                        <div class="submenu <?php echo (strpos($_SERVER['PHP_SELF'], 'model') !== false) ? 'active' : ''; ?>">
                            <span class="submenu-link" tabindex="0" role="button" aria-haspopup="true">
                                <span><i class="fa-solid fa-cubes nav-icon"></i> Model</span>
                                <span class="arrow">&#9660;</span>
                            </span>
                            <div class="submenu-dropdown">
                                <a href="../model/add.php" <?php echo ($current_page == 'add.php' && strpos($_SERVER['PHP_SELF'], 'model') !== false) ? 'class="active-link"' : ''; ?>>
                                    <i class="fa-solid fa-plus nav-icon"></i> Add Model
                                </a>
                                <a href="../model/list.php" <?php echo ($current_page == 'list.php' && strpos($_SERVER['PHP_SELF'], 'model') !== false) ? 'class="active-link"' : ''; ?>>
                                    <i class="fa-solid fa-list nav-icon"></i> Model List
                                </a>
                            </div>
                        </div>

                        <hr class="dropdown-divider">

                        <div class="submenu <?php echo (strpos($_SERVER['PHP_SELF'], 'machine') !== false) ? 'active' : ''; ?>">
                            <span class="submenu-link" tabindex="0" role="button" aria-haspopup="true">
                                <span><i class="fa-solid fa-gear nav-icon"></i> Machine</span>
                                <span class="arrow">&#9660;</span>
                            </span>
                            <div class="submenu-dropdown">
                                <a href="../machine/add_machine.php" <?php echo ($current_page == 'add_machine.php') ? 'class="active-link"' : ''; ?>>
                                    <i class="fa-solid fa-plus nav-icon"></i> Add Machine
                                </a>
                                <a href="../machine/list_machine.php" <?php echo ($current_page == 'list_machine.php') ? 'class="active-link"' : ''; ?>>
                                    <i class="fa-solid fa-list nav-icon"></i> Machine List
                                </a>
                            </div>
                        </div>

                        <hr class="dropdown-divider">
                    <?php endif; ?>

                    <div class="submenu <?php echo (strpos($_SERVER['PHP_SELF'], 'spares') !== false) ? 'active' : ''; ?>">
                        <span class="submenu-link" tabindex="0" role="button" aria-haspopup="true">
                            <span><i class="fa-solid fa-wrench nav-icon"></i> Spares</span>
                            <span class="arrow">&#9660;</span>
                        </span>
                        <div class="submenu-dropdown">
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN'): ?>
                                <a href="../spares/add_spare.php" <?php echo ($current_page == 'add_spare.php') ? 'class="active-link"' : ''; ?>>
                                    <i class="fa-solid fa-plus nav-icon"></i> Add Spare
                                </a>
                            <?php endif; ?>
                            <a href="../spares/list_spare.php" <?php echo ($current_page == 'list_spare.php') ? 'class="active-link"' : ''; ?>>
                                <i class="fa-solid fa-list nav-icon"></i> Spares List
                            </a>
                        </div>
                    </div>

                    <?php if (isAdmin()): ?>
                        <hr class="dropdown-divider">

                        <!-- CUSTOMERS -->
                        <div class="submenu <?php echo (strpos($_SERVER['PHP_SELF'], 'customers') !== false) ? 'active' : ''; ?>">
                            <span class="submenu-link" tabindex="0" role="button" aria-haspopup="true">
                                <span><i class="fa-solid fa-users nav-icon"></i> Customers</span>
                                <span class="arrow">&#9660;</span>
                            </span>
                            <div class="submenu-dropdown">
                                <a href="../customers/manage_customers.php?action=add">
                                    <i class="fa-solid fa-user-plus nav-icon"></i> Add Customer
                                </a>
                                <a href="../customers/manage_customers.php" <?php echo ($current_page == 'manage_customers.php' && !isset($_GET['action'])) ? 'class="active-link"' : ''; ?>>
                                    <i class="fa-solid fa-users nav-icon"></i> Customer List
                                </a>
                            </div>
                        </div>

                        <hr class="dropdown-divider">

                        <!-- SUPPLIER (Separated as its own distinct submenu under Master) -->
                        <div class="submenu <?php echo (strpos($_SERVER['PHP_SELF'], 'supplier') !== false) ? 'active' : ''; ?>">
                            <span class="submenu-link" tabindex="0" role="button" aria-haspopup="true">
                                <span><i class="fa-solid fa-truck-field nav-icon"></i> Supplier</span>
                                <span class="arrow">&#9660;</span>
                            </span>
                            <div class="submenu-dropdown">
                                <a href="../supplier/add.php" <?php echo ($current_page == 'add.php' && strpos($_SERVER['PHP_SELF'], 'supplier') !== false) ? 'class="active-link"' : ''; ?>>
                                    <i class="fa-solid fa-plus nav-icon"></i> Add Supplier
                                </a>
                                <a href="../supplier/manage_supplier.php" <?php echo ($current_page == 'manage_supplier.php' || (strpos($_SERVER['PHP_SELF'], 'supplier') !== false && $current_page != 'add.php')) ? 'class="active-link"' : ''; ?>>
                                    <i class="fa-solid fa-truck-field nav-icon"></i> Supplier List
                                </a>
                            </div>
                        </div>

                        <hr class="dropdown-divider">

                        <!-- EMPLOYEE (Submenu under Master) -->
                        <div class="submenu <?php echo (strpos($_SERVER['PHP_SELF'], 'employee') !== false) ? 'active' : ''; ?>">
                            <span class="submenu-link" tabindex="0" role="button" aria-haspopup="true">
                                <span><i class="fa-solid fa-id-badge nav-icon"></i> Employee</span>
                                <span class="arrow">&#9660;</span>
                            </span>
                            <div class="submenu-dropdown">
                                <a href="../employee/add.php" <?php echo ($current_page == 'add.php' && strpos($_SERVER['PHP_SELF'], 'employee') !== false) ? 'class="active-link"' : ''; ?>>
                                    <i class="fa-solid fa-user-plus nav-icon"></i> Add Employee
                                </a>
                                <a href="../employee/list.php" <?php echo ($current_page == 'list.php' && strpos($_SERVER['PHP_SELF'], 'employee') !== false) ? 'class="active-link"' : ''; ?>>
                                    <i class="fa-solid fa-id-badge nav-icon"></i> Employee List
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </li>

            <!-- 7. REPORTS -->
            <?php if (isAdmin()): ?>
                <li class="menu-item has-dropdown">
                    <span class="menu-link <?php echo (strpos($_SERVER['PHP_SELF'], 'report') !== false) ? 'active-link' : ''; ?>" tabindex="0" role="button" aria-haspopup="true">
                        <i class="fa-solid fa-chart-pie nav-icon"></i> Reports <span class="arrow">&#9660;</span>
                    </span>
                    <div class="dropdown">
                        <a href="../report/daily_sales.php" <?php echo ($current_page == 'daily_sales.php') ? 'class="active-link"' : ''; ?>>
                            <i class="fa-solid fa-calendar-day nav-icon"></i> Daily Sales <kbd class="nav-shortcut">Ctrl+D</kbd>
                        </a>
                        <a href="../report/monthly_sales.php" <?php echo ($current_page == 'monthly_sales.php') ? 'class="active-link"' : ''; ?>>
                            <i class="fa-solid fa-calendar-days nav-icon"></i> Monthly Sales
                        </a>
                    </div>
                </li>
            <?php endif; ?>

            <!-- 8. USERS (Only for ADMIN) -->
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN'): ?>
                <li class="menu-item">
                    <a href="../users/manage_users.php" <?php echo ($current_page == 'manage_users.php') ? 'class="active-link"' : ''; ?>>
                        <i class="fa-solid fa-user-gear nav-icon"></i> Users
                    </a>
                </li>
            <?php endif; ?>

            <!-- SHOW QUICK ACCESS TOGGLE IN HEADER RIGHT CORNER -->
            <li class="menu-item quick-access-header-toggle" style="margin-left: auto; display: flex; align-items: center; padding-right: 16px; flex-shrink: 0; white-space: nowrap;">
                <label class="quick-access-toggle" title="Toggle Left Quick Access Sidebar">
                    <input type="checkbox" id="toggleQuickAccessCheckbox" <?php echo $showQuickAccess ? 'checked' : ''; ?> onchange="toggleQuickAccessSidebar(this.checked)">
                    <span class="toggle-slider"></span>
                    <span>Show Quick Access</span>
                </label>
            </li>
        </ul>
    </div>

    <!-- RECTANGULAR BREADCRUMB BANNER STRIP RIGHT BELOW THE HEADER -->
    <div class="erp-breadcrumb-banner">
        <div class="breadcrumb-inner">
            <span class="bc-brand">Sunder Billing</span>
            <span class="bc-sep">/</span>
            <span class="bc-module"><?= htmlspecialchars($current_module_title) ?></span>
            <span class="bc-sep">/</span>
            <span class="bc-page"><?= htmlspecialchars($current_page_title) ?></span>
        </div>
    </div>

    <!-- LEFT SIDE QUICK ACCESS SIDEBAR PANEL (EXACT MATCH FOR USER MONITOR PHOTO) -->
    <?php
    $quick_jobcards = [];
    if (isset($conn) && $conn) {
        $qjc_res = @mysqli_query($conn, "SELECT id, cardNo FROM jobcard ORDER BY id DESC LIMIT 100");
        if ($qjc_res) {
            while ($qrow = mysqli_fetch_assoc($qjc_res)) {
                $quick_jobcards[] = $qrow;
            }
        }
    }
    ?>
    <div id="quickAccessSidebarPanel" class="quick-access-sidebar <?php echo $showQuickAccess ? '' : 'hidden'; ?>">
        <!-- Banner 1: Quick Access -->
        <div class="quick-access-banner">
            <i class="fa-solid fa-bolt" style="color: #facc15;"></i> Quick Access
        </div>

        <!-- Section 1: Links -->
        <div class="quick-access-menu-group">
            <a href="../jobcard/create.php" class="quick-access-nav-link">
                <i class="fa-solid fa-clipboard-list nav-icon"></i> Job Card
            </a>
            <a href="../sales/create.php" class="quick-access-nav-link">
                <i class="fa-solid fa-cart-shopping nav-icon"></i> Sales
            </a>
            <a href="../users/manage_users.php?action=change_password" class="quick-access-nav-link">
                <i class="fa-solid fa-key nav-icon"></i> Change Password
            </a>
            <a href="../login/logout.php" class="quick-access-nav-link">
                <i class="fa-solid fa-right-from-bracket nav-icon"></i> Exit
            </a>

            <!-- ADMIN / USER PROFILE BELOW EXIT (Matches Red Box in Image 2) -->
            <?php if (isset($_SESSION['employee_name']) || isset($_SESSION['username'])): ?>
                <div style="margin: 8px 12px; padding: 8px 12px; background: rgba(249, 115, 22, 0.15); border: 1.5px solid #f97316; border-radius: 8px; font-weight: 700; color: #9a3412; font-size: 13px; display: flex; align-items: center; gap: 8px; box-shadow: 0 2px 4px rgba(249, 115, 22, 0.1);">
                    <i class="fa-solid fa-user-circle" style="font-size: 18px; color: #ea580c;"></i>
                    <div style="display: flex; flex-direction: column; line-height: 1.2;">
                        <?php if (isset($_SESSION['employee_name'])): ?>
                            <span><?php echo htmlspecialchars($_SESSION['employee_name']); ?></span>
                        <?php else: ?>
                            <span><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
                        <?php endif; ?>
                        <small style="font-size: 10.5px; color: #c2410c; font-weight: 700; text-transform: uppercase;"><?php echo htmlspecialchars($_SESSION['role'] ?? 'ADMIN'); ?></small>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Banner 2: Task -->
        <div class="quick-access-task-banner">
            <i class="fa-solid fa-list-check" style="color: #facc15;"></i> Task
        </div>

        <!-- Section 2: Numbered Job Card List (1) 2403J00015, 2) 2403J00016 ...) -->
        <div class="quick-access-task-container">
            <?php if (!empty($quick_jobcards)): ?>
                <?php $t_idx = 1; foreach ($quick_jobcards as $qjc): ?>
                    <a href="../jobcard/edit.php?id=<?php echo $qjc['id']; ?>" class="quick-access-task-link" title="Edit <?php echo htmlspecialchars(!empty($qjc['cardNo']) ? $qjc['cardNo'] : 'JobCard #'.$qjc['id']); ?>">
                        <?php echo $t_idx++; ?>) <?php echo htmlspecialchars(!empty($qjc['cardNo']) ? $qjc['cardNo'] : 'JobCard #'.$qjc['id']); ?>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <span style="padding: 10px 16px; font-size: 12px; color: #064e3b; display: block;">No Tasks found</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- SCRIPT FOR TOGGLING QUICK ACCESS SIDEBAR & MOBILE ACTION DROPDOWN -->
    <script>
    function toggleMobileMenu(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        var menuContainer = document.querySelector('.menu-container');
        var menuBar = document.getElementById('mainMenu');
        var actionPanel = document.getElementById('mobileActionPanel');

        if (menuContainer) {
            menuContainer.classList.toggle('open');
        }
        if (menuBar) {
            menuBar.classList.toggle('open');
        }
        if (actionPanel) {
            actionPanel.classList.remove('open');
        }
    }

    function toggleMobileActionPanel(e) {
        if (e) e.stopPropagation();
        var actionPanel = document.getElementById('mobileActionPanel');
        var actionBtn = document.getElementById('mobileActionBtn');
        var menuBar = document.getElementById('mainMenu');
        if (actionPanel && actionBtn) {
            var rect = actionBtn.getBoundingClientRect();
            actionPanel.style.position = 'fixed';
            actionPanel.style.top = (rect.bottom + 4) + 'px';
            actionPanel.style.left = rect.left + 'px';
            actionPanel.style.width = rect.width + 'px';
            actionPanel.style.minWidth = rect.width + 'px';
            actionPanel.style.maxWidth = rect.width + 'px';
            actionPanel.classList.toggle('open');
        }
        if (menuBar) {
            menuBar.classList.remove('open');
        }
    }

    function toggleQuickAccessFromHandle() {
        var sidebar = document.getElementById('quickAccessSidebarPanel');
        if (!sidebar) return;
        var isHidden = sidebar.classList.contains('hidden');
        var nextShowState = isHidden;
        
        var checkbox = document.getElementById('toggleQuickAccessCheckbox');
        var checkboxMobile = document.getElementById('toggleQuickAccessCheckboxMobile');
        if (checkbox) checkbox.checked = nextShowState;
        if (checkboxMobile) checkboxMobile.checked = nextShowState;
        toggleQuickAccessSidebar(nextShowState);
    }

    function toggleQuickAccessSidebar(show) {
        var sidebar = document.getElementById('quickAccessSidebarPanel');
        var handleIcon = document.getElementById('sidebarToggleHandleIcon');
        var body = document.body;
        var html = document.documentElement;

        var cb1 = document.getElementById('toggleQuickAccessCheckbox');
        var cb2 = document.getElementById('toggleQuickAccessCheckboxMobile');
        if (cb1) cb1.checked = show;
        if (cb2) cb2.checked = show;

        if (!sidebar) return;

        if (show) {
            sidebar.classList.remove('hidden');
            body.classList.add('has-quick-access');
            html.classList.add('has-quick-access');
            if (handleIcon) handleIcon.className = 'fa-solid fa-chevron-left';
            localStorage.setItem('showQuickAccess', 'true');
            document.cookie = "showQuickAccess=true; path=/; max-age=31536000";
        } else {
            sidebar.classList.add('hidden');
            body.classList.remove('has-quick-access');
            html.classList.remove('has-quick-access');
            if (handleIcon) handleIcon.className = 'fa-solid fa-chevron-right';
            localStorage.setItem('showQuickAccess', 'false');
            document.cookie = "showQuickAccess=false; path=/; max-age=31536000";
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var m = document.cookie.match(/(?:^|; )showQuickAccess=([^;]*)/);
        var pref = m ? m[1] : localStorage.getItem('showQuickAccess');
        var checkbox = document.getElementById('toggleQuickAccessCheckbox');
        var checkboxMobile = document.getElementById('toggleQuickAccessCheckboxMobile');
        var shouldShow = (pref === 'true');

        if (checkbox) checkbox.checked = shouldShow;
        if (checkboxMobile) checkboxMobile.checked = shouldShow;

        // Click listeners for header dropdowns (Click to toggle on mobile)
        var mainMenu = document.getElementById('mainMenu');
        if (mainMenu) {
            mainMenu.querySelectorAll('.menu-item.has-dropdown > .menu-link').forEach(function (link) {
                link.addEventListener('click', function (e) {
                    var dropdown = this.nextElementSibling;
                    if (dropdown && dropdown.classList.contains('dropdown')) {
                        e.preventDefault();
                        e.stopPropagation();

                        mainMenu.querySelectorAll('.dropdown.open').forEach(function (d) {
                            if (d !== dropdown) d.classList.remove('open');
                        });

                        dropdown.classList.toggle('open');
                    }
                });
            });

            mainMenu.querySelectorAll('.submenu > .submenu-link').forEach(function (subLink) {
                subLink.addEventListener('click', function (e) {
                    var subDropdown = this.nextElementSibling;
                    if (subDropdown && subDropdown.classList.contains('submenu-dropdown')) {
                        e.preventDefault();
                        e.stopPropagation();

                        var parentDropdown = this.closest('.dropdown');
                        if (parentDropdown) {
                            parentDropdown.querySelectorAll('.submenu-dropdown.open').forEach(function (sd) {
                                if (sd !== subDropdown) sd.classList.remove('open');
                            });
                        }

                        subDropdown.classList.toggle('open');
                    }
                });
            });
        }

        // Close mobile action panel or menu when clicking anywhere outside
        document.addEventListener('click', function (e) {
            var actionPanel = document.getElementById('mobileActionPanel');
            var actionBtn = document.getElementById('mobileActionBtn');
            var menuContainer = document.querySelector('.menu-container');
            var menuBar = document.getElementById('mainMenu');
            var menuToggleBtn = document.getElementById('menuToggle');

            if (actionPanel && actionPanel.classList.contains('open')) {
                if (!actionPanel.contains(e.target) && (!actionBtn || !actionBtn.contains(e.target))) {
                    actionPanel.classList.remove('open');
                }
            }

            if (window.innerWidth <= 768) {
                if (menuContainer && menuContainer.classList.contains('open')) {
                    if (!menuContainer.contains(e.target) && (!menuToggleBtn || !menuToggleBtn.contains(e.target))) {
                        menuContainer.classList.remove('open');
                        if (menuBar) menuBar.classList.remove('open');
                    }
                }
            }
        });
    });
    </script>

    <!-- STRICT 4-DIRECTION KEYBOARD ARROW NAVIGATION & SHORTCUT SCRIPT -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const mainMenu = document.getElementById('mainMenu');

        // GLOBAL SHORTCUT KEYS (Ctrl+J, Ctrl+S, Ctrl+P, Ctrl+U, Ctrl+D, Ctrl+M)
        document.addEventListener('keydown', function (e) {
            if (!e.ctrlKey && !e.metaKey) return;

            const key = e.key.toLowerCase();
            switch (key) {
                case 'j':
                    e.preventDefault();
                    window.location.href = '../jobcard/create.php';
                    break;
                case 's':
                    e.preventDefault();
                    window.location.href = '../sales/create.php';
                    break;
                case 'p':
                    e.preventDefault();
                    window.location.href = '../purchase/create.php';
                    break;
                case 'u':
                    e.preventDefault();
                    window.location.href = '../users/manage_users.php';
                    break;
                case 'd':
                    e.preventDefault();
                    window.location.href = '../report/daily_sales.php';
                    break;
                case 'm':
                    e.preventDefault();
                    if (!mainMenu) return;
                    const masterItem = Array.from(mainMenu.querySelectorAll('.menu-item')).find(item => item.textContent.includes('Master'));
                    if (masterItem) {
                        const link = masterItem.querySelector('.menu-link');
                        if (link) {
                            link.focus();
                            const firstSub = masterItem.querySelector('.dropdown .submenu-link');
                            if (firstSub) firstSub.focus();
                        }
                    }
                    break;
            }
        });

        if (!mainMenu) return;

        function getTopItems() {
            return Array.from(mainMenu.querySelectorAll('.menu-item > a, .menu-item > .menu-link'));
        }

        function getPrimaryDropdownItems(dropdown) {
            return Array.from(dropdown.children).reduce((acc, child) => {
                if (child.matches('a')) acc.push(child);
                if (child.classList && child.classList.contains('submenu')) {
                    const subLink = child.querySelector(':scope > .submenu-link');
                    if (subLink) acc.push(subLink);
                }
                return acc;
            }, []);
        }

        mainMenu.addEventListener('keydown', function (e) {
            const target = e.target;
            if (!target) return;

            const isTopItem = target.matches('.menu-item > a, .menu-item > .menu-link');
            const isSubmenuLink = target.matches('.submenu-link');
            const inSubmenuDropdown = target.closest('.submenu-dropdown');
            const inDropdown = target.closest('.dropdown');

            const key = e.key;

            // 1. TOP-LEVEL HEADERS: LEFT/RIGHT TO SWITCH HEADERS, DOWN TO ENTER DROPDOWN
            if (isTopItem) {
                const topItems = getTopItems();
                const index = topItems.indexOf(target);
                if (index === -1) return;

                if (key === 'ArrowRight') {
                    e.preventDefault();
                    const next = topItems[(index + 1) % topItems.length];
                    if (next) next.focus();
                } else if (key === 'ArrowLeft') {
                    e.preventDefault();
                    const prev = topItems[(index - 1 + topItems.length) % topItems.length];
                    if (prev) prev.focus();
                } else if (key === 'ArrowDown') {
                    const dropdown = target.parentElement.querySelector('.dropdown');
                    if (dropdown) {
                        e.preventDefault();
                        const items = getPrimaryDropdownItems(dropdown);
                        if (items.length > 0) items[0].focus();
                    }
                }
            }

            // 2. INSIDE DROPDOWNS & SUBMENUS
            else if (inDropdown) {
                // A. INSIDE A RIGHT SUBMENU DROPDOWN (e.g. inside + Add Brand, Brand List, etc.)
                if (inSubmenuDropdown) {
                    const subItems = Array.from(target.closest('.submenu-dropdown').querySelectorAll('a'));
                    const index = subItems.indexOf(target);

                    if (key === 'ArrowDown') {
                        e.preventDefault();
                        if (index < subItems.length - 1) {
                            subItems[index + 1].focus();
                        }
                    } else if (key === 'ArrowUp') {
                        e.preventDefault();
                        if (index > 0) {
                            subItems[index - 1].focus();
                        } else {
                            // Return focus to parent submenu header (e.g. Brand)
                            const parentSub = target.closest('.submenu').querySelector('.submenu-link');
                            if (parentSub) parentSub.focus();
                        }
                    } else if (key === 'ArrowLeft' || key === 'Escape') {
                        e.preventDefault();
                        // Exit right submenu & return focus to parent submenu link (e.g. Brand, Customers, Supplier)
                        const parentSub = target.closest('.submenu').querySelector('.submenu-link');
                        if (parentSub) parentSub.focus();
                    }
                } 
                // B. ON PRIMARY DROPDOWN ITEMS (e.g. Brand, Model, Machine, Spares, Customers, Supplier)
                else {
                    const dropdown = target.closest('.dropdown');
                    const items = getPrimaryDropdownItems(dropdown);
                    const index = items.indexOf(target);

                    if (key === 'ArrowDown') {
                        e.preventDefault();
                        // Strictly move down to the next primary submenu link in Master
                        if (index < items.length - 1) {
                            items[index + 1].focus();
                        }
                    } else if (key === 'ArrowUp') {
                        e.preventDefault();
                        // Move up through primary submenu links
                        if (index > 0) {
                            items[index - 1].focus();
                        } else {
                            // Focus top-level menu link (e.g. Master)
                            const topLink = dropdown.parentElement.querySelector('a, .menu-link');
                            if (topLink) topLink.focus();
                        }
                    } else if ((key === 'ArrowRight' || key === 'Enter' || key === ' ') && isSubmenuLink) {
                        e.preventDefault();
                        // Strictly ONLY enter right submenu on ArrowRight, Enter, or Space
                        const subDropdown = target.parentElement.querySelector('.submenu-dropdown');
                        if (subDropdown) {
                            const firstSubItem = subDropdown.querySelector('a');
                            if (firstSubItem) firstSubItem.focus();
                        }
                    } else if (key === 'ArrowLeft') {
                        e.preventDefault();
                        // Return to top header bar
                        const topItems = getTopItems();
                        const topLink = dropdown.parentElement.querySelector('a, .menu-link');
                        const topIndex = topItems.indexOf(topLink);
                        if (topIndex !== -1) {
                            const prev = topItems[(topIndex - 1 + topItems.length) % topItems.length];
                            if (prev) prev.focus();
                        }
                    } else if (key === 'Escape') {
                        e.preventDefault();
                        // Close dropdown and focus top header
                        const topLink = dropdown.parentElement.querySelector('a, .menu-link');
                        if (topLink) topLink.focus();
                    }
                }
            }
        });
    });
    </script>