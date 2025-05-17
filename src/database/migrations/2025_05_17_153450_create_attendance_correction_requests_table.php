<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceCorrectionRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_correction_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')
                  ->constrained()
                  ->onDelete('cascade');
            $table->foreignId('user_id') // 申請者ID
                  ->constrained()
                  ->onDelete('cascade');
            $table->dateTime('requested_clock_in_time')->nullable();
            $table->dateTime('requested_clock_out_time')->nullable();
            $table->json('requested_break_details')->nullable(); // 休憩情報の修正
            $table->text('requested_note')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendance_correction_requests');
    }
}
