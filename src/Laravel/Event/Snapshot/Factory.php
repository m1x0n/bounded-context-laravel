<?php namespace BoundedContext\Laravel\Event\Snapshot;

use BoundedContext\Contracts\Event\Event;
use BoundedContext\Event\Snapshot\Snapshot;
use BoundedContext\Contracts\Command\Command;
use BoundedContext\Contracts\Version\Factory as EventVersionFactory;
use BoundedContext\Contracts\Generator\DateTime;
use BoundedContext\Contracts\Generator\Identifier;
use BoundedContext\Schema\Schema;
use BoundedContext\Contracts\Schema\Schema as SchemaContract;
use BoundedContext\Map\Map;
use EventSourced\ValueObject\ValueObject\Integer;
use EventSourced\ValueObject\Serializer\Serializer;

class Factory implements \BoundedContext\Contracts\Event\Snapshot\Factory
{
    protected $identifier_generator;
    protected $datetime_generator;
    protected $event_version_factory;
    protected $event_map;
    protected $serializer;

    public function __construct(
        Identifier $identifier_generator,
        DateTime $datetime_generator,
        EventVersionFactory $event_version_factory,
        Map $event_map,
        Serializer $serializer
    )
    {
        $this->identifier_generator = $identifier_generator;
        $this->datetime_generator = $datetime_generator;
        $this->event_version_factory = $event_version_factory;
        $this->event_map = $event_map;
        $this->serializer = $serializer;
    }

    public function event(Event $event)
    {
        $serialized = $this->serializer->serialize($event);
        return new Snapshot(
            $this->identifier_generator->generate(),
            $this->event_version_factory->event($event),
            $this->datetime_generator->now(),
            $this->event_map->get_id($event),
            new Schema($serialized)
        );
    }
    
    public function command(Command $command)
    {
        $serialized = $this->serializer->serialize($command);
        return new Snapshot(
            $this->identifier_generator->generate(),
            $this->event_version_factory->command($command),
            $this->datetime_generator->now(),
            $this->event_map->get_id($command),
            new Schema($serialized)
        );
    }

    public function schema(SchemaContract $schema)
    {
        return new Snapshot(
            $this->identifier_generator->string($schema->id),
            new Integer($schema->version),
            $this->datetime_generator->string($schema->occurred_at),
            $this->identifier_generator->string($schema->type_id),
            new Schema($schema->event)
        );
    }
}
