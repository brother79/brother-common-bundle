<?php

namespace Brother\CommonBundle\Model;

/**
 * Created by PhpStorm.
 * User: Andrey Dashkovskiy
 * Date: 26.11.13
 * Time: 16:24
 */

class BaseApi
{

    /**
     * Возврат успеха
     */
    const SUCCESS = 0;
    /**
     * Возврат ошибки
     */
    const ERROR = -1;

    /**
     * @var array() Список ошибок
     */
    protected $errors = array();

    /**
     * @var
     */
    protected $warnings = array();

    /**
     * @var
     */
    protected $messages = array();


    /**
     * @var array
     */
    private $response = array();


    function __construct()
    {
    }

    /**
     * @param $model
     * @param $id
     * @param $field
     * @param $value
     * @return $this
     */
    public function addRenderModel($model, $id, $field, $value)
    {
        $renderId = $model . '_' . $id . '_' . $field;
        return $this->addRenderDom('[render-id=' . $renderId . ']', $value);
    }

    /**
     * @param $renderId
     * @param $value
     * @return $this
     */
    public function addRenderById($renderId, $value)
    {
        return $this->addRenderDom('[render-id=' . $renderId . ']', $value);
    }

    /**
     * @param $selector
     * @param $value
     * @return $this
     */
    public function addRenderDom($selector, $value)
    {
        if (empty($this->response['render'])) {
            $this->response['render'] = array();
        }
        $this->response['render'][$selector] = $value;
        return $this;
    }


    /**
     * Валидация
     *
     * @param $name String Имя поля
     * @param $value String Значение
     * @param $validator String Валидатор
     * @param null $message Сообщение кастомное если нужно
     * @return boolean
     */
    protected function validate($name, $value, $validator, $message = null)
    {
        return $this->{'validate' . ucfirst($validator)}($name, $value, $message);
    }

    /**
     * Валидация на пустое значение
     *
     * @param $name
     * @param $value
     * @param null|string $message
     * @return bool
     */
    public function validateEmpty($name, $value, $message = null)
    {
        if ($message == null) {
            $message = 'Необходимо заполнить';
        }
        if (empty($value)) {
            $this->errors[$name] = $message;
            return false;
        }
        return true;
    }

    /**
     * Есть ли ошибки?
     * @return bool
     */
    public function isValid()
    {
        return count($this->errors) == 0;
    }

    /**
     * собираем результат
     * @return array
     */
    public function result()
    {
        if ($this->isValid()) {
            return array('status' => self::SUCCESS, 'response' => $this->response, 'errors' => $this->errors, 'warnings' => $this->warnings, 'messages' => $this->messages);
        } else {
            return array('status' => self::ERROR, 'response' => $this->response, 'errors' => $this->errors, 'warnings' => $this->warnings, 'messages' => $this->messages);
        }
    }

    /**
     * Запоминаем результат
     * @param $name
     * @param $value
     * @return BaseApi
     */
    public function setResponse($name, $value)
    {
        $this->response[$name] = $value;
        return $this;
    }

    /**
     * @param $message
     * @return $this
     */
    public function addMessage($message)
    {
        $this->messages[] = $message;
        return $this;
    }

    /**
     * @param $error
     * @param null $key
     * @return $this
     */
    public function addError($error, $key = null)
    {
        if ($key) {
            $this->errors[$key] = $error;
        } else {
            $this->errors[] = $error;
        }
        return $this;
    }

    public function setErrors($errors)
    {
        $this->errors = $errors;
    }

    /**
     * @param $warning
     * @return $this
     */
    public function addWarning($warning)
    {
        $this->warnings[] = $warning;
        return $this;
    }

} 