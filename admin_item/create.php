<?php
session_start();
include('../includes/header.php');
include('../includes/config.php');

// var_dump($_SESSION);
// unset($_SESSION);

?>

<body>
    <div class="container">
        <?php include('../includes/alert.php'); ?>
        <form method="POST" action="store.php" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Item Name</label>
                <input type="text" class="form-control" id="name" placeholder="Enter item name" name="product_name"
                    value="<?php

                            if (isset($_SESSION['name']))
                                echo htmlspecialchars($_SESSION['name']);
                            ?>" />

                <small><?php
                        if (isset($_SESSION['nameError'])) {
                            echo $_SESSION['nameError'];
                            unset($_SESSION['nameError']);
                        }
                        ?></small>


                <label for="desc">Description</label>

                <input type="text" class="form-control" id="desc" placeholder="Enter item description"
                    name="product_description"
                    value="<?php if (isset($_SESSION['desc']))
                                echo htmlspecialchars($_SESSION['desc']); ?>">
                <small><?php
                        if (isset($_SESSION['descError'])) {
                            echo $_SESSION['descError'];
                            unset($_SESSION['descError']);
                        }
                        ?></small>

                <label for="category">Category ID</label>

                <input type="number" class="form-control" id="category" placeholder="Enter category id"
                    name="category_id"
                    value="<?php echo htmlspecialchars($_SESSION['category'] ?? ''); ?>">

                <label for="price">unit price</label>

                <input type="text" class="form-control" id="price" placeholder="Enter unit price" name="unit_price"
                    value="<?php echo htmlspecialchars($_SESSION['price'] ?? ''); ?>">
                <small><?php
                        if (isset($_SESSION['priceError'])) {
                            echo $_SESSION['priceError'];
                            unset($_SESSION['priceError']);
                        }
                        ?></small>

                <label for="qty">stock quantity</label>

                <input type="number" class="form-control" id="qty" placeholder="1" name="stock_quantity"
                    value="<?php echo htmlspecialchars($_SESSION['qty'] ?? ''); ?>" />
                <input class="form-control" type="file" name="image_path" /><br />
                <small><?php
                        if (isset($_SESSION['imageError'])) {
                            echo $_SESSION['imageError'];
                            unset($_SESSION['imageError']);
                        }
                        ?></small>
            </div>
            <button type="submit" class="btn btn-primary" name="submit">Submit</button>
            <a href="index.php" role="button" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
    <?php
    include('../includes/footer.php');
    ?>