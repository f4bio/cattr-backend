<?php


namespace Tests\Utils\Reflection;

use ReflectionObject;

class ReflectionHelper
{
    public static function getValue(object $object, string $name)
    {
        $ro = new ReflectionObject($object);
        $property = $ro->getProperty($name);
        $property->setAccessible(true);

        return $property->getValue($object);
    }
}
