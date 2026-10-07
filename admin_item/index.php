<?php
session_start();
include('../includes/adminHeader.php');
include('../includes/config.php');
echo '<link rel="stylesheet" href="/InfoMan-Project/includes/style/adminstyle.css?v=' . time() . '">';

if (isset($_GET['search'])) {
    $keyword = strtolower(trim($_GET['search']));
    $sql = "SELECT * FROM tbl_products WHERE product_description LIKE ?";
    $result = mysqli_execute_query($conn, $sql, ["%{$keyword}%"]);
} else {
    $keyword = '';
    $sql = "SELECT * FROM tbl_products";
    $result = mysqli_query($conn, $sql);
}
// echo $sql;

$itemCount = mysqli_num_rows($result);

?>

<body>
    <div class="admin-container">
        <div class="admin-toolbar">
            <h2>number of items <?= $itemCount ?> </h2>
            <a href="create.php" class="btn-add" role="button">Add item</a>
        </div>

        <div class="table-card">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>ID</th>
                        <th>Name</th>
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
                        $statusClass = 'status-' . strtolower(preg_replace('/[^a-z]/i', '', $row['product_status']));
                        echo "<tr>";
                        echo "<td><img src='" . htmlspecialchars($row['image_path']) . "' width='150' height='150' /> </td>";
                        echo "<td>{$row['product_id']}</td>";
                        echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['product_description']) . "</td>";
                        echo "<td>{$row['unit_price']}</td>";
                        echo "<td>{$row['stock_quantity']}</td>";
                        echo "<td><span class='status {$statusClass}'>" . htmlspecialchars($row['product_status']) . "</span></td>";

                        echo "<td><a href='edit.php?id={$row['product_id']}'><i class='fa-regular fa-pen-to-square' style='color: blue'></i></a><a href='delete.php?id={$row['product_id']}'><i class='fa-solid fa-trash' style='color: red'></i></a></td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>