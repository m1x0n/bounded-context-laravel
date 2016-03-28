<?php namespace BoundedContext\Laravel\Player\Snapshot;

use BoundedContext\Contracts\Player\Snapshot\Snapshot;
use EventSourced\ValueObject\Contracts\ValueObject\Identifier;
use BoundedContext\Player\Snapshot\Factory;
use Illuminate\Contracts\Foundation\Application;
use EventSourced\ValueObject\Serializer\Serializer;

class Repository implements \BoundedContext\Contracts\Player\Snapshot\Repository
{
    private $app;
    private $connection;
    private $table = 'snapshots_player';
    private $serializer;
    private $snapshot_factory;

    public function __construct(
        Application $app,
        Factory $snapshot_factory,
        Serializer $serializer
    )
    {
        $this->app = $app;
        $this->connection = $app->make('db');
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
