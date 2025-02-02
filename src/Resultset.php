<?php

    namespace b2db;

    use b2db\interfaces\QueryInterface;

    /**
     * Resultset class
     *
     * @author Daniel Andre Eikeland <zegenie@zegeniestudios.net>
     * @version 2.0
     * @license http://www.opensource.org/licenses/mozilla1.1.php Mozilla Public License 1.1 (MPL 1.1)
     * @package b2db
     * @subpackage core
     */

    /**
     * Resultset class
     *
     * @package b2db
     * @subpackage core
     */
    class Resultset implements \Countable
    {

        /**
         * @var Row[]
         */
        protected $rows = [];

        /**
         * @var QueryInterface
         */
        protected $query;

        protected $int_ptr;

        protected $max_ptr;

        protected $insert_id;

        protected $id_col;

        public function __construct(Statement $statement)
        {
            try {
                $this->query = $statement->getQuery();

                if ($this->query instanceof QueryInterface) {
                    if ($this->query->isInsert()) {
                        $this->insert_id = $statement->getInsertID();
                    } elseif ($this->query->isSelect()) {
                        while ($row = $statement->fetch()) {
                            $this->rows[] = new Row($row, $statement);
                        }
                        $this->max_ptr = count($this->rows);
                        $this->int_ptr = 0;
                    } elseif ($this->query->isCount()) {
                        $value = $statement->fetch();
                        $this->max_ptr = $value['num_col'] ?? 0;
                    }
                }
            } catch (\Exception $e) {
                throw $e;
            }
        }

        /**
         * @return bool
         */
        protected function _next()
        {
            if ($this->int_ptr == $this->max_ptr) {
                return false;
            } else {
                $this->int_ptr++;
                return true;
            }
        }

        /**
         * @return int
         */
        public function getCount()
        {
            return $this->max_ptr;
        }

        /**
         * Returns the current row
         *
         * @return Row
         */
        public function getCurrentRow()
        {
            if (isset($this->rows[($this->int_ptr - 1)])) {
                return $this->rows[($this->int_ptr - 1)];
            }
            return null;
        }

        /**
         * Advances through the resultset and returns the current row
         * Returns false when there are no more rows
         *
         * @return Row|false
         */
        public function getNextRow()
        {
            if ($this->_next()) {
                $row = $this->getCurrentRow();
                if ($row instanceof Row) {
                    return $row;
                }
                throw new \Exception('This should never happen. Please file a bug report');
            } else {
                return false;
            }
        }

        public function get($column, $foreign_key = null)
        {
            $row = $this->getCurrentRow();
            if ($row instanceof Row) {
                return $row->get($column, $foreign_key);
            } else {
                throw new \Exception("Cannot return value of {$column} on a row that doesn't exist");
            }
        }

        /**
         * @return Row[]
         */
        public function getAllRows()
        {
            return $this->rows;
        }

        public function getSQL()
        {
            return ($this->query instanceof Criteria) ? $this->query->getSQL() : '';
        }

        public function printSQL()
        {
            $str = '';
            if ($this->query instanceof Criteria) {
                $str .= $this->query->getSQL();
                foreach ($this->query->getValues() as $val) {
                    if (!is_int($val)) {
                        $val = '\'' . $val . '\'';
                    }
                    $str = substr_replace($str, $val, mb_strpos($str, '?'), 1);
                }
            }
            return $str;
        }

        public function getInsertID()
        {
            return $this->insert_id;
        }

        public function rewind()
        {
            $this->int_ptr = 0;
        }

        /**
         * @return Row
         */
        public function current()
        {
            $row = $this->getCurrentRow();

            return $row;
        }

        public function key()
        {
            if ($this->id_col === null)
                $this->id_col = $this->query->getTable()->getIdColumn();

            $row = $this->getCurrentRow();

            return ($row instanceof Row) ? $row->get($this->id_col) : null;
        }

        public function next()
        {
            $this->_next();
        }

        public function valid()
        {
            $val = (boolean) $this->int_ptr < $this->max_ptr;
            return $val;
        }

        public function count(): int
        {
            return (integer) $this->max_ptr;
        }

        /**
         * @return Query
         */
        public function getQuery()
        {
            return $this->query;
        }

    }
