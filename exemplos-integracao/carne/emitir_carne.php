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
    $instructions = [$_POST["instrucao1"], $_POST["instrucao2"], $_POST["instrucao3"], $_POST["instrucao4"]];

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
    ];

    $body = [
        'items' => $items,
        'repeats' => (int)$_POST["repeticoes"],
        'split_items' => false,
        'expire_at' => $_POST["vencimento"],
        'customer' => $customer,
        'instructions' => $instructions
    ];

    try {
        $api = new EfiPay($options);
        $charge = $api->createCarnet([], $body);
        
        echo json_encode($charge);
    } catch (EfiException $e) {
        print_r($e->code);
        print_r($e->error);
        print_r($e->errorDescription);
    } catch (Exception $e) {
        print_r($e->getMessage());
    }
}