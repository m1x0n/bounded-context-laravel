<?php

namespace BoundedContext\Laravel\Providers;

use BoundedContext\Map\Map;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class BoundedContextProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/commands.php' => config_path('commands.php'),
        ]);
        
        $this->publishes([
            __DIR__.'/../../config/events.php' => config_path('events.php'),
        ]);
        
        $this->publishes([
            __DIR__.'/../../config/logs.php' => config_path('logs.php'),
        ]);

        $this->publishes([
            __DIR__.'/../../config/projections.php' => config_path('projections.php'),
        ]);

        $this->publishes([
            __DIR__.'/../../config/players.php' => config_path('players.php'),
        ]);

        $this->publishes([
            __DIR__.'/../../migrations/' => database_path('/migrations')
        ], 'migrations');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Loggable Class IDs to Class
        $this->app->singleton(\BoundedContext\Map\Map::class, function($app) {
            $commands = (!Config::get('commands')) ? [] : Config::get('commands');
            $events = (!Config::get('events')) ? [] : Config::get('events');
            
            $player_environments = Config::get('players');
            
            $players_array = [];
            foreach($player_environments as $player_environment) {
                foreach($player_environment as $player_types) {
                    foreach($player_types as $id => $player) {
                        $players_array[$id] = $player;
                    }
                }
            }

            $map = array_merge($commands, $events, $players_array);

            return new Map(
                $map,
                $this->app->make('BoundedContext\Contracts\Generator\Identifier')
            );
        });

        $this->app->bind(
            \BoundedContext\Contracts\Event\Snapshot\Factory::class,
                \BoundedContext\Laravel\Event\Snapshot\Factory::class
        );

        $this->app->bind(
            \BoundedContext\Contracts\Event\Snapshot\Upgrader::class,
                \BoundedContext\Laravel\Event\Snapshot\Upgrader::class
        );

        $this->app->bind(
            \BoundedContext\Contracts\Event\Factory::class,
                \BoundedContext\Laravel\Event\Factory::class
        );

        $this->app->bind(
            \BoundedContext\Contracts\Version\Factory::class,
                \BoundedContext\Laravel\Version\Factory::class
        );

        /**
         * Logs
         */
        $this->app->bind(
            \BoundedContext\Contracts\Sourced\Stream\Factory::class,
                \BoundedContext\Laravel\Illuminate\Stream\Factory::class
        );

        $this->app->bind(
            \BoundedContext\Contracts\Sourced\Stream\Builder::class,
                \BoundedContext\Sourced\Stream\Builder::class
        );

        $this->app->singleton(
            \BoundedContext\Contracts\Sourced\Log\Event::class, 
                \BoundedContext\Laravel\Illuminate\Log\Event::class
        );

        $this->app->singleton(
            \BoundedContext\Contracts\Sourced\Log\Command::class, 
                \BoundedContext\Laravel\Illuminate\Log\Command::class
        );

        /**
         * Aggregates
         */
        $this->app->bind(
            \BoundedContext\Contracts\Sourced\Aggregate\State\Snapshot\Factory::class,
                \BoundedContext\Sourced\Aggregate\State\Snapshot\Factory::class
        );

        $this->app->bind(
            \BoundedContext\Contracts\Sourced\Aggregate\State\Snapshot\Repository::class,
                \BoundedContext\Laravel\Sourced\Aggregate\State\Snapshot\Repository::class
        );

        $this->app->bind(
            \BoundedContext\Contracts\Sourced\Aggregate\State\Factory::class,
                \BoundedContext\Laravel\Sourced\Aggregate\State\Factory::class
        );

        $this->app->bind(
            \BoundedContext\Contracts\Sourced\Aggregate\Factory::class,
                \BoundedContext\Laravel\Sourced\Aggregate\Factory::class
        );

        $this->app->bind(
            \BoundedContext\Contracts\Sourced\Aggregate\Repository::class,
                \BoundedContext\Sourced\Aggregate\Repository::class
        );

        $this->app->bind(
            \BoundedContext\Contracts\Sourced\Aggregate\Stream\Builder::class,
                \BoundedContext\Sourced\Aggregate\Stream\Builder::class
        );

        $this->app->bind(
            \BoundedContext\Contracts\Sourced\Aggregate\Stream\Factory::class,
                \BoundedContext\Laravel\Sourced\Aggregate\Stream\Factory::class
        );

        $this->app->bind(
            \BoundedContext\Contracts\Business\Invariant\Factory::class,
                \BoundedContext\Laravel\Business\Invariant\Factory::class
        );

        /**
         * Players
         */
        $this->app->bind(
            \BoundedContext\Contracts\Player\Snapshot\Repository::class,
            \BoundedContext\Laravel\Player\Snapshot\Repository::class
        );

        $projection_types = Config::get('projections');

        if (is_null($projection_types)) {
            return;
        }
        
        foreach ($projection_types as $projection_type) {
            foreach ($projection_type as $projection => $implemented_projection) {
                $queryable =
                    '\\' .
                    chop($projection, 'Projection') .
                    "Queryable";

                $implemented_queryable =
                    chop($implemented_projection, 'Projection') .
                    "Queryable";
                
                $this->app
                    ->when($implemented_projection)
                    ->needs(\BoundedContext\Contracts\Projection\Queryable::class)
                    ->give($implemented_queryable);

                $this->app->singleton($projection, $implemented_projection);
                $this->app->singleton($queryable, $implemented_queryable);
            }
        }

        $this->app->bind(
            \BoundedContext\Contracts\Player\Factory::class,
                \BoundedContext\Laravel\Player\Factory::class
        );

        $this->app->bind(
            \BoundedContext\Contracts\Player\Repository::class,
                \BoundedContext\Player\Repository::class
        );

        /**
         * General
         */

        $this->app->bind(
            \BoundedContext\Contracts\Bus\Dispatcher::class,
                \BoundedContext\Laravel\Bus\Dispatcher::class
        );

        $this->app->bind(
            \BoundedContext\Contracts\Generator\Identifier::class,
                \BoundedContext\Laravel\Generator\Uuid::class
        );

        $this->app->bind(
            \EventSourced\ValueObject\Contracts\ValueObject\Identifier::class,
                \EventSourced\ValueObject\ValueObject\Uuid::class
        );

        $this->app->bind(
            \BoundedContext\Contracts\Generator\DateTime::class,
                \BoundedContext\Laravel\Generator\DateTime::class
        );

        $this->app->bind(
            \EventSourced\ValueObject\Contracts\ValueObject\DateTime::class,
                \EventSourced\ValueObject\ValueObject\DateTime::class
        );

        $this->app->bind(
            \BoundedContext\Contracts\Projection\Factory::class,
                \BoundedContext\Laravel\Illuminate\Projection\Factory::class
        );
        
        $this->app->bind(
            \BoundedContext\Contracts\Sourced\Aggregate\TypeId\Factory::class,
                \BoundedContext\Sourced\Aggregate\TypeId\Factory::class
        );
        
        $this->app->bind(
            \EventSourced\ValueObject\Serializer\Reflector::class,
                \EventSourced\ValueObject\Reflector\Reflector::class
        );
        
        $this->app->bind(
            \EventSourced\ValueObject\Deserializer\Reflector::class,
                \EventSourced\ValueObject\Reflector\Reflector::class
        );
        
        $this->app->bind(
            \EventSourced\ValueObject\Contracts\Serializer::class,
                \EventSourced\ValueObject\Serializer\Serializer::class
        );
        
        $this->app->bind(
            \EventSourced\ValueObject\Contracts\Deserializer::class,
                \EventSourced\ValueObject\Deserializer\Deserializer::class
        );
    }
}
