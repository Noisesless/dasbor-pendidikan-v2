<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
            $table->string('semester', 10)->index();
            $table->string('npsn', 20)->index();
            $table->string('nama_sekolah');
            $table->string('jenjang')->index();
            $table->string('kecamatan')->index();
            $table->integer('jumlah_guru')->default(0);
            $table->integer('jumlah_sertifikasi')->default(0);
            $table->integer('jumlah_pns')->default(0);
            $table->integer('jumlah_pppk')->default(0);
            $table->integer('jumlah_gty')->default(0);
            $table->integer('jumlah_gtt')->default(0);
            $table->integer('jumlah_honor')->default(0);
            $table->timestamps();

            $table->unique(['semester', 'npsn']);
            $table->index(['semester', 'jenjang']);
            $table->index(['semester', 'kecamatan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_stats');
    }
};
