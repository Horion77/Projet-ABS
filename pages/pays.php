<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id > 0) {
    redirect('country.php?id=' . $id);
}
redirect('map.php');
