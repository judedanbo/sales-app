<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating product categories...');

        // Create root categories
        $uniforms = Category::factory()->uniforms()->create();
        $textbooks = Category::factory()->textbooks()->create();
        $stationery = Category::factory()->stationery()->create();
        $sportsEquipment = Category::factory()->sportsEquipment()->create();

        // Create uniform subcategories
        $boysUniforms = Category::factory()->boysUniforms($uniforms)->create();
        $girlsUniforms = Category::factory()->girlsUniforms($uniforms)->create();

        // Create uniform sub-subcategories for boys
        Category::create([
            'name' => 'Boys Shirts',
            'slug' => 'boys-shirts',
            'description' => 'School shirts for male students',
            'parent_id' => $boysUniforms->id,
            'sort_order' => 1,
            'color' => 'white',
            'icon' => 'shirt',
            'is_active' => true,
        ]);

        Category::create([
            'name' => 'Boys Pants',
            'slug' => 'boys-pants',
            'description' => 'School pants and trousers for male students',
            'parent_id' => $boysUniforms->id,
            'sort_order' => 2,
            'color' => 'gray',
            'icon' => 'shirt',
            'is_active' => true,
        ]);

        Category::create([
            'name' => 'Boys Shoes',
            'slug' => 'boys-shoes',
            'description' => 'School shoes for male students',
            'parent_id' => $boysUniforms->id,
            'sort_order' => 3,
            'color' => 'black',
            'icon' => 'shirt',
            'is_active' => true,
        ]);

        // Create uniform sub-subcategories for girls
        Category::create([
            'name' => 'Girls Blouses',
            'slug' => 'girls-blouses',
            'description' => 'School blouses for female students',
            'parent_id' => $girlsUniforms->id,
            'sort_order' => 1,
            'color' => 'white',
            'icon' => 'shirt',
            'is_active' => true,
        ]);

        Category::create([
            'name' => 'Girls Skirts',
            'slug' => 'girls-skirts',
            'description' => 'School skirts for female students',
            'parent_id' => $girlsUniforms->id,
            'sort_order' => 2,
            'color' => 'navy',
            'icon' => 'shirt',
            'is_active' => true,
        ]);

        Category::create([
            'name' => 'Girls Shoes',
            'slug' => 'girls-shoes',
            'description' => 'School shoes for female students',
            'parent_id' => $girlsUniforms->id,
            'sort_order' => 3,
            'color' => 'black',
            'icon' => 'shirt',
            'is_active' => true,
        ]);

        // Create textbook subcategories by subject
        $subjects = [
            ['Mathematics', 'Advanced mathematics textbooks and workbooks', 'blue', 'calculator'],
            ['Science', 'Physics, Chemistry, and Biology textbooks', 'green', 'flask'],
            ['English', 'Literature and language textbooks', 'purple', 'book'],
            ['History', 'Historical textbooks and reference materials', 'brown', 'scroll'],
            ['Geography', 'Maps, atlases, and geography textbooks', 'teal', 'globe'],
            ['Languages', 'Foreign language learning materials', 'orange', 'language'],
        ];

        foreach ($subjects as $index => $subject) {
            Category::create([
                'name' => $subject[0],
                'slug' => strtolower(str_replace(' ', '-', $subject[0])),
                'description' => $subject[1],
                'parent_id' => $textbooks->id,
                'sort_order' => $index + 1,
                'color' => $subject[2],
                'icon' => $subject[3],
                'is_active' => true,
            ]);
        }

        // Create stationery subcategories
        $writingMaterials = Category::factory()->writingMaterials($stationery)->create();
        $mathInstruments = Category::factory()->mathInstruments($stationery)->create();

        Category::create([
            'name' => 'Notebooks',
            'slug' => 'notebooks',
            'description' => 'Exercise books, notebooks, and journals',
            'parent_id' => $stationery->id,
            'sort_order' => 3,
            'color' => 'yellow',
            'icon' => 'book',
            'is_active' => true,
        ]);

        Category::create([
            'name' => 'Art Supplies',
            'slug' => 'art-supplies',
            'description' => 'Paints, brushes, and artistic materials',
            'parent_id' => $stationery->id,
            'sort_order' => 4,
            'color' => 'rainbow',
            'icon' => 'palette',
            'is_active' => true,
        ]);

        // Create specific writing materials
        $writingItems = [
            ['Pens', 'Ballpoint and gel pens', 'blue'],
            ['Pencils', 'Graphite and mechanical pencils', 'yellow'],
            ['Erasers', 'Rubber and kneaded erasers', 'pink'],
            ['Markers', 'Permanent and whiteboard markers', 'red'],
        ];

        foreach ($writingItems as $index => $item) {
            Category::create([
                'name' => $item[0],
                'slug' => strtolower($item[0]),
                'description' => $item[1],
                'parent_id' => $writingMaterials->id,
                'sort_order' => $index + 1,
                'color' => $item[2],
                'icon' => 'pencil',
                'is_active' => true,
            ]);
        }

        // Create sports equipment subcategories
        $sportsCategories = [
            ['Team Sports', 'Equipment for football, basketball, volleyball', 'green', 'users'],
            ['Individual Sports', 'Equipment for athletics, tennis, badminton', 'blue', 'user'],
            ['Fitness Equipment', 'Gym equipment and fitness accessories', 'red', 'dumbbell'],
            ['Sports Apparel', 'Sports uniforms and athletic wear', 'orange', 'shirt'],
        ];

        foreach ($sportsCategories as $index => $sport) {
            Category::create([
                'name' => $sport[0],
                'slug' => strtolower(str_replace(' ', '-', $sport[0])),
                'description' => $sport[1],
                'parent_id' => $sportsEquipment->id,
                'sort_order' => $index + 1,
                'color' => $sport[2],
                'icon' => $sport[3],
                'is_active' => true,
            ]);
        }

        // Create additional miscellaneous categories
        Category::create([
            'name' => 'Technology',
            'slug' => 'technology',
            'description' => 'Calculators, tablets, and educational technology',
            'parent_id' => null,
            'sort_order' => 5,
            'color' => 'gray',
            'icon' => 'computer',
            'is_active' => true,
        ]);

        Category::create([
            'name' => 'School Bags',
            'slug' => 'school-bags',
            'description' => 'Backpacks, messenger bags, and school accessories',
            'parent_id' => null,
            'sort_order' => 6,
            'color' => 'brown',
            'icon' => 'briefcase',
            'is_active' => true,
        ]);

        // Create a few inactive categories for testing
        Category::factory()->inactive()->create([
            'name' => 'Discontinued Items',
            'slug' => 'discontinued-items',
            'description' => 'Products no longer available',
            'sort_order' => 99,
        ]);

        $this->command->info('Created '.Category::count().' categories with hierarchical structure.');
    }
}
