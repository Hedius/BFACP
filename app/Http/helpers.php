<?php

if (! function_exists('divide')) {

    /**
     * Function to divide two numbers together and catch
     * divide by zero exception.
     *
     * @param int $num1
     * @param int $num2
     * @param int $precision
     *
     * @return float
     */

    function divide($num1 = 0, $num2 = 0, $precision = 2)
    {
        try {
            return round(($num1 / $num2), $precision);
        } catch (Exception $e) {
            return -1;
        }
    }
}

if (! function_exists('percent')) {

    /**
     * Function to get percentage of two numbers together and
     * catch divide by zero exception.
     *
     * @param int $num1
     * @param int $num2
     * @param int $precision
     *
     * @return float
     */
    function percent($num1 = 0, $num2 = 0, $precision = 2)
    {
        try {
            return round(($num1 / $num2) * 100, $precision);
        } catch (Exception $e) {
            return -1;
        }
    }
}


