<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::create('external_references',function(Blueprint $t){$t->id();$t->string('system');$t->string('entity_type');$t->string('external_id');$t->string('display_name')->nullable();$t->json('payload')->nullable();$t->timestamps();$t->unique(['system','entity_type','external_id']);});
  Schema::create('integration_events',function(Blueprint $t){$t->id();$t->string('event');$t->string('source');$t->string('external_id')->nullable();$t->json('payload');$t->timestamps();});
 }
 public function down():void {Schema::dropIfExists('integration_events');Schema::dropIfExists('external_references');}
};
