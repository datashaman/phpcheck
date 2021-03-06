<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>PHPCheck Functions</title>

    <link href="https://fonts.googleapis.com/css?family=Source+Code+Pro" rel="stylesheet">

    <link rel="stylesheet" href="https://unpkg.com/purecss@1.0.0/build/base-min.css">
    <link rel="stylesheet" href="https://unpkg.com/purecss@1.0.0/build/pure-min.css"
        integrity="sha384-nn4HPE8lTHyVtfCBi5yW9d20FjT8BJwUXyWZT9InLYax14RDjBj46LmSztkmNP9w"
        crossorigin="anonymous">

    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.1/css/all.css"
        integrity="sha384-50oBUHEmvpQ+1lW4y57PTFmhCaXp0ML5d60M1M7uH2+nqUivzIebhndOJK28anvf"
        crossorigin="anonymous">

    <link rel="stylesheet" href="functions.css">
</head>
<body>

<div id="layout">
    <a href="#menu" id="menuLink" class="menu-link">
        <!-- Hamburger icon -->
        <span></span>
    </a>

    <div id="menu">
        <div class="pure-menu">
            <a class="pure-menu-heading" href="#">PHPCheck</a>

            <ul class="pure-menu-list">
                <li class="pure-menu-heading">Helpers</li>
                <?php foreach ($functions['helpers.php'] as $function): ?>
                    <li class="pure-menu-item">
                        <a href="#<?= $function['shortName'] ?>" class="pure-menu-link">
                            <?= $function['shortName'] ?>
                            <?php if (!isset($function['example'])): ?>
                                <sup class="fas fa-exclamation"></sup>
                            <?php endif ?>
                        </a>
                    </li>
                <?php endforeach ?>

                <li class="pure-menu-heading">Generators</li>
                <?php foreach ($functions['generators.php'] as $function): ?>
                    <li class="pure-menu-item">
                        <a href="#<?= $function['shortName'] ?>" class="pure-menu-link">
                            <?= $function['shortName'] ?>
                            <?php if (!isset($function['example'])): ?>
                                <sup class="fas fa-exclamation"></sup>
                            <?php endif ?>
                        </a>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
    </div>

    <div id="main">
        <?php foreach ($functions['helpers.php'] as $function): ?>
            <?= $view->render('function', compact('function')) ?>
        <?php endforeach ?>

        <?php foreach ($functions['generators.php'] as $function): ?>
            <?= $view->render('function', compact('function')) ?>
        <?php endforeach ?>
    </div>
</div>

<script src="https://unpkg.com/clipboard@2/dist/clipboard.min.js"></script>
<script>new ClipboardJS('.copy');</script>

</body>
</html>
