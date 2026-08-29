<?php
require_once(__DIR__ . '/auth.php');
requireLogin();

$current_page = basename($_SERVER['PHP_SELF']);
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
            padding-top: 110px;
            /* Offset for fixed header */
            padding-bottom: 20px;
        }

        /* TOP BAR */
        .topbar {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            padding: 0 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 56px;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.3);
        }

        .brand {
            font-weight: 700;
            color: #fff;
            font-size: 18px;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .brand .brand-accent {
            color: #4ade80;
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
            top: 56px;
            z-index: 999;
            border-bottom: 1px solid #e5e7eb;
            box-shadow: 0 1px 6px rgba(0, 0, 0, 0.06);
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
            gap: 4px;
            padding: 14px 15px;
            text-decoration: none;
            color: #374151;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
            white-space: nowrap;
            user-select: none;
        }

        .menu-item>a:hover,
        .menu-item>.menu-link:hover {
            color: #111827;
            border-bottom-color: #4ade80;
            background: #f9fafb;
        }

        .menu-item>a.active-link {
            color: #16a34a;
            border-bottom-color: #22c55e;
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
        .menu-item.active > a .arrow,
        .menu-item.active > .menu-link .arrow,
        .submenu:hover > .submenu-link .arrow,
        .submenu.active > .submenu-link .arrow {
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
            /* Changed from hidden to show submenus */
        }

        .menu-item.active>.dropdown,
        .menu-item:hover>.dropdown {
            display: block;
        }

        .submenu:hover>.submenu-dropdown {
            display: block;
        }

        .dropdown>a {
            padding: 10px 16px;
            display: block;
            color: #374151;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.15s;
            border-left: 3px solid transparent;
        }

        .dropdown>a:hover {
            background: #f0fdf4;
            color: #16a34a;
            border-left-color: #4ade80;
        }

        .dropdown>a.active-link {
            background: #f0fdf4;
            color: #16a34a;
            border-left-color: #22c55e;
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
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s;
            border-left: 3px solid transparent;
        }

        .submenu:hover > .submenu-link,
        .submenu-link:hover,
        .submenu.active > .submenu-link {
            background: #f0fdf4;
            color: #16a34a;
            border-left-color: #4ade80;
        }

        .submenu-dropdown {
            display: none;
            position: absolute;
            left: 100%;
            top: 0;
            background: #fff;
            min-width: 180px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }

        .submenu.active>.submenu-dropdown {
            display: block;
        }

        .submenu-dropdown a {
            padding: 10px 16px;
            display: block;
            color: #374151;
            font-size: 13px;
            text-decoration: none;
            transition: all 0.15s;
            border-left: 3px solid transparent;
        }

        .submenu-dropdown a:hover {
            background: #f0fdf4;
            color: #16a34a;
            border-left-color: #4ade80;
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

            .brand {
                font-size: 16px;
            }

            .user-pill {
                padding: 4px 8px;
                font-size: 11px;
            }

            .user-pill small {
                display: none;
            }

            .btn-logout {
                padding: 5px 10px;
                font-size: 12px;
            }

            #menuToggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .menu-container {
                top: 56px;
            }

            .menu-bar {
                flex-direction: column;
                display: none;
                padding: 0;
                border-top: 1px solid #e5e7eb;
                max-height: calc(100vh - 56px);
                overflow-y: auto;
                background: #ffffff;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            }

            .menu-bar.open {
                display: flex;
            }

            .menu-item>a,
            .menu-item>.menu-link {
                border-bottom: none;
                border-left: 3px solid transparent;
                padding: 12px 20px;
            }

            .menu-item>a:hover,
            .menu-item>.menu-link:hover {
                border-bottom: none;
                border-left-color: #4ade80;
            }

            .dropdown {
                position: static;
                box-shadow: none;
                border: none;
                background: #f9fafb;
                border-radius: 0;
            }

            .submenu-dropdown {
                position: static;
                margin-left: 15px;
                box-shadow: none;
                border: none;
            }

            /* Prevent iOS auto-zoom on form elements */
            input,
            select,
            textarea {
                font-size: 16px !important;
            }
        }
    </style>
</head>

<body>

    <!-- TOP BAR -->
    <div class="topbar">
        <div class="brand">
            &#9881; <span class="brand-accent">Sunder</span>&nbsp;Billing
        </div>
        <div class="topbar-right">
            <button id="menuToggle" title="Toggle Menu">&#9776;</button>
            <?php if (isset($_SESSION['employee_name']) || isset($_SESSION['username'])): ?>
                <div class="user-pill">
                    <?php if (isset($_SESSION['employee_name'])): ?>
                        <span><?php echo htmlspecialchars($_SESSION['employee_name']); ?></span>
                        <small>Employee</small>
                    <?php else: ?>
                        <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                        <small><?php echo htmlspecialchars($_SESSION['role'] ?? 'Admin'); ?></small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <button class="btn-logout" onclick="window.location.href='../login/logout.php'">
                Logout &#x2192;
            </button>
        </div>
    </div>

    <!-- MENU -->
    <div class="menu-container">
        <ul class="menu-bar" id="mainMenu">

            <!-- DASHBOARD -->
            <li class="menu-item">
                <a href="../login/dashboard.php" <?php echo ($current_page == 'dashboard.php') ? 'class="active-link"' : ''; ?>>
                    Dashboard
                </a>
            </li>

            <!-- STOCK -->
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN'): ?>
                <li class="menu-item has-dropdown">
                    <span class="menu-link">Stock <span class="arrow">&#9660;</span></span>
                    <div class="dropdown">
                        <a href="../stock/add_stock.php" <?php echo ($current_page == 'add_stock.php') ? 'class="active-link"' : ''; ?>>
                            + Add Stock
                        </a>
                        <a href="../stock/list.php" <?php echo ($current_page == 'list.php' && strpos($_SERVER['PHP_SELF'], 'stock') !== false) ? 'class="active-link"' : ''; ?>>
                            Stock List
                        </a>
                    </div>
                </li>
            <?php endif; ?>

            <!-- SPARES -->
            <li class="menu-item has-dropdown">
                <span class="menu-link">Spares <span class="arrow">&#9660;</span></span>
                <div class="dropdown">
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN'): ?>
                        <a href="../spares/add_spare.php" <?php echo ($current_page == 'add_spare.php') ? 'class="active-link"' : ''; ?>>
                            + Add Spare
                        </a>
                    <?php endif; ?>
                    <a href="../spares/list_spare.php" <?php echo ($current_page == 'list_spare.php') ? 'class="active-link"' : ''; ?>>
                        Spares List
                    </a>
                </div>
            </li>

            <!-- JOB CARD -->
            <li class="menu-item has-dropdown">
                <span class="menu-link">Job Card <span class="arrow">&#9660;</span></span>
                <div class="dropdown">
                    <a href="../jobcard/create.php" <?php echo ($current_page == 'create.php' && strpos($_SERVER['PHP_SELF'], 'jobcard') !== false) ? 'class="active-link"' : ''; ?>>
                        Create Job
                    </a>
                    <a href="../jobcard/list.php" <?php echo ($current_page == 'list.php' && strpos($_SERVER['PHP_SELF'], 'jobcard') !== false) ? 'class="active-link"' : ''; ?>>
                        Job Card List
                    </a>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN'): ?>
                        <a href="../jobcard/spares_list.php" <?php echo ($current_page == 'spares_list.php' && strpos($_SERVER['PHP_SELF'], 'jobcard') !== false) ? 'class="active-link"' : ''; ?>>
                            Job Card Spares
                        </a>
                    <?php endif; ?>
                </div>
            </li>

            <!-- SALES -->
            <li class="menu-item has-dropdown">
                <span class="menu-link">Sales <span class="arrow">&#9660;</span></span>
                <div class="dropdown">
                    <a href="../sales/create.php" <?php echo ($current_page == 'create.php' && strpos($_SERVER['PHP_SELF'], 'sales') !== false) ? 'class="active-link"' : ''; ?>>
                        Create Sale
                    </a>
                    <a href="../sales/list.php" <?php echo ($current_page == 'list.php' && strpos($_SERVER['PHP_SELF'], 'sales') !== false) ? 'class="active-link"' : ''; ?>>
                        Sales List
                    </a>
                </div>
            </li>

            <!-- PURCHASE -->
            <?php if (isAdmin()): ?>
                <li class="menu-item has-dropdown">
                    <span class="menu-link">Purchase <span class="arrow">&#9660;</span></span>
                    <div class="dropdown">
                        <a href="../purchase/create.php" <?php echo ($current_page == 'create.php' && strpos($_SERVER['PHP_SELF'], 'purchase') !== false) ? 'class="active-link"' : ''; ?>>
                            + Create Purchase
                        </a>
                        <a href="../purchase/purchase_list.php" <?php echo ($current_page == 'purchase_list.php' && strpos($_SERVER['PHP_SELF'], 'purchase') !== false) ? 'class="active-link"' : ''; ?>>
                            Purchase List
                        </a>
                    </div>
                </li>
            <?php endif; ?>

            <!-- CUSTOMERS -->
            <?php if (isAdmin()): ?>
                <li class="menu-item has-dropdown">
                    <span class="menu-link">Customers <span class="arrow">&#9660;</span></span>
                    <div class="dropdown">

                        <a href="../customers/manage_customers.php" <?php echo ($current_page == 'manage_customers.php') ? 'class="active-link"' : ''; ?>>
                            Customer List
                        </a>
                        <a href="../supplier/manage_supplier.php" <?php echo ($current_page == 'manage_suppliers.php' && strpos($_SERVER['PHP_SELF'], 'supplier') !== false) ? 'class="active-link"' : ''; ?>>
                            Supplier List
                        </a>
                        <a href="../supplier/manage_supplier.php" <?php echo ($current_page == 'manage_suppliers.php' && strpos($_SERVER['PHP_SELF'], 'supplier') !== false) ? 'class="active-link"' : ''; ?>>
                            + Add Supplier
                        </a>
                    </div>
                </li>
            <?php endif; ?>

            <!-- MASTER -->
            <?php if (isAdmin()): ?>
                <li class="menu-item has-dropdown">
                    <span class="menu-link">Master <span class="arrow">&#9660;</span></span>
                    <div class="dropdown">

                        <div class="submenu">
                            <span class="submenu-link">Brand <span class="arrow">&#9660;</span></span>
                            <div class="submenu-dropdown">
                                <a href="../brand/add.php">+ Add Brand</a>
                                <a href="../brand/list.php">Brand List</a>
                            </div>
                        </div>

                        <hr class="dropdown-divider">

                        <div class="submenu">
                            <span class="submenu-link">Model <span class="arrow">&#9660;</span></span>
                            <div class="submenu-dropdown">
                                <a href="../model/add.php">+ Add Model</a>
                                <a href="../model/list.php">Model List</a>
                            </div>
                        </div>

                        <hr class="dropdown-divider">

                        <div class="submenu">
                            <span class="submenu-link">Machine <span class="arrow">&#9660;</span></span>
                            <div class="submenu-dropdown">
                                <a href="../machine/add_machine.php">+ Add Machine</a>
                                <a href="../machine/list_machine.php">Machine List</a>
                            </div>
                        </div>

                    </div>
                </li>
            <?php endif; ?>

            <!-- REPORTS -->
            <?php if (isAdmin()): ?>
                <li class="menu-item has-dropdown">
                    <span class="menu-link">Reports <span class="arrow">&#9660;</span></span>
                    <div class="dropdown">
                        <a href="../report/daily_sales.php" <?php echo ($current_page == 'daily_sales.php') ? 'class="active-link"' : ''; ?>>
                            Daily Sales
                        </a>
                        <a href="../report/monthly_sales.php" <?php echo ($current_page == 'monthly_sales.php') ? 'class="active-link"' : ''; ?>>
                            Monthly Sales
                        </a>
                    </div>
                </li>
            <?php endif; ?>

            <!-- USERS (Only for ADMIN) -->
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN'): ?>
                <li class="menu-item">
                    <a href="../users/manage_users.php" <?php echo ($current_page == 'manage_users.php') ? 'class="active-link"' : ''; ?>>
                        Users
                    </a>
                </li>
            <?php endif; ?>

        </ul>
    </div>