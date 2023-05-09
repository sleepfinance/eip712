<?php

namespace SleepFinance;
use kornrunner\Keccak;
use SWeb3\ABI;

class Encoder
{
    /**
     * Get the dependencies of a struct type. If a struct has the same dependency multiple times, it's only included once
     * in the resulting array.
     */
    public static function  getDependencies(
        Eip712 $eip712,
        string $type,
        array $dependencies = []
    ): array {
        preg_match(Eip712::$TYPE_REGEX, $type, $matches);
        $actualType = $matches[0];

        if (in_array($actualType, $dependencies)) {
            return $dependencies;
        }

        if (!isset($eip712->types[$actualType])) {
            return $dependencies;
        }
        $typeList = $eip712->getType($actualType)->reduce(
            function ($previous, $type) use ($eip712) {
                $deps = call_user_func_array([static::class, 'getDependencies'], [$eip712, $type->type, $previous]);
                return [
                    ...$previous,
                    ...collect($deps)->filter(fn ($dependency) => !in_array($dependency, $previous))->all(),
                ];
            },
            []
        );
        return [
            $actualType,
            ...$typeList
        ];
    }

    /**
     * Encode a type to a string. All dependant types are alphabetically sorted.
     *
     * @param {Eip712} Eip712
     * @param {string} type
     * @param {Options} [options]
     * @return {string}
     */
    public static function encodeType(Eip712 $eip712, string $type): string
    {
        $dependencies = static::getDependencies($eip712, $type);
        $primary = array_shift($dependencies);
        sort($dependencies);
        $types = [$primary, ...$dependencies];
        return collect($types)
            ->map(function ($dependency) use ($eip712) {
                $list = $eip712->getType($dependency)->map(fn ($type) => "{$type->type} {$type->name}")->implode(',');
                return "$dependency($list)";
            })->implode('');
    }

    /**
     * Get a type string as hash.
     */
    public static function getTypeHash(Eip712 $eip712, string $type): string
    {
        return Keccak::hash(static::encodeType($eip712, $type), 256);
    }

    /**
     * Encodes a single value to an ABI serialisable string, number or Buffer. Returns the data as tuple, which consists of
     * an array of ABI compatible types, and an array of corresponding values.
     */
    public static function encodeValue(
        Eip712 $eip712,
        string $type,
        $data
    ): array {
        if (preg_match(Eip712::$ARRAY_REGEX, $type, $matches) === 1) {
            $arrayType = $matches[1];
            $length = isset($matches[2]) ? (int)$matches[2] : null;
            if (!is_array($data)) {
                throw new \Exception('Cannot encode data: value is not of array type');
            }

            if ($length && count($data) !== $length) {
                throw new  \Exception("Cannot encode data: expected length of {$length}, but got" . count($data));
            }
            $encodedData = collect($data)->map(fn ($item) => static::encodeValue($eip712, $arrayType, $item));
            $types = $encodedData->map(fn ($item) => $item[0])->all();
            $values = $encodedData->map(fn ($item) => $item[1])->all();
            return ['bytes32', Keccak::hash(hex2bin(ABI::EncodeGroup($types, $values)), 256)];
        }

        if (isset($eip712->types[$type])) {
            return ['bytes32', static::getStructHash($eip712, $type, $data)];
        }

        // Strings and arbitrary byte arrays are hashed to bytes32
        if ($type === 'string' || $type === 'bytes') {
            return ['bytes32', Keccak::hash($data, 256)];
        }

        return [$type, $data];
    }

    /**
     * Encode the data to an ABI encoded Buffer. The data should be a key -> value object with all the required values. All
     * dependant types are automatically encoded.
     */
    public static function encodeData(
        Eip712 $eip712,
        string $type,
        $data,
    ): string {
        [$types, $values] = $eip712->getType($type)->reduce(
            function ($memo, $field) use ($data, $eip712) {
                [$types, $values] = $memo;
                if (!isset($data[$field->name]) || $data[$field->name] === null) {
                    throw new \Exception("Cannot encode data: missing data for '$field->name'");
                }
                $value = $data[$field->name];
                [$type, $encodedValue] = static::encodeValue($eip712, $field->type, $value);

                return [
                    [...$types, $type],
                    [...$values, $encodedValue]
                ];
            },
            [['bytes32'], [static::getTypeHash($eip712, $type)]]
        );

        return ABI::EncodeGroup($types, $values);
    }

    /**
     * Get encoded data as a hash. The data should be a key -> value object with all the required values. All dependant
     * types are automatically encoded.
     */
    public static function getStructHash(
        Eip712 $eip712,
        string $type,
        $data,
    ): string {
        return Keccak::hash(hex2bin(static::encodeData($eip712, $type, $data)), 256);
    }


    /**
     * Get the EIP-191 encoded message to sign, from the Eip712 object. If `hash` is enabled, the message will be hashed
     * with Keccak256.
     */
    public static function encode(
        Eip712 $eip712
    ): string {
        $domainHash = static::getStructHash($eip712, $eip712->eip712Domain, $eip712->domain);
        $messageHash = static::getStructHash($eip712, $eip712->primaryType, $eip712->message);
        return Keccak::hash(hex2bin('1901' . $domainHash . $messageHash), 256);
    }
}
