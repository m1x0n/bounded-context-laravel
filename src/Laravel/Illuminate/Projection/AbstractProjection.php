<?php namespace BoundedContext\Laravel\Illuminate\Projection;

use Illuminate\Contracts\Foundation\Application;

abstract class AbstractProjection
{
    protected $application;
    protected $connection;
    protected $table = 'projection_table';

    public function __construct(Application $app)
    {
        $this->application = $app;
        $this->connection = $app->make('db');
    }

    public function reset()
    {
        $this->query()->delete();
    }

    protected function query()
    {
        return $this->connection->table($this->table);
    }
}
