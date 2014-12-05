<?php
/**
 * Created by PhpStorm.
 * User: Александр
 * Date: 11.04.14
 * Time: 15:09
 */

class ServiceException extends Exception { }

class Service
{
    /**
     * Формирование строки плейсхолдеров для SQL-запросов
     *
     * @param array $data
     * @param string $type
     * @return string
     */
    public static function createPrepareFields (array &$data, $type = 'insert')
    {
        $string = '';
        if ($type == 'insert')
        {
            if (isset($data[0]))
            {
                foreach ($data AS $index => $value)
                {
                    $tmp = '';
                    foreach ($value AS $key => $v)
                    {
                        $tmp .= ":$key$index,";
                        $data_tmp[$key . $index] = $v;
                    }
                    $string .= '(' . trim($tmp, ',') . '),';
                }
                $string = trim($string, ',');
                $data = $data_tmp;
                unset($data_tmp);
                unset($tmp);
            }
            else
            {
                foreach ($data AS $key => $value)
                {
                    $string .= ":$key,";
                }
                $string = '(' . trim($string, ',') . ')';
            }
        }
        else if ($type == 'inline')
        {
            if (isset($data[0]))
            {
                foreach ($data AS $index => $value)
                {
                    $tmp = '';
                    foreach ($value AS $key => $v)
                    {
                        $tmp .= "$v,";
                    }
                    $string .= '(' . trim($tmp, ',') . '),';
                }
                $string = trim($string, ',');
                unset($tmp);
            }
            else
            {
                foreach ($data AS $key => $value)
                {
                    $string .= "$value,";
                }
                $string = '(' . trim($string, ',') . ')';
            }
        }
        else
        {
            foreach ($data AS $key => $value)
            {
                if ($key !== 'i')
                $string .= "$key = :$key,";
            }
            $string = trim($string, ',');
        }
        return $string;
    }

    /**
     * Формирование списка полей для SQL-запросов
     *
     * @param array $data
     * @return string
     */
    public static function createSelectString (array $data)
    {
        if (isset($data[0]))
            $data = $data[0];

        return implode(',', array_keys($data));
    }

    /**
     * Разбор SQL-шаблона
     *
     * @param $request
     * @param array $data
     * @return mixed
     * @throws Exception
     */
    public static function sql ($request, &$data)
    {
        try
        {
            if (stripos($request, '[fields]'))
            {
                $request = str_replace('[fields]', self::createSelectString($data), $request);
            }
            if (stripos($request, '[expression]'))
            {
                if (stripos($request, 'EXCEPT') !== false)
                {
                    $expression = self::createPrepareFields($data, 'inline');
                }
                elseif (stripos($request, 'INSERT') !== false)
                {
                    $expression = self::createPrepareFields($data);
                }
                elseif (stripos($request, 'UPDATE') !== false)
                {
                    $data2 = $data;
                    foreach($data AS $key => $value)
                    if (strpos($request, ':' . $key) !== false)
                    {
                        unset($data2[$key]);
                    }
                    $expression = self::createPrepareFields($data2, 'update');
                }

                if (isset($expression))
                {
                    $request = str_replace('[expression]', $expression, $request);
                }
            }

            return $request;
        }
        catch (Exception $e)
        {
            throw $e;
        }
    }

}

?>