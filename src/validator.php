<?php

/**
 * 数组变量验证类
 * 功能：对变量进行验证得到结果
 */
include_once ('validatorLib.php');
class validator
{

    public $rules = array(); // 验证字段规则
    public $labels = array(); // 验证字段人性化标记
    public $errorDefaultLangs = array(); // 默认错误语言包
    public $errorLangs = array(); // 错误语言包
    protected $errors = array(); // 错误列表 field => rule
    protected $data = array(); // 待验证的数组

    protected $validator; // 待验证的数组

    /**
     * 构造函数设置需要的数据。
     * @param   array   用于验证的key=>value数组
     * @return  void
     */
    public function __construct($valid_array = '')
    {
        if (empty($valid_array)) {
            $valid_array = array_merge($_POST, $_GET);
        }
        $this->data = $valid_array;
        $this->validator = new validatorLib();
        //包含默认错误语言包赋值给_errorDefaultLangs
        $this->errorDefaultLangs = $this->validator->getErrorLang();
    }
    
    public function setData($valid_array = '')
    {
        $this->data = $valid_array;
        return $this;
    }

    /**
     * 为验证数组字段设置一个人性化的标记名。
     * @param   string  字段名
     * @param   string  人性化标记名
     * @return  $this
     */
    public function label($field, $label)
    {
        $this->labels[$field] = $label;
        return $this;
    }

    /**
     * 设置人性化的标记名，用数组批量设置。
     * @param   array  list of field => label names
     * @return  $this
     */
    public function labels(array $labels)
    {
        $this->labels = $labels + $this->labels;
        return $this;
    }

    /**
     * 设定规则。所有的规则必须是函数或者方法的名字。 参数必须和回调函数的参数设置一致。
     *     举例:
     *     $validation->rule('username', 'required')
     *                ->rule('username', array('required')
     *                ->rule('username', array('minlength', 4 )
     *                ->rule('username', array('minlength'=> 4 )
     *                ->rule('username', array('minlength', array(4))
     *                ->rule('username', array('rangelength', array(4,5))
     *                ->rule('username', array('rangelength'=>array(4,5)) );
     * @param   string    字段名
     * @param   callback  有效的PHP回调函数
     * @param   array     回调函数参数
     * @return  $this
     */
    public function rule($field, $rule)
    {
        $rule = $this->formatRule($rule);

        // 如果没有设定标记名，那么这里设定人性化标记名。
        if (!isset($this->labels[$field])) {
            $this->labels[$field] = preg_replace('/[^\pL]+/u', ' ', $field);
        }
        $this->rules[$field][] = $rule; // 保存规则和参数到rules。
        return $this;
    }

    /**
     * 把所支持的规则初始化为标准的rule。
     * @param   muli 支持的rule格式，字符串或者数组。
     * @return  array 处理过的rule格式
     */
    private function formatRule($rule)
    {
        if (is_string($rule)) {
            $rule = array($rule, array());
        } elseif (is_array($rule)) {
            $count = count($rule);
            if ($count == 1) {
                list($key, $val) = each($rule);
            } else {
                if ($count > 2){
                    $rule = array_slice($rule, 0, 2);
                }
                $key=$rule[0];
                $val=$rule[1];
            }
            if (is_string($key) || is_array($key)) {
                if(!is_array($val)){
                    $val = (array)$val;
                }
                $rule = array($key, $val);
            } else {
                $rule = array($val, array());
            }
        }
        return $rule;
    }

    /**
     * 添加数组规则。
     * @param   array   规则数组
     * @return  $this
     */
    public function rules($rules)
    {
        foreach ($rules as $key => $rule) {
            if (is_int($key) && !empty($rule['field']) && !empty($rule['rule'])) {
                $this->rule($rule['field'], $rule['rule']);
            } elseif (is_string($key) && !empty($rule['field'])) {
                $this->rule($key, $rule);
            }
        }
        return $this;
    }

    /**
     * 执行所有的验证规则。
     *     if ($validation->check()) {
     *          验证失败的处理
     *     }
     * @return  boolean
     */
    public function check($isBreak = TRUE)
    {
        foreach ($this->rules as $field => $rules) {
            $field = str_replace('[]', '', $field); //如果有些变量是带[]，去掉键值中的[]。
            $value = isset($this->data[$field]) ? $this->data[$field] : NULL; // 得到验证字段值。
            foreach ($rules as $rule) { //保存是验证规则数组。
                list($ruleName, $params) = $rule; //规则被定义 array($rule, $params) 这种格式。
                array_unshift($params, $value);
                $checkResult = $this->singleCheck($ruleName, $params);
                list($result, $errorName) = $checkResult;
                if (!$result) {
                    $this->error($field, $errorName, $params);
                    if($isBreak){
                        break 2;
                    }
                    break 1;
                }
            }
        }
        if (!empty($this->errors)) {
            return FALSE;
        }
        return TRUE;
    }

    /**
     * 执行单个验证规则。
     * 写入$error_name变量
     * @param   不带参数的可执行规则
     * @param   规则传递进来的参数
     * @return  boolean
     */
    private function singleCheck($ruleName, $params)
    {
        $errorName = $ruleName; // 默认错误名为rule
        if (is_string($ruleName) && method_exists($this->validator, $ruleName)) { //valid类中自带的验证方法
            $method = new ReflectionMethod($this->validator, $ruleName);
            $passed = $method->invokeArgs(NULL, $params);
        } elseif (is_array($ruleName)) { //数组格式的回调函数
            $errorName = $ruleName[1]; // 数组回调函数,方法名就是错误名
            $passed = call_user_func_array($ruleName, $params);
        } elseif (strpos($ruleName, '::') === FALSE) {//普通函数，自定义的或者是php自带的。
            $function = new ReflectionFunction($ruleName);
            $passed = $function->invokeArgs($params); //$function($this[$field], $param, ...) with Reflection
        } elseif (!is_string($ruleName)) {// 匿名函数
            $errorName = FALSE; //没有错误名，错误必须在函数中手动添加。
            $passed = call_user_func_array($ruleName, $params);
        } else {//静态方法格式的回调函数
            list($class, $method) = explode('::', $ruleName, 2);
            $errorName = $method;
            $method = new ReflectionMethod($class, $method);
            $passed = $method->invokeArgs(NULL, $params);
        }
        return array($passed, $errorName);
    }

    /**
     * 验证字段添加错误
     * $lang = array(
      'field' => array
      (
      'required' => '名字不能为空',
      'alpha' => '只允许输入字母',
      'default' => '输入错误',
      ),
      );
     * @param   string/array  可以是field或者$lang数组
     * @param   string		  错误信息标识
     * @param   string		  错误信息描述字符串
     * @return  $this
     */
    public function errorLang($fielddata, $error = '', $langstr = '')
    {
        if (is_array($fielddata) && empty($error)) {
            foreach ($fielddata as $field => $lang) {
                foreach ($lang as $key => $strlang) {
                    $this->errorLangs[$field][$key] = $strlang;
                }
            }
        } else {
            $this->errorLangs[$fielddata][$error] = $langstr;
        }
        return $this;
    }

    /**
     * 验证字段添加一个错误
     * @param   string  验证字段
     * @param   string  错误信息
     * @param   string  错误参数，可能被语言包使用。
     * @return  $this
     */
    public function error($field, $errorName, array $params = NULL)
    {
        $this->errors[$field] = array($errorName, $params);
        return $this;
    }
    
    /**
     * 清空错误，适用于多次校验的情况。
     * @return  $this
     */
    public function emptyError()
    {
        $this->errors = array();
        return $this;
    }

    /**
     * 输出错误代码
     * @param   string  验证字段
     * @param   string  错误信息
     * @return  $thisE
     */
    public function getError($errorType = 'ARRAY')
    {
        $return = '';
        $errorArr = array();
        foreach ($this->errors as $field => $array) {
            list($error, $params) = $array;
            $label = $this->labels[$field];
            $lang = isset($this->errorLangs[$field][$error]) ? $this->errorLangs[$field][$error] : (isset($this->errorDefaultLangs[$error]) ? $this->errorDefaultLangs[$error] : $field);
            $lang = preg_replace('/:param(\d{1,2})/e', '$params[\\1]', $lang);
            $lang = str_replace(array(':field', ':param'), array($label, $params[0]), $lang);
            $errorArr[$field] = $lang;
        }
        switch (strtoupper($errorType)) {
            case 'XML':
                $doc = new DOMDocument('1.0', 'UTF-8');
                $doc->formatOutput = true;
                $mark_dom = $doc->createElement('error');
                $doc->appendChild($mark_dom);
                foreach ($errorArr as $key => $val) {
                    $field_dom = $doc->createElement($key);
                    $field_dom->appendChild($doc->createTextNode($val));
                    $mark_dom->appendChild($field_dom);
                }
                $return = $doc->saveXML();
                break;
            case 'ARRAY':
                $return = $errorArr;
                break;
            case 'JSON':
                $return = json_encode($errorArr);
                break;
        }
        return $return;
    }

}

// End
