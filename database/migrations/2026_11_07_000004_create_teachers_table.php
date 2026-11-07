<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nama_lengkap');
            $table->string('nip', 30)->nullable();
            $table->string('nuptk', 30)->nullable();
            $table->char('jenis_kelamin', 1)->nullable();
            $table->string('npsn', 20)->nullable()->index();
            $table->string('satuan_pendidikan')->nullable();
            $table->string('jenjang')->index();
            $table->string('kecamatan')->index();
            $table->string('status_kepegawaian')->index();
            $table->string('sertifikasi')->nullable();
            $table->string('bidang_studi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
