<?php namespace BoundedContext\Laravel\Event\Snapshot;

use BoundedContext\Contracts\Event\Event;
use BoundedContext\Event\Snapshot\Snapshot;
use BoundedContext\Contracts\Command\Command;
use BoundedContext\Command\Snapshot\Snapshot as CommandSnapshot;
use BoundedContext\Contracts\Version\Factory as EventVersionFactory;
use BoundedContext\Contracts\Generator\DateTime;
use BoundedContext\Contracts\Generator\Identifier;
use BoundedContext\Schema\Schema;
use BoundedContext\Contracts\Schema\Schema as SchemaContract;
use BoundedContext\Map\Map;
use EventSourced\ValueObject\ValueObject\Integer;
use EventSourced\ValueObject\Serializer\Serializer;
use BoundedContext\Event\Type as EventType;

class Factory implements \BoundedContext\Contracts\Event\Snapshot\Factory, \BoundedContext\Contracts\Command\Snapshot\Factory
{
    protected $identifier_generator;
    protected $datetime_generator;
    protected $event_version_factory;
    protected $map;
    protected $serializer;
    
    public function __construct(
        Identifier $identifier_generator,
        DateTime $datetime_generator,
        EventVersionFactory $event_version_factory,
        Map $map,
        Serializer $serializer
    )
    {
        $this->identifier_generator = $identifier_generator;
        $this->datetime_generator = $datetime_generator;
        $this->event_version_factory = $event_version_factory;
        $this->map = $map;
        $this->serializer = $serializer;
    }

    public function event(Event $event)
    {
        $serialized = $this->serializer->serialize($event->values());
        $domain_event = $event->values();
        return new Snapshot(
            $event->id(),
            $this->event_version_factory->event($domain_event),
            $this->datetime_generator->now(),
            $this->map->get_id($domain_event),
            $this->event_to_type($domain_event),
            $event->command_id(),
            $event->root_entity_id(),
            $event->aggregate_type_id(),         
            new Schema($serialized)
        );
    }
    
    public function command(Command $command)
    {
        $serialized = $this->serializer->serialize($command);
        return new CommandSnapshot(
            $this->event_version_factory->command($command),
            $this->datetime_generator->now(),
            $this->map->get_id($command),
            new Schema($serialized)
        );
    }
    
    private function event_to_type($event)
    {
        $class = strtolower(get_class($event));
        $parts = explode("\\", $class);
        unset($parts[0]);
        unset($parts[3]);
        unset($parts[5]);
        $parts = array_values($parts);
        return new EventType(implode(".", $parts));
    }

    public function schema(SchemaContract $schema)
    {
        return new Snapshot(
            $this->identifier_generator->string($schema->id),
            new Integer($schema->version),
            $this->datetime_generator->string($schema->occurred_at),
            $this->identifier_generator->string($schema->type_id),
            new EventType($schema->type),
            $this->identifier_generator->string($schema->command_id),
            $this->identifier_generator->string($schema->root_entity_id),
            $this->identifier_generator->string($schema->aggregate_type_id),
            new Schema($schema->event)
        );
    }
}
