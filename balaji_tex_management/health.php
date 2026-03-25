<?php
// Simple healthcheck endpoint for Railway - no DB required
http_response_code(200);
header('Content-Type: application/json');
echo json_encode(['status' => 'ok']);
