<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Sale;

class ProcessShippedSales extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sales:shipped-to-delivered';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark Sales that have been shipped X days ago, as delivered.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $shippedBefore = now()->subDays(config('prilov.sales.days_shipping_to_delivered'));
        // We want to fire events.
        $sales = Sale::where('shipped', '<', $shippedBefore)->where('status', Sale::STATUS_SHIPPED)->get();
        foreach ($sales as $sale) {
            $sale->status = Sale::STATUS_DELIVERED;
            $sale->delivered = now();
            $sale->save();
        }
    }
}
