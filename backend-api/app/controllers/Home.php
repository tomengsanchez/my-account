<?php
namespace controllers; // This controller is in the 'controllers' namespace

class Home {
    public function index(){
        echo json_encode(['message' => 'Welcome to the My Ecosys Account API']);
    }
}
