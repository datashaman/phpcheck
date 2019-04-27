<?php declare(strict_types=1);
/*
 * This file is part of the phpcheck package.
 *
 * ©Marlin Forbes <marlinf@datashaman.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Datashaman\PHPCheck;

use Closure;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;

class Reflection
{
    public function getClass($class)
    {
        return new ReflectionClass($class);
    }

    public function getFunctionSignature(ReflectionFunction $function): string
    {
        return $function->getName();
    }

    public function getMethodSignature(ReflectionMethod $method): string
    {
        return $method->getDeclaringClass()->getName() . '::' . $method->getName();
    }

    public function getParamTags(ReflectionParameter $param): array
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

    public function reflect($subject)
    {
        if (
            \is_string($subject)
            || $subject instanceof Closure
        ) {
            $subject = new ReflectionFunction($subject);
        }

        return $subject;
    }

    private function getParamAnnotations($reflectionCallable): array
    {
        $factory    = DocBlockFactory::createInstance();
        $docComment = $reflectionCallable->getDocComment();

        if ($docComment === false) {
            return [];
        }

        $docBlock = $factory->create($docComment);

        return $docBlock->getTagsByName('param');
    }

    private function getParamAnnotation(ReflectionParameter $param): ?Param
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
}
