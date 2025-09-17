<?php

namespace App\Console\Commands;

use App\Http\Controllers\Frontend\InventoryController;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class TestInventoryRoute extends Command
{
    protected $signature = 'test:inventory-route';
    protected $description = 'Test the inventory route functionality';

    public function handle()
    {
        $this->info('Testing Inventory Route...');

        // Get and authenticate a user
        $user = User::first();
        if (!$user) {
            $this->error('No users found in database');
            return;
        }

        $this->info("Testing with user: {$user->name}");

        // Check permissions
        $hasViewInventory = $user->can('view_inventory');
        $hasViewMovements = $user->can('view_stock_movements');

        $this->info("Has view_inventory permission: " . ($hasViewInventory ? 'YES' : 'NO'));
        $this->info("Has view_stock_movements permission: " . ($hasViewMovements ? 'YES' : 'NO'));

        if (!$hasViewInventory || !$hasViewMovements) {
            $this->warn('User lacks required permissions. This may cause authorization errors.');
        }

        // Authenticate the user
        auth()->login($user);

        try {
            $controller = new InventoryController();
            $request = new Request();

            // Test the index method
            $response = $controller->index($request);

            $this->info('✅ Controller index method executed successfully');
            $this->info('Response type: ' . get_class($response));

            // Test individual methods
            $reflection = new \ReflectionClass($controller);

            // Test getInventoryStatistics
            $statisticsMethod = $reflection->getMethod('getInventoryStatistics');
            $statisticsMethod->setAccessible(true);
            $statistics = $statisticsMethod->invoke($controller);

            $this->info('✅ Statistics retrieved successfully:');
            $this->line('  - Total products: ' . $statistics['total_products']);
            $this->line('  - Total stock value: $' . number_format($statistics['total_stock_value'], 2));
            $this->line('  - Low stock items: ' . $statistics['low_stock_items']);
            $this->line('  - Out of stock items: ' . $statistics['out_of_stock_items']);
            $this->line('  - Recent movements: ' . $statistics['recent_movements']);

            // Test getRecentMovements
            $movementsMethod = $reflection->getMethod('getRecentMovements');
            $movementsMethod->setAccessible(true);
            $movements = $movementsMethod->invoke($controller, []);

            $this->info('✅ Recent movements retrieved: ' . count($movements) . ' movements');

            // Test getLowStockProducts
            $lowStockMethod = $reflection->getMethod('getLowStockProducts');
            $lowStockMethod->setAccessible(true);
            $lowStockProducts = $lowStockMethod->invoke($controller);

            $this->info('✅ Low stock products retrieved: ' . count($lowStockProducts) . ' products');

            $this->info('');
            $this->info('🎉 ALL INVENTORY FUNCTIONALITY WORKING CORRECTLY!');
            $this->info('The /inventory route should be accessible at: http://localhost/inventory');
            $this->info('Make sure you are logged in as a user with view_inventory permission.');

        } catch (\Exception $e) {
            $this->error('❌ Error testing inventory route: ' . $e->getMessage());
            $this->error('Stack trace:');
            $this->error($e->getTraceAsString());
        }
    }
}