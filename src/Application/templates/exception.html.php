<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= get_class(/** @var Exception $e */ $e) ?>: <?= $e->getMessage() ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <style>
        body {
            font-family: monospace;
        }

        p {
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="mt-4">
            <h4><?= (new ReflectionClass($e))->getShortName() ?></h4>
            <h5 class="text-muted"><?= $e->getMessage() ?></h5>
            <p>in <?= $e->getFile() ?>(<?= $e->getLine() ?>)</p>
            <p><?= str_replace("\n", '<br>', $e->getTraceAsString()) ?></p>
        </div>

        <?php if ($e = $e->getPrevious()): ?>
            <div class="mt-4">
                <h4><?= (new ReflectionClass($e))->getShortName() ?></h4>
                <h5 class="text-muted"><?= $e->getMessage() ?></h5>
                <p>in <?= $e->getFile() ?>(<?= $e->getLine() ?>)</p>
                <p><?= str_replace("\n", '<br>', $e->getTraceAsString()) ?></p>
            </div>
        <?php endif ?>
    </div>
</body>
</html>