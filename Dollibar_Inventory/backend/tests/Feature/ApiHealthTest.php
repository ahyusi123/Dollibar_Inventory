<?php
namespace Tests\Feature;
use Tests\TestCase;
class ApiHealthTest extends TestCase {
 public function test_api_root_is_reachable(): void {
  $this->get('/')->assertOk()->assertJson(['app'=>'Dollibar API']);
 }
}
