<?php


    namespace WpEloquent;

    use Illuminate\Support\Str;
    use wpdb;
    use WpEloquent\ExtendsWpdb\WpdbInterface;

    class QuerySanitizer
    {


        /**
         * @var wpdb
         */
        private $wpdb;

        /**
         * @var string
         */
        private $query;

        /**
         * @var array
         */
        private $bindings;


        public function __construct(WpdbInterface $wpdb, string $query, array $bindings)
        {

            $this->wpdb = $wpdb;
            $this->query = $query;
            $this->bindings = $bindings;

        }


        /**
         * @return string
         */
        public function sanitize()
        {

            $this->replaceQuestionMarks( $this->createWpDbPlaceholders() );
            $this->checkBindingsForWildcard();

            $safe_sql = $this->wpdb->prepare( $this->query, ...$this->bindings);

            return $safe_sql;

        }


        private function createWpDbPlaceholders() : array
        {

            return array_map(function ($binding) {

                if (is_float($binding)) {
                    return '%f';
                }

                if (is_int($binding)) {
                    return '%d';
                }

                return '%s';


            }, $this->bindings);

        }

        private function replaceQuestionMarks( array $wpdb_prepare_placeholders )
        {

            $this->query = Str::replaceArray('?', $wpdb_prepare_placeholders, $this->query);

        }

        private function checkBindingsForWildcard()
        {

            $this->bindings = array_map(function ($binding) {


                while ($this->containsLikeEscapingIndicator($binding)) {


                    $before = Str::before($binding, '{');
                    $after = Str::after($binding, '}');
                    $unsafe = $this->betweenFirstOccurrence($binding, '{', '}');

                    $binding = $before. $this->escLike($unsafe) . $after;


                }


                return $binding;


            }, $this->bindings);

        }

        private function containsLikeEscapingIndicator(string $binding) : bool
        {

            return Str::containsAll($binding, ['{', '}']);


        }

        private function betweenFirstOccurrence ($binding, $before , $after ) : string
        {

            return Str::before(Str::after($binding, $before), $after);

        }

        private function escLike ($binding) : string
        {

            $test =  addcslashes( $binding, '_%\\' );

           return $test;

        }

    }