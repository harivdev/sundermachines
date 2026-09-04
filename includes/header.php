<?php
require_once(__DIR__ . '/auth.php');
requireLogin();

$current_page = basename($_SERVER['PHP_SELF']);

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
            padding-top: 50px;
            padding-bottom: 80px;
        }

        /* TOP BAR (Mobile only) */
        .topbar {
            display: none !important;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand {
            font-weight: 700;
            color: #24231F;
            font-size: 16px;
            letter-spacing: 0.4px;
            display: flex;
            align-items: center;
            gap: 6px;
            text-decoration: none !important;
        }

        .brand .brand-accent {
            color: #C9A227;
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
            background: #FBF7E8;
            color: #24231F;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid #E3D49C;
        }

        .user-pill small {
            color: #8A877D;
            font-size: 11px;
            text-transform: uppercase;
        }

        .btn-logout {
            background: transparent;
            color: #9A7618;
            padding: 7px 16px;
            border: 1px solid #C9A227;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            font-family: 'Inter', Arial, sans-serif;
            transition: all 0.25s ease;
        }

        .btn-logout:hover {
            background: #C9A227;
            color: #FFFFFF;
            border-color: #C9A227;
        }

        /* MENU CONTAINER */
        .menu-container {
            background: #FCFBF7;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 999;
            border-bottom: 1px solid #E4E0D6;
            box-shadow: 0 1px 4px rgba(36, 35, 31, 0.05);
        }

        .menu-bar {
            display: flex;
            margin: 0;
            padding: 0 12px;
            list-style: none;
            align-items: center;
            width: 100%;
            height: 46px;
            overflow: visible !important;
        }

        .menu-item {
            position: relative;
        }

        .menu-item>a,
        .menu-item>.menu-link {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 10px 16px;
            text-decoration: none !important;
            color: #4D4A42;
            font-weight: 600;
            font-size: 16.5px;
            cursor: pointer;
            border-bottom: none !important;
            transition: all 0.25s ease;
            white-space: nowrap;
            user-select: none;
        }

        .nav-icon {
            font-size: 16.5px;
            color: inherit;
            transition: color 0.15s ease;
            margin-right: 5px;
        }

        .nav-shortcut {
            display: inline-block;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            color: #8A877D !important; /* Muted shortcut color */
            border: none !important;
            background: transparent !important;
            padding: 0;
            margin-left: auto;
            letter-spacing: 0.3px;
            vertical-align: middle;
            transition: color 0.25s ease;
        }

        .dropdown a:hover .nav-shortcut,
        .dropdown a:focus .nav-shortcut {
            color: #9A7618 !important;
        }

        .menu-item:hover > a,
        .menu-item:hover > .menu-link,
        .menu-item>a:hover,
        .menu-item>.menu-link:hover,
        .menu-item>a:focus,
        .menu-item>.menu-link:focus,
        .menu-item>a:focus-visible,
        .menu-item>.menu-link:focus-visible {
            color: #C9A227 !important;
            background: transparent !important;
            border-bottom: none !important;
            text-decoration: none !important;
            outline: none;
        }

        .menu-item:hover > a .nav-icon,
        .menu-item:hover > .menu-link .nav-icon,
        .menu-item>a:hover .nav-icon,
        .menu-item>.menu-link:hover .nav-icon {
            color: #E6C65C !important;
        }

        .menu-item>a.active-link,
        .menu-item>.menu-link.active-link {
            color: #9A7618 !important;
            background: transparent !important;
            border-bottom: none !important;
            font-weight: 600;
        }

        .arrow {
            font-size: 12px;
            display: inline-block;
            margin-left: 3px;
            transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), color 0.25s ease;
            transform: rotate(-90deg);
        }

        .menu-item:hover > a .arrow,
        .menu-item:hover > .menu-link .arrow,
        .menu-item>a:hover .arrow,
        .menu-item>.menu-link:hover .arrow {
            color: #E6C65C !important;
            transform: rotate(0deg);
        }
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
            background: #FFFFFF;
            min-width: 200px;
            box-shadow: 0 8px 24px rgba(36, 35, 31, 0.12);
            top: 100%;
            left: 0;
            z-index: 1001;
            border-radius: 0 0 10px 10px;
            border: 1px solid #E2DED3;
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
            padding: 9px 15px;
            display: flex;
            align-items: center;
            gap: 6px;
            color: #4D4A42;
            font-size: 16px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.25s ease;
            border-left: 3px solid transparent;
        }

        .dropdown a .nav-icon,
        .dropdown a i,
        .submenu-link .nav-icon,
        .submenu-link i,
        .submenu-dropdown a .nav-icon,
        .submenu-dropdown a i {
            font-size: 14.5px;
        }

        .dropdown>a:hover,
        .dropdown>a:focus,
        .dropdown>a:focus-visible {
            background: #FBF7E8;
            color: #9A7618;
            border-left-color: #C9A227;
            outline: none;
        }

        .dropdown>a.active-link {
            background: #FBF7E8;
            color: #9A7618;
            border-left-color: #C9A227;
            font-weight: 600;
        }

        .dropdown-divider {
            border: none;
            border-top: 1px solid #ECE8DE;
            margin: 4px 0;
        }

        /* SUBMENU */
        .submenu {
            position: relative;
        }

        .submenu-link {
            padding: 9px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 6px;
            color: #4D4A42;
            font-size: 16px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.25s ease;
            border-left: 3px solid transparent;
        }

        .submenu:hover > .submenu-link,
        .submenu-link:hover,
        .submenu-link:focus,
        .submenu-link:focus-visible {
            background: #FBF7E8;
            color: #9A7618;
            border-left-color: #C9A227;
            outline: none;
        }

        .submenu-dropdown {
            display: none;
            position: absolute;
            left: 100%;
            top: -4px;
            background: #FFFFFF;
            min-width: 190px;
            box-shadow: 0 8px 24px rgba(36, 35, 31, 0.12);
            border-radius: 8px;
            border: 1px solid #E2DED3;
            overflow: hidden;
            z-index: 1002;
        }

        .submenu-dropdown a {
            padding: 9px 15px;
            display: flex;
            align-items: center;
            gap: 6px;
            color: #4D4A42;
            font-size: 16px;
            text-decoration: none;
            transition: all 0.25s ease;
            border-left: 3px solid transparent;
        }

        .submenu-dropdown a:hover,
        .submenu-dropdown a:focus,
        .submenu-dropdown a:focus-visible {
            background: #FBF7E8;
            color: #9A7618;
            border-left-color: #C9A227;
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
            color: #24231F;
            font-size: 22px;
            padding: 4px 8px;
        }

        /* MOBILE RESPONSIVE NAV */
        /* MOBILE RESPONSIVE NAV */
        @media (max-width: 768px) {
            body {
                padding-top: 65px !important;
                padding-bottom: 160px !important;
            }

            .topbar {
                display: flex !important;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 56px;
                background: #FCFBF7;
                z-index: 10000;
                padding: 0 12px;
                align-items: center;
                justify-content: space-between;
                border-bottom: 1px solid #E4E0D6;
                box-shadow: 0 2px 10px rgba(36, 35, 31, 0.08);
            }

            .topbar-left {
                display: flex;
                align-items: center;
                gap: 10px;
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
                color: #24231F;
                text-decoration: none;
                display: flex;
                align-items: center;
                gap: 6px;
            }

            /* Hamburger icon - FIRST ON LEFT */
            #menuToggle {
                display: flex !important;
                align-items: center;
                justify-content: center;
                background: none;
                border: none;
                color: #24231F;
                font-size: 22px;
                cursor: pointer;
                padding: 6px;
            }

            /* Mobile Action button */
            .mobile-action-btn {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                background: rgba(36, 35, 31, 0.08);
                color: #24231F;
                border: 1px solid rgba(36, 35, 31, 0.15);
                border-radius: 8px;
                padding: 6px 12px;
                font-size: 12px;
                font-weight: 600;
                cursor: pointer;
                transition: background 0.15s ease;
            }

            .mobile-action-btn:hover {
                background: rgba(36, 35, 31, 0.12);
            }

            /* Mobile Action Dropdown Panel (Exact width matching Action label button) */
            .mobile-action-panel {
                position: fixed;
                top: 58px;
                background: #FCFBF7;
                border: 1px solid #E4E0D6;
                border-radius: 10px;
                padding: 8px 6px;
                box-shadow: 0 10px 25px rgba(36, 35, 31, 0.15);
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

            /* MOBILE MENU DRAWER (Strict Fixed Width) */
            .menu-container {
                display: block !important;
                position: fixed !important;
                top: 56px !important;
                left: 0 !important;
                width: 250px !important;
                min-width: 250px !important;
                max-width: 250px !important;
                overflow-x: hidden !important;
                background: #FFFFFF !important;
                z-index: 10005 !important;
                border-right: 1px solid #E4E0D6;
                border-bottom: 1px solid #E4E0D6;
                box-shadow: 6px 0 25px rgba(36, 35, 31, 0.08) !important;
                max-height: calc(100vh - 56px);
                overflow-y: auto !important;
                scrollbar-gutter: stable !important;
                -webkit-overflow-scrolling: touch;
                transform: translateX(-105%);
                opacity: 0;
                pointer-events: none;
                transition: transform 0.42s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.32s ease !important;
            }

            /* Custom Slim Scrollbar for Drawer to prevent layout shifts */
            .menu-container::-webkit-scrollbar {
                width: 5px !important;
            }
            .menu-container::-webkit-scrollbar-track {
                background: transparent !important;
            }
            .menu-container::-webkit-scrollbar-thumb {
                background: #D8D4C8 !important;
                border-radius: 4px !important;
            }

            .menu-container.open {
                transform: translateX(0) !important;
                opacity: 1 !important;
                pointer-events: auto !important;
            }

            .menu-container .nav-icon {
                margin-right: 0 !important;
                font-size: 15px !important;
            }

            .menu-bar {
                flex-direction: column !important;
                display: flex !important;
                height: auto !important;
                max-height: none !important;
                padding: 0 !important;
                margin: 0 !important;
                background: #FFFFFF !important;
                border-top: none;
            }

            .menu-item {
                width: 100% !important;
                height: auto !important;
                border-bottom: 1px solid #F6F3EA;
            }

            .menu-item > a,
            .menu-item > .menu-link {
                display: flex !important;
                align-items: center !important;
                justify-content: flex-start !important;
                gap: 2px !important;
                padding: 13px 18px !important;
                color: #4D4A42 !important;
                font-size: 15px !important;
                font-weight: 600 !important;
                text-decoration: none !important;
                background: #FFFFFF !important;
                border: none !important;
                border-left: 3px solid transparent !important;
                cursor: pointer !important;
                transition: all 0.28s cubic-bezier(0.4, 0, 0.2, 1) !important;
            }

            /* Arrows align in a straight vertical line on the right edge */
            .menu-item > a .arrow,
            .menu-item > .menu-link .arrow {
                margin-left: auto !important;
                flex-shrink: 0 !important;
                transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1) !important;
            }

            .menu-item > a:hover,
            .menu-item > .menu-link:hover,
            .menu-item > a:active,
            .menu-item > .menu-link:active,
            .menu-item > a.active-link,
            .menu-item > .menu-link.active-link {
                background: #FBF7E8 !important;
                color: #9A7618 !important;
                border-left-color: #C9A227 !important;
            }

            /* MOBILE INLINE ACCORDION DROPDOWNS */
            .dropdown {
                display: block !important;
                max-height: 0 !important;
                overflow: hidden !important;
                opacity: 0 !important;
                position: static !important;
                width: 100% !important;
                background: #FCFBF7 !important;
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                border-radius: 0 !important;
                transition: max-height 0.45s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.35s ease !important;
            }

            .dropdown.open {
                max-height: 600px !important;
                opacity: 1 !important;
            }

            .dropdown > a {
                display: flex !important;
                align-items: center !important;
                gap: 6px !important;
                padding: 11px 22px !important;
                color: #4D4A42 !important;
                font-size: 14px !important;
                font-weight: 500 !important;
                text-decoration: none !important;
                background: transparent !important;
                border-bottom: 1px solid #F6F3EA !important;
                border-left: 3px solid transparent !important;
                transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
            }

            .dropdown > a:hover,
            .dropdown > a:active,
            .dropdown > a.active-link {
                background: #FBF7E8 !important;
                color: #9A7618 !important;
                border-left-color: #C9A227 !important;
            }

            /* MOBILE INLINE ACCORDION SUBMENUS */
            .submenu {
                width: 100% !important;
            }

            .submenu-link {
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                gap: 6px !important;
                padding: 11px 22px !important;
                color: #4D4A42 !important;
                font-size: 14px !important;
                font-weight: 500 !important;
                background: transparent !important;
                border-bottom: 1px solid #F6F3EA !important;
                border-left: 3px solid transparent !important;
                cursor: pointer !important;
                transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
            }

            .submenu:hover > .submenu-link,
            .submenu-link:hover,
            .submenu-link:active {
                background: #FBF7E8 !important;
                color: #9A7618 !important;
                border-left-color: #C9A227 !important;
            }

            .submenu-dropdown {
                display: block !important;
                max-height: 0 !important;
                overflow: hidden !important;
                opacity: 0 !important;
                position: static !important;
                width: 100% !important;
                background: #F6F3EA !important;
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                border-radius: 0 !important;
                transition: max-height 0.45s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.35s ease !important;
            }

            .submenu-dropdown.open {
                max-height: 400px !important;
                opacity: 1 !important;
            }

            .submenu-dropdown a {
                display: flex !important;
                align-items: center !important;
                gap: 10px !important;
                padding: 10px 10px 10px 40px !important;
                color: #5F5C54 !important;
                font-size: 13.5px !important;
                font-weight: 500 !important;
                text-decoration: none !important;
                border-bottom: 1px solid #E6E1D6 !important;
            }

            .submenu-dropdown a:hover,
            .submenu-dropdown a:active {
                background: #FBF7E8 !important;
                color: #9A7618 !important;
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

            /* MOBILE PAGINATION FOOTER ALIGNMENT & CLEAN TEXT WRAPPING */
            .pagination-bar,
            .list-pagination-bar,
            .pagination-container,
            .cd-pagination-bar {
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center !important;
                text-align: center !important;
                gap: 10px !important;
                margin: 15px auto !important;
                padding: 0 10px !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }

            .pagination-bar > span,
            .pagination-bar > div:first-child,
            .list-pagination-bar > div:first-child,
            .pagination-info,
            .cd-pag-info {
                width: 100% !important;
                font-size: 13px !important;
                font-weight: 500 !important;
                text-align: center !important;
                color: #475569 !important;
                display: block !important;
                margin-bottom: 4px !important;
                white-space: normal !important;
                word-break: normal !important;
            }

            .pagination-bar .pag-btns,
            .list-pagination-bar .pagination-buttons,
            .pagination-buttons,
            .cd-pag-controls,
            .pagination-bar > div:last-child {
                width: 100% !important;
                display: flex !important;
                justify-content: center !important;
                align-items: center !important;
                flex-wrap: wrap !important;
                gap: 4px !important;
            }

            .pagination-bar .pag-btn,
            .pagination-buttons a,
            .pagination-buttons span,
            .pag-btn {
                font-size: 12px !important;
                padding: 6px 10px !important;
                white-space: nowrap !important;
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



        /* BREADCRUMB PAGE BANNER STRIP (REMOVED) */
        .erp-breadcrumb-banner {
            display: none !important;
        }

        .breadcrumb-inner {
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 4px;
            font-family: 'Inter', sans-serif;
        }

        .bc-pill {
            background: transparent !important;
            padding: 0 !important;
            border: none !important;
            border-radius: 0 !important;
            font-size: 16px !important;
            font-style: italic !important;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
            line-height: 1.2;
            box-shadow: none !important;
        }

        .bc-brand {
            background: transparent !important;
            padding: 0 !important;
            border-radius: 0 !important;
            font-weight: 800;
            font-size: 17.5px !important;
            font-style: normal !important;
            letter-spacing: 0.6px;
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            box-shadow: none !important;
            margin-right: 40px !important; /* Pushes page names further to the right as requested */
        }

        .bc-sep {
            opacity: 0.75;
            font-size: 13px;
            color: #ffffff;
            margin: 0 4px;
            font-style: normal !important;
        }

        .bc-module {
            color: #a7f3d0;
        }

        .bc-page {
            color: #ffffff;
            text-decoration: none !important;
        }

        /* Prevent hover underlines & bottom borders across all header navigation elements, dropdowns, and breadcrumb banner */
        .menu-container a,
        .menu-container a:hover,
        .menu-container a:focus,
        .menu-container a:active,
        .menu-container span,
        .menu-container span:hover,
        .menu-item > a,
        .menu-item > a:hover,
        .menu-item > .menu-link,
        .menu-item > .menu-link:hover,
        .dropdown a,
        .dropdown a:hover,
        .dropdown a:focus,
        .submenu a,
        .submenu a:hover,
        .submenu-link:hover,
        .erp-breadcrumb-banner a,
        .erp-breadcrumb-banner a:hover,
        .erp-breadcrumb-banner a:focus,
        .erp-breadcrumb-banner span,
        .erp-breadcrumb-banner span:hover,
        .bc-brand,
        .bc-brand:hover,
        .bc-module,
        .bc-module:hover,
        .bc-page,
        .bc-page:hover {
            text-decoration: none !important;
            border-bottom: none !important;
            box-shadow: none !important;
        }



    </style>
</head>

<body>

    <!-- MOBILE TOP BAR HEADER -->
    <div class="topbar">
        <div class="topbar-left">
            <button id="menuToggle" onclick="toggleMobileMenu(event)" aria-label="Toggle Navigation">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>
        <div class="topbar-right">
            <button class="btn-logout" onclick="window.location.href='../login/logout.php'">
                Logout &#x2192;
            </button>
        </div>
    </div>

    <!-- MOBILE ACTION PANEL DROPDOWN -->
    <div id="mobileActionPanel" class="mobile-action-panel">

        <!-- Admin role pill -->
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

        <!-- Logout button -->
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

            <!-- 9. LOGOUT (Desktop only, pushed to right) -->
            <li class="menu-item topbar-actions-desktop" style="margin-left: auto; padding-right: 16px;">
                <button class="btn-logout" onclick="window.location.href='../login/logout.php'">
                    Logout &#x2192;
                </button>
            </li>

        </ul>
    </div>



    <!-- SCRIPT FOR MOBILE MENU -->
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

    document.addEventListener('DOMContentLoaded', function () {
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