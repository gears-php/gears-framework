<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= get_class(/** @var Exception $e */ $e) ?>: <?= $e->getMessage() ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <style>
        div {
            font-size: 0.85rem;
        }

        div.trace-in {
            font-size: 0.8rem;
        }
    </style>
</head>

<body>
    <div class="container mt-1">

        <ul class="nav nav-tabs" role="tablist">
            <?php
            $e_breadcrumbs = new Exception('', 0, $e); $i = 0;
            while ($e_breadcrumbs = $e_breadcrumbs->getPrevious()): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold" id="error-tab-<?= $i ?>" data-bs-toggle="tab" data-bs-target="#error-tab-<?= $i ?>" type="button" role="tab" aria-controls="error-tab-<?= $i ?>" aria-selected="true">
                        <?= (new ReflectionClass($e_breadcrumbs))->getShortName() ?>
                    </button>
                </li>
            <?php endwhile ?>
        </ul>

        <div class="tab-content">
            <?php
            $e = new Exception('', 0, $e); $i = 0;
            while ($e = $e->getPrevious()): ?>


<?php if (false): ?>
<pre>
<?= get_class($e) ?><br>
<?= $e->getMessage() ?><br>
in <?= $e->getFile() ?>(<?= $e->getLine() ?>)<br>
<?= str_replace("\n", '<br>', $e->getTraceAsString()) ?>
</pre>
<?php endif ?>

                <div class="mb-4 tab-pane show active" role="tabpanel" id="error-tab-<?= $i ?>">

    <!--                <div class="bg-secondary text-light px-3 py-1">--><?php //= (new ReflectionClass($e))->getShortName() ?><!--</div>-->

                    <h5 class="bg-dark text-secondary px-3 py-3 mb-0"><?= $e->getMessage() ?></h5>


    <!--                --><?php //var_export($e->getTrace()) ?>

                    <div class="bg-light px-3 py-2">in <?= $e->getFile() ?> <span class="text-secondary">[<?= $e->getLine() ?>]</span></div>
                    <?php foreach ($e->getTrace() as $t): ?>
                        <div class="bg-light px-3 pt-2 border-top"><?= $t['class'] ?? '' ?><?= $t['type'] ?? '' ?><span class="fw-bold"><?= $t['function'] ?></span></div>
                        <div class="bg-light px-3 pb-2 trace-in">in <?= $t['file'] ?> <span class="text-secondary">[<?= $t['line'] ?>]</span>
                        </div>
                    <?php endforeach ?>
                </div>
            <?php endwhile ?>
        </div>
    </div>
</body>
</html>