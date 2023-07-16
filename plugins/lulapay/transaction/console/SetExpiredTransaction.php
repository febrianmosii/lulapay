<?php namespace Lulapay\Transaction\Console;

use Illuminate\Console\Command;
use Lulapay\Transaction\Models\Transaction;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class SetExpiredTransaction extends Command
{
    /**
     * @var string The console command name.
     */
    protected $name = 'transaction:set_expired_transaction';

    /**
     * @var string The console command description.
     */
    protected $description = 'No description provided yet...';

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        $transactions = Transaction::whereTransactionStatusId(1)->where("expired_time", "<", date('Y-m-d H:i:s'))->get();

        $this->output->writeln('Running expiry transaction scheduler...');
        $this->output->writeln('');

        $bar = $this->output->createProgressBar(count($transactions));

        foreach ($transactions as $transaction) {
            $transaction->transaction_status_id = 3;

            $log = [
                'type'                  => 'User-RQ',
                'transaction_status_id' => 3,
                'data'                  => json_encode([
                    'expired_time' => $transaction->expired_time
                ])
            ];
    
            $transaction->transaction_logs()->create($log);

            $bar->advance();
        }

        $bar->finish();

        $this->output->writeln('');
        $this->output->writeln('');
        $this->output->writeln('Expiry transaction scheduler has been run successfully...');
    }

    /**
     * Get the console command arguments.
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
     * @return array
     */
    protected function getOptions()
    {
        return [];
    }
}
