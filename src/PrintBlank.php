<?php

/**
 * PrintBlank class file.
 *
 * @author Victor Zhuk <chewire@gmail.com>
 * @copyright Copyright &copy; 2013 Victor Zhuk
 * @license http://www.yiiframework.com/license/
 * @package yii-rpblank
 * @since 1.0
 */

class PrintBlank extends CApplicationComponent
{

	const ALIAS = 'printblank';

    public $params = array();

    public function init()
    {
		Yii::setPathOfAlias(self::ALIAS, dirname(__FILE__));
		Yii::import(self::ALIAS . '.*');
    }

    public function blank($name)
    {
        return $this->_createObject($name);
    }

    private function _createObject($name)
    {
        if (isset($name) && $name !== '') {
            if (isset($this->params['blanks'][$name]) && is_array($this->params['blanks'][$name])) {
				$blank = $this->params['blanks'][$name];
				if(isset($blank['class'])){
					$class = $this->params['blanks'][$name]['class'];
				} else{
					throw new PrintBlankException('Blank class is empty');
				}
				unset($blank['class']);
				$objParams = $this->_mergeParams($this->params['defaults'], $blank);
				Yii::import($class);
				$ref = new ReflectionClass(array_pop(explode('.', $class)));
				return $ref->newInstance($objParams);
            } else {
                throw new PrintBlankException(array('Blank "{name}" does not exist', array('{name}' => $name)));
            }
        } else {
            throw new PrintBlankException('Blank name is empty');
        }
    }

	function _mergeParams($Arr1, $Arr2)
	{
		foreach($Arr2 as $key => $Value) {
			if(array_key_exists($key, $Arr1) && is_array($Value))
				$Arr1[$key] = $this->_mergeParams($Arr1[$key], $Arr2[$key]);
			else
				$Arr1[$key] = $Value;
		}
		return $Arr1;
	}

}

?>
