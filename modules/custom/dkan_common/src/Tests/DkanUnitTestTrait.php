<?php

namespace Drupal\dkan_common\Tests;
use Symfony\Component\DependencyInjection\ContainerInterface;
use PHPUnit\Framework\TestCase;
/**
 * Trait to sideload some utilities into other Unit tests.
 * 
 * @@author Yaasir Ketwaroo <yaasir.ketwaroo@semanticbits.com>
 */
Trait DkanUnitTestTrait {

    /**
     * Helper to call projected methods
     * 
     * @param object $object
     * @param string $methodName
     * @param variable-lengrh $arguments Additional arguments to pass to 
     * @return mixed
     * @throws InvalidArgumentException If method is not defined in object
     */
    protected function invokeProtectedMethod($object, string $methodName, ...$arguments) {

        $reflection = new \ReflectionClass($object);
        if (!$reflection->hasMethod($methodName)) {
            throw new \InvalidArgumentException("Method not found: {$methodName}");
        }

        $reflectedMethod = $reflection->getMethod($methodName);
        $reflectedMethod->setAccessible(TRUE);

        return $reflectedMethod->invoke($object, ...$arguments);
    }

    /**
     * Helper to get projected property. 
     * 
     * @param object $object
     * @param string $property
     * @return mixed
     * @throws InvalidArgumentException
     */
    protected function accessProtectedProperty($object, string $property) {
        $reflection = new \ReflectionClass($object);
        if (!$reflection->hasProperty($property)) {
            throw new \InvalidArgumentException("Property not found: {$property}");
        }
        $reflectionProperty = $reflection->getProperty($property);
        $reflectionProperty->setAccessible(TRUE);
        return $reflectionProperty->getValue($object);
    }
    
    /**
     * Helper to set projected property. 
     * 
     * @param object $object
     * @param string $property
     * @return mixed
     * @throws InvalidArgumentException
     */
    protected function writeProtectedProperty($object, string $property, $value) {
        $reflection = new \ReflectionClass($object);
        if (!$reflection->hasProperty($property)) {
            throw new \InvalidArgumentException("Property not found: {$property}");
        }
        $reflectionProperty = $reflection->getProperty($property);
        $reflectionProperty->setAccessible(TRUE);
        return $reflectionProperty->setValue($object, $value);
    }

    

  /**
   * Creates a mock instance of the service container with the `get` method overriden.
   * 
   * @return PHPUnit\Framework\MockObject\MockObject
   * @throws \Exception If not in a unit test case.
   */
  protected function getMockContainer() {

    if (!($this instanceof TestCase)) {
      throw new \Exception('This function is meant to be used only with a PHPUnit test case.');
    }

    return $this->getMockBuilder(ContainerInterface::class)
                    ->setMethods(['get'])
                    ->disableOriginalConstructor()
                    ->getMockForAbstractClass();
  }

}
