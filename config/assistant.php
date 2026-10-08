<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Barista
    |--------------------------------------------------------------------------
    |
    | Budgets for one guest turn against the self-hosted, OpenAI-compatible
    | chat endpoint. Provider connection values live in config/services.php
    | under "local_llm".
    |
    */

    // A whole guest turn, every tool round trip included, has to fit inside
    // this: Cloudflare drops a request still unanswered after 100 seconds.
    'reply_budget_seconds' => 85,

    // Longest single request to the model.
    'request_timeout_seconds' => 120,

    'max_tool_iterations' => 6,

    // Generous in case a server ignores reasoning_effort and the model still
    // reasons before it emits a tool call.
    'max_tokens' => 4096,

    'temperature' => 0.3,

    // The local model is a "thinking" model; "none" skips the long reasoning
    // pass, which roughly halves the latency.
    'reasoning_effort' => env('ASSISTANT_REASONING_EFFORT', 'none'),

];
