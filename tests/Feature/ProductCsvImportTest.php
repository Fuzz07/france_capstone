<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductCsvImportTest extends TestCase
{
    use RefreshDatabase;

    // NOTE: price is a decimal column. SQLite (tests) returns a float and MySQL
    // (production) returns a string, so price assertions compare numerically.

    // firstOrCreate so a test may run more than one import in a row.
    private function admin(): User
    {
        return User::firstOrCreate(
            ['email' => 'csv.admin@example.com'],
            [
                'name' => 'Store Admin',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );
    }

    private function csv(string $contents, string $name = 'products.csv'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($path, $contents);

        return new UploadedFile($path, $name, 'text/csv', null, true);
    }

    private function import(string $contents, string $name = 'products.csv')
    {
        return $this->actingAs($this->admin())
            ->post(route('products.import'), ['csv_file' => $this->csv($contents, $name)]);
    }

    /** @test */
    public function it_imports_name_price_and_quantity_and_generates_the_sku()
    {
        $this->import("name,price,quantity\nCoca-Cola 1.5L,85.50,24\nBond Paper A4,320,10\n")
            ->assertRedirect(route('products.index'));

        $this->assertSame(2, Product::count());

        $cola = Product::where('name', 'Coca-Cola 1.5L')->first();
        $this->assertEquals(85.50, (float) $cola->price);
        $this->assertSame(24, $cola->quantity);
        $this->assertSame('COC-001', $cola->sku, 'The SKU should come from the product name.');

        $this->assertSame('BON-001', Product::where('name', 'Bond Paper A4')->first()->sku);
    }

    /** @test */
    public function generated_skus_never_collide()
    {
        // Three names sharing a prefix, plus one SKU already taken in the catalog.
        Product::create(['sku' => 'PEN-001', 'name' => 'Existing Pen', 'price' => 5, 'quantity' => 1]);

        $this->import("name,price,quantity\nPencil,5,10\nPen Blue,8,10\nPen Red,8,10\n");

        $skus = Product::whereIn('name', ['Pencil', 'Pen Blue', 'Pen Red'])->pluck('sku')->all();

        $this->assertCount(3, array_unique($skus));
        $this->assertNotContains('PEN-001', $skus, 'A SKU already in use must be skipped.');
    }

    /** @test */
    public function a_name_already_in_the_catalog_is_updated_and_keeps_its_sku()
    {
        Product::create([
            'sku' => 'OLD-999',
            'name' => 'Ballpen Black',
            'category' => 'School Supplies',
            'price' => 10.00,
            'quantity' => 5,
        ]);

        // Different casing and padding — still the same product.
        $this->import("name,price,quantity\n  ballpen BLACK ,12.50,40\n");

        $this->assertSame(1, Product::count(), 'The product must not be duplicated.');

        $product = Product::first();
        $this->assertSame('OLD-999', $product->sku, 'The existing SKU must be preserved.');
        $this->assertEquals(12.50, (float) $product->price);
        $this->assertSame(40, $product->quantity);
        $this->assertSame('School Supplies', $product->category, 'Untouched columns must survive.');
    }

    /** @test */
    public function it_accepts_alternate_column_headings()
    {
        $this->import("Product Name,Unit Price,QTY\nNotebook,45,12\n");

        $product = Product::first();
        $this->assertNotNull($product, 'Header aliases should have been recognised.');
        $this->assertSame('Notebook', $product->name);
        $this->assertEquals(45.00, (float) $product->price);
        $this->assertSame(12, $product->quantity);
    }

    /** @test */
    public function columns_may_appear_in_any_order()
    {
        $this->import("quantity,name,price\n7,Eraser,3.25\n");

        $product = Product::first();
        $this->assertSame('Eraser', $product->name);
        $this->assertEquals(3.25, (float) $product->price);
        $this->assertSame(7, $product->quantity);
    }

    /** @test */
    public function it_reads_a_file_saved_by_excel_with_a_bom_and_semicolons()
    {
        $this->import("\xEF\xBB\xBFname;price;quantity\nGlue Stick;25;30\n");

        $product = Product::first();
        $this->assertNotNull($product, 'A BOM or semicolon delimiter should not break the import.');
        $this->assertSame('Glue Stick', $product->name);
        $this->assertSame(30, $product->quantity);
    }

    /** @test */
    public function it_strips_currency_formatting_from_the_price()
    {
        $this->import("name,price,quantity\n\"Rice Sack 25kg\",\"PHP 1,250.00\",4\n");

        $this->assertEquals(1250.00, (float) Product::first()->price);
    }

    /** @test */
    public function bad_rows_are_skipped_and_reported_while_good_rows_import()
    {
        $response = $this->import(
            "name,price,quantity\n"
            . "Good Item,50,10\n"
            . ",25,5\n"              // blank name
            . "No Price Item,abc,5\n"
            . "Bad Qty Item,30,many\n"
            . "\n"                   // blank line, not an error
            . "Another Good,15,3\n"
        );

        $response->assertSessionHas('noticeType', 'warning');

        $this->assertSame(2, Product::count());
        $this->assertSame(3, count(session('import_errors')));

        $this->assertStringContainsString('Row 3', session('import_errors')[0]);
        $this->assertStringContainsString('No Price Item', session('import_errors')[1]);
        $this->assertStringContainsString('Bad Qty Item', session('import_errors')[2]);
    }

    /** @test */
    public function a_missing_column_is_rejected_before_anything_is_written()
    {
        $response = $this->import("name,price\nNo Quantity Column,50\n");

        $response->assertSessionHas('noticeType', 'danger');
        $this->assertStringContainsString('quantity', session('notice'));
        $this->assertSame(0, Product::count());
    }

    /** @test */
    public function a_non_csv_upload_is_rejected()
    {
        $response = $this->import('name,price,quantity', 'products.xlsx');

        $response->assertSessionHas('noticeType', 'danger');
        $this->assertSame(0, Product::count());
    }

    /** @test */
    public function the_import_is_recorded_in_the_activity_log()
    {
        $this->import("name,price,quantity\nStapler,120,6\n");

        $log = ActivityLog::where('action', 'import_products')->first();

        $this->assertNotNull($log, 'The import should be logged.');
        $this->assertStringContainsString('1 added', $log->description);
    }

    /** @test */
    public function a_customer_cannot_reach_the_import_endpoint()
    {
        $customer = User::create([
            'name' => 'Shopper',
            'email' => 'shopper@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
        ]);

        $this->actingAs($customer)
            ->post(route('products.import'), ['csv_file' => $this->csv("name,price,quantity\nX,1,1\n")]);

        $this->assertSame(0, Product::count(), 'Only admins may import products.');
    }

    /** @test */
    public function it_imports_the_unit_column()
    {
        $this->import(
            "name,price,quantity,unit
"
            . "Bond Paper A4,320,10,rms
"
            . "Ballpen,12,50,pcs
"
            . "Sky Flakes,95,8,pcks
"
        );

        $this->assertSame('rms', Product::where('name', 'Bond Paper A4')->first()->unit);
        $this->assertSame('pcs', Product::where('name', 'Ballpen')->first()->unit);
        $this->assertSame('pck', Product::where('name', 'Sky Flakes')->first()->unit);
    }

    /** @test */
    public function unit_spellings_are_folded_to_one_abbreviation()
    {
        $this->import(
            "name,price,quantity,unit
"
            . "Item A,1,1,Pieces
"
            . "Item B,1,1,PC
"
            . "Item C,1,1,ream
"
            . "Item D,1,1,Packs
"
            . "Item E,1,1,dozen
"
            . "Item F,1,1,Kilograms
"
        );

        $units = Product::orderBy('name')->pluck('unit', 'name')->all();

        $this->assertSame('pcs', $units['Item A']);
        $this->assertSame('pcs', $units['Item B']);
        $this->assertSame('rms', $units['Item C']);
        $this->assertSame('pck', $units['Item D']);
        $this->assertSame('doz', $units['Item E']);
        $this->assertSame('kg', $units['Item F']);
    }

    /** @test */
    public function an_unrecognised_unit_is_kept_as_typed()
    {
        $this->import("name,price,quantity,unit
Custom Item,10,5,gallon
");

        $this->assertSame('gallon', Product::first()->unit);
    }

    /** @test */
    public function a_blank_or_missing_unit_falls_back_to_pcs()
    {
        // Column present but empty on the row.
        $this->import("name,price,quantity,unit
No Unit Given,10,5,
");
        $this->assertSame('pcs', Product::where('name', 'No Unit Given')->first()->unit);

        // Column absent altogether — the old three-column format still imports.
        $this->import("name,price,quantity
Legacy Row,10,5
");
        $this->assertSame('pcs', Product::where('name', 'Legacy Row')->first()->unit);
    }

    /** @test */
    public function the_unit_column_may_be_named_uom_and_sit_anywhere()
    {
        $this->import("uom,quantity,name,price
box,4,Chalk,55
");

        $product = Product::first();
        $this->assertSame('Chalk', $product->name);
        $this->assertSame('box', $product->unit);
        $this->assertSame(4, $product->quantity);
    }

    /** @test */
    public function unit_price_still_maps_to_price_not_to_unit()
    {
        // "unit price" and "unit" are distinct headers; the first must not be
        // swallowed by the unit column.
        $this->import("name,unit price,quantity,unit
Marker,35.50,12,pcs
");

        $product = Product::first();
        $this->assertEquals(35.50, (float) $product->price);
        $this->assertSame('pcs', $product->unit);
    }

    /** @test */
    public function re_importing_updates_the_unit_of_an_existing_product()
    {
        Product::create([
            'sku' => 'KEEP-1',
            'name' => 'Bond Paper A4',
            'unit' => 'pcs',
            'price' => 300,
            'quantity' => 5,
        ]);

        $this->import("name,price,quantity,unit
Bond Paper A4,320,10,reams
");

        $product = Product::first();
        $this->assertSame(1, Product::count());
        $this->assertSame('KEEP-1', $product->sku);
        $this->assertSame('rms', $product->unit);
    }

    /** @test */
    public function the_manual_form_saves_and_normalises_the_unit()
    {
        $this->actingAs($this->admin())->post(route('products.store'), [
            'name' => 'Manual Item',
            'price' => 20,
            'quantity' => 3,
            'unit' => 'Boxes',
        ]);

        $this->assertSame('box', Product::first()->unit);
    }

    /** @test */
    public function the_inventory_list_shows_the_unit()
    {
        Product::create([
            'sku' => 'U-1',
            'name' => 'Bond Paper A4',
            'unit' => 'rms',
            'price' => 320,
            'quantity' => 10,
        ]);

        $this->actingAs($this->admin())
            ->get(route('products.index'))
            ->assertStatus(200)
            ->assertSee('10 rms in stock');
    }

    /** @test */
    public function the_inventory_page_shows_the_import_button()
    {
        $this->actingAs($this->admin())
            ->get(route('products.index'))
            ->assertStatus(200)
            ->assertSee('Import CSV')
            ->assertSee('name,price,quantity,unit');
    }
}
