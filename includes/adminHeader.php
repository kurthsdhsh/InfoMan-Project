<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="/InfoMan-Project/includes/style/adminstyle.css">

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN"
        crossorigin="anonymous">

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
        integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA=="
        crossorigin="anonymous"
        referrerpolicy="no-referrer">

    <link
        href="/InfoMan-Project/includes/style/style.css"
        rel="stylesheet"
        type="text/css">

    <link
        href="/InfoMan-Project/includes/style/loginstyle.css"
        rel="stylesheet"
        type="text/css">

    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
        crossorigin="anonymous">
    </script>

    <title>Shop</title>

</head>


<body>

    <nav class="navbar navbar-expand-lg bg-body-tertiary">

        <div class="container-fluid">

            <a
                class="navbar-brand"
                href="/InfoMan-Project/admin/home.php">
                ADMIN DASHBOARD
            </a>


            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent"
                aria-controls="navbarSupportedContent"
                aria-expanded="false"
                aria-label="Toggle navigation">

                <span class="navbar-toggler-icon"></span>

            </button>


            <div
                class="collapse navbar-collapse"
                id="navbarSupportedContent">


                <ul class="navbar-nav me-auto mb-2 mb-lg-0">



                    <!-- Dashboard -->
                    <li class="nav-item">
                        <a class="nav-link"
                        href="/InfoMan-Project/admin/home.php">
                            Dashboard
                        </a>
                    </li>

                    <!-- Product Catalog Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle"
                        href="#"
                        id="catalogDropdown"
                        role="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false">
                            Product Catalog
                        </a>

                        <ul class="dropdown-menu"
                            aria-labelledby="catalogDropdown">

                            <li>
                                <a class="dropdown-item"
                                href="/InfoMan-Project/admin/category/categories.php">
                                    Categories
                                </a>
                            </li>

                            <li>
                                <a class="dropdown-item"
                                href="/InfoMan-Project/admin/product/products.php">
                                    Products
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Inventory Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle"
                        href="#"
                        id="inventoryDropdown"
                        role="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false">
                            Inventory
                        </a>

                        <ul class="dropdown-menu"
                            aria-labelledby="inventoryDropdown">

                            <li>
                                <a class="dropdown-item"
                                href="/InfoMan-Project/admin/inventory/inventory.php">
                                    Stock Management
                                </a>
                            </li>

                            <li>
                                <a class="dropdown-item"
                                href="/InfoMan-Project/admin/inventory/stock_history.php">
                                    Stock History
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle"
                        href="#"
                        id="reportsDropdown"
                        role="button"
                        data-bs-toggle="dropdown"
                        aria-expanded="false">
                            Sales &amp; Reports
                        </a>

                        <ul class="dropdown-menu"
                            aria-labelledby="reportsDropdown">

                            <li>
                                <a class="dropdown-item" href="#">
                                    Orders
                                </a>
                            </li>

                            <li>
                                <a class="dropdown-item" 
                                href="/InfoMan-Project/admin/expense/expenses.php">
                                    Expenses
                                </a>
                            </li>

                            <li>
                                <a class="dropdown-item" href="#">
                                    Business Reports
                                </a>
                            </li>
                        </ul>
                    </li>

                </ul>


                <!-- Search -->

                <form
                    action="/InfoMan-Project/search.php"
                    method="GET"
                    class="d-flex">

                    <input
                        class="form-control me-2"
                        type="search"
                        placeholder="Search"
                        aria-label="Search"
                        name="search">

                    <button
                        class="btn btn-outline-success"
                        type="submit">

                        Search

                    </button>

                </form>


                <!-- Login / Logout -->

                <?php

                if (!isset($_SESSION['user_id'])) {

                    echo "
                        <div class='navbar-nav ms-auto'>

                            <a
                                href='/InfoMan-Project/admin/adminlogin.php'
                                class='nav-item nav-link'>

                                Admin

                            </a>

                            <a
                                href='/InfoMan-Project/user/login.php'
                                class='nav-item nav-link'>

                                Login

                            </a>

                        </div>
                    ";

                } else {

                    echo "
                        <div class='navbar-nav ms-auto'>

                            <a
                                href='/InfoMan-Project/user/logout.php'
                                class='nav-item nav-link'>

                                Logout

                            </a>

                        </div>
                    ";

                }

                ?>


            </div>

        </div>

    </nav>