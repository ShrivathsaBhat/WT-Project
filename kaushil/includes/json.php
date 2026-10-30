<?php
// Team JSON contract: { "success": true, "data": ... } / { "success": false, "message": "..." }
function json_ok($data = null): void { header('Content-Type: application/json'); echo json_encode(['success' => true, 'data' => $data]); exit; }
function json_fail(string $msg, int $code = 400): void { http_response_code($code); header('Content-Type: application/json'); echo json_encode(['success' => false, 'message' => $msg]); exit; }
