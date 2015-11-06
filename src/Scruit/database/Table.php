<?php
namespace Scruit\database;
class Table
{
    private $name = null;
    private $columns = array();

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function addColumn(Column $column)
    {
        $this->columns[] = $column;
    }

    /**
     * @param array $columns
     */
    public function setColumns($columns)
    {
        $this->columns = $columns;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    public function getPrimaryKeys ()
    {
        return array_values(array_filter($this->columns, function (Column $column) {return $column->getPrimary();}));
    }

    public function isAutoIncrement ()
    {
        return count(array_filter($this->getPrimaryKeys(), function(Column $column) {return $column->getAutoIncrement();}));
    }

    public function createInsert()
    {
        $columns = array_values(array_filter($this->columns, function (Column $column) {return !($column->getPrimary() && $column->getAutoIncrement());}));
        $columnBlock = implode(',', array_map(function (Column $column) {return $column->getName();}, $columns));
        $valueBlock = implode(',', array_map(function (Column $column) {return '?';}, $columns));
        $marker = implode('', array_map(function (Column $column) {return $column->marker();}, $columns));
        return array(
            'SQL' => "INSERT INTO $this->name ($columnBlock) VALUES ($valueBlock)",
            'marker' => $marker,
            'columns' => array_map(function ($column) {return $column->getName();}, $columns),
            'dummyValues' => array_map(function($column){return $column->dummyValue();}, $columns),
            'dummyHash' => array_combine(
                array_map(function($column){return $column->getName();},    $columns) ,
                array_map(function($column){return $column->dummyValue();}, $columns)
            ),
        );
    }

    public function createUpdate()
    {
        $primary = array_values(array_filter($this->columns, function (Column $column) {return $column->getPrimary();}));
        $whereBlock = implode(" AND ", array_map(function (Column $column) {return $column->getName() .' = ?';}, $primary));
        $columns = array_values(array_filter($this->columns, function (Column $column) {return !($column->getPrimary() && $column->getAutoIncrement());}));
        $columnBlock = implode(", ", array_map(function (Column $column) {return $column->getName() . ' = ?';}, $columns));
        $marker = implode('', array_map(function (Column $column) {return $column->marker();}, $columns)) .
            implode('', array_map(function (Column $column) {return $column->marker();}, $primary));
        $columnNames = array_map(function ($column) {return $column->getName();}, $columns);
        foreach ($primary as $column) $columnNames[] = $column->getName();
        foreach ($primary as $column) $columns[] = $column;

        return array(
            'SQL' => "UPDATE $this->name SET $columnBlock WHERE $whereBlock",
            'marker' => $marker,
            'columns' => $columnNames,
            'dummyValues' => array_map(function($column){return $column->dummyValue();}, $columns),
            'dummyHash' => array_combine(
                array_map(function($column){return $column->getName();},    $this->columns) ,
                array_map(function($column){return $column->dummyValue();}, $this->columns)
            ),
        );
    }

    public function createSave()
    {
        $columnBlock = implode(',', array_map(function (Column $column) {return $column->getName();}, $this->columns));
        $valueBlock = implode(',', array_map(function (Column $column) {return '?';}, $this->columns));
        $marker = implode('', array_map(function (Column $column) {return $column->marker();}, $this->columns));
        return array(
            'SQL' => "REPLACE INTO $this->name ($columnBlock) VALUES ($valueBlock)",
            'marker' => $marker,
            'columns' => array_map(function ($column) {return $column->getName();}, $this->columns),
            'dummyValues' => array_map(function($column){return $column->dummyValue();}, $this->columns),
            'dummyHash' => array_combine(
                array_map(function($column){return $column->getName();},    $this->columns) ,
                array_map(function($column){return $column->dummyValue();}, $this->columns)
            ),
        );
    }


    public function createSelectOne()
    {
        $primary = array_values(array_filter($this->columns, function (Column $column) {return $column->getPrimary();}));
        $whereBlock = implode(" AND \n", array_map(function (Column $column) {return $column->getName() . ' = ?';}, $primary));
        $marker = implode('', array_map(function (Column $column) {return $column->marker();}, $primary));
        return array(
            'SQL' => "SELECT * FROM $this->name WHERE $whereBlock",
            'marker' => $marker,
            'columns' => array_map(function ($column) {return $column->getName();}, $primary),
            'dummyKeys' => array_map(function ($column) {return $column->dummyValue();}, $primary),
            'dummyValues' => array_map(function ($column) {return $column->dummyValue();}, $this->columns),
            'dummyHash' => array_combine(
                array_map(function($column){return $column->getName();},    $this->columns) ,
                array_map(function($column){return $column->dummyValue();}, $this->columns)
            ),
        );
    }

    public function createDeleteOne()
    {
        $primary = array_values(array_filter($this->columns, function (Column $column) {return $column->getPrimary();}));
        $whereBlock = implode(" AND \n", array_map(function (Column $column) {return $column->getName() . ' = ?';}, $primary));
        $marker = implode('', array_map(function (Column $column) {return $column->marker();}, $primary));
        return array(
            'SQL' => "DELETE FROM $this->name WHERE $whereBlock",
            'marker' => $marker,
            'columns' => array_map(function ($column) {return $column->getName();}, $primary),
            'dummyKeys' => array_map(function ($column) {return $column->dummyValue();}, $primary),
            'dummyValues' => array_map(function ($column) {return $column->dummyValue();}, $this->columns),
            'dummyHash' => array_combine(
                array_map(function($column){return $column->getName();},    $this->columns) ,
                array_map(function($column){return $column->dummyValue();}, $this->columns)
            ),
        );
    }

    /**
     * @return null
     */
    public function getName()
    {
        return $this->name;
    }
}