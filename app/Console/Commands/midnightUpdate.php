<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\GoogleSheetController;

class midnightUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run:midnightUpdate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command execute every midnight for append data in google sheet';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    public $sheetController;

    public function __construct(GoogleSheetController $sheetController)
    {
        parent::__construct();
        $this->sheetController = $sheetController;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $data = $this->sheetController->appendBrandWiseSales();
        
    }
}
