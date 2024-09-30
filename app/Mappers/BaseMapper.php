<?php

namespace App\Mappers;

abstract class BaseMapper
{
    /**
     * Return all the names of the valid values
     *
     * @param string $locale
     * @return array
     */
    abstract static public function getAll(string $locale = 'en'): array;

    /**
     * Get the name of a specific value or id
     *
     * @param integer $value
     * @param string $locale
     * @return string
     */
    static public function getText(int $value, string $locale = 'en'): string
    {
        return self::getAll($locale)[$value] ?? "";
    }

    /**
     * Get all valid values of this mapper
     *
     * @return array
     */
    abstract static public function getValidValues(): array;

    /**
     * Mainly used in factories for testing.
     * Get a random value of the valid values of this mapper.
     *
     * @return void
     */
    static public function getRandomValue(){
        $valid_values = self::getValidValues();
        return $valid_values[array_rand($valid_values)];
    }
}