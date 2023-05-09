<?php

namespace SleepFinance;

use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;
use Illuminate\Support\Facades\Validator;

class Eip712  extends Fluent
{
    public static $TYPE_REGEX = "/^\w+/";
    public static $ARRAY_REGEX = "/^(.*)\[([0-9]*?)]$/";
    public static $BYTES_REGEX = "/^bytes([0-9]{1,2})$/";
    public static $NUMBER_REGEX = "/^u?int([0-9]{0,3})$/";
    public static $STATIC_TYPES = ['address', 'bool', 'bytes', 'string'];

    public function __construct(array|string $attributes, public string $eip712Domain = 'EIP712Domain')
    {
        $attrs = is_string($attributes) ? json_decode($attributes, true) : $attributes;
        $validator = Validator::make(
            $attrs,
            [
                'types' => 'required|array',
                'types.' . $this->eip712Domain => 'required|array',
                "types.{$this->eip712Domain}.name" => 'nullable|string',
                "types.{$this->eip712Domain}.version" => 'nullable|string',
                "types.{$this->eip712Domain}.chainId" => 'nullable|numeric',
                "types.{$this->eip712Domain}.verifyingContract" => 'nullable|string|regex:/^0x[0-9a-z]{40}$/i',
                "types.{$this->eip712Domain}.salt" => 'nullable|string|regex:/^0x[0-9a-z]{64}$/i',
                'primaryType' => 'required|string',
                'domainType' => 'nullable|string',
                'domain' => 'required|array',
                'message' => 'required|array',
            ]
        );
        if ($validator->fails() && $this->eip712Domain == 'EIP712Domain') throw new \Exception('Invalid Typed Data');
        parent::__construct($attrs);
    }


    function getType($type): Collection
    {
        return collect($this->types[$type])->map(fn ($t) => (object)$t);
    }

    function hashTypedDataV4()
    {
        return Encoder::encode($this);
    }

    function domainSeparatorV4()
    {
        return Encoder::getStructHash($this, $this->eip712Domain, $this->domain);
    }
}
