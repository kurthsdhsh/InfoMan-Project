<?php
// Get a safe, trimmed value from the URL.
function adminFilterValue($key)
{
    return trim($_GET[$key] ?? '');
}

// Display a reusable GET filter form.
function renderAdminFilterForm(
    $action,
    $searchValue,
    $searchPlaceholder,
    $filters = []
) {
    ?>
    <form method="GET" action="<?= htmlspecialchars($action) ?>"
          style="display:flex; flex-wrap:wrap; gap:10px; margin-bottom:20px; align-items:center;">

        <input
            type="text"
            name="search"
            class="form-control"
            placeholder="<?= htmlspecialchars($searchPlaceholder) ?>"
            value="<?= htmlspecialchars($searchValue) ?>"
            style="max-width:260px;"
        >

        <?php foreach ($filters as $filter): ?>
            <select
                name="<?= htmlspecialchars($filter['name']) ?>"
                class="form-select"
                style="max-width:200px;"
            >
                <option value="">
                    <?= htmlspecialchars($filter['label']) ?>
                </option>

                <?php foreach ($filter['options'] as $value => $label): ?>
                    <option
                        value="<?= htmlspecialchars((string)$value) ?>"
                        <?= (string)$filter['selected'] === (string)$value
                            ? 'selected'
                            : '' ?>
                    >
                        <?= htmlspecialchars($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endforeach; ?>

        <button type="submit" class="btn btn-primary">Search</button>
        <a href="<?= htmlspecialchars($action) ?>" class="btn btn-secondary">Reset</a>
    </form>
    <?php
}
?>
