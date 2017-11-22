<?php

namespace Tests\unit\Utils;

use App\Utils\ClassUtils;
use PHPUnit\Framework\TestCase;

class ClassUtilsTest extends TestCase
{
  public function setUp()
  {
    $this->classUtils = new ClassUtils();
  }

  public function testInstantiateItShouldReturnAnInstanceOfTheRequestedClassWithTheProvidedArgumentsProperlyProcessed()
  {
    $returnValue = $this->classUtils->instantiate('\Tests\unit\Utils\FakeClass', ['test name', 25]);

    $this->assertEquals(FakeClass::class, get_class($returnValue));
    $this->assertEquals('test name', $returnValue->name);
    $this->assertEquals(25, $returnValue->age);
  }

  public function testInstantiateIfANamespaceIsProvidedAndTheClassNameIsAFqnItShouldReturnAnInstanceOfTheRequestedClassWithTheProvidedArgumentsProperlyProcessed()
  {
    $returnValue = $this->classUtils->instantiate('\Tests\unit\Utils\FakeClass', ['test name 2', 30], '\Tests\unit\Utils');

    $this->assertEquals(FakeClass::class, get_class($returnValue));
    $this->assertEquals('test name 2', $returnValue->name);
    $this->assertEquals(30, $returnValue->age);
  }

  public function testInstantiateIfANamespaceIsProvidedAndTheClassNameIsNotAFqnItShouldReturnAnInstanceOfTheRequestedClassWithTheProvidedArgumentsProperlyProcessed()
  {
    $returnValue = $this->classUtils->instantiate('FakeClass', ['another test name', 32], '\Tests\unit\Utils');

    $this->assertEquals(FakeClass::class, get_class($returnValue));
    $this->assertEquals('another test name', $returnValue->name);
    $this->assertEquals(32, $returnValue->age);
  }

  public function testInstantiateIfANamespaceWithoutTrailingBackslashIsProvidedAndTheClassNameIsNotAFqnItShouldReturnAnInstanceOfTheRequestedClassWithTheProvidedArgumentsProperlyProcessed()
  {
    $returnValue = $this->classUtils->instantiate('FakeClass', ['another test name 2', 49], 'Tests\unit\Utils');

    $this->assertEquals(FakeClass::class, get_class($returnValue));
    $this->assertEquals('another test name 2', $returnValue->name);
    $this->assertEquals(49, $returnValue->age);
  }
}

class FakeClass
{
  public $name;
  public $age;

  public function __construct(string $name, int $age)
  {
    $this->name = $name;
    $this->age = $age;
  }
}