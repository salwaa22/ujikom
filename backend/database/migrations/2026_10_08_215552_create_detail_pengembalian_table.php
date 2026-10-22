<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_pengembalian', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pengembalian_id')
                ->constrained('pengembalian')
                ->cascadeOnDelete();

            $table->foreignId('alat_id')
                ->constrained('alat')
                ->cascadeOnDelete();

            $table->integer('jumlah_baik')->default(0);
            $table->integer('jumlah_rusak_ringan')->default(0);
            $table->integer('jumlah_rusak_berat')->default(0);
            $table->integer('jumlah_tidak_lengkap')->default(0);

            $table->integer('denda_kerusakan')->default(0);

            $table->timestamps();
        });
    }
};
