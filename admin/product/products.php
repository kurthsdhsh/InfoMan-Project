<?php
include('../auth.php');
include('../../includes/adminHeader.php');
include('../../includes/config.php');
include('../../includes/filter_helper.php');

        $search = adminFilterValue('search');
        $categoryFilter = adminFilterValue('category_id');
        $statusFilter = adminFilterValue('status');

        $conditions = [];
        $params = [];
        $types = '';

        if ($search !== '') {
            $conditions[] = 'p.product_name LIKE ?';
            $params[] = '%' . $search . '%';
            $types .= 's';
        }

        if ($categoryFilter !== '') {
            $conditions[] = 'p.category_id = ?';
            $params[] = (int)$categoryFilter;
            $types .= 'i';
        }

        if ($statusFilter !== '') {
            $conditions[] = 'p.product_status = ?';
            $params[] = $statusFilter;
            $types .= 's';
        }

        $sql = "SELECT
                    p.product_id,
                    p.product_name,
                    p.product_description,
                    p.unit_price,
                    p.stock_quantity,
                    p.image_path,
                    p.product_status,
                    c.category_name
                FROM tbl_products p
                INNER JOIN tbl_categories c
                    ON p.category_id = c.category_id";

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY p.product_id DESC';

        $stmt = mysqli_prepare($conn, $sql);

        if (!empty($params)) {
            $bindParams = [$types];

            foreach ($params as $key => $value) {
                $bindParams[] = &$params[$key];
            }

            call_user_func_array(
                [$stmt, 'bind_param'],
                $bindParams
            );
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $itemCount = mysqli_num_rows($result);

        // Load categories for the dropdown.
        $categoryResult = mysqli_query(
            $conn,
            "SELECT category_id, category_name
            FROM tbl_categories
            ORDER BY category_name"
        );

        $categoryOptions = [];

        while ($category = mysqli_fetch_assoc($categoryResult)) {
            $categoryOptions[$category['category_id']] =
                $category['category_name'];
        }


        $success = $_SESSION['success'] ?? '';
        $error = $_SESSION['error'] ?? '';

        unset($_SESSION['success']);
        unset($_SESSION['error']);
        ?>


    <div class="admin-container">

        <?php if ($success != ''): ?>

            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?>
            </div>

        <?php endif; ?>


        <?php if ($error != ''): ?>

            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>

        <?php endif; ?>


        <div class="admin-toolbar">

            <h2>Products (<?= $itemCount ?>)</h2>

            <a href="product_form.php" class="btn-add" role="button">
                Add product
            </a>

        </div>


        
        <?php
        renderAdminFilterForm(
            basename($_SERVER['PHP_SELF']),
            $search,
            'Search product name...',
            [
                [
                    'name' => 'category_id',
                    'label' => 'All Categories',
                    'options' => $categoryOptions,
                    'selected' => $categoryFilter
                ],
                [
                    'name' => 'status',
                    'label' => 'All Statuses',
                    'options' => [
                        'Active' => 'Active',
                        'Inactive' => 'Inactive'
                    ],
                    'selected' => $statusFilter
                ]
            ]
        );
        ?>


        <div class="table-card">

            <table class="admin-table">

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>

                </thead>


                <tbody>

                    <?php

                    while ($row = mysqli_fetch_assoc($result)) {

                        echo "<tr>";

                        echo "<td>{$row['product_id']}</td>";

                        echo "<td>";

                        if (!empty($row['image_path'])) {

                            echo "<img src='/InfoMan-Project/{$row['image_path']}'
                                      width='70'
                                      height='70'
                                      style='object-fit: cover; border-radius: 8px;'>";

                        } else {

                            echo "No image";

                        }

                        echo "</td>";

                        echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";

                        echo "<td>" . htmlspecialchars($row['category_name']) . "</td>";

                        echo "<td>" . htmlspecialchars($row['product_description'] ?? '') . "</td>";

                        echo "<td>₱" . number_format($row['unit_price'], 2) . "</td>";

                        echo "<td>{$row['stock_quantity']}</td>";

                        echo "<td>" . htmlspecialchars($row['product_status']) . "</td>";

                        echo "<td>";

                        echo "<a href='product_form.php?id={$row['product_id']}'>
                                <i class='fa-regular fa-pen-to-square'
                                   style='color: blue'></i>
                              </a>";

                        echo "<a href='delete.php?id={$row['product_id']}'
                                 onclick=\"return confirm('Are you sure you want to delete this product?');\">
                                <i class='fa-solid fa-trash'
                                   style='color: red'></i>
                              </a>";

                        echo "</td>";

                        echo "</tr>";
                    }

                    ?>

                </tbody>

            </table>

        </div>

    </div>
    
    </main>