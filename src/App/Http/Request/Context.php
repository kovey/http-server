<?php
/**
 * @description request context
 *
 * @package
 *
 * @author kovey
 *
 * @time 2022-08-02 10:07:31
 *
 */
namespace Kovey\Web\App\Http\Request;

class Context
{
    private Array $data;

    public function __construct()
    {
        $this->data = array();
    }

    public function __get(string $field) : mixed
    {
        return $this->get($field);
    }

    public function get(string $field) : mixed
    {
        return $this->data[$field] ?? null;
    }

    public function set(string $field, mixed $value) : self
    {
        $this->data[$field] = $value;
        return $this;
    }

    public function __set(string $field, mixed $value)
    {
        $this->set($field, $value);
    }
}
