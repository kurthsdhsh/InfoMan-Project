<?php
session_start();
include('../includes/adminHeader.php');
include('../includes/config.php');

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
    <a href="create.php" class="btn btn-primary btn-lg " role="button" aria-disabled="true">Add item</a></p>
    <h2>number of items <?= $itemCount ?> </h2>
    <table class="table table-striped table-bordered">
        <?php
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td><img src='{$row['image_path']}' width='150' height='150' /> </td>";
            echo "<td>{$row['product_id']}</td>";
            echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['product_description']) . "</td>";
            echo "<td>{$row['unit_price']}</td>";
            echo "<td>{$row['stock_quantity']}</td>";
            echo "<td>" . htmlspecialchars($row['product_status']) . "</td>";

            echo "<td><a href='edit.php?id={$row['product_id']}'><i class='fa-regular fa-pen-to-square' style='color: blue'></i></a><a href='delete.php?id={$row['product_id']}'><i class='fa-solid fa-trash' style='color: red'></i></a></td>";
            echo "</tr>";
        }
        ?>
    </table>