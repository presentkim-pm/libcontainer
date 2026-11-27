<?php

declare(strict_types=1);

namespace kim\present\libcontainer\tests;

use kim\present\libcontainer\MenuListener;
use pocketmine\event\Event;
use PHPUnit\Framework\TestCase;

/**
 * PocketMine resolves each handler's event class by reflection when the listener is registered, so a handler typed
 * with a class the server does not ship crashes LibContainer::register(). Catch that without a server.
 */
final class MenuListenerTest extends TestCase{

    public function testEveryHandlerTakesAnExistingEventClass() : void{
        $checked = 0;
        foreach((new \ReflectionClass(MenuListener::class))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method){
            if($method->isStatic() || $method->getNumberOfParameters() !== 1){
                continue;
            }
            $type = $method->getParameters()[0]->getType();
            if(!$type instanceof \ReflectionNamedType || $type->isBuiltin()){
                continue;
            }
            $class = $type->getName();
            if(!str_ends_with($class, 'Event')){
                continue;
            }
            self::assertTrue(class_exists($class), "{$method->getName()}() takes $class, which does not exist");
            self::assertTrue(is_subclass_of($class, Event::class), "$class is not an event");
            $checked++;
        }
        self::assertGreaterThan(0, $checked);
    }
}
