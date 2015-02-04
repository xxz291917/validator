<?php

class validatorLib
{

    public static function getErrorLang()
    {
        return array(
            'required' => ':field不能为空',
            'pattern' => ':field不能匹配需要的格式',
            'rangelength' => ':field长度必须介于:param1和:param2之间',
            'minlength' => ':field长度至少:param1个字符',
            'maxlength' => ':field长度必须大于:param1个字符',
            'equalto' => ':field必须等于:param1',
            'email' => ':field必须是email地址',
            'url' => ':field必须是一个url',
            'ip' => ':field必须是一个ip地址',
            'phone' => ':field必须是一个合法电话号码',
            'date' => ':field必须是一个日期',
            'number' => ':field必须是数字',
            'digits' => ':field必须是整数',
            'decimal' => ':field小数位数必须是:param1位',
            'range' => ':field值必须在:param1和:param2之间',
            'min' => ':field值必须小于等于:param1',
            'max' => ':field值必须大于等于:param1',
            'color' => ':field必须是一个有效颜色值',
            'inarray' => ':field不符合选值范围',
        );
    }

    /**
     * Checks if a field is not empty.
     *
     * @return  boolean
     */
    public static function required($value)
    {
        if (is_object($value) AND $value instanceof ArrayObject) {
            // Get the array from the ArrayObject
            $value = $value->getArrayCopy();
        }
        // Value cannot be NULL, FALSE, '', or an empty array
        return !in_array($value, array(NULL, FALSE, '', array(), 0), TRUE);
    }

    /**
     * Checks a field against a regular expression.
     *
     * @param   string  value
     * @param   string  regular expression to match (including delimiters)
     * @return  boolean
     */
    public static function pattern($value, $expression)
    {
        return (bool) preg_match($expression, (string) $value);
    }

    /**
     * Generates an error if the field is too long or too short.
     *
     * @param   mixed   input value
     * @param   $min
     * @param   $max
     * @return  bool
     */
    public static function rangelength($value, $min = 0, $max = 0)
    {
        if (is_array($value)) {
            $size = count($value);
        } elseif (is_string($value)) {
            //$size = strlen($value);
            $size = mb_strlen($value, 'utf8');
        } else {
            return FALSE;
        }
        if ($size >= $min AND $size <= $max) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Checks that a field is long enough.
     *
     * @param   string   value
     * @param   integer  minimum length required
     * @return  boolean
     */
    public static function minlength($value, $length)
    {
        if (is_array($value)) {
            $size = count($value);
        } elseif (is_string($value)) {
            //$size = strlen($value);
            $size = mb_strlen($value, 'utf8');
        } else {
            return FALSE;
        }
        return $size >= $length;
    }

    /**
     * Checks that a field is short enough.
     *
     * @param   string   value
     * @param   integer  maximum length required
     * @return  boolean
     */
    public static function maxlength($value, $length)
    {
        if (is_array($value)) {
            $size = count($value);
        } elseif (is_string($value)) {
            //$size = strlen($value);
            $size = mb_strlen($value, 'utf8');
        } else {
            return FALSE;
        }
        return $size <= $length;
    }

    /**
     * Checks that a field is exactly the right length.
     *
     * @param   string   value
     * @param   integer|array  exact length required, or array of valid lengths
     * @return  boolean

      public static function exact_length($value, $length) {
      $size = strlen($value);
      if (is_array($length)) {
      return in_array($size, $length);
      }
      return $size === $length;
      } */

    /**
     * Checks that a field is exactly the value required.
     *
     * @param   string   value
     * @param   string   required value
     * @return  boolean
     */
    public static function equalto($value, $required)
    {
        return ($value === $required);
    }

    /**
     * Check an email address for correct format.
     *
     * @link  http://www.iamcal.com/publish/articles/php/parsing_email/
     * @link  http://www.w3.org/Protocols/rfc822/
     *
     * @param   string   email address
     * @param   boolean  strict RFC compatibility
     * @return  boolean
     */
    public static function email($email, $strict = FALSE)
    {
        if (strlen($email) > 254) {
            return FALSE;
        }
        if ($strict === TRUE) {
            $qtext = '[^\\x0d\\x22\\x5c\\x80-\\xff]';
            $dtext = '[^\\x0d\\x5b-\\x5d\\x80-\\xff]';
            $atom = '[^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+';
            $pair = '\\x5c[\\x00-\\x7f]';

            $domain_literal = "\\x5b($dtext|$pair)*\\x5d";
            $quoted_string = "\\x22($qtext|$pair)*\\x22";
            $sub_domain = "($atom|$domain_literal)";
            $word = "($atom|$quoted_string)";
            $domain = "$sub_domain(\\x2e$sub_domain)*";
            $local_part = "$word(\\x2e$word)*";

            $expression = "/^$local_part\\x40$domain$/D";
        } else {
            $expression = '/^[-_a-z0-9\'+*$^&%=~!?{}]++(?:\.[-_a-z0-9\'+*$^&%=~!?{}]+)*+@(?:(?![-.])[-a-z0-9.]+(?<![-.])\.[a-z]{2,6}|\d{1,3}(?:\.\d{1,3}){3})$/iD';
        }
        return (bool) preg_match($expression, (string) $email);
    }

    /**
     * Validate the domain of an email address by checking if the domain has a
     * valid MX record.
     *
     * @link  http://php.net/checkdnsrr  not added to Windows until PHP 5.3.0
     *
     * @param   string   email address
     * @return  boolean

      public static function email_domain($email) {
      if ( ! Valid::not_empty($email))// Empty fields cause issues with checkdnsrr()
      return FALSE;
      // Check if the email domain has a valid MX record
      return (bool) checkdnsrr(preg_replace('/^[^@]++@/', '', $email), 'MX');
      } */

    /**
     * Validate a URL.
     *
     * @param   string   URL
     * @return  boolean
     */
    public static function url($url)
    {
        // Based on http://www.apps.ietf.org/rfc/rfc1738.html#sec-5
        if (!preg_match(
                        '~^

			# scheme
			[-a-z0-9+.]++://

			# username:password (optional)
			(?:
				    [-a-z0-9$_.+!*\'(),;?&=%]++   # username
				(?::[-a-z0-9$_.+!*\'(),;?&=%]++)? # password (optional)
				@
			)?

			(?:
				# ip address
				\d{1,3}+(?:\.\d{1,3}+){3}+

				| # or

				# hostname (captured)
				(
					     (?!-)[-a-z0-9]{1,63}+(?<!-)
					(?:\.(?!-)[-a-z0-9]{1,63}+(?<!-)){0,126}+
				)
			)

			# port (optional)
			(?::\d{1,5}+)?

			# path (optional)
			(?:/.*)?

			$~iDx', $url, $matches))
            return FALSE;

        // We matched an IP address
        if (!isset($matches[1]))
            return TRUE;

        // Check maximum length of the whole hostname
        // http://en.wikipedia.org/wiki/Domain_name#cite_note-0
        if (strlen($matches[1]) > 253)
            return FALSE;

        // An extra check for the top level domain
        // It must start with a letter
        $tld = ltrim(substr($matches[1], (int) strrpos($matches[1], '.')), '.');
        return ctype_alpha($tld[0]);
    }

    /**
     * Validate an IP.
     *
     * @param   string   IP address
     * @param   boolean  allow private IP networks
     * @return  boolean
     */
    public static function ip($ip, $allow_private = TRUE)
    {
        // Do not allow reserved addresses
        $flags = FILTER_FLAG_NO_RES_RANGE;

        if ($allow_private === FALSE) {
            // Do not allow private or reserved addresses
            $flags = $flags | FILTER_FLAG_NO_PRIV_RANGE;
        }
        return (bool) filter_var($ip, FILTER_VALIDATE_IP, $flags);
    }

    /**
     * Checks if a phone number is valid.
     * 匹配格式：
     * 11位手机号码
     * 3-4位区号，7-8位直播号码，1－4位分机号
     * '400' =>  '/^400(-\d{3,4}){2}$/',  
     * 如：12345678901、1234-12345678-1234
     * @param string $phone number to check
     * @param int $lengths 检测一个电话号码的长度是否符合要求
     * @return  boolean
     */
    public static function phone($number, $lengths = NULL)
    {
        if (is_numeric($lengths)) {
            return ctype_digit($number) && strlen($number) == $lengths;
        }else{
            $reg = '/^((\d{11})|(\d{7,8})|(\d{3,4}-\d{7,8})|(\d{3,4}-\d{7,8}-\d{1,4})|(\d{7,8}-\d{1,4})|400(-\d{3,4}){2})$/';
            return (bool)preg_match($reg, $number);
        }
    }

    /**
     * Tests if a string is a valid date string.
     *
     * @param   string   date to check
     * @return  boolean
     */
    public static function date($str)
    {
        return (strtotime($str) !== FALSE);
    }

    /**
     * 字母
     *
     * @param   string   input string
     * @return  boolean

      public static function alpha($str) {
      return ctype_alpha($str);
      } */
    /**
     * 字母，数字
     *
     * @param   string   input string
     * @return  boolean

      public static function alpha_numeric($str){
      return ctype_alnum($str);
      } */
    /**
     * 字母，数字，下划线
     *
     * @param   string   input string
     * @return  boolean

      public static function alpha_dash($str) {
      $regex = '/^[-a-z0-9_]++$/iD';
      return (bool) preg_match($regex, $str);
      } */

    /**
     * Checks whether a string is a valid number (negative and decimal numbers allowed).
     *
     * @param   string   input string
     * @return  boolean
     */
    public static function number($str)
    {
        // Get the decimal point for the current locale
        list($decimal) = array_values(localeconv());
        // A lookahead is used to make sure the string contains at least one digit (before or after the decimal point)
        return (bool) preg_match('/^-?+(?=.*[0-9])[0-9]*+' . preg_quote($decimal) . '?+[0-9]*+$/D', (string) $str);
    }

    public static function digits($str)
    {
        return ctype_digit($str);
    }

    /**
     * Tests if a number is within a range.
     *
     * @param   string   number to check
     * @param   integer  minimum value
     * @param   integer  maximum value
     * @return  boolean
     */
    public static function range($number, $min, $max)
    {
        return ($number >= $min AND $number <= $max);
    }

    public static function max($number, $max)
    {
        return ($number <= $max);
    }

    public static function min($number, $min)
    {
        return ($number >= $min);
    }

    /**
     * Checks if a string is a proper decimal format. Optionally, a specific
     * number of digits can be checked too.
     *
     * @param   string   number to check
     * @param   integer  number of decimal places
     * @param   integer  number of digits
     * @return  boolean
     */
    public static function decimal($str, $places = 2, $digits = NULL)
    {
        if ($digits > 0) {
            // Specific number of digits
            $digits = '{' . ( (int) $digits) . '}';
        } else {
            // Any number of digits
            $digits = '+';
        }
        // Get the decimal point for the current locale
        list($decimal) = array_values(localeconv());
        return (bool) preg_match('/^[+-]?[0-9]' . $digits . preg_quote($decimal) . '[0-9]{' . ( (int) $places) . '}$/D', $str);
    }

    /**
     * Checks if a string is a proper hexadecimal HTML color value. The validation
     * is quite flexible as it does not require an initial "#" and also allows for
     * the short notation using only three instead of six hexadecimal characters.
     *
     * @param   string   input string
     * @return  boolean
     */
    public static function color($str)
    {
        return (bool) preg_match('/^#?+[0-9a-f]{3}(?:[0-9a-f]{3})?$/iD', $str);
    }
    
    public static function inarray($str,$arr)
    { 
        return (bool) in_array($str,$arr);
    }

}

// End Valid
