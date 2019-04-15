<?php

declare(strict_types=1);
/*
 * This file is part of the phpcheck package.
 *
 * (c) Marlin Forbes <marlinf@datashaman.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Datashaman\PHPCheck;

use Exception;
use Faker\Factory;
use Faker\Generator as FakerGenerator;
use Generator;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionParameter;

class ArgumentFactory
{
    protected const TYPE_GENERATORS = [
        'bool'   => 'booleans',
        'float'  => 'floats',
        'int'    => 'integers',
        'string' => 'strings',
    ];

    /**
     * @var Runner
     */
    protected $runner;

    /**
     * @var FakerGenerator
     */
    protected $faker;

    /**
     * @var Gen
     */
    protected $gen;

    public function __construct(Runner $runner, int $seed = null)
    {
        $this->faker  = Factory::create();
        $this->gen    = new Gen($runner, $seed);
        $this->runner = $runner;
    }

    public function getGen()
    {
        return $this->gen;
    }

    public function make($function): Generator
    {
        $generators = [];

        foreach ($function->getParameters() as $param) {
            $tags = $this->getParamTags($param);

            if (\array_key_exists('gen', $tags)) {
                [$generator, $args] = $this->parseGenTag($tags['gen']);
            } else {
                $paramType = $param->hasType() ? $param->getType() : null;
                $type      = $paramType ? $paramType->getName() : 'mixed';

                if (!\array_key_exists($type, self::TYPE_GENERATORS)) {
                    throw new Exception("No generator found for $type");
                }

                $generator = self::TYPE_GENERATORS[$type];
                $args      = [];
            }

            if (!\method_exists($this->gen, $generator)) {
                throw new Exception("Gen $generator does not exist");
            }

            $generators[] = $this->gen->$generator(...$args);
        }

        while (true) {
            $arguments = [];

            foreach ($generators as $generator) {
                while ($generator->valid()) {
                    $arguments[] = $generator->current();
                    $generator->next();

                    break;
                }
            }

            yield $arguments;
        }
    }

    protected function getParamAnnotations($reflectionCallable): array
    {
        $factory    = DocBlockFactory::createInstance();
        $docComment = $reflectionCallable->getDocComment();

        if ($docComment === false) {
            return [];
        }

        $docBlock = $factory->create($docComment);

        return $docBlock->getTagsByName('param');
    }

    protected function getParamAnnotation(ReflectionParameter $param): ?Param
    {
        $method      = $param->getDeclaringFunction();
        $annotations = $this->getParamAnnotations($method);

        foreach ($annotations as $annotation) {
            if ($annotation->getVariableName() === $param->getName()) {
                return $annotation;
            }
        }

        return null;
    }

    protected function getParamTags(ReflectionParameter $param): array
    {
        $annotation = $this->getParamAnnotation($param);

        if (!$annotation) {
            return [];
        }

        $tags = [];

        foreach ($annotation->getDescription()->getTags() as $tag) {
            $tags[$tag->getName()] = (string) $tag->getDescription();
        }

        return $tags;
    }

    protected function parseGenTag(string $tag)
    {
        $embeds = [];

        $tag = \preg_replace_callback(
            '/{@gen\s+([^\}]*)}/',
            function ($matches) use (&$embeds) {
                $id = \uniqid('', true);
                $embeds[$id] = $matches[1];

                return \json_encode($id);
            },
            $tag
        );

        $parts     = \explode(':', $tag);
        $generator = $parts[0];
        $args      = [];

        if (\count($parts) > 1) {
            $args = \array_map(
                function ($arg) use ($embeds) {
                    if (\is_string($arg) && \array_key_exists($arg, $embeds)) {
                        $tag = $embeds[$arg];
                        [$generator, $args] = $this->parseGenTag($tag);

                        if (!\method_exists($this->gen, $generator)) {
                            throw new Exception("Gen $generator does not exist");
                        }

                        return $this->gen->$generator(...$args);
                    }

                    return $arg;
                },
                \json_decode($parts[1], true)
            );
        }

        return [$generator, $args];
    }
}
