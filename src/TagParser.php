<?php

namespace Datashaman\PHPCheck;

use Ds\Map;
use Exception;
use PhpParser\{
    Node,
    Node\Expr\Array_,
    Node\Expr\FuncCall,
    Node\Scalar\String_,
    Node\Stmt\Function_,
    NodeDumper,
    NodeFinder,
    NodeTraverser,
    NodeVisitorAbstract,
    ParserFactory,
};

class TagParser extends NodeVisitorAbstract
{
    protected $count;
    protected $gen;
    protected $values;

    public function __construct()
    {
        $this->gen = app('gen');
    }

    protected function startsWith($haystack, $needle) {
        return $needle !== '' && substr($haystack, 0, strlen($needle)) === (string) $needle;
    }

    protected function resolveValue($value)
    {
        $class = get_class($value);

        if ($class === Array_::class) {
            return array_map(
                function ($item) {
                    return $this->resolveValue($item->value);
                },
                $value->items
            );
        }

        if ($this->startsWith($class, 'PhpParser\\Node\\Scalar')) {
            return $value->value;
        }

        throw new Exception('Unhandled node value');
    }

    protected function resolveFuncCall($node)
    {
        $name = (string) $node->name;

        $args = [];
        foreach ($node->args as $arg) {
            if ($arg->value instanceof FuncCall) {
                $result = $this->resolveFuncCall($arg->value);
                $args[] = $result;
            } else {
                $args[] = $this->resolveValue($arg->value);
            }
        }

        if (method_exists($this->gen, $name)) {
            return $this->gen->{$name}(...$args);
        }

        if (method_exists(iter::class, $name)) {
            return iter::$name(...$args);
        }

        throw new Exception('Unhandled function: ' . $name);
    }

    public function parse($tag)
    {
        $this->values = new Map();

        $code = "<?php $tag ?>";
        $factory = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $ast = $factory->parse($code);

        $nodeFinder = new NodeFinder();
        $node = $nodeFinder->findFirstInstanceOf($ast, FuncCall::class);
        $result = $this->resolveFuncCall($node);

        return $result;
    }
}
