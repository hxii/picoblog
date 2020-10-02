<?php

// Inlcude the file
include_once 'picoblog.php';

// Instantiate the class with the source file
$mb = new \hxii\PicoBlog('blog.txt');

// Parse query string and get blog entries
$query = $mb->parseQuery();
$entries = ($query) ? $mb->getEntries($query) : $mb->getEntries('all');

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>picoblog @ <?= $_SERVER['HTTP_HOST'] ?></title>
</head>

<body>
    <h1>picoblog</h1>
    <?php
    // Display message and link to main list if viewing a filtered entry list
    if ($query) {
        echo '<div>Currently viewing ' . implode('', $query) . '. Back to <a href="' . $_SERVER['PHP_SELF'] . '">list?</a></div>';
    }
    ?>
    <ol>
        <!-- Render entries -->
        <?= $mb->renderEntries($entries, '<li class="e">{entry}</li>'); ?>
    </ol>
</body>

</html>