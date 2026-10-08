<?php
include('../auth.php');

include('../../includes/adminHeader.php');
include('../../includes/config.php');

echo '<link rel="stylesheet" href="/InfoMan-Project/includes/style/adminstyle.css?v=' . time() . '">';

$sql = "SELECT * FROM tbl_categories";
$result = mysqli_query($conn, $sql);

$itemCount = mysqli_num_rows($result);

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';

unset($_SESSION['success']);
unset($_SESSION['error']);
?>

<body>

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
            <h2>Categories (<?= $itemCount ?>)</h2>

            <a href="category_form.php" class="btn-add" role="button">
                Add category
            </a>
        </div>

        <div class="table-card">

            <table class="admin-table">

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php

                    while ($row = mysqli_fetch_assoc($result)) {

                        echo "<tr>";

                        echo "<td>{$row['category_id']}</td>";

                        echo "<td>" .
                            htmlspecialchars($row['category_name']) .
                            "</td>";

                        echo "<td>" .
                            htmlspecialchars($row['description'] ?? '') .
                            "</td>";

                        echo "<td>";

                        echo "<a href='category_form.php?id={$row['category_id']}'>
                                <i class='fa-regular fa-pen-to-square' style='color: blue'></i>
                              </a>";

                        echo "<a href='delete.php?id={$row['category_id']}'
                                 onclick=\"return confirm('Are you sure you want to delete this category?');\">
                                <i class='fa-solid fa-trash' style='color: red'></i>
                              </a>";

                        echo "</td>";

                        echo "</tr>";
                    }

                    ?>

                </tbody>

            </table>

        </div>

    </div>

</body>