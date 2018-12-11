<?php
namespace Brother\CommonBundle\Model;

/**
 * Возврат аякса
 *
 */
use Brother\CommonBundle\AppDebug;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * User: Andrey Dashkovskiy
 * Date: 26.11.13
 * Time: 16:24
 * Class BaseApi
 * @package Brother\CommonBundle\Model
 */
class AjaxResponse {

    /**
     * Возврат успеха
     */
    const SUCCESS = 0;

    /**
     * Возврат ошибки
     */
    const ERROR = -1;

    /**
     * Список ошибок
     *
     * @var array
     */
    protected $errors = [];

    /**
     * Список варнингов
     *
     * @var array
     */
    protected $warnings = [];

    /**
     * Список сообщений
     *
     * @var array
     */
    protected $messages = [];


    /**
     * Ответ
     *
     * @var array
     */
    protected $response = [];

    /**
     * Разрешён только аякс, иначе можно показать в основом шаблоне
     *
     * @var bool
     */
    protected $ajaxOnly = false;
    static $instance = null;

    /**
     * Конструктор
     */
    function __construct() {
    }

    function __toString() {
        return json_encode($this->result());
    }

    /**
     * @return AjaxResponse
     */
    static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new AjaxResponse();
        }
        return self::$instance;
    }

    /**
     * Добавляет рендер
     *
     * @param $model
     * @param $id
     * @param $field
     * @param $value
     *
     * @return $this
     */
    public function addRenderModel($model, $id, $field, $value) {
        $renderId = $model . '_' . $id . '_' . $field;
        return $this->addRenderDom('[data-render-id=' . $renderId . ']', $value);
    }

    /**
     * Рендер по ключевому полю
     *
     * @param $renderId
     * @param $value
     *
     * @return $this
     */
    public function addRenderById($renderId, $value) {
        return $this->addRenderDom('[data-render-id=' . $renderId . ']', $value);
    }

    /**
     * Рендер по селектору
     *
     * @param $selector
     * @param $value
     *
     * @return $this
     */
    public function addRenderDom($selector, $value) {
        if (empty($this->response['renders'])) {
            $this->response['renders'] = [];
        }
        $this->response['renders'][] = [$selector => $value];
        return $this;
    }

    /**
     * @return boolean
     */
    public function isAjaxOnly() {
        return $this->ajaxOnly;
    }

    /**
     * @param boolean $ajaxOnly
     *
     * @return $this
     */
    public function setAjaxOnly($ajaxOnly) {
        $this->ajaxOnly = $ajaxOnly;
        return $this;
    }

    /**
     * Валидация
     *
     * @param      $name      String Имя поля
     * @param      $value     String Значение
     * @param      $validator String Валидатор
     * @param null $message   Сообщение кастомное если нужно
     *
     * @return boolean
     */
    protected function validate($name, $value, $validator, $message = null) {
        return $this->{'validate' . ucfirst($validator)}($name, $value, $message);
    }

    /**
     * Валидация на пустое значение
     *
     * @param             $name
     * @param             $value
     * @param null|string $message
     *
     * @return bool
     */
    public function validateEmpty($name, $value, $message = null) {
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
     *
     * @return bool
     */
    public function isValid() {
        return count($this->errors) == 0;
    }

    /**
     * собираем результат
     *
     * @return array
     */
    public function result() {
        if ($this->isValid()) {
            return [
                'status' => self::SUCCESS,
                'response' => $this->response,
                'errors' => $this->errors,
                'warnings' => $this->warnings,
				'messages' => array_values($this->messages)
            ];
        } else {
            return [
                'status' => self::ERROR,
                'response' => $this->response,
                'errors' => $this->errors,
                'warnings' => $this->warnings,
				'messages' => $this->messages
			];
        }
    }

    public function resultResponse(){
        return new JsonResponse($this->result());
    }

    /**
     * Запоминаем результат
     *
     * @param $name
     * @param $value
     *
     * @return AjaxResponse
     */
    public function setResponse($name, $value) {
        $this->response[$name] = $value;
        return $this;
    }

    public function setResponseArray($values) {
        foreach ($values as $key => $value) {
            $this->setResponse($key, $value);
        }
        return $this;
    }

    /**
     * @param $message
     *
     * @return $this
     */
    public function addMessage($message) {
        $this->messages[] = $message;
        return $this;
    }

    /**
     * @param      $error
     * @param null $key
     *
     * @return $this
     */
    public function addError($error, $key = null) {
        if ($key) {
            $this->errors[$key] = $error;
        } else {
            $this->errors[] = $error;
        }
        return $this;
    }

    public function setErrors($errors) {
        if ($errors) {
            $this->errors = $errors;
        } else {
            $this->errors = [];
        }
        return $this;
    }

    /**
     * @param $warning
     *
     * @return $this
     */
    public function addWarning($warning) {
        $this->warnings[] = $warning;
        return $this;
    }

    /**
     * Для юнит тестов - надо чистить ответ от старых данных
     */
    public function clear(){
        $this->messages = [];
    }

}
