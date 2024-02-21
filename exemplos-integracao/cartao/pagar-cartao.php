<?php

$autoload = realpath(__DIR__ . '/../vendor/autoload.php');
if (!file_exists($autoload)) {
    die("Autoload file not found or on path <code>$autoload</code>.");
}
require_once $autoload;

use Efi\Exception\EfiException;
use Efi\EfiPay;

// Lê o arquivo json com suas credenciais
$file = file_get_contents(__DIR__ . '/../credentials.json');
$options = json_decode($file, true);
unset($options['certificate']);

if (isset($_POST)) {

    $item_1 = [
        'name' => $_POST["descricao"],
        'amount' => (int) $_POST["quantidade"],
        'value' => (int) $_POST["valor"]
    ];

    $items = [
        $item_1
    ];


    $customer = [
        'name' => $_POST["nome_cliente"],
        'cpf' => $_POST["cpf"],
        'phone_number' => $_POST["telefone"],
        'email' => $_POST["email"],
        'birth' => $_POST["nascimento"]
    ];

    $paymentToken = $_POST["payament_token"];

    $billingAddress = [
        'street' => $_POST["rua"],
        'number' => $_POST["numero"],
        'neighborhood' => $_POST["bairro"],
        'zipcode' => $_POST["cep"],
        'city' => $_POST["cidade"],
        'state' => $_POST["estado"],
    ];

    $creditCard = [
        'installments' => (int) $_POST["installments"],
        'billing_address' => $billingAddress,
        'payment_token' => $paymentToken,
        'customer' => $customer
    ];

    $payment = [
        'credit_card' => $creditCard
    ];

    $body = [
        'items' => $items,
        'payment' => $payment
    ];

    try {
        $api = new EfiPay($options);
        $charge = $api->createOneStepCharge($params = [], $body);

        echo json_encode($charge);
    } catch (EfiException $e) {
        print_r($e->code);
        print_r($e->error);
        print_r($e->errorDescription);
    } catch (Exception $e) {
        print_r($e->getMessage());
    }
}
