<?php namespace BoundedContext\Laravel\Version;

use BoundedContext\Contracts\Event\DomainEvent;
use BoundedContext\Contracts\Command\Command;
use BoundedContext\Schema\Schema;
use EventSourced\ValueObject\ValueObject\Integer;

class Factory implements \BoundedContext\Contracts\Version\Factory
{
    public function event(DomainEvent $event)
    {
        $event_class = get_class($event);
        $upgrader_class = preg_replace('/Event/', 'Upgrader\\Event', $event_class);

        return $this->getVersion($upgrader_class);
    }
    
    public function command(Command $command)
    {
        $command_class = get_class($command);
        $upgrader_class = preg_replace('/Command/', 'Upgrader\\Command', $command_class);

        return $this->getVersion($upgrader_class);
    }

    private function getVersion($upgrader_class)
    {
        if (!class_exists($upgrader_class)) {
            return new Integer(1);
        }
        $upgrader = new $upgrader_class(new Schema(), new Integer(0));

        return $upgrader->latest_version();
    }
}
