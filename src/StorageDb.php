<?php
/**
 * @link https://github.com/yii2tech
 * @copyright Copyright (c) 2015 Yii2tech
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace p4it\config;

use yii\db\Connection;
use yii\db\Query;
use yii\di\Instance;

/**
 * StorageDb represents the configuration storage based on database table.
 * Example migration for such table:
 *
 * ```php
 * $tableName = 'AppConfig';
 * $columns = [
 *     'id' => 'string',
 *     'value' => 'text',
 *     'PRIMARY KEY(id)',
 * ];
 * $this->createTable($tableName, $columns);
 * ```
 *
 * You may use same table for multiple configuration storage providing [[filter]] value.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 1.0
 */
class StorageDb extends Storage
{
    use StorageFilterTrait;

    /**
     * @var Connection|array|string the DB connection object or the application component ID of the DB connection.
     * After the StorageDb object is created, if you want to change this property, you should only assign it
     * with a DB connection object.
     */
    public $db = 'db';
    /**
     * @var string name of the table, which should store values.
     */
    public $table = 'AppConfig';
    /**
     * @var string name of the column, which should store config item ID.
     * @since 1.0.7
     */
    public $idColumn = 'id';
    /**
     * @var string name of the column, which should store config item value.
     * @since 1.0.7
     */
    public $valueColumn = 'value';


    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        $this->db = Instance::ensure($this->db, Connection::className());
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $values)
    {
        $this->clear();

        $data = [];
        $filter = $this->composeFilterCondition();
        $columns = array_merge(array_keys($filter), [$this->idColumn, $this->valueColumn]);
        $filterValues = array_values($filter);

        foreach ($values as $id => $value) {
            $data[] = array_merge($filterValues, [$id, $value]);
        }

        $insertedRowsCount = $this->db->createCommand()
            ->batchInsert($this->table, $columns, $data)
            ->execute();

        return (count($values) === $insertedRowsCount);
    }

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        $query = new Query();
        $rows = $query->from($this->table)
            ->andWhere($this->composeFilterCondition())
            ->all();

        $values = [];
        foreach ($rows as $row) {
            $values[$row[$this->idColumn]] = $row[$this->valueColumn];
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->db->createCommand()
            ->delete($this->table, $this->composeFilterCondition())
            ->execute();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function clearValue($id)
    {
        $this->db->createCommand()
            ->delete($this->table, $this->composeFilterCondition([$this->idColumn => $id]))
            ->execute();

        return true;
    }
}