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
        $upgrader = new $upgrader_class(new Schema(), new Integer(0));

        return $upgrader->latest_version();
    }
    
    public function command(Command $command)
    {
        $command_class = get_class($command);
        $upgrader_class = preg_replace('/Command/', 'Upgrader\\Command', $command_class);
        $upgrader = new $upgrader_class(new Schema(), new Integer(0));
        
        return $upgrader->latest_version();
    }
}
