<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('populations', function (Blueprint $table) {
            $table->id();
            $table->string('kecamatan')->index();
            $table->year('tahun')->index();
            $table->integer('u04_l')->default(0);
            $table->integer('u04_p')->default(0);
            $table->integer('u04_t')->default(0);
            $table->integer('u56_l')->default(0);
            $table->integer('u56_p')->default(0);
            $table->integer('u56_t')->default(0);
            $table->integer('u712_l')->default(0);
            $table->integer('u712_p')->default(0);
            $table->integer('u712_t')->default(0);
            $table->integer('u1315_l')->default(0);
            $table->integer('u1315_p')->default(0);
            $table->integer('u1315_t')->default(0);
            $table->integer('u1618_l')->default(0);
            $table->integer('u1618_p')->default(0);
            $table->integer('u1618_t')->default(0);
            $table->integer('u18_t')->default(0);
            $table->integer('total_penduduk')->default(0);
            $table->timestamps();

            $table->unique(['kecamatan', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('populations');
    }
};
