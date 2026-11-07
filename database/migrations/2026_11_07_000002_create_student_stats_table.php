<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
            $table->string('semester', 10)->index();
            $table->string('npsn', 20)->index();
            $table->string('nama_sekolah');
            $table->string('jenjang')->index();
            $table->string('kecamatan')->index();
            $table->integer('total_siswa')->default(0);
            $table->integer('total_laki')->default(0);
            $table->integer('total_perempuan')->default(0);
            $table->integer('usia_sesuai')->default(0);
            $table->integer('usia_sesuai_laki')->default(0);
            $table->integer('usia_sesuai_perempuan')->default(0);
            $table->integer('usia_5_6')->default(0);
            $table->integer('usia_7_12')->default(0);
            $table->integer('usia_13_15')->default(0);
            $table->integer('usia_16_18')->default(0);
            $table->integer('jumlah_rombel')->default(0);
            $table->timestamps();

            $table->unique(['semester', 'npsn']);
            $table->index(['semester', 'jenjang']);
            $table->index(['semester', 'kecamatan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_stats');
    }
};
