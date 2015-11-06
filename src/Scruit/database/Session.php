<?php
namespace Scruit\database;
class Session
{
    private $config = null;
    /**
     * @var \mysqli
     */
    private $connection = null;
    private $ddl = null;

    public function __construct(/* overloads */)
    {
        if (func_num_args() === 0) {
            throw new \InvalidArgumentException('IAE');
        }
        $args = func_get_args();
        $config = array_shift($args);
        if (is_array($config)) {
            $this->config = $config;
        } elseif (is_string($config)) {
            $this->config = $this->resolveDataSource($config);
        } else {
            throw new \InvalidArgumentException('IAE');
        }
        if ($args) {
             $this->ddl = array_shift($args);
        }
    }

    public function executeMulti($sql) {
        if (!$this->connection) $this->open();
        $ret = $this->connection->multi_query($sql);
        if (!$ret) {
            $errMsg = "Can't Execute Query:" . $sql;
            $errMsg .= "\n MySQL Message:" . $this->connection->error;
            throw new \RuntimeException ($errMsg);
        }
        do {
            if ($this->connection->errno) {
                $errMsg = "Can't Execute Query:" . $sql;
                $errMsg .= "\n MySQL Message:" . $this->connection->error;
                throw new \RuntimeException ($errMsg);
            }
            if ($result = $this->connection->store_result()) {
                $result->free_result();
            }
            if (!$this->connection->more_results()) {
                break;
            }
        } while ($this->connection->next_result());
        return $ret;
    }

    public function execute($sql) {
        $this->open();
        $ret = $this->connection->query($sql);
        if (!$ret) {
            $errMsg = "Can't Execute Query:" . $sql;
            $errMsg .= "\n MySQL Message:" . $this->connection->error;
            throw new \RuntimeException ($errMsg);
        }
        return $ret;
    }

    public function resolveDataSource ($config)
    {

        if (!preg_match('#(.+?)://([^:/]+):?([^@]*)@((?:p:)?[^/:]+)(?::(\d+))?/(.+)#', $config, $matches))
            throw new \InvalidArgumentException('予期せぬDSNの書式です');
        $dsn = array();
        $dsn['type'] = $matches[1];
        $dsn['user'] = $matches[2];
        $dsn['pass'] = $matches[3];
        $dsn['host'] = $matches[4];
        $dsn['port'] = $matches[5];
        $dsn['db'] = $matches[6];
        return $dsn;
    }
    public function open()
    {
        if ($this->connection == null) {
            $this->connection = new \mysqli(
                $this->config['host'],
                $this->config['user'],
                $this->config['pass']
            );
            if ($this->connection->errno) {
                throw new \Exception('mysql-error:' . $this->connection->error);
            }
            $driver = new \mysqli_driver();
            $driver->report_mode = (MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $this->connection->select_db($this->config['db']);
            if (isset($this->ddl) && is_file($this->ddl)) {
                $this->connection->query('SET foreign_key_checks=0');
                foreach($this->getTables() as $table) {
                    $this->connection->query("DROP TABLE ${table}");
                }
                $this->executeMulti(file_get_contents($this->ddl));
                $this->connection->query('SET foreign_key_checks=1');
                $this->ddl = null;
            }
        }
    }

    public function getTables()
    {
        if (!isset($this->connection)) $this->open();
        $result = array();
        $rs = $this->connection->query('SHOW TABLES;');
        while ($row = $rs->fetch_assoc()) {
            $result[] = array_shift($row);
        }
        return $result;
    }

    public function getTableInfo($tableName)
    {
        if (!isset($this->connection)) $this->open();
        $ret = array();
        $rs = $this->connection->query("SHOW COLUMNS FROM $tableName");
        while ($row = $rs->fetch_assoc()) {
            $temp['column'] = $row['Field'];
            preg_match('/([^(]+)\(?(\d*),?(\d*)\)?/i', $row['Type'], $matches);
            $patterns = array('/varchar|char/i', '/text|mediumtext/i', '/int|integer|bigint/i', '/decimal|dec|numeric/i', '/float|double/i', '/datetime|timestamp/i', '/date/i', '/blob|mediumblob|lognblob/i', '/tinyint|bit|bool|boolean/i');
            $replacements = array('string', 'text', 'integer', 'decimal', 'float', 'datetime', 'dateZ', 'binary', 'boolean');
            $temp['type'] = preg_replace($patterns, $replacements, $matches[1]);
            $temp['type_org'] = $matches[1];
            $temp['type_name'] = $row['Type'];
            $temp['length'] = $matches[2];
            $temp['length_decimally'] = $matches[3];
            $temp['extra'] = $row['Extra'];
            $temp['nullable'] = $row['Null'] == 'YES';
            $temp['key'] = $row['Key'] == 'PRI';
            $temp['default'] = $row['Default'];
            $temp['index'] = $row['Key'] != '';
            $ret[$temp['column']] = $temp;
        }
        return $ret;
    }

    public function getIndexInfo($tableName)
    {
        $this->open();
        $ret = array();
        $rs = $this->connection->query("SHOW INDEX FROM $tableName");
        while ($row = $rs->fetch_assoc()) {
            $ret[$row['Key_name']][] = $row;
        }
        return $ret;
    }

    public function getForeignKeyInfo($tableName)
    {
        $this->open();
        $ret = array();
        $rs = $this->connection->query("SELECT * FROM  INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA = '{$this->config['db']}' AND TABLE_NAME='$tableName'");
        while ($row = $rs->fetch_assoc()) {
            if (!$row['REFERENCED_TABLE_NAME']) continue;
            $ret[$row['CONSTRAINT_NAME']][] = array(
                'name' => $row['CONSTRAINT_NAME'],
                'column' => $row['COLUMN_NAME'],
                'to_table' => $row['REFERENCED_TABLE_NAME'],
                'to_column' => $row['REFERENCED_COLUMN_NAME'],
            );
        }
        return $ret;
    }

    public function getScheme()
    {
        $this->open();
        $rs = $this->connection->query('SHOW TABLES');
        $tables = array();
        while ($table = $rs->fetch_array()) {
            $rs2 = $this->connection->query("SHOW COLUMNS FROM $table[0]");
            $scheme = new Table($table[0]);
            while ($column = $rs2->fetch_object()) {
                $scheme->addColumn(new Column(array(
                    'name' => $column->Field,
                    'dataType' => $column->Type,
                    'primary' => ($column->Key === "PRI"),
                    'autoIncrement' => ($column->Extra === "auto_increment"),
                    'notNull' => ($column->Null === 'NO'),
                    'default' => $column->Default,
                )));
            }
            $tables[] = $scheme;
        }
        return $tables;
    }

    public function escape($val)
    {
        return $this->connection->real_escape_string($val);
    }
}
