<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Storage::fake('public');

    // Create permissions
    Permission::create(['name' => 'edit_products']);
    Permission::create(['name' => 'view_products']);

    // Create roles
    $this->superAdminRole = Role::create(['name' => 'super_admin']);
    $this->editorRole = Role::create(['name' => 'editor']);
    $this->viewerRole = Role::create(['name' => 'viewer']);

    // Assign permissions to roles
    $this->superAdminRole->givePermissionTo(['edit_products', 'view_products']);
    $this->editorRole->givePermissionTo(['edit_products', 'view_products']);
    $this->viewerRole->givePermissionTo(['view_products']);

    // Create users with different roles
    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole($this->superAdminRole);

    $this->editor = User::factory()->create();
    $this->editor->assignRole($this->editorRole);

    $this->viewer = User::factory()->create();
    $this->viewer->assignRole($this->viewerRole);

    // Create test product
    $category = Category::factory()->create();
    $this->product = Product::factory()->create([
        'category_id' => $category->id,
        'image_url' => null,
    ]);
});

describe('Product Image Upload', function () {
    it('allows super admin to upload product image', function () {
        $file = UploadedFile::fake()->image('test-product.jpg');

        $response = $this->actingAs($this->superAdmin)
            ->post("/products/{$this->product->id}/image", [
                'image' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Product image uploaded successfully.');

        $this->product->refresh();
        expect($this->product->image_url)->not()->toBeNull();
        expect($this->product->updated_by)->toBe($this->superAdmin->id);

        Storage::disk('public')->assertExists("products/{$file->hashName()}");
    });

    it('allows user with edit_products permission to upload product image', function () {
        $file = UploadedFile::fake()->image('test-product.png');

        $response = $this->actingAs($this->editor)
            ->post("/products/{$this->product->id}/image", [
                'image' => $file,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Product image uploaded successfully.');

        $this->product->refresh();
        expect($this->product->image_url)->not()->toBeNull();
        expect($this->product->updated_by)->toBe($this->editor->id);
    });

    it('denies access to users without edit_products permission', function () {
        $file = UploadedFile::fake()->image('test-product.jpg');

        $response = $this->actingAs($this->viewer)
            ->post("/products/{$this->product->id}/image", [
                'image' => $file,
            ]);

        $response->assertForbidden();

        $this->product->refresh();
        expect($this->product->image_url)->toBeNull();
    });

    it('denies access to unauthenticated users', function () {
        $file = UploadedFile::fake()->image('test-product.jpg');

        $response = $this->post("/products/{$this->product->id}/image", [
            'image' => $file,
        ]);

        $response->assertRedirect('/login');

        $this->product->refresh();
        expect($this->product->image_url)->toBeNull();
    });

    it('validates file type', function () {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->superAdmin)
            ->post("/products/{$this->product->id}/image", [
                'image' => $file,
            ]);

        $response->assertSessionHasErrors(['image' => 'The image must be a file of type: jpeg, png, jpg, webp.']);

        $this->product->refresh();
        expect($this->product->image_url)->toBeNull();
    });

    it('validates file size', function () {
        $file = UploadedFile::fake()->image('huge-image.jpg')->size(3000); // 3MB

        $response = $this->actingAs($this->superAdmin)
            ->post("/products/{$this->product->id}/image", [
                'image' => $file,
            ]);

        $response->assertSessionHasErrors(['image' => 'The image size must not exceed 2MB.']);

        $this->product->refresh();
        expect($this->product->image_url)->toBeNull();
    });

    it('validates image dimensions', function () {
        $file = UploadedFile::fake()->image('tiny-image.jpg', 50, 50); // 50x50 pixels

        $response = $this->actingAs($this->superAdmin)
            ->post("/products/{$this->product->id}/image", [
                'image' => $file,
            ]);

        $response->assertSessionHasErrors(['image' => 'The image dimensions must be between 100x100 and 2000x2000 pixels.']);

        $this->product->refresh();
        expect($this->product->image_url)->toBeNull();
    });

    it('replaces existing image when uploading new one', function () {
        // First, upload an initial image
        $oldFile = UploadedFile::fake()->image('old-product.jpg');
        $this->actingAs($this->superAdmin)
            ->post("/products/{$this->product->id}/image", [
                'image' => $oldFile,
            ]);

        $this->product->refresh();
        $oldImageUrl = $this->product->image_url;

        // Upload a new image
        $newFile = UploadedFile::fake()->image('new-product.jpg');
        $response = $this->actingAs($this->superAdmin)
            ->post("/products/{$this->product->id}/image", [
                'image' => $newFile,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Product image uploaded successfully.');

        $this->product->refresh();
        expect($this->product->image_url)->not()->toBe($oldImageUrl);
        expect($this->product->image_url)->not()->toBeNull();
    });

    it('requires image field', function () {
        $response = $this->actingAs($this->superAdmin)
            ->post("/products/{$this->product->id}/image", []);

        $response->assertSessionHasErrors(['image' => 'Please select an image to upload.']);

        $this->product->refresh();
        expect($this->product->image_url)->toBeNull();
    });
});

describe('Product Image Deletion', function () {
    beforeEach(function () {
        // Set up product with an image
        $file = UploadedFile::fake()->image('test-product.jpg');
        Storage::disk('public')->putFileAs('products', $file, 'test-image.jpg');
        $this->product->update(['image_url' => '/storage/products/test-image.jpg']);
    });

    it('allows super admin to delete product image', function () {
        $response = $this->actingAs($this->superAdmin)
            ->delete("/products/{$this->product->id}/image");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Product image deleted successfully.');

        $this->product->refresh();
        expect($this->product->image_url)->toBeNull();
        expect($this->product->updated_by)->toBe($this->superAdmin->id);

        Storage::disk('public')->assertMissing('products/test-image.jpg');
    });

    it('allows user with edit_products permission to delete product image', function () {
        $response = $this->actingAs($this->editor)
            ->delete("/products/{$this->product->id}/image");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Product image deleted successfully.');

        $this->product->refresh();
        expect($this->product->image_url)->toBeNull();
        expect($this->product->updated_by)->toBe($this->editor->id);
    });

    it('denies access to users without edit_products permission', function () {
        $response = $this->actingAs($this->viewer)
            ->delete("/products/{$this->product->id}/image");

        $response->assertForbidden();

        $this->product->refresh();
        expect($this->product->image_url)->not()->toBeNull();
    });

    it('denies access to unauthenticated users', function () {
        $response = $this->delete("/products/{$this->product->id}/image");

        $response->assertRedirect('/login');

        $this->product->refresh();
        expect($this->product->image_url)->not()->toBeNull();
    });

    it('handles deletion when no image exists', function () {
        $this->product->update(['image_url' => null]);

        $response = $this->actingAs($this->superAdmin)
            ->delete("/products/{$this->product->id}/image");

        $response->assertRedirect();
        $response->assertSessionHasErrors(['image' => 'No image to delete.']);

        $this->product->refresh();
        expect($this->product->image_url)->toBeNull();
    });

    it('handles deletion when image file does not exist on disk', function () {
        // Delete the file from storage but keep the URL in database
        Storage::disk('public')->delete('products/test-image.jpg');

        $response = $this->actingAs($this->superAdmin)
            ->delete("/products/{$this->product->id}/image");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Product image deleted successfully.');

        $this->product->refresh();
        expect($this->product->image_url)->toBeNull();
    });
});

describe('Product Show Page Access Control', function () {
    it('shows image upload interface to users with edit_products permission', function () {
        $response = $this->actingAs($this->superAdmin)
            ->get("/products/{$this->product->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Products/Show')
            ->has('product')
        );
    });

    it('shows product details to users with view_products permission', function () {
        $response = $this->actingAs($this->viewer)
            ->get("/products/{$this->product->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Products/Show')
            ->has('product')
        );
    });

    it('denies access to users without view_products permission', function () {
        $userWithoutPermissions = User::factory()->create();

        $response = $this->actingAs($userWithoutPermissions)
            ->get("/products/{$this->product->id}");

        $response->assertForbidden();
    });

    it('denies access to unauthenticated users', function () {
        $response = $this->get("/products/{$this->product->id}");

        $response->assertRedirect('/login');
    });
});
