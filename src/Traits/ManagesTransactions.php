<?php


    namespace WpEloquent\Traits;

    use Closure;
    use Illuminate\Database\Query\Grammars\MySqlGrammar;
    use Throwable;
    use WpEloquent\ExtendsWpdb\WpdbInterface;

    /**
     * @property WpdbInterface $wpdb
     * @property MySqlGrammar $query_grammar
     */
    trait ManagesTransactions
    {



        /**
         * Execute a Closure within a transaction.
         *
         * @param  \Closure  $callback
         * @param  int  $attempts
         *
         * @return mixed
         * @throws Throwable
         */
        public function transaction(Closure $callback, $attempts = 1)
        {

            $this->beginTransaction();

            for ($currentAttempt = 1; $currentAttempt <= $attempts; $currentAttempt++) {


                // We'll simply execute the given callback within a try / catch block and if we
                // catch any exception we can rollback this transaction so that none of this
                // gets actually persisted to a database or stored in a permanent fashion.
                try {

                    $callbackResult = $callback($this);

                }

                    // If we catch an exception we'll rollback this transaction and try again if we
                    // are not out of attempts. If we are out of attempts we will just throw the
                    // exception back out and let the developer handle an uncaught exceptions
                catch (Throwable $e) {

                    $this->handleTransactionException($e, $currentAttempt, $attempts);

                    continue;

                }

                try {

                    $this->commit();

                }

                catch (Throwable $e) {

                    $this->handleCommitException($e, $currentAttempt, $attempts);

                    continue;
                }

                return $callbackResult;
            }
        }

        /**
         * Start a new database transaction.
         *
         * @return void
         * @throws Throwable
         */
        public function beginTransaction()
        {

            if ( $this->transaction_count === 0  ) {

                try {

                    $this->wpdb->startTransaction();

                }

                catch (Throwable $e) {

                    $this->handleBeginTransactionException($e);

                }

            }

            $this->wpdb->createSavepoint(

                $this->query_grammar->compileSavepoint('trans'.($this->transaction_count + 1))

            );

            $this->transaction_count++;

        }

        /**
         * Commit the active database transaction.
         *
         * @return void
         * @throws Throwable
         */
        public function commit()
        {

            $this->wpdb->commitTransaction();

            // If successfully reset the transaction count.
            $this->transaction_count = 0;

        }

        /**
         * Rollback the active database transaction.
         *
         * @param  bool  $respect_savepoint
         * @param  null  $to_level
         *
         * @return void
         * @throws Throwable
         */
        public function rollBack( $to_level = null )
        {


            $to_level = $to_level ?? $this->transaction_count;

            if (  $to_level < 0 || $to_level > $this->transaction_count) {

                return;

            }

            try {

                if ( $to_level === 0  ) {

                    $this->wpdb->rollbackTransaction();

                }

                if ( $to_level > 0 ) {


                    $this->wpdb->rollbackTransaction(
                        $this->query_grammar->compileSavepointRollBack('trans'.($to_level))
                    );


                }


            }
            catch (Throwable $e) {

                $this->handleRollBackException($e);
            }

            $this->decreaseTransactionCount($to_level -1 ?? null );



        }











        private function decreaseTransactionCount ($to_level = null ) {

            $this->transaction_count--;

            if ( $to_level ) {

                $this->transaction_count = $to_level;

            }

            if ( $this->transaction_count < 0  ) {

                $this->transaction_count = 0;

            }



        }

        /**
         * Get the number of active transaction_count.
         *
         * @return int
         */
        public function transactionLevel() : int
        {

            return $this->transaction_count;
        }


        /**
         * Handle an exception from a transaction beginning.
         *
         * @param  Throwable  $e
         *
         * @return void
         * @throws Throwable
         */
        private function handleBeginTransactionException(Throwable $e)
        {

            // If the caused by lost connection, reconnect again and redo transaction
            // wpdb automatically tries to reconnect if we lost the connection.
            if ($this->wpdb->check_connection(false)) {


                $this->wpdb->startTransaction();

                return;

            }

            // If we can reconnect with wpdb or if we cant start the transaction a second time,
            // throw out the exception
            throw $e;

        }


        /**
         * Handle an exception encountered when running a transacted statement.
         *
         * @param  Throwable  $e
         * @param  int  $currentAttempt
         * @param  int  $maxAttempts
         *
         * @return void
         * @throws Throwable
         */
        private function handleTransactionException( Throwable $e, $currentAttempt, $maxAttempts)
        {

            // On a deadlock, MySQL rolls back the entire transaction so we can't just
            // retry the query. We have to throw this exception all the way out and
            // let the developer handle it in another way. We will decrement too.
            if ($this->isConcurrencyError($e) && $this->transaction_count > 1) {

                $this->transaction_count--;

                throw $e;

            }

            // If there was an exception we will rollback this transaction and then we
            // can check if we have exceeded the maximum attempt count for this and
            // if we haven't we will return and try this query again in our loop.
            $this->rollBack();

            if ($this->isConcurrencyError($e) && $currentAttempt < $maxAttempts) {

                return;

            }


        }


        /**
         * Handle an exception encountered when committing a transaction.
         *
         * @param  Throwable  $e
         * @param  int  $currentAttempt
         * @param  int  $maxAttempts
         *
         * @return void
         * @throws Throwable
         */
        private function handleCommitException(Throwable $e, $currentAttempt, $maxAttempts)
        {

            $this->transaction_count = max(0, $this->transaction_count - 1);

            if ($this->isConcurrencyError($e) && $currentAttempt < $maxAttempts) {
                return;
            }

            if ($this->wpdb->check_connection()) {

                $this->transaction_count = 0;

                return;

            }

            throw $e;
        }


        /**
         * Handle an exception from a rollback.
         *
         * @param  Throwable  $e
         *
         * @return void
         * @throws Throwable
         */
        private function handleRollBackException(Throwable $e)
        {

            if ($this->wpdb->check_connection()) {

                $this->transaction_count = 0;

            }

            throw $e;

        }


    }