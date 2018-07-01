<?php

class Mapper {
	const TYPE_INT = 'int';
	const TYPE_FLOAT = 'float';
	const TYPE_STRING = 'string';

	private $customMappings = [];
	private $directMappings = [];
	private $typeMappings = [];

	function map($obj, $json) {
		foreach ($json as $prop => $value) {
			if (isset($this->customMappings[$prop]) && is_callable($this->customMappings[$prop])) {
				$value = $this->customMappings[$prop](new static(), $value);
			} else if (isset($this->directMappings[$prop])) {
				$prop = $this->directMappings[$prop];
			}

			if (isset($this->typeMappings[$prop])) {
				switch ($this->typeMappings[$prop]) {
					case static::TYPE_INT:
						$value = (int) $value;
						break;
					case static::TYPE_STRING:
						$value = (string) $value;
						break;
					case static::TYPE_FLOAT:
						$value = (float) $value;
						break;
				}
			}

			$m = 'set' . ucfirst($prop);
			if (method_exists($obj, $m)) {
				$obj->{$m}($value);
			}
		}

		return $obj;
	}

	function setCustomMapping($property, Callable $nestedMapper) {
		$this->customMappings[$property] = $nestedMapper;
		return $this;
	}

	function setDirectMapping($from, $to) {
		$this->directMappings[$from] = $to;
	}

	function setTypeMapping($property, $type) {
		$this->typeMappings[$property] = $type;
	}
}

class InvokableMapper {
	public function __invoke(Mapper $mapper, $data) {

	}
}



class A {
	private $name;
	private $address;
	private $lastName;
	private $age;

	function setName($v) {
		$this->name = $v;
	}

	function setAddress(Address $a) {
		$this->address = $a;
	}

	function setLastName($ln) {
		$this->lastName = $ln;
	}

	function setAge($a) {
		$this->age = $a;
	}
}

class Address {
	private $number;
	private $something;

	function setNumber($number) {
		$this->number = $number;
	}

	function setSomething($sth) {
		$this->something = $sth;
	}
}

$json = '{"last_name": "Someone", "name": "something", "age": "42", "address": {"number": 123, "something": true}}';

$json = json_decode($json);

$mapper = new Mapper();
$mapper->setDirectMapping('last_name', 'lastName');
$mapper->setTypeMapping('age', Mapper::TYPE_INT);
$mapper->setCustomMapping('address', function(Mapper $mapper, $data) {
	$a = new Address();
	$mapper->setCustomMapping('something', new InvokableMapper());
	$mapper->map($a, $data);
	return $a;
});


var_dump($mapper->map(new A(), $json));