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

    $body = ['items' => $items];

    try {
        $api = new EfiPay($options);
        $charge = $api->createCharge($params = [], $body);


        if ($charge["code"] == 200) {

            $params = ['id' => $charge["data"]["charge_id"]];

            $body = [
                //'billet_discount' => 1,
                //'card_discount' => 1,
                'message' => $_POST["message"],
                'expire_at' => $_POST["vencimento"],
                //'request_delivery_address' => (boolean) $_POST["request"],
                'request_delivery_address' => (bool) $_POST["request"],
                'payment_method' => $_POST["method"]
            ];

            $response = $api->defineLinkPayMethod($params, $body);

            echo json_encode($response);
        } else {
            echo json_encode($charge);
        }
    } catch (EfiException $e) {
        print_r($e->code);
        print_r($e->error);
        print_r($e->errorDescription);
    } catch (Exception $e) {
        print_r($e->getMessage());
    }
}
