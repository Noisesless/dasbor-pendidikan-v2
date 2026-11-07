<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rekap sekolah/guru/murid per kecamatan per tahun ajaran (Dindikpora & Kemenag).
        Schema::create('regional_education_stats', function (Blueprint $table) {
            $table->id();
            $table->string('kecamatan', 64);
            $table->string('jenjang', 16);
            $table->string('tahun_ajaran', 16);
            $table->unsignedInteger('sekolah_negeri')->default(0);
            $table->unsignedInteger('sekolah_swasta')->default(0);
            $table->unsignedInteger('guru_negeri')->default(0);
            $table->unsignedInteger('guru_swasta')->default(0);
            $table->unsignedInteger('murid_negeri')->default(0);
            $table->unsignedInteger('murid_swasta')->default(0);
            $table->string('sumber', 16)->default('dindikpora');
            $table->timestamps();

            $table->unique(['kecamatan', 'jenjang', 'tahun_ajaran', 'sumber'], 'regional_stats_unique');
            $table->index(['jenjang', 'tahun_ajaran']);
        });

        // Indikator makro kabupaten (BPS): HLS, RLS, IPM, kemiskinan, dll.
        Schema::create('regional_indicators', function (Blueprint $table) {
            $table->id();
            $table->string('indikator', 32);
            $table->string('wilayah', 64)->default('Banjarnegara');
            $table->unsignedSmallInteger('tahun');
            $table->decimal('nilai', 14, 4);
            $table->string('satuan', 32)->nullable();
            $table->timestamps();

            $table->unique(['indikator', 'wilayah', 'tahun'], 'regional_indicator_unique');
            $table->index(['indikator', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regional_education_stats');
        Schema::dropIfExists('regional_indicators');
    }
};
