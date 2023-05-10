# `eip-712`

[![PHP](https://github.com/sleepfinance/eip712/actions/workflows/tests.yml/badge.svg)](https://github.com/sleepfinance/eip712/actions/workflows/php.yml)
[![codecov](https://codecov.io/gh/sleepfinance/eip712/branch/main/graph/badge.svg?token=CPJCHXZTN2)](https://codecov.io/gh/sleepfinance/eip712)
[![Licensed under the MIT License](https://img.shields.io/badge/License-MIT-blue.svg)](https://github.com/sleepfinance/eip712/blob/master/LICENSE)

This is a library laravel / php to help generata an EIP712 Hash for signing and verifying [EIP-712](https://eips.ethereum.org/EIPS/eip-712) based messages. It is fully written for php 8.0, and is currently only compatible with the latest specification of EIP-712 ([eth_signTypedData_v4](https://docs.metamask.io/guide/signing-data.html#sign-typed-data-v4)).

https://eips.ethereum.org/EIPS/eip-712

Note that this library currently does not handle the signing itself. For this, you can use something like [`kornrunner\Secp256k1`]('https://github.com/kornrunner/php-secp256k1). For some examples please see below.

## Installation

```
$ composer require sleepfinance/eip712
```

### Getting Started

First, define your typed data as a JSON string or PHP array, according to the JSON schema specified by EIP-712. For example:

```json
{
	"types": {
		"EIP712Domain": [
			{ "name": "name", "type": "string" },
			{ "name": "version", "type": "string" },
			{ "name": "chainId", "type": "uint256" },
			{ "name": "verifyingContract", "type": "address" }
		],
		"Person": [
			{ "name": "name", "type": "string" },
			{ "name": "wallet", "type": "address" }
		],
		"Mail": [
			{ "name": "from", "type": "Person" },
			{ "name": "to", "type": "Person" },
			{ "name": "contents", "type": "string" }
		]
	},
	"primaryType": "Mail",
	"domain": {
		"name": "Ether Mail",
		"version": "1",
		"chainId": 1,
		"verifyingContract": "0xCcCCccccCCCCcCCCCCCcCcCccCcCCCcCcccccccC"
	},
	"message": {
		"from": {
			"name": "Cow",
			"wallet": "0xCD2a3d9F938E13CD947Ec05AbC7FE734Df8DD826"
		},
		"to": {
			"name": "Bob",
			"wallet": "0xbBbBBBBbbBBBbbbBbbBbbbbBBbBbbbbBbBbbBBbB"
		},
		"contents": "Hello, Bob!"
	}
}
```

### Example

```php
use SleepFinance\Eip712;
use kornrunner\Secp256k1;


// import the EIP-712 json fil;
$mailTypedJson = file_get_contents('path/to/your-json-file.json');

$eip712 = new Eip712($mailTypedJson);

$hashToSign = $eip712->hashTypedDataV4();

//signing with account 0xf3022686aa43B98362c989659561b9B348977897
$pvk="0x2870b52bfe2401ac0eed7f62fd4bd03eb579c61369c6b4dd6931fb4a57d71b09";

$secp256k1 = new Secp256k1();

$signed   = $secp256k1->sign($hashToSign, $pvk);

//Hex
$signatureToSubmit = $signed->toHex();

```
It allows arrays

```php
use SleepFinance\Eip712;
use kornrunner\Secp256k1;



$mailTypedData = [
    "types" => [
        "EIP712Domain" => [
            [
                "name" => "name",
                "type" => "string"
            ],
            [
                "name" => "version",
                "type" => "string"
            ],
            [
                "name" => "chainId",
                "type" => "uint256"
            ],
            [
                "name" => "verifyingContract",
                "type" => "address"
            ]
        ],
        "Person" => [
            [
                "name" => "name",
                "type" => "string"
            ],
            [
                "name" => "wallet",
                "type" => "address"
            ]
        ],
        "Mail" => [
            [
                "name" => "from",
                "type" => "Person"
            ],
            [
                "name" => "to",
                "type" => "Person"
            ],
            [
                "name" => "contents",
                "type" => "string"
            ]
        ]
    ],
    "primaryType" => "Mail",
    "domain" => [
        "name" => "Ether Mail",
        "version" => "1",
        "chainId" => 1,
        "verifyingContract" => "0xCcCCccccCCCCcCCCCCCcCcCccCcCCCcCcccccccC"
    ],
    "message" => [
        "from" => [
            "name" => "Cow",
            "wallet" => "0xCD2a3d9F938E13CD947Ec05AbC7FE734Df8DD826"
        ],
        "to" => [
            "name" => "Bob",
            "wallet" => "0xbBbBBBBbbBBBbbbBbbBbbbbBBbBbbbbBbBbbBBbB"
        ],
        "contents" => "Hello, Bob!"
    ]
];
$eip712 = new Eip712($mailTypedData);

$hashToSign = $eip712->hashTypedDataV4();

//signing with account 0xf3022686aa43B98362c989659561b9B348977897
$pvk="0x2870b52bfe2401ac0eed7f62fd4bd03eb579c61369c6b4dd6931fb4a57d71b09";

$secp256k1 = new Secp256k1();

$signed   = $secp256k1->sign($hashToSign, $pvk);

//Hex
$signatureToSubmit = $signed->toHex();
```
### You many need to recompose signature!!

Sometimes ``` $signatureToSubmit = $signed->toHex();```  hex doesnt work. You may need to recompose signature

```php
$signed   = $secp256k1->sign($hashToSign, $pvk);

//$signatureToSubmit = $signed->toHex();

$r   = $this->hexup(gmp_strval($signed->getR(), 16));

$s   = $this->hexup(gmp_strval($signed->getS(), 16));

$v   = dechex((int) $signed->getRecoveryParam() + 27);

$signatureToSubmit = "0x$r$s$v";

----

function hexup(string $value): string
{
    return strlen($value) % 2 === 0 ? $value : "0{$value}";
}
```

### Encoder Functions

Here is a brief description of the functions available in the encoder. For more detailed examples, you can refer to [`src/tests`](https://github.com/sleepfinance/eip712/blob/master/tests).

#### `Encoder::encode(SleepFinance\Eip712 $typedData)`

This function will return the full EIP-191 encoded message to be signed hashed using Keccak256.

```php
use SleepFinance\Encoder;


$mailTypedJson = file_get_contents('path/to/your-json-file.json');

$eip712 = new Eip712($mailTypedJson);

$hashToSign = Encoder::encode($eip712);

dump($hashToSign);
//be609aee343fb3c4b28e1df9e632fca64fcfaede20f02e86244efddf30957bd2
```

#### `Encoder::getStructHash(SleepFinance\Eip712 $typedData, $type, $data)`

This function returns a Keccak-256 hash for a single struct type (e.g. EIP712Domain, Person or Mail).

```php
use SleepFinance\Encoder;


$mailTypedJson = file_get_contents('path/to/your-json-file.json');

$eip712 = new Eip712($mailTypedJson);

$hash = Encoder::getStructHash($eip712, "EIP712Domain", $eip712->domain);

dump($hash);
 // f2cee375fa42b42143804025fc449deafd50cc031ca257e0b194a650a912090f
```

#### `Encoder::encodeData(Eip712 $typedData, string $type, data)`

This function returns the raw ABI encoded data for the struct type.

```php
use SleepFinance\Encoder;


$mailTypedJson = file_get_contents('path/to/your-json-file.json');

$eip712 = new Eip712($mailTypedJson);

$abiEncodedData = Encoder::encodeData($eip712, "EIP712Domain", $eip712->domain);

dump($abiEncodedData);
 // 8b73c3c69bb8fe3d512ecc4cf759cc79239f7b179b0ffacaa9a75d522b39400fc70ef06638535b4881fafcac8287e210e3769ff1a8e91f1b95d6246e61e4d3c6c89efdaa54c0f20c7adf612882df0950f5a951637e0307cdcb4c672f298b8bc60000000000000000000000000000000000000000000000000000000000000001000000000000000000000000cccccccccccccccccccccccccccccccccccccccc
```

#### `Encoder::getTypeHash(Eip712 $typedData, string $type)`

This function returns the type hash for a struct type. This is the same as `Keccak256(EIP712Domain(string name,string version,uint256 chainId,address verifyingContract))`, with support optional sub-types automatically included too.

```php
use SleepFinance\Encoder;


$mailTypedJson = file_get_contents('path/to/your-json-file.json');

$eip712 = new Eip712($mailTypedJson);

$typeHash = Encoder::getTypeHash($eip712, "EIP712Domain");

dump($typeHash);
 // 8b73c3c69bb8fe3d512ecc4cf759cc79239f7b179b0ffacaa9a75d522b39400f
```


#### `Encoder::encodeType(Eip712 $typedData, string $type)`

This function returns the type string before hashing it, e.g. `EIP712Domain(string name,string version,uint256 chainId,address verifyingContract)`, with optional sub-types automatically included too.


```php
use SleepFinance\Encoder;


$mailTypedJson = file_get_contents('path/to/your-json-file.json');

$eip712 = new Eip712($mailTypedJson);

$encodedType = Encoder::encodeType($eip712, "EIP712Domain");

dump($encodedType);
 // EIP712Domain(string name,string version,uint256 chainId,address verifyingContract)
```

### Non-standard domains are currently not tested!

It's possible to use a custom domain format, like from the CIP-23 specification, if you want to use a custom implementation of EIP-712.

To do this, Intialize EIP172 with your custom domain; schema validation will be skipped!
```php
$eip712 = new Eip712($mailTypedJson, 'MyCustomDomain');
```

[twitter]('https://twitter.com/sleeprotocol')
[Telegram]('https://t.me/sleepfinance')

## see you on the flipside!


