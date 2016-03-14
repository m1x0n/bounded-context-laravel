<?php namespace BoundedContext\Laravel\Illuminate\Log;

use BoundedContext\Contracts\Event\Snapshot\Factory;
use Illuminate\Database\DatabaseManager;
use BoundedContext\Laravel\ValueObject\Uuid;
use BoundedContext\Laravel\Generator\Uuid as IdGenerator;

class Event implements \BoundedContext\Contracts\Sourced\Log\Event
{
    private $connection;
    private $snapshot_factory;
    private $table;
    private $id_generator;
    
    public function __construct(IdGenerator $id_generator, Factory $snapshot_factory, DatabaseManager $db_manager)
    {
        $this->snapshot_factory = $snapshot_factory;
        $this->connection = $db_manager->connection();
        $this->id_generator = $id_generator;
        $this->table = "event_log";
    }
    
    public function append(\BoundedContext\Contracts\Event\Event $event)
    {
        $snapshot = $this->snapshot_factory->loggable($event);
        $this->connection->table($this->table)->insert([
            'id' => $this->uuid_to_binary($this->id_generator->generate()),
            'aggregate_id' => $this->uuid_to_binary($event->id()),
            'aggregate_type_id' => $this->uuid_to_binary($event->aggregate_type_id()),
            'snapshot' => json_encode($snapshot->serialize())
        ]);
    }
    
    private function uuid_to_binary(Uuid $uuid)
    {
        $hex = str_replace("-", "", $uuid->value());
        return hex2bin($hex);
    }

    public function append_collection(\BoundedContext\Contracts\Collection\Collection $events)
    {
        foreach ($events as $event) {
            $this->append($event);
        }
    }

    /** Remove, never used as far as I can see */
    public function builder()
    {
        
    }

    public function reset()
    {
        $this->connection
            ->table($this->table)
            ->delete();
    }
}
