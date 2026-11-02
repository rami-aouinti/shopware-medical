<?php declare(strict_types=1);
namespace MedicalProductManagement\Service;
final class ProductCustomFieldValidator
{
    /** @param array<string, mixed> $customFields
     * @return list<array{field:string,message:string,code:string}>
     */
    public function validate(array $customFields): array
    {
        $errors = [];
        if (array_key_exists('package_quantity', $customFields) && $customFields['package_quantity'] !== null) {
            $value = $customFields['package_quantity'];
            if (!is_int($value) || $value <= 0) {
                $errors[] = ['field'=>'package_quantity','message'=>'Units per package must be a positive integer.','code'=>'MEDICAL_PACKAGE_QUANTITY_INVALID'];
            }
        }
        if (array_key_exists('package_unit', $customFields) && $customFields['package_unit'] !== null) {
            $value = $customFields['package_unit'];
            if (!is_string($value) || trim($value) === '' || mb_strlen(trim($value)) > 32) {
                $errors[] = ['field'=>'package_unit','message'=>'Package unit must contain between 1 and 32 characters.','code'=>'MEDICAL_PACKAGE_UNIT_INVALID'];
            }
        }
        if (array_key_exists('minimum_order_quantity', $customFields) && $customFields['minimum_order_quantity'] !== null) {
            $value = $customFields['minimum_order_quantity'];
            if ((!is_int($value) && !is_float($value)) || !is_finite((float) $value) || $value <= 0) {
                $errors[] = ['field'=>'minimum_order_quantity','message'=>'Minimum order quantity must be a finite number greater than zero.','code'=>'MEDICAL_MINIMUM_ORDER_QUANTITY_INVALID'];
            }
        }
        return $errors;
    }
}