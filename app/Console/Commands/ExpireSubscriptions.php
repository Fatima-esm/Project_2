<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;

class ExpireSubscriptions extends Command
{
    
    protected $signature = 'subscriptions:expire';

    protected $description = 'Expire finished subscriptions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Subscription::where('status','paid') ->where( 'expires_at','<', now() )->update(['status'=>'expired' ]);
    }
}
