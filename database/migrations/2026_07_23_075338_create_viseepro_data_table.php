<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('viseepro_data', function (Blueprint $table) {

            $table->id();

            $table->foreignId('customer_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('activity_id')->nullable();

            $table->string('ncli')->nullable();

            $table->string('snd')->nullable();

            $table->string('nama_perusahaan')->nullable();

            $table->string('regional')->nullable();

            $table->string('witel')->nullable();

            $table->string('sto')->nullable();

            $table->string('nama_agent')->nullable();

            $table->string('activity_status')->nullable();

            $table->text('activity_reason')->nullable();

            $table->string('pic_name')->nullable();

            $table->string('pic_role')->nullable();

            $table->string('pic_cp')->nullable();

            $table->text('address')->nullable();

            $table->string('latitude')->nullable();

            $table->string('longitude')->nullable();

            $table->date('input_date')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('viseepro_data');
    }
};