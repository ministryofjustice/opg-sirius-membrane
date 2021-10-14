<?php

namespace Application\Service;

class AbstractService
{
    /**
     * @param string $string
     * @return string
     */
    protected function canonicalize(string $string)
    {
        if ($string !== null) {
            $string = mb_convert_case(
                $string,
                MB_CASE_LOWER,
                mb_detect_encoding($string)
            );
        }

        return $string;
    }
}
