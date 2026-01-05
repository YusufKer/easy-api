<?php 

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Order;
use App\Services\Logger;
use App\Core\Validator;
use App\Core\OrderValidator;

class OrdersController {
    private $orderModel;
    private Logger $logger;
    private $proteinModel;

    public function __construct($orderModel, $proteinModel, Logger $logger) {
        $this->orderModel = $orderModel;
        $this->proteinModel = $proteinModel;
        $this->logger = $logger;
    }

    public function createOrder(Request $request, Response $response): Response {
        $input = $request->getParsedBody();

        // Validate input 
        $validator = new OrderValidator(
            $this->proteinModel
        );

        foreach ($input as $order_item) {
            $validator->validateOrderItem($order_item);
        }

        $user_id =  $request->getAttribute('user_id');
        $this->orderModel->create('ORDER12348', $user_id);

        // Implementation for creating an order
        $payload = [
            'success' => true,
            'message' => 'Order created successfully',
            'details' => $input, 
            'timestamp' => date('Y-m-d H:i:s')
        ];
        $response->getBody()->write(json_encode($payload));

        return $response;
    }
}