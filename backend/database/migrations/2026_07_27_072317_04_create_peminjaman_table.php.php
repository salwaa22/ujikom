<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void {
        Schema::create('peminjaman', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->dateTime('tgl_pinjam');
            $table->dateTime('tgl_kembali_plan');
            $table->enum('status', ['diajukan', 'dipinjam', 'telat', 'menunggu_pengembalian', 'dikembalikan'])->default('diajukan');
            $table->timestamps();
        });
    }
};
