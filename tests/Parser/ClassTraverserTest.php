<?php

declare(strict_types = 1);

/*
 * This file is part of the Doctum utility.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Doctum\Tests\Parser;

use PHPUnit\Framework\TestCase;
use Doctum\Parser\ClassTraverser;
use Doctum\Parser\ClassVisitorInterface;
use Doctum\Project;
use Doctum\Reflection\ClassReflection;
use Doctum\Store\ArrayStore;

class ClassTraverserTest extends TestCase
{

    /**
     * @dataProvider getTraverseOrderClasses
     */
    public function testTraverseOrder(
        string $interfaceName,
        string $parentName,
        string $className,
        ClassReflection $class,
        ClassReflection $parent,
        ClassReflection $interface
    ): void {
        $store = new ArrayStore();
        $store->setClasses([$class, $parent, $interface]);

        $project = new Project($store);

        $visitor = $this->getMockBuilder(ClassVisitorInterface::class)->getMock();
        $visitor->method('visit')->withConsecutive(
            [$project->loadClass($interfaceName)],
            [$project->loadClass($parentName)],
            [$project->loadClass($className)]
        );

        $traverser = new ClassTraverser();
        /** @var ClassVisitorInterface $visitor */
        $traverser->addVisitor($visitor);

        $traverser->traverse($project);
        $proj = $store->readProject($project);

        $this->assertArrayHasKey('C1', $proj);
        $this->assertArrayHasKey('C2', $proj);
        $this->assertArrayHasKey('C3', $proj);
    }

    /**
     * @dataProvider getNamespaceDetectionClasses
     */
    public function testNamespaceDetection(
        string $interfaceName,
        string $parentName,
        string $className,
        ClassReflection $class,
        ClassReflection $parent,
        ClassReflection $interface,
        array $expectedNamespaces
    ): void {
        $store = new ArrayStore();
        $store->setClasses([$class, $parent, $interface]);

        $project = new Project($store);

        $project->loadClass($interfaceName);
        $project->loadClass($parentName);
        $project->loadClass($className);

        $this->assertEquals($expectedNamespaces, $project->getNamespaces());
    }

    public function getTraverseOrderClasses(): array
    {
        // as classes are sorted by name in Project, we try all combinaison
        // by giving different names to the classes
        return [
            $this->createClasses('C1', 'C2', 'C3'),
            $this->createClasses('C1', 'C3', 'C2'),
            $this->createClasses('C2', 'C1', 'C3'),
            $this->createClasses('C2', 'C3', 'C1'),
            $this->createClasses('C3', 'C1', 'C2'),
            $this->createClasses('C3', 'C2', 'C1'),
        ];
    }

    public function getNamespaceDetectionClasses(): array
    {
        return [
            array_merge($this->createClasses('C1', 'C2', 'C3'), [['']]),
            array_merge($this->createClasses('C1', 'C2', 'C3', 'Ns1'), [['', 'Ns1']]),
            array_merge($this->createClasses('C1', 'C2', 'C3', 'Ns1\Ns2\Ns3'), [['', 'Ns1', 'Ns1\Ns2', 'Ns1\Ns2\Ns3']]),
        ];
    }

    protected function createClasses(
        string $interfaceName,
        string $parentName,
        string $className,
        ?string $namespaceName = null
    ): array {
        $interface = new ClassReflection($interfaceName, 1);

        $parent = new ClassReflection($parentName, 1);
        /** @var mixed $interfaceName */
        $parent->addInterface($interfaceName);

        $class = new ClassReflection($className, 1);
        $class->setParent($parentName);
        if ($namespaceName) {
            $class->setNamespace($namespaceName);
        }

        return [$interfaceName, $parentName, $className, $class, $parent, $interface];
    }

}
