<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\API\CompanyAPIController;

class EmployeesDutyExpiryCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dutyExpiry:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $companyController = new CompanyAPIController();
        $companyController->getEmployeeDutyExpiryCount();
        return Command::SUCCESS;
    }
}
