<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('intrusion_logs', function (Blueprint $table) {
                $table->id();

                $table->string('src_ip', 45)->nullable();
                $table->string('dst_ip', 45)->nullable();
                $table->integer('src_port')->nullable();
                $table->integer('dst_port')->nullable();
                $table->integer('protocol')->nullable();

                $table->double('flow_duration')->nullable();
                $table->double('flow_pkts_s')->nullable();
                $table->double('flow_bytes_s')->nullable();
                $table->integer('tot_fwd_pkts')->nullable();
                $table->integer('tot_bwd_pkts')->nullable();
                $table->integer('tot_fwd_bytes')->nullable();
                $table->integer('tot_bwd_bytes')->nullable();
                $table->double('fwd_pkt_len_mean')->nullable();
                $table->double('bwd_pkt_len_mean')->nullable();
                $table->double('fwd_iat_mean')->nullable();
                $table->double('bwd_iat_mean')->nullable();

                $table->string('risk_level', 20);
                $table->double('prob_attack');
                $table->string('attack_type', 100)->nullable();

                $table->timestamps();
            });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intrusion_logs');
    }
};
