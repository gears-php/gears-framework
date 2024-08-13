<?php

function gf_base_path(): bool|string
{
    return strstr(__DIR__, '/vendor/', true);
}

function gf_trace_file(string $file, int $line): void
{
    ?>
    <div class="px-3 pb-2 mb-2 border-bottom gf-trace-file">
    <?= substr($file, strlen(gf_base_path()) + 1) ?><small class="text-black-50">:<?= $line ?></small>
    </div>
    <?php
}

/** @var Exception $e */
$errors = [$e];
while ($e = $e->getPrevious()) {
    array_unshift($errors, $e);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $errors[0]->getMessage() ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <style>
        body {
            background-color: #dee2e6;
        }

        .nav-link,
        .gf-trace-file {
            font-size: .9rem;
        }
        .gf-stack-trace{
            font-size: .8rem;
        }

        .nav-link.active {
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
            <?php foreach ($errors as $i => $err): ?>
                <button class="nav-link <?php if (!$i): ?>active  <?php endif ?>" id="error-tab-<?= $i ?>" data-bs-toggle="tab" data-bs-target="#error-pane-<?= $i ?>" type="button" role="tab" aria-controls="error-pane-<?= $i ?>" aria-selected="false" title="<?= get_class($err) ?>">
                    <?= (new ReflectionClass($err))->getShortName() ?>
                    <?= $i + 1 < count($errors) ? '&rarr;' : '' ?>
                </button>
            <?php endforeach ?>
            <button class="nav-link" id="error-tab-raw" data-bs-toggle="tab" data-bs-target="#error-pane-raw" type="button" role="tab" aria-controls="error-pane-raw" aria-selected="false">&#9636; Raw</button>
        </div>

        <div class="tab-content shadow mb-5 bg-body">
            <?php foreach ($errors as $i => $err): ?>
                <div class="mb-4 tab-pane <?php if (!$i): ?>active shown<?php endif ?>" role="tabpanel" id="error-pane-<?= $i++ ?>"  aria-labelledby="error-tab-<?= $i ?>">
                    <h5 class="p-3 bg-light text-black-50"><?= $err->getMessage() ?></h5>
                    <?php gf_trace_file($err->getFile(), $err->getLine()) ?>
                    <?php foreach ($err->getTrace() as $t): ?>
                        <?php if (!str_starts_with($t['file'], gf_base_path() . '/vendsor/')) : ?>
                            <div class="px-3"><?= $t['class'] ?? '' ?><?= $t['type'] ?? '' ?><span class="fw-bold"><?= $t['function'] ?></span></div>
                        <?php endif ?>

                        <?php gf_trace_file($t['file'], $t['line']) ?>
                    <?php endforeach ?>

                </div>
            <?php endforeach ?>

            <!-- raw stack trace -->
            <div class="mb-4 tab-pane" role="tabpanel" id="error-pane-raw" aria-labelledby="error-tab-raw">
                <?php foreach ($errors as $i => $err): ?>
                    <pre class="gf-stack-trace px-3 py-3 mb-0">
<?= $i > 0 ? '&rarr; ' : '' ?><?= get_class($err) ?>: <?= $err->getMessage() ?>&nbsp;
    in <?= $err->getFile() ?>(<?= $err->getLine() ?>)<br>
<?= $err->getTraceAsString() ?>
                    </pre>
                <?php endforeach ?>
            </div>
            <!-- // raw stack trace -->

        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
</body>
</html>