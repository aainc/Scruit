<?php
namespace Scruit\database;
/**
 * 二つのCreateTable文からデータベースの差分を解析する
 * Class DatabaseDiff
 * @package Scruit\database
 */
class Diff
{
    /**
     * @var Analyzer
     */
    private $newDatabase = null;

    /**
     * @var Analyzer
     */
    private $oldDatabase = null;

    private $newTables = null;
    private $oldTables = null;
    private $newSchemes = null;
    private $oldSchemes = null;
    private $newIndex     = array ();
    private $oldIndex     = array ();
    private $newConstraint  = array ();
    private $oldConstraint = array ();
    private $createTables = array();
    private $droppedTables = array();
    private $schemeDiffs = array();
    private $indexDiffs   = array ();
    private $constraintDiffs   = array ();


    public function __construct($new, $old)
    {
        $this->newDatabase = $new;
        $this->oldDatabase = $old;
        $this->fillTableDiff();
    }

    public function getNewTables()
    {
        return $this->createTables;
    }

    public function getDroppedTables()
    {
        return $this->droppedTables;
    }

    public function getSchemeDiffs()
    {
        return $this->schemeDiffs;
    }

    public function fillTableDiff()
    {
        $this->newTables = $this->newDatabase->getTables();
        $this->oldTables = $this->oldDatabase->getTables();
        foreach ($this->newTables as $tableName) {
            $this->newSchemes[$tableName] = $this->newDatabase->getTableInfo($tableName);
            $this->newIndex[$tableName]   = $this->newDatabase->getIndexInfo ( $tableName );
            $this->newConstraint[$tableName]   = $this->newDatabase->getForeignKeyInfo( $tableName );
            if (!in_array($tableName, $this->oldTables)) {
                $this->createTables[] = $tableName;
            } else {
                $this->oldSchemes[$tableName]      = $this->oldDatabase->getTableInfo($tableName);
                $this->oldIndex[$tableName]        = $this->oldDatabase->getIndexInfo($tableName);
                $this->oldConstraint[$tableName]   = $this->oldDatabase->getForeignKeyInfo( $tableName );
                $this->schemeDiffs[$tableName]     = $this->getSchemeDiff($tableName);
                $this->indexDiffs[$tableName]      = $this->getIndexDiff($tableName);
                $this->constraintDiffs[$tableName] = $this->getConstraintDiff($tableName);
            }
        }
        foreach ($this->oldTables as $tableName) {
            if (!in_array($tableName, $this->newTables)) {
                $this->droppedTables[] = $tableName;
            }
        }
    }

    public function getSchemeDiff($tableName)
    {
        $result = array(
            'changed' => array(),
            'add' => array(),
            'drop' => array(),
        );
        $newScheme = $this->newSchemes[$tableName];
        $oldScheme = $this->oldSchemes[$tableName];
        foreach ($newScheme as $key => $value) {
            $oldColumn = null;
            $newColumn = $value ;
            if (isset($oldScheme[$key])) {
                $oldColumn = $oldScheme[$key];
                unset($oldColumn['Key']);
                unset($oldColumn['index']);
            }
            unset($newColumn['Key']);
            unset($newColumn['index']);
            if (!isset($oldScheme[$key])) {
                $result['add'][] = $value;
            } elseif ($oldColumn != $newColumn) {
                $result['changed'][] = $value;
            }
        }
        foreach ($oldScheme as $key => $value) {
            if (!isset($newScheme[$key])) {
                $result['drop'][] = $value;
            }
        }
        return $result;
    }


    public function toCreateTable($tableName)
    {
        $sql = '';
        $sql .= "CREATE TABLE `$tableName` (\n";
        foreach ($this->newSchemes[$tableName] as $key => $value) {
            $sql .= $this->createFieldDefinitions($tableName, $key);
            $sql .= ',';
            $sql .= "\n";
        }
        if (isset($this->newIndex[$tableName]['PRIMARY']) && count($this->newIndex[$tableName]['PRIMARY']) > 1) {
            $sql .= 'PRIMARY KEY (' . implode(',', array_map(function($def){return $def['Column_name'];}, $this->newIndex[$tableName]['PRIMARY'])) . '),';
            $sql .= "\n";
        }
        if ($this->newIndex[$tableName]) {
            foreach ($this->newIndex[$tableName] as $key => $value) {
                if ($key == 'PRIMARY') continue;
                if ($value[0]['Non_unique'] == 0) {
                    $sql .= 'UNIQUE ';
                }
                $sql .= 'KEY `' . $key . '` (' . implode(',', array_map(function($def){
                        return isset($def['Sub_part']) && $def['Sub_part'] ? ('`' . $def['Column_name'] . '`'. '(' .$def['Sub_part'] . ')') : ('`' . $def['Column_name'] . '`');
                    }, $value)) . '),';
                $sql .= "\n";
            }
        }
        if ($this->newConstraint[$tableName]) {
            // QuickFix: 最後にalterの形にする必要があるためdiff扱いにする
            $this->constraintDiffs[$tableName] = array(
                'add' => array(),
                'drop' => array(),
                'changed' => array(),
            );
            foreach ($this->newConstraint[$tableName] as $key => $value) {
                $this->constraintDiffs[$tableName]['add'][$key] = $value;
                /*
                $sql .= 'CONSTRAINT ' . $key . ' FOREIGN KEY (';
                $sql .=  implode(',', array_map(function($def){ return $def['column']; }, $value)) . ') ';
                $sql .= ' REFERENCES ' . $value[0]['to_table'];
                $sql .= '(' . implode(',', array_map(function($def){ return $def['to_column']; }, $value)) . '),';
                $sql .= "\n";
                */
            }
        }
        $sql = preg_replace('#,$#', '', $sql);
        $sql .= ');';
        return $sql;
    }

    public function toDropTable($tableName)
    {
        return 'DROP TABLE `' . $tableName . '`;';
    }

    public function createFieldDefinitions($tableName, $key)
    {
        $value = $this->newSchemes[$tableName][$key];
        $sql = '';
        $sql .= "`" . $value['column'] . "` " . $value['type_name'] . " " . ($value['nullable'] ? 'NULL' : 'NOT NULL');
        if (isset($value['default'])) {
            $sql .= ' DEFAULT ';
            if ($value['default'] === 'NULL')  {
                $sql .= 'NULL';
            }
            else {
                $sql .= "'" . $this->newDatabase->escape($value['default']) . "'";
            }
        }
        if (count(array_filter($this->newSchemes[$tableName], function($col){return isset($col['key']) && $col['key'] === true;})) === 1 &&
            isset($value['key']) && $value['key'] === true) {
            $sql .= ' ' . 'PRIMARY KEY';
        }
        if (isset($value ['extra'])) {
            $sql .= ' ' . $value['extra'];
        }
        return $sql;
    }

    public function getIndexDiff ( $tableName ) {
        $result = array (
            'changed' => array (),
            'add'     => array (),
            'drop'    => array (),
        );
        $new = $this->newIndex[$tableName];
        $old = $this->oldIndex[$tableName];
        foreach ( $new as $key => $value  ) {
            $oldKey = null;
            $newKey = $value ;
            if (isset($old[$key])) {
                $oldKey = $old[$key];
                array_walk($oldKey, function(&$elm){unset($elm['Cardinality']);});
            }
            array_walk($newKey, function(&$elm){unset($elm['Cardinality']);});
            if (!isset($old[$key])) {
                $result['add'][] = $value;
            }
            elseif ($oldKey != $newKey) {
                $result['changed'][] =  $value;
            }
        }
        foreach ($old as $key => $value) {
            if (!isset($new[$key])) {
                $result['drop'][] = $value;
            }
        }
        return $result;
    }

    public function getConstraintDiff ( $tableName ) {
        $result = array (
            'changed' => array (),
            'add'     => array (),
            'drop'    => array (),
        );
        $new = $this->newConstraint[$tableName];
        $old = $this->oldConstraint[$tableName];
        foreach ( $new as $key => $value  ) {
            $oldKey = null;
            $newKey = $value ;
            if (isset($old[$key])) {
                $oldKey = $old[$key];
            }
            if (!isset($old[$key])) {
                $result['add'][] = $value;
            }
            elseif ($oldKey !== $newKey) {
                $result['changed'][] =  $value;
            }
        }
        foreach ($old as $key => $value) {
            if (!isset($new[$key])) {
                $result['drop'][] = $value;
            }
        }
        return $result;
    }


    public function toAddColumn($tableName, $key)
    {
        $before = null;
        foreach ($this->newSchemes[$tableName] as $key2 => $val) {
            if ($key2 == $key) break;
            $before = $key2;
        }
        $sql = '';
        $sql .= "ALTER TABLE `$tableName` ADD " . $this->createFieldDefinitions($tableName, $key) . ( $before ? ' AFTER ' . $before : ' FIRST') . ';';
        return $sql;
    }

    public function toChangeColumn($tableName, $key)
    {
        $sql = '';
        $sql .= "ALTER TABLE `$tableName` MODIFY " . $this->createFieldDefinitions($tableName, $key) . ';';
        return $sql;
    }

    public function toDropColumn($tableName, $key)
    {
        $sql = '';
        $sql .= "ALTER TABLE `$tableName` DROP `$key`;";
        return $sql;
    }

    public function toDropIndex ( $definition ) {
        return "ALTER TABLE `" . $definition[0]['Table'] .  "` " .
        "DROP INDEX `" .  $definition[0]['Key_name'] . "`;";
    }

    public function toAddIndex ( $definition ) {
        $sql  = '';
        $sql .= "ALTER TABLE `" . $definition[0]['Table'] . "` ";
        $sql .= "ADD ";
        if ($definition[0]['Non_unique'] == 0) {
            $sql .= "UNIQUE ";
        }
        $sql .= "INDEX `"   . $definition[0]['Key_name'] . "` ";
        $sql .= "(" . implode( ',', array_map ( function ($def) {
                return isset($def['Sub_part']) && $def['Sub_part'] ? ('`' . $def['Column_name'] . '`' . '(' .$def['Sub_part'] . ')') : ('`' . $def['Column_name'] . '`');
            }, $definition ) ) . ');';
        return $sql;
    }

    public function toChangeIndex ( $definition ) {
        $sql = '';
        $sql .= $this->toDropIndex ( $definition ) . "\n";
        $sql .= $this->toAddIndex ( $definition ) . "\n";
        return $sql;
    }

    public function toAddConstraint ( $tableName, $definition ) {
        $sql  = '';
        $sql .= "ALTER TABLE `" . $tableName . "` ";
        $sql .= "ADD CONSTRAINT `" . $definition[0]['name'] . '` ';
        $sql .= "FOREIGN KEY(" . implode(',',array_map(function($def){return $def['column'];}, $definition)) . ') ';
        $sql .= "REFERENCES `" . $definition[0]['to_table'] . "` (" . implode(',', array_map(function($def){return $def['to_column'];}, $definition)) . ');';
        return $sql;
    }

    public function toDropConstraint ( $tableName, $definition ) {
        $sql  = '';
        $sql .= "ALTER TABLE `" .$tableName . "` ";
        $sql .= "DROP CONSTRAINT " . $definition[0]['name'] . ';';
        return $sql;
    }

    public function toChangeConstraint ( $tableName, $definition ) {
        $sql  = '';
        $sql .= $this->toDropConstraint($tableName, $definition);
        $sql .= $this->toAddConstraint($tableName, $definition);
        return $sql;
    }
    public function toScript()
    {
        $sql = '';
        foreach ($this->createTables as $tableName) {
            $sql .= $this->toCreateTable($tableName) . "\n";
        }
        foreach ($this->droppedTables as $tableName) {

            $sql .= $this->toDropTable($tableName) . "\n";
        }

        foreach ($this->schemeDiffs as $tableName => $diffs) {
            foreach ($diffs['changed'] as $changed) {
                $sql .= $this->toChangeColumn($tableName, $changed['column']) . "\n";
            }

            foreach ($diffs['add'] as $add) {
                $sql .= $this->toAddColumn($tableName, $add['column']) . "\n";
            }

            foreach ($diffs['drop'] as $drop) {
                $sql .= $this->toDropColumn($tableName, $drop['column']) . "\n";
            }
        }

        foreach ( $this->indexDiffs as $tableName => $diffs ) {
            foreach ( $diffs['changed'] as $changed )  $sql .= $this->toChangeIndex ( $changed ) . "\n";
            foreach ( $diffs['add'] as $add )          $sql .= $this->toAddIndex ( $add )        . "\n";
            foreach ( $diffs['drop'] as $drop )        $sql .= $this->toDropIndex ( $drop )     . "\n";
        }

        foreach ( $this->constraintDiffs as $tableName => $diffs ) {
            foreach ( $diffs['changed'] as $changed )  $sql .= $this->toChangeConstraint($tableName, $changed) . "\n";
            foreach ( $diffs['add'] as $add )          $sql .= $this->toAddConstraint($tableName, $add)        . "\n";
            foreach ( $diffs['drop'] as $drop )        $sql .= $this->toDropConstraint($tableName, $drop)      . "\n";
        }
        return $sql;
    }

    public function __toString(){
        return $this->toScript();
    }
}
