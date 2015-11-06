<?php
namespace Scruit\database;
class Column
{
    private $name = null;
    private $dataType = null;
    private $primary = null;
    private $autoIncrement = null;
    private $notNull = null;
    private $default = null;

    public function __construct(array $scheme)
    {
        $this->name = $scheme['name'];
        $this->dataType = $scheme['dataType'];
        $this->primary = $scheme['primary'];
        $this->autoIncrement = $scheme['autoIncrement'];
        $this->notNull = $scheme['notNull'];
        $this->default = $scheme['default'];
    }

    public function marker()
    {
        $char = '';
        if (preg_match('#int|decimal#i', $this->getDataType())) {
            $char = 'i';
        } elseif (preg_match('#double|float#i', $this->getDataType())) {
            $char = 'd';
        } elseif (preg_match('#blob#i', $this->getDataType())) {
            $char = 'b';
        } else {
            $char = 's';
        }
        return $char;
    }

    public function length()
    {
        $tmp = array();
        if (!preg_match('#\((\d+)\)#', $this->getDataType(), $tmp)) {
            return null;
        } else {
           return $tmp[1];
        }
    }

    public function dummyValue()
    {
        $marker = $this->marker();
        if ($marker === 'i') {
            return 1;
        } elseif ($marker === 'd') {
            return '1.0';
        } else {
            return $this->name;
        }
    }

    public function validatorDefinition()
    {
        $type = null;
        if (preg_match('#int|decimal#i', $this->getDataType())) {
            $type = 'integer';
        } elseif (preg_match('#double|float#i', $this->getDataType())) {
            $type = 'double';
        } elseif (preg_match('#date#i', $this->getDataType())) {
            $type = 'date';
        } else {
            $type = 'string';
        }
        $result = array();
        $result['required'] = ($this->getPrimary() && $this->getAutoIncrement()) ? false : $this->notNull;
        $result['dataType'] = $type;
        if ($type === 'string') {
            $result['max'] = $this->length();
        }
        return $result;
    }


    /**
     * @param null $autoIncrement
     */
    public function setAutoIncrement($autoIncrement)
    {
        $this->autoIncrement = $autoIncrement;
    }

    /**
     * @return null
     */
    public function getAutoIncrement()
    {
        return $this->autoIncrement;
    }

    /**
     * @param null $dataType
     */
    public function setDataType($dataType)
    {
        $this->dataType = $dataType;
    }

    /**
     * @return null
     */
    public function getDataType()
    {
        return $this->dataType;
    }

    /**
     * @param null $default
     */
    public function setDefault($default)
    {
        $this->default = $default;
    }

    /**
     * @return null
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param null $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param null $notNull
     */
    public function setNotNull($notNull)
    {
        $this->notNull = $notNull;
    }

    /**
     * @return null
     */
    public function getNotNull()
    {
        return $this->notNull;
    }

    /**
     * @param null $primary
     */
    public function setPrimary($primary)
    {
        $this->primary = $primary;
    }

    /**
     * @return null
     */
    public function getPrimary()
    {
        return $this->primary;
    }
}