<?php declare(strict_types=1);

namespace MedicalInventory\Service;

use InvalidArgumentException;

class WarehouseValidator
{
    /**
     * @return array{code: string, name: string, countryCode: string}
     */
    public function validate(string $code, string $name, string $countryCode): array
    {
        $code = strtoupper(trim($code));
        $name = trim($name);
        $countryCode = strtoupper(trim($countryCode));

        if (!preg_match('/^[A-Z0-9][A-Z0-9_-]{1,63}$/D', $code)) {
            throw new InvalidArgumentException(
                'Warehouse code must contain 2 to 64 characters: A-Z, 0-9, underscore or hyphen.'
            );
        }

        if ($name === '' || mb_strlen($name) > 255) {
            throw new InvalidArgumentException('Warehouse name is required and must not exceed 255 characters.');
        }

        if (!preg_match('/^[A-Z]{2}$/D', $countryCode)) {
            throw new InvalidArgumentException('Country code must contain exactly two letters (for example DE).');
        }

        return [
            'code' => $code,
            'name' => $name,
            'countryCode' => $countryCode,
        ];
    }
}
