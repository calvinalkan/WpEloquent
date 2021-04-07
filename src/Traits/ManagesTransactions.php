<?php


    namespace WpEloquent;

    use Closure;
    use Throwable;

    /**
     * @property \wpdb $wpdb
     */
    trait ManagesTransactions
    {

        /*
        |
        |
        |--------------------------------------------------------------------------
        | Database Transactions
        |--------------------------------------------------------------------------
        |
        | Allows to wrap any query in a database transaction and automatically rollback
        | on errors.
        |
        |
        |
        |
        */

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

            for ($currentAttempt = 1; $currentAttempt <= $attempts; $currentAttempt++) {

                $this->beginTransaction();

                // We'll simply execute the given callback within a try / catch block and if we
                // catch any exception we can rollback this transaction so that none of this
                // gets actually persisted to a database or stored in a permanent fashion.
                try {

                    $callbackResult = $callback($this);

                }

                    // If we catch an exception we'll rollback this transaction and try again if we
                    // are not out of attempts. If we are out of attempts we will just throw the
                    // exception back out and let the developer handle an uncaught exceptions.
                catch (Throwable $e) {

                    $this->handleTransactionException($e, $currentAttempt, $attempts);

                    continue;

                }

                try {

                    $this->commit();

                }

                catch (Throwable $e) {

                    $this->handleCommitTransactionException($e, $currentAttempt, $attempts );

                    continue;
                }

                return $callbackResult;
            }
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
            if ($this->wpdb->check_connection()) {


                $this->wpdbTransaction();


            }

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
        private function handleTransactionException(Throwable $e, $currentAttempt, $maxAttempts)
        {

            // On a deadlock, MySQL rolls back the entire transaction so we can't just
            // retry the query. We have to throw this exception all the way out and
            // let the developer handle it in another way. We will decrement too.
            if ($this->causedByConcurrencyError($e) && $this->transaction_count > 1) {

                $this->transaction_count--;

                throw $e;

            }

            // If there was an exception we will rollback this transaction and then we
            // can check if we have exceeded the maximum attempt count for this and
            // if we haven't we will return and try this query again in our loop.
            $this->rollBack();

            if ($this->causedByConcurrencyError($e) && $currentAttempt < $maxAttempts) {
                return;
            }

            throw $e;

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
        private function handleCommitTransactionException(
            Throwable $e,
            $currentAttempt,
            $maxAttempts
        ) {

            $this->transaction_count = max(0, $this->transaction_count - 1);

            if ($this->causedByConcurrencyError($e) && $currentAttempt < $maxAttempts) {
                return;
            }

            if ($this->wpdb->check_connection()) {

                $this->transaction_count = 0;

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
        protected function handleRollBackException(Throwable $e)
        {

            if ($this->wpdb->check_connection()) {

                $this->transaction_count = 0;


            }

            throw $e;
        }




        /**
         * Start a new database transaction.
         *
         * @return void
         * @throws Throwable
         */
        public function beginTransaction()
        {

            if ($this->transaction_count == 0) {

                try {

                    $this->wpdbTransaction();

                }
                catch (Throwable $e) {

                    $this->handleBeginTransactionException($e);

                }
            }

            elseif ($this->transaction_count >= 1 && $this->query_grammar->supportsSavepoints()) {

                $this->wpdbSavepoint();

            }

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

            if ($this->transaction_count == 1) {

                $this->wpdbCommit();

            }

            $this->transaction_count = max(0, $this->transaction_count - 1);


        }



        /**
         * Rollback the active database transaction.
         *
         * @param  int|null  $toLevel
         *
         * @return void
         * @throws Throwable
         */
        public function rollBack($to_level = null)
        {

            // We allow developers to rollback to a certain transaction level. We will verify
            // that this given transaction level is valid before attempting to rollback to
            // that level. If it's not we will just return out and not attempt anything.
            $to_level = is_null($to_level) ? $this->transaction_count - 1 : $to_level;

            if ($to_level < 0 || $to_level >= $this->transaction_count) {
                return;
            }

            // Next, we will actually perform this rollback within this database and fire the
            // rollback event. We will also set the current transaction level to the given
            // level that was passed into this method so it will be right from here out.
            try {

                $this->wpdbRollback($to_level);

            }
            catch (Throwable $e) {

                $this->handleRollBackException($e);
            }

            $this->transaction_count = $to_level;


        }


        /**
         * Get the number of active transaction_count.
         *
         * @return int
         */
        public function transactionLevel()
        {

            return $this->transaction_count;
        }

        /**
         * Execute the callback after a transaction commits.
         *
         * @param  callable  $callback
         *
         * @return void
         */
        public function afterCommit($callback)
        {

            if ($this->transaction_countManager) {
                return $this->transaction_countManager->addCallback($callback);
            }

            throw new RuntimeException('Transactions Manager has not been set.');
        }


        /**
         * Start a transaction as wpdb query
         */
        private function wpdbTransaction()
        {

            $this->wpdb->query("SET autocommit = 0; START TRANSACTION;");

        }

        /**
         * Create a save point as wpdb query
         *
         * @return void
         * @throws Throwable
         */
        private function wpdbSavepoint()
        {

            $this->wpdb->query(

                $this->query_grammar->compileSavepoint('trans'.($this->transaction_count + 1))

            );


        }

        /**
         * Create a commit as wpdb query
         */
        private function wpdbCommit() {

            $this->wpdb->query("COMMIT;");

        }

        /**
         *
         * Rollback as wpdb query
         *
         */
        private function wpdbRollback($to_level) {

            if ($to_level == 0) {

                $this->wpdb->query("ROLLBACK");

            }
            elseif ($this->query_grammar->supportsSavepoints()) {

                $this->wpdb->query(
                    $this->query_grammar->compileSavepointRollBack('trans'.($to_level + 1))
                );
            }

        }

    }