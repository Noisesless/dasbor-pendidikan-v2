<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('npsn')->index();
            $table->string('nama_sekolah');
            $table->string('jenjang');
            $table->string('status');
            $table->string('akreditasi')->nullable();
            $table->string('kecamatan');
            $table->string('kode_wilayah', 20)->nullable();
            $table->text('alamat')->nullable();
            $table->string('kepala_sekolah')->nullable();
            $table->string('telepon', 50)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();

            $table->index(['jenjang', 'status']);
            $table->index('kecamatan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
