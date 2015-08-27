<?php

namespace BoundedContext\Laravel\Illuminate;

use BoundedContext\Laravel\Item\Upgrader;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;

use BoundedContext\Collection\Collectable;
use BoundedContext\Stream\Stream;
use BoundedContext\ValueObject\Uuid;
use BoundedContext\Collection\Collection;

class Log implements \BoundedContext\Contracts\Log
{
    private $connection;
    private $upgrader;
    private $table;

    public function __construct(
        Upgrader $upgrader,
        DatabaseManager $manager,
        $table = 'event_log'
    )
    {
        $this->upgrader = $upgrader;
        $this->connection = $manager->connection();
        $this->table = $table;
    }

    public function query()
    {
        return $this->connection->table($this->table);
    }

    public function reset()
    {
        $this->connection->table($this->table)
            ->delete();
    }

    public function get_stream(Uuid $id)
    {
        $stream = new Stream($this);
        $stream->move_to($id);

        return $stream;
    }

    private function get_starting_id(Uuid $id)
    {
        if($id->is_null())
        {
            return 0;
        }

        $query = $this->connection->table($this->table)
            ->where('item_id', '=', $id->serialize())
            ->first();

        if(!$query)
        {
            throw new \Exception("The uuid [".$id->serialize()."] does not exist in log.");
        }

        return $query->id;
    }

    private function get_serialized_items(Uuid $id, $limit)
    {
        $starting_id = $this->get_starting_id($id);

        $item_records = $this->connection->table($this->table)
            ->where('id', '>', $starting_id)
            ->limit($limit)
            ->get();

        $items = [];

        foreach($item_records as $item_record)
        {
            $items[] = json_decode($item_record->item, true);
        }

        return $items;
    }

    public function get_collection(Uuid $id, $limit = 1000)
    {
        $serialized_items = $this->get_serialized_items($id, $limit);

        $items = new Collection();

        foreach($serialized_items as $serialized_item)
        {
            $items->append(
                $this->upgrader->deserialize($serialized_item)
            );
        }

        return $items;
    }

    public function append(Collectable $event)
    {
        $item = $this->upgrader->generate($event);

        $this->$this->connection->table($this->table)->insert(array(
            'item_id' => $item->id()->serialize(),
            'item' => json_encode($item->serialize())
        ));
    }

    public function append_collection(Collection $events)
    {
        $items = [];

        foreach($events as $event)
        {
            $item = $this->upgrader->generate($event);

            $items[] = [
                'item_id' => $item->id()->serialize(),
                'item' => json_encode($item->serialize())
            ];
        }

        $this->connection->table($this->table)
            ->insert($items);
    }
}