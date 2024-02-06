<?php

require_once 'vendor/autoload.php';

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Script\Interpreter\Interpreter;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Address\ScriptHashAddress;
use BitWasp\Bitcoin\Address\AddressCreator;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Transaction\Factory\Signer;
use BitWasp\Bitcoin\Transaction\OutPoint;

Bitcoin::setNetwork(NetworkFactory::bitcoinTestnet());
$network = Bitcoin::getNetwork();

$random = new Random();
$privKeyFactory = new PrivateKeyFactory();
$privateKey = $privKeyFactory->generateCompressed($random);
$publicKey = $privateKey->getPublicKey();


function redeem_hex($preimage) {
    $hash = hash('sha256', $preimage);
    $hashBuffer = Buffer::hex($hash);
    $redeemScript = ScriptFactory::sequence([Opcodes::OP_SHA256, $hashBuffer, Opcodes::OP_EQUAL]);
    return $redeemScript;
}

function getAddress($redeemScript) {
    global $network;
    $p2sh = new ScriptHashAddress($redeemScript->getScriptHash());
    // $p2shAddress = $p2sh->getAddress($network);
    return $p2sh;
}

function createfundingtxn($address, $amount, $utxoTxId) {
    global $privateKey;

    $fundingTx = TransactionFactory::build()
    ->input($utxoTxId, 0)
    ->payToAddress($amount, $address)
    ->get();


    return $fundingTx;
}

function createSpendingTxn($fundingTx, $privateKey, $address, $amount) {

    // $fundingTxId = Buffer::hex($fundingTx->getTxId()->getHex());
    $fundingTxId = $fundingTx->getTxId();

    $outpoint = new OutPoint($fundingTxId, 0);
    // die(var_dump($outpoint));
    $spendingTx = TransactionFactory::build()
        ->spendOutPoint($outpoint)
        ->payToAddress($amount, $address)
        ->get();

    // Create a transaction signature
    // $txSigner = new Signer($spendingTx, Bitcoin::getEcAdapter());
    // $txSigner->sign(0, $privateKey, $fundingTx->getOutput(0)->getScript())
    //     ->get();

    return $spendingTx;
}

function test() {
    global $network, $privateKey;

    $result['hex'] = redeem_hex("Btrust Builders")->getHex(); 
    $result['address'] = getAddress(redeem_hex("Btrust Builders"))->getAddress($network);
    $fundingtxn = createfundingtxn(getAddress(redeem_hex("Btrust Builders")), 1000000, '99fe5212e4e52e2d7b35ec0098ae37881a7adaf889a7d46683d3fbb473234c28');
    $spendingTx = createSpendingTxn($fundingtxn, $privateKey, getAddress(redeem_hex("Btrust Builders")), '900000');
    $result['fundingTx'] = print_r($fundingtxn, true);
    $result['spendingTx'] = print_r($spendingTx, true);

    return $result;
}

echo json_encode(test(), JSON_PRETTY_PRINT);