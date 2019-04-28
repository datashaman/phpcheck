<div id="<?= $function['shortName'] ?>" class="function">
    <h3 class="header">
        <?= $function['header'] ?>
    </h3>

    <div class="content">
        <?php if (isset($function['summary'])): ?>
            <div class="summary">
                <?= $function['summary'] ?>
            </div>
        <?php endif ?>

        <?php if (isset($function['description'])): ?>
            <div class="description">
                <?= $function['description'] ?>
            </div>
        <?php endif ?>

        <?php if ($function['arguments']): ?>
            <h4>Arguments</h4>

            <ul class="arguments">
                <?php foreach ($function['arguments'] as $arg): ?>
                    <li>
                        <span class="code">
                            <?php if ($arg['type']): ?><span class="type"><?= $arg['type'] ?></span><?php endif ?>
                            <span class="name">$<?= $arg['name'] ?></span>
                            <?php if ($arg['default']): ?>= <span class="default"><?= $arg['default'] ?></span><?php endif ?>
                        </span>
                        <?php if ($arg['description']): ?> : <span class="argument-description"><?= $arg['description'] ?></span> <?php endif ?>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>

        <?php if (isset($function['return'])): ?>
            <div class="return">
                <h4>Returns</h4>

                <ul>
                    <li>
                        <span class="code">
                            <?php if (!empty($function['return']['type'])): ?><span class="type"><?= $function['return']['type'] ?></span><?php endif ?>
                            <?php if (!empty($function['return']['description'])): ?> : <span class="description"><?= $function['return']['description'] ?></span> <?php endif ?>
                        </span>
                    </li>
                </ul>
            </div>
        <?php endif ?>

        <?php if (isset($function['example'])): ?>
            <div class="example">
                <h4>Example</h4>

                <div>
                    <pre><?= $function['example'] ?></pre>
                </div>

                <h4>Output</h4>

                <div class="output">
                    <pre><?= $function['output'] ?></pre>
                </div>

                <?php if ($function['gist']): ?>
                    <form class="pure-form">
                        <input id="<?= $function['shortName'] ?>-gist" class="pure-input-1-2" type="text" value="melody run <?= $function['gist'] ?>" readonly>

                        <button type="button"
                            class="copy pure-button pure-button-primary"
                            data-clipboard-target="#<?= $function['shortName'] ?>-gist">
                            <i class="fas fa-clipboard"></i>
                        </button>
                    </form>
                <?php endif ?>
            </div>
        <?php endif ?>
    </div>
</div>
