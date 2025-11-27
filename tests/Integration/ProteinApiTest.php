<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use DI\Container;
use App\Controllers\ProteinController;
use App\Controllers\FlavoursController;
use App\Controllers\CutsController;
use Dotenv\Dotenv;

class ProteinApiTest extends TestCase {
    private $app;
    private $db;
    
    protected function setUp(): void {
        // Load test environment variables
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../', '.env.test');
        $dotenv->load();
        
        // Set up test database connection
        $db_host = $_ENV['DB_HOST'];
        $db_name = $_ENV['DB_NAME'];
        $db_user = $_ENV['DB_USER'];
        $db_pass = $_ENV['DB_PASSWORD'];
        
        $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $this->db = new \PDO($dsn, $db_user, $db_pass, $options);
        
        // Clean the protein table for fresh test
        $this->db->exec("DELETE FROM protein");
        
        // Create DI Container
        $container = new Container();
        
        // Add database to container
        $container->set('db', $this->db);
        
        // Configure controllers with database dependency
        $container->set(ProteinController::class, function($c) {
            return new ProteinController($c->get('db'));
        });
        
        $container->set(FlavoursController::class, function($c) {
            return new FlavoursController($c->get('db'));
        });
        
        $container->set(CutsController::class, function($c) {
            return new CutsController($c->get('db'));
        });
        
        // Create Slim App with container
        AppFactory::setContainer($container);
        $this->app = AppFactory::create();
        
        // Add error middleware
        $this->app->addErrorMiddleware(true, true, true);
        
        // Add JSON body parsing middleware
        $this->app->addBodyParsingMiddleware();
        
        // Load routes (without auth middleware for testing)
        $this->loadTestRoutes();
    }
    
    protected function tearDown(): void {
        // Clean up after tests
        $this->db->exec("DELETE FROM protein");
        $this->db = null;
    }
    
    private function loadTestRoutes(): void {
        // Define routes without auth middleware for testing
        $this->app->get('/api/protein', [ProteinController::class, 'index']);
        $this->app->get('/api/protein/{id}', [ProteinController::class, 'getById']);
        $this->app->post('/api/protein', [ProteinController::class, 'addProtein']);
        $this->app->delete('/api/protein/{id}', [ProteinController::class, 'deleteProtein']);
    }
    
    public function test_can_create_and_retrieve_protein(): void {
        // Create a protein
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/protein')
            ->withParsedBody(['name' => 'Chicken']);
            
        $response = $this->app->handle($request);
        $this->assertEquals(201, $response->getStatusCode());
        
        $createBody = json_decode((string) $response->getBody(), true);
        $this->assertTrue($createBody['success']);
        $this->assertArrayHasKey('id', $createBody['data']);
        
        // Retrieve all proteins
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/api/protein');
            
        $response = $this->app->handle($request);
        $body = json_decode((string) $response->getBody(), true);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($body['success']);
        $this->assertCount(1, $body['data']);
        $this->assertEquals('Chicken', $body['data'][0]['name']);
    }
    
    public function test_can_get_protein_by_id(): void {
        // First create a protein
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/protein')
            ->withParsedBody(['name' => 'Beef']);
            
        $response = $this->app->handle($request);
        $createBody = json_decode((string) $response->getBody(), true);
        $proteinId = $createBody['data']['id'];
        
        // Now retrieve it by ID
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', "/api/protein/{$proteinId}");
            
        $response = $this->app->handle($request);
        $body = json_decode((string) $response->getBody(), true);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($body['success']);
        $this->assertEquals('Beef', $body['data']['name']);
        $this->assertEquals($proteinId, $body['data']['id']);
    }
    
    public function test_returns_404_for_non_existent_protein(): void {
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/api/protein/99999');
            
        $response = $this->app->handle($request);
        $body = json_decode((string) $response->getBody(), true);
        
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertFalse($body['success']);
    }
    
    public function test_can_delete_protein(): void {
        // Create a protein
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/protein')
            ->withParsedBody(['name' => 'Fish']);
            
        $response = $this->app->handle($request);
        $createBody = json_decode((string) $response->getBody(), true);
        $proteinId = $createBody['data']['id'];
        
        // Delete it
        $request = (new ServerRequestFactory())
            ->createServerRequest('DELETE', "/api/protein/{$proteinId}");
            
        $response = $this->app->handle($request);
        $body = json_decode((string) $response->getBody(), true);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($body['success']);
        
        // Verify it's gone
        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', "/api/protein/{$proteinId}");
            
        $response = $this->app->handle($request);
        $this->assertEquals(404, $response->getStatusCode());
    }
    
    public function test_validation_rejects_empty_protein_name(): void {
        $request = (new ServerRequestFactory())
            ->createServerRequest('POST', '/api/protein')
            ->withParsedBody(['name' => '']);
            
        $response = $this->app->handle($request);
        $body = json_decode((string) $response->getBody(), true);
        
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertFalse($body['success']);
    }
}
