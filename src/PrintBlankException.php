<?php

/**
 * PrintBlankException class file.
 *
 * @author Victor Zhuk <chewire@gmail.com>
 * @copyright Copyright &copy; 2013 Victor Zhuk
 * @license http://www.yiiframework.com/license/
 * @package yii-printblank
 * @since 1.0
 */

class PrintBlankException extends CException
{    
    public function __construct($message = null, $code = 0)
    {
        if (!$message) {
            throw new $this(Yii::t('yii-printblank', 'Unknown error'));
        }
		if (is_array($message)) {
			if (count($message) > 1) {
				parent::__construct(Yii::t('yii-printblank', $message[0], $message[1]), $code);
			} else {
				parent::__construct(Yii::t('yii-printblank', $message[0]), $code);
			}
		} else {
			parent::__construct(Yii::t('yii-printblank', $message), $code);
		}
    }
}

?>
