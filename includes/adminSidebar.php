<?php
// adminSidebar.php: replaces adminHeader.php. Opens <html>, <body>, the left sidebar and <main>.
// Every admin page does:  include('../includes/adminSidebar.php');  ...content...  </main></body></html>
// Set $pageTitle before including it to change the browser tab title.

$current = $_SERVER['SCRIPT_NAME'];

// returns 'active' when the current page URL contains one of the given parts
if (!function_exists('sidebarActive')) {
    function sidebarActive(array $parts): string
    {
        global $current;
        foreach ($parts as $part) {
            if (strpos($current, $part) !== false) {
                return 'active';
            }
        }
        return '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Admin Dashboard'); ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
        integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA=="
        crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="/InfoMan-Project/includes/style/adminstyle.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/InfoMan-Project/includes/style/adminsidebar.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous">
    </script>
</head>

<body class="admin-body">

    <!-- top bar: only visible on small screens, opens the sidebar -->
    <div class="admin-topbar">
        <button type="button" class="admin-menu-btn" aria-label="Open menu"
            onclick="document.getElementById('adminSidebar').classList.toggle('open')">
            <i class="fa-solid fa-bars"></i>
        </button>
        <span>ADMIN DASHBOARD</span>
    </div>

    <aside class="admin-sidebar" id="adminSidebar">

        <a class="sidebar-brand" href="/InfoMan-Project/admin/home.php">
            <i class="fa-solid fa-store"></i>
            <span>ADMIN DASHBOARD</span>
        </a>

        <nav class="sidebar-nav">

            <a class="sidebar-link <?php echo sidebarActive(['/admin/home.php']); ?>"
                href="/InfoMan-Project/admin/home.php">
                <i class="fa-solid fa-house"></i> Dashboard
            </a>

            <div class="sidebar-section">Customer Management</div>

            <a class="sidebar-link <?php echo sidebarActive(['/chk_user/']); ?>"
                href="/InfoMan-Project/admin/chk_user/user.php">
                <i class="fa-solid fa-table-cells-large"></i> Customers
            </a>

            <div class="sidebar-section">Product Catalog</div>

            <a class="sidebar-link <?php echo sidebarActive(['/category/']); ?>"
                href="/InfoMan-Project/admin/category/categories.php">
                <i class="fa-solid fa-table-cells-large"></i> Categories
            </a>
            <a class="sidebar-link <?php echo sidebarActive(['/product/', '/admin_item/']); ?>"
                href="/InfoMan-Project/admin/product/products.php">
                <i class="fa-solid fa-cube"></i> Products
            </a>

            <div class="sidebar-section">Inventory</div>

            <a class="sidebar-link <?php echo sidebarActive(['/inventory/inventory.php']); ?>"
                href="/InfoMan-Project/admin/inventory/inventory.php">
                <i class="fa-solid fa-warehouse"></i> Stock Management
            </a>
            <a class="sidebar-link <?php echo sidebarActive(['/inventory/stock_history.php']); ?>"
                href="/InfoMan-Project/admin/inventory/stock_history.php">
                <i class="fa-solid fa-clock-rotate-left"></i> Stock History
            </a>

            <div class="sidebar-section">Sales &amp; Reports</div>

            <a class="sidebar-link <?php echo sidebarActive(['/order/orders.php']); ?>"
                href="/InfoMan-Project/admin/order/orders.php">
                <i class="fa-solid fa-cart-shopping"></i> Orders
            </a>
            <a class="sidebar-link <?php echo sidebarActive(['/order/payments.php']); ?>"
                href="/InfoMan-Project/admin/order/payments.php">
                <i class="fa-solid fa-credit-card"></i> Payments
            </a>
            <a class="sidebar-link <?php echo sidebarActive(['/expense/']); ?>"
                href="/InfoMan-Project/admin/expense/expenses.php">
                <i class="fa-solid fa-money-bill-1"></i> Expenses
            </a>
            <a class="sidebar-link" href="#">
                <i class="fa-solid fa-chart-simple"></i> Business Reports
            </a>

        </nav>

        <a class="sidebar-link sidebar-logout" href="/InfoMan-Project/includes/logout.php">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>

    </aside>

    <main class="admin-content">