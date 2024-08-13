<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= /** @var $e */ $e->getMessage() ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <style>
        body {
            background-color: #dee2e6;
        }

        button.nav-link,
        div.trace-in {
            font-size: 0.9rem;
        }

        button.nav-link.active {
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.15);
            font-weight: bolder;
        }
    </style>
</head>

<body>
    <div class="bg-danger">
        <div class="container px-3 py-1 text-light ">Oops! Something went wrong...</div>
    </div>

    <div class="container">
        <div class="nav nav-tabs mt-4" role="tablist">
            <?php
            $e_breadcrumbs = new Exception('', 0, $e); $i = 0;
            while ($e_breadcrumbs = $e_breadcrumbs->getPrevious()): ?>
                <button class="nav-link <?php if (!$i): ?>active  <?php endif ?>" id="error-tab-<?= $i ?>" data-bs-toggle="tab" data-bs-target="#error-pane-<?= $i ?>" type="button" role="tab" aria-controls="error-pane-<?= $i ?>" aria-selected="true" title="<?= get_class($e_breadcrumbs) ?>">
                    <?= $i++ ? '&larr;' : '' ?>
                    <?= (new ReflectionClass($e_breadcrumbs))->getShortName() ?>
                </button>
            <?php endwhile ?>
        </div>

        <div class="tab-content shadow mb-5 bg-body">
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
                <div class="mb-4 tab-pane <?php if (!$i): ?>active shown<?php endif ?>" role="tabpanel" id="error-pane-<?= $i++ ?>"  aria-labelledby="error-tab-<?= $i ?>">

<!--                    <div class="bg-secondary text-light px-3 py-1">--><?php //= (new ReflectionClass($e))->getShortName() ?><!--</div>-->

                    <h5 class="p-3 mb-0 bg-dark text-secondary"><?= $e->getMessage() ?></h5>


    <!--                --><?php //var_export($e->getTrace()) ?>

                    <div class="px-3 py-2">in <?= $e->getFile() ?> <span class="text-secondary">[<?= $e->getLine() ?>]</span></div>
                    <?php foreach ($e->getTrace() as $t): ?>
                        <div class="px-3 pt-2 border-top"><?= $t['class'] ?? '' ?><?= $t['type'] ?? '' ?><span class="fw-bold"><?= $t['function'] ?></span></div>
                        <div class="px-3 pb-2 trace-in">in <?= $t['file'] ?> <span class="text-secondary">[<?= $t['line'] ?>]</span>
                        </div>
                    <?php endforeach ?>
                </div>
            <?php endwhile ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
</body>
</html>