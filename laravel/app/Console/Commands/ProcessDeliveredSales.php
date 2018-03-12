<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Sale;

class ProcessDeliveredSales extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sales:delivered-to-completed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark Sales that have been delivered X days ago, as completed.';

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
        // We want to fire events.
        $sales = Sale::where(function ($query) {
            $deliveredBefore = now()->subDays(config('prilov.sales.days_delivered_to_completed'));
            $query->where('delivered', '<', $deliveredBefore)->orWhere('received', '<', $deliveredBefore);
        })->whereBetween('status', [Sale::STATUS_DELIVERED, Sale::STATUS_RECEIVED])->get();

        foreach ($sales as $sale) {
            $sale->status = Sale::STATUS_COMPLETED;
            $sale->save();
        }
    }
}
