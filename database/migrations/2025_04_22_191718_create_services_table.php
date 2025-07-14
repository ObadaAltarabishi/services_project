<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->date('exchange_time')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            // العلاقة مع المستخدم الذي أنشأ الخدمة
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // العلاقة مع الفئة الأساسية للخدمة
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            
            // العلاقة مع الفئة التي يمكن استبدال الخدمة بها (بدلاً من الخدمة)
            $table->foreignId('exchange_with_category_id')
                  ->nullable()
                  ->constrained('categories')
                  ->onDelete('set null');
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('services');
    }
};
