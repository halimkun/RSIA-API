<?php
// success response
function isSuccess($data, $message = null, $code = 200)
{
    return response()->json([
        'success' => true,
        'message' => $message,
        'data'    => $data
    ], $code);
}

// fail response
function isFail($message = null, $code = 400)
{
    return response()->json([
        'success' => false,
        'message' => $message
    ], $code);
}

// not found response
function isNotFound(string $message)
{
    return response()->json([
        'success' => false,
        'message' => $message
    ], 404);
}

// ok response
function isOk(string $message)
{
    return response()->json([
        'success' => true,
        'message' => $message
    ], 200);
}

// unauthenticated response
function isUnauthenticated(string $message = 'Unauthenticated')
{
    return response()->json([
        'success' => false,
        'message' => $message
    ], 401);
}

// forbidden response
function isForbidden(string $message = 'Forbidden')
{
    return response()->json([
        'success' => false,
        'message' => $message
    ], 403);
}

// error server response
function isError(string $message = 'Internal Server Error')
{
    return response()->json([
        'success' => false,
        'message' => $message
    ], 500);
}

//debug return 
function debugReturn($data, $message = "Debug", $code = 200)
{
    return response()->json([
        'debug'     => true,
        'date'      => date('Y-m-d H:i:s'),
        'message'   => $message,
        'data'      => $data
    ], $code);
}