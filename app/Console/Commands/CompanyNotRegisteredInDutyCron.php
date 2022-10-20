<?php

namespace App\Console\Commands;

use App\Http\Controllers\API\CompanyAPIController;
use Illuminate\Console\Command;

class CompanyNotRegisteredInDutyCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'CompanyNotRegistratedInDuty:cron';

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
        $companyController->getCompanyWithEmployeesCount();
        return Command::SUCCESS;
    }
}
