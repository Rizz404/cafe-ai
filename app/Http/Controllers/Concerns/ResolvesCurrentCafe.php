<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Cafe;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait ResolvesCurrentCafe
{
    protected function currentCafe(Request $request): Cafe
    {
        $cafe = $request->user()->currentCafe();

        if (! $cafe) {
            throw new HttpException(403, 'No cafe is linked to this account yet.');
        }

        return $cafe;
    }
}
