<?php


    namespace Tests\integration;

    use Codeception\TestCase\WPTestCase;
    use Illuminate\Database\Schema\Blueprint;
    use PHPUnit\Framework\TestCase;
    use WpEloquent\MySqlSchemaBuilder;
    use WpEloquent\QuerySanitizer;
    use WpEloquent\SanitizerFactory;
    use WpEloquent\WpConnection;

    class EloquentIntegrationTest extends WPTestCase
    {


        /**
         * @test
         * @doesNotPerformAssertions
         */
        public function test()
        {


            global $wpdb;

            $connection = new WpConnection($wpdb, new SanitizerFactory($wpdb));

            // $connection->table('cities')
            //         ->join('citizens', 'cities.id', '=', 'citizens.city_id')
            //         ->select('citizens.last_name', 'cities.*', 'citizens.first_name')
            //         ->where('citizens.first_name', 'calvin')->get();

            // $unsafe_user_input = 'Madrid';
            //
            // $connection->table('cities')
            //     ->join('citizens', function ($join) use ($unsafe_user_input) {
            //         $join->on('cities.id', '=', 'citizens.city_id')
            //              ->where('cities.name', '!=', $unsafe_user_input);
            //     })
            //     ->get();

            $connection->table('cities')->
                where('amount', '<', function ($query) {
                    $query->selectRaw('avg(i.amount)')->from('incomes as i');
                })->get();


        }


    }

