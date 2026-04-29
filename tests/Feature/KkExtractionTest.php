<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\SimpleExcel\SimpleExcelWriter;

uses(RefreshDatabase::class);

test('kk extraction endpoint validates input', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->postJson("/api/users/{$user->id}/extract-kk", []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['file']);
});

test('kk extraction correctly parses excel data', function () {
    $user = User::factory()->create(['name' => 'John Doe']);
    $this->actingAs($user);

    // Create a dummy Excel file
    Storage::fake('local');
    $filePath = storage_path('framework/testing/disks/local/test-kk.xlsx');
    
    $writer = SimpleExcelWriter::create($filePath)
        ->addRow([
            'Nama Lengkap' => 'John Doe',
            'Status Hubungan Dalam Keluarga' => 'KEPALA KELUARGA',
            'Jenis Kelamin' => 'Laki-laki',
            'Tanggal Lahir' => '1990-01-01',
            'Tempat Lahir' => 'Jakarta',
            'NIK' => '123456789',
            'Jenis Pekerjaan' => 'Software Engineer'
        ])
        ->addRow([
            'Nama Lengkap' => 'Jane Doe',
            'Status Hubungan Dalam Keluarga' => 'ANAK',
            'Jenis Kelamin' => 'Perempuan',
            'Tanggal Lahir' => '1992-05-15',
            'Tempat Lahir' => 'Bandung',
            'NIK' => '987654321',
            'Jenis Pekerjaan' => 'Designer'
        ]);
    
    $writer->close();

    $file = new UploadedFile($filePath, 'test-kk.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

    $response = $this->postJson("/api/users/{$user->id}/extract-kk", [
        'file' => $file
    ]);

    $response->assertStatus(200);
    $data = $response->json();

    expect($data)->toHaveCount(2);
    
    // First person (self)
    expect($data[0]['full_name'])->toBe('John Doe');
    expect($data[0]['relation_type'])->toBe('self');
    expect($data[0]['gender'])->toBe('M');
    
    // Second person (child/other)
    expect($data[1]['full_name'])->toBe('Jane Doe');
    expect($data[1]['relation_type'])->toBe('child');
    expect($data[1]['gender'])->toBe('F');
    expect($data[1]['occupation'])->toBe('Designer');
});

test('mockup kk download works', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/api/family-tree/mockup-kk');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});
