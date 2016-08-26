<?php namespace BoundedContext\Laravel\Player\Snapshot;

use BoundedContext\Contracts\Player\Snapshot\Snapshot;
use EventSourced\ValueObject\Contracts\ValueObject\Identifier;
use BoundedContext\Player\Snapshot\Factory;
use EventSourced\ValueObject\Serializer\Serializer;
use DB;

class Repository implements \BoundedContext\Contracts\Player\Snapshot\Repository
{
    private $connection;
    private $table = 'snapshots_player';
    private $serializer;
    private $snapshot_factory;

    public function __construct(
        Factory $snapshot_factory,
        Serializer $serializer
    )
    {
        $this->connection = DB::connection();
        $this->snapshot_factory = $snapshot_factory;
        $this->serializer = $serializer;
    }

    protected function query()
    {
        return $this->connection->table($this->table);
    }

    public function get(Identifier $id)
    {
        $row = $this->query()
            ->sharedLock()
            ->where('id', $id->value())
            ->first();

        if (!$row) {
            throw new \Exception("The Player Snapshot [".$id->value()."] does not exist.");
        }

        $row_array = (array) $row;
        return $this->snapshot_factory->make($row_array);
    }

    public function save(Snapshot $snapshot)
    {
        $this->query()
            ->where('id', $snapshot->id()->value())
            ->update(
                $this->serializer->serialize($snapshot)
            );
    }
}
