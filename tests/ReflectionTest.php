<?php
use PHPUnit\Framework\TestCase;
use edphp\exception\ClassNotFoundException;

class ReflectionTest extends TestCase {

    public function testReflectClass() {
        $config = $this->invokeClass('edphp\Config');
        PHPUnit\Framework\Assert::assertTrue($config);
    }

    public function invokeClass($class, $vars = [])
    {
        try {
            $reflect = new ReflectionClass($class);

            $constructor = $reflect->getConstructor();

            $args = $constructor ? $this->bindParams($constructor, $vars) : [];

            return $reflect->newInstanceArgs($args);

        } catch (ReflectionException $e) {
            throw new ClassNotFoundException('class not exists ');
        }
    }

}