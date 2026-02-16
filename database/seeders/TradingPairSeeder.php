<?php

namespace Database\Seeders;

use App\Models\TradingPair;
use Illuminate\Database\Seeder;

class TradingPairSeeder extends Seeder
{
    public function run(): void
    {
        $pairs = [
            ['symbol' => 'SEIUSDC', 'base_asset' => 'SEI', 'quote_asset' => 'USDC', 'stream_name' => 'seiusdc@depth20'],
        ];

        foreach ($pairs as $pair) {
            TradingPair::updateOrCreate(
                ['symbol' => $pair['symbol']],
                $pair
            );
        }
    }
}
