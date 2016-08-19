<?php namespace BoundedContext\Laravel\Illuminate\Projection;

use Illuminate\Database\Query\Builder;
use DB;

abstract class AbstractQueryable
{
    protected $connection;
    protected $table = 'projection_table';

    public function __construct()
    {
        $this->connection = DB::connection();
    }

    /**
     * @return Builder
     */
    protected function query()
    {
        return $this->connection->table($this->table);
    }
}
