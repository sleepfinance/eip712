<?php

namespace Tests;
use SleepFinance\Eip712;
use SleepFinance\Encoder;
use Tests\TestCase;
class EncoderTest extends TestCase
{
    public function testGetDependencies(): void
    {
        $mailTypedData = file_get_contents(__DIR__ . '/__fixtures__/typed-data-1.json');
        $approvalTypedData = file_get_contents(__DIR__ . '/__fixtures__/typed-data-2.json');
        $eip712 = new Eip712($mailTypedData);
        $approvalEip712 = new Eip712($approvalTypedData);
        $this->assertSame(Encoder::getDependencies($eip712, 'EIP712Domain'), ['EIP712Domain']);
        $this->assertSame(Encoder::getDependencies($eip712, 'Person'), ['Person']);
        $this->assertSame(Encoder::getDependencies($eip712, 'Mail'), ['Mail', 'Person']);
        $this->assertSame(Encoder::getDependencies($approvalEip712, 'Transaction'), ['Transaction']);
        $this->assertSame(Encoder::getDependencies($approvalEip712, 'TransactionApproval'), ['TransactionApproval', 'Transaction']);
    }


    public function testEncodeType(): void
    {
        $mailTypedData = file_get_contents(__DIR__ . '/__fixtures__/typed-data-1.json');
        $approvalTypedData = file_get_contents(__DIR__ . '/__fixtures__/typed-data-2.json');
        $eip712 = new Eip712($mailTypedData);
        $approvalEip712 = new Eip712($approvalTypedData);
        $this->assertSame(Encoder::encodeType($eip712, 'EIP712Domain'), 'EIP712Domain(string name,string version,uint256 chainId,address verifyingContract)');
        $this->assertSame(Encoder::encodeType($eip712, 'Person'), 'Person(string name,address wallet)');
        $this->assertSame(Encoder::encodeType($eip712, 'Mail'), 'Mail(Person from,Person to,string contents)Person(string name,address wallet)');
        $this->assertSame(Encoder::encodeType($approvalEip712, 'EIP712Domain'), 'EIP712Domain(string name,string version,uint256 chainId,address verifyingContract,bytes32 salt)');
        $this->assertSame(Encoder::encodeType($approvalEip712, 'Transaction'), 'Transaction(address to,uint256 amount,bytes data,uint256 nonce)');
        $this->assertSame(Encoder::encodeType($approvalEip712, 'TransactionApproval'), 'TransactionApproval(address owner,Transaction transaction)Transaction(address to,uint256 amount,bytes data,uint256 nonce)');
    }


    public function testGetTypeHash(): void
    {
        $mailTypedData = file_get_contents(__DIR__ . '/__fixtures__/typed-data-1.json');
        $approvalTypedData = file_get_contents(__DIR__ . '/__fixtures__/typed-data-2.json');
        $arrayTypedData = file_get_contents(__DIR__ . '/__fixtures__/typed-data-3.json');
        $eip712 = new Eip712($mailTypedData);
        $approvalEip712 = new Eip712($approvalTypedData);
        $arrayEip712 = new Eip712($arrayTypedData);
        $this->assertSame(Encoder::getTypeHash($eip712, 'EIP712Domain'), '8b73c3c69bb8fe3d512ecc4cf759cc79239f7b179b0ffacaa9a75d522b39400f');
        $this->assertSame(Encoder::getTypeHash($eip712, 'Person'), 'b9d8c78acf9b987311de6c7b45bb6a9c8e1bf361fa7fd3467a2163f994c79500');
        $this->assertSame(Encoder::getTypeHash($eip712, 'Mail'), 'a0cedeb2dc280ba39b857546d74f5549c3a1d7bdc2dd96bf881f76108e23dac2');
        $this->assertSame(Encoder::getTypeHash($approvalEip712, 'EIP712Domain'), 'd87cd6ef79d4e2b95e15ce8abf732db51ec771f1ca2edccf22a46c729ac56472');
        $this->assertSame(Encoder::getTypeHash($approvalEip712, 'Transaction'), 'a826c254899945d99ae513c9f1275b904f19492f4438f3d8364fa98e70fbf233');
        $this->assertSame(Encoder::getTypeHash($approvalEip712, 'TransactionApproval'), '5b360b7b2cc780b6a0687ac409805af3219ef7d9dcc865669e39a1dc7394ffc5');
        $this->assertSame(Encoder::getTypeHash($arrayEip712, 'EIP712Domain'), '8b73c3c69bb8fe3d512ecc4cf759cc79239f7b179b0ffacaa9a75d522b39400f');
        $this->assertSame(Encoder::getTypeHash($arrayEip712, 'Person'), 'b9d8c78acf9b987311de6c7b45bb6a9c8e1bf361fa7fd3467a2163f994c79500');
        $this->assertSame(Encoder::getTypeHash($arrayEip712, 'Mail'), 'c81112a69b6596b8bc0678e67d97fbf9bed619811fc781419323ec02d1c7290d');
    }


    public function testEncodeData(): void
    {
        $mailTypedData = file_get_contents(__DIR__ . '/__fixtures__/typed-data-1.json');
        $approvalTypedData = file_get_contents(__DIR__ . '/__fixtures__/typed-data-2.json');
        $arrayTypedData = file_get_contents(__DIR__ . '/__fixtures__/typed-data-3.json');
        $eip712 = new Eip712($mailTypedData);
        $approvalEip712 = new Eip712($approvalTypedData);
        $arrayEip712 = new Eip712($arrayTypedData);

        $this->assertSame(strtolower(Encoder::encodeData($eip712, 'EIP712Domain', $eip712->domain)), '8b73c3c69bb8fe3d512ecc4cf759cc79239f7b179b0ffacaa9a75d522b39400fc70ef06638535b4881fafcac8287e210e3769ff1a8e91f1b95d6246e61e4d3c6c89efdaa54c0f20c7adf612882df0950f5a951637e0307cdcb4c672f298b8bc60000000000000000000000000000000000000000000000000000000000000001000000000000000000000000cccccccccccccccccccccccccccccccccccccccc');
        $this->assertSame(strtolower(Encoder::encodeData($eip712, 'Person', $eip712->message['from'])), 'b9d8c78acf9b987311de6c7b45bb6a9c8e1bf361fa7fd3467a2163f994c795008c1d2bd5348394761719da11ec67eedae9502d137e8940fee8ecd6f641ee1648000000000000000000000000cd2a3d9f938e13cd947ec05abc7fe734df8dd826');
        $this->assertSame(Encoder::encodeData($eip712, 'Person', $eip712->message['to']), 'b9d8c78acf9b987311de6c7b45bb6a9c8e1bf361fa7fd3467a2163f994c7950028cac318a86c8a0a6a9156c2dba2c8c2363677ba0514ef616592d81557e679b6000000000000000000000000bBbBBBBbbBBBbbbBbbBbbbbBBbBbbbbBbBbbBBbB');
        $this->assertSame(strtolower(Encoder::encodeData($eip712, 'Mail', $eip712->message)), 'a0cedeb2dc280ba39b857546d74f5549c3a1d7bdc2dd96bf881f76108e23dac2fc71e5fa27ff56c350aa531bc129ebdf613b772b6604664f5d8dbe21b85eb0c8cd54f074a4af31b4411ff6a60c9719dbd559c221c8ac3492d9d872b041d703d1b5aadf3154a261abdd9086fc627b61efca26ae5702701d05cd2305f7c52a2fc8');
        $this->assertSame(strtolower(Encoder::encodeData($approvalEip712, 'EIP712Domain', $approvalEip712->domain)), 'd87cd6ef79d4e2b95e15ce8abf732db51ec771f1ca2edccf22a46c729ac56472d210ccb0bd8574cfdb6efd17ae4e6ab527687a29dcf03060d4a41b9b56d0b637c89efdaa54c0f20c7adf612882df0950f5a951637e0307cdcb4c672f298b8bc60000000000000000000000000000000000000000000000000000000000000001000000000000000000000000aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa1dbbd6c8d75f4b446bcb44cee3ba5da8120e056d4d2f817213df8703ef065ed3');
        $this->assertSame(strtolower(Encoder::encodeData($approvalEip712, 'Transaction', $approvalEip712->message['transaction'])), 'a826c254899945d99ae513c9f1275b904f19492f4438f3d8364fa98e70fbf2330000000000000000000000004bbeeb066ed09b7aed07bf39eee0460dfa2615200000000000000000000000000000000000000000000000000de0b6b3a7640000c5d2460186f7233c927e7db2dcc703c0e500b653ca82273b7bfad8045d85a4700000000000000000000000000000000000000000000000000000000000000001');
        $this->assertSame(strtolower(Encoder::encodeData($approvalEip712, 'TransactionApproval', $approvalEip712->message)), '5b360b7b2cc780b6a0687ac409805af3219ef7d9dcc865669e39a1dc7394ffc5000000000000000000000000bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb9e7ba42b4ace63ae7d8ee163d5e642a085b32c2553717dcb37974e83fad289d0');
        $this->assertSame(strtolower(Encoder::encodeData($arrayEip712, 'Mail', $arrayEip712->message)), 'c81112a69b6596b8bc0678e67d97fbf9bed619811fc781419323ec02d1c7290dafd2599280d009dcb3e261f4bccebec901d67c3f54b56d49bf8327359fc69cd7392bb8ab5338a9075ce8fec1b431e334007d4de1e5e83201ca35762e24428e24b7c4150525d88db452c5f08f93f4593daa458ab6280b012532183aed3a8e4a01');
    }




    public function testGetStructHash(): void
    {
        $mailTypedData = file_get_contents(__DIR__ . '/__fixtures__/typed-data-1.json');
        $approvalTypedData = file_get_contents(__DIR__ . '/__fixtures__/typed-data-2.json');
        $arrayTypedData = file_get_contents(__DIR__ . '/__fixtures__/typed-data-3.json');
        $eip712 = new Eip712($mailTypedData);
        $approvalEip712 = new Eip712($approvalTypedData);
        $arrayEip712 = new Eip712($arrayTypedData);
        $this->assertSame(Encoder::getStructHash($eip712, 'EIP712Domain', $eip712->domain), 'f2cee375fa42b42143804025fc449deafd50cc031ca257e0b194a650a912090f');
        $this->assertSame(Encoder::getStructHash($eip712, 'Person', $eip712->message['from']), 'fc71e5fa27ff56c350aa531bc129ebdf613b772b6604664f5d8dbe21b85eb0c8');
        $this->assertSame(Encoder::getStructHash($eip712, 'Mail', $eip712->message), 'c52c0ee5d84264471806290a3f2c4cecfc5490626bf912d01f240d7a274b371e');
        $this->assertSame(Encoder::getStructHash($approvalEip712, 'EIP712Domain', $approvalEip712->domain), '67083568259b4a947b02ce4dca4cc91f1e7f01d109c8805668755be5ab5adbb9');
        $this->assertSame(Encoder::getStructHash($approvalEip712, 'Transaction', $approvalEip712->message['transaction']), '9e7ba42b4ace63ae7d8ee163d5e642a085b32c2553717dcb37974e83fad289d0');
        $this->assertSame(Encoder::getStructHash($approvalEip712, 'TransactionApproval', $approvalEip712->message), '309886ad75ec7c2c6a69bffa2669bad00e3b1e0a85221eff4e8926a2f8ff5077');
    }


    public function testEncode(): void
    {
        $mailTypedData = file_get_contents(__DIR__ . '/__fixtures__/typed-data-1.json');
        $approvalTypedData = file_get_contents(__DIR__ . '/__fixtures__/typed-data-2.json');
        $arrayTypedData = file_get_contents(__DIR__ . '/__fixtures__/typed-data-3.json');
        $eip712 = new Eip712($mailTypedData);
        $approvalEip712 = new Eip712($approvalTypedData);
        $arrayEip712 = new Eip712($arrayTypedData);
        $this->assertSame(Encoder::encode($eip712), 'be609aee343fb3c4b28e1df9e632fca64fcfaede20f02e86244efddf30957bd2');
        $this->assertSame(Encoder::encode($approvalEip712), 'ee0cdea747f4a81355be92dbf30e209dbd2954a82d5a82482b7c7800089c7f57');
        $this->assertSame(Encoder::encode($arrayEip712), 'c6f6c8028eadb17bc5c9e2ea2f738e92e49cfa627d19896c250fd2eac653e4e0');
    }
}
