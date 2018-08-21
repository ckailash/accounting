<?php

use Faker\Factory as Faker;

use Illuminate\Support\Facades\Schema;
use Models\User;
use Models\Account;
use Scottlaurent\Accounting\Models\Journal;
use Models\CompanyJournal;
use Scottlaurent\Accounting\Models\Ledger;
use Illuminate\Support\Facades\DB;

/**
 * Class BaseTest
 */
abstract class BaseTest extends \Orchestra\Testbench\TestCase
{

	/**
	 * @var Ledger
	 */
	public $company_assets_ledger;

	/**
	 * @var Ledger
	 */
	public $company_liability_ledger;

	/**
	 * @var Ledger
	 */
	public $company_equity_ledger;

	/**
	 * @var Ledger
	 */
	public $company_income_ledger;

	/**
	 * @var Ledger
	 */
	public $company_expense_ledger;

	/**
	 * @var Journal
	 */
	public $company_ar_journal;

	/**
	 * @var Journal
	 */
	public $company_cash_journal;

	/**
	 * @var Journal
	 */
	public $company_income_journal;

	/**
     * Setup the test environment.
     */
    public function setUp()
    {
        parent::setUp();
        $this->requireFilesIn(__DIR__.'/Models');
        if (!Schema::hasTable('migrations')) {
			$hasRun = false;
        } else {
        	$hasRun = DB::table('migrations')->where('migration', '2017_05_21_000000_create_accounting_ledgers_table')->exists();
        }

	    if (!$hasRun) {
		    $this->artisan('migrate', ['--database'=>'testbench','--path'=>'migrations']);
		    $this->loadMigrationsFrom(realpath(__DIR__.'/migrations'));
		    $this->setUpCompanyLedgersAndJournals();
	    } else {
		    $this->initializeLedgerAndJournals();
	    }

        $this->faker = Faker::create();
    }

	/**
	 * When using PHP Storm,
	 * @param null $directory
	 */
	public function requireFilesIn($directory = null)
	{
		if ($directory) {
			foreach (scandir($directory) as $filename) {
			    $file_path = $directory . '/' . $filename;
			    if (is_file($file_path)) {
			        require_once $file_path;
			    }
			}
		}
	}
	
	
	/**
	 * Define environment setup.
	 *
	 * @param  \Illuminate\Foundation\Application  $app
	 * @return void
	 */
	protected function getEnvironmentSetUp($app)
	{
	    // Setup default database to use sqlite :memory:
	    $app['config']->set('database.default', 'testbench');
	    $app['config']->set('database.connections.testbench', [
	    	'driver' => 'mysql',
		    'host' => '192.168.10.10',
		    'port' => '3306',
		    'database' => 'test',
		    'username' => 'homestead',
		    'password' => 'secret'
	    ]);
	    
	    Eloquent::unguard();
	}
	
	
	/**
	 * @param \Illuminate\Foundation\Application $app
	 * @return array
	 */
	protected function getPackageProviders($app)
	{
	    return [
	         \Orchestra\Database\ConsoleServiceProvider::class,
	    ];
	}
	
	/**
	 * @param int $qty
	 * @return User[]
	 */
	protected function createFakeUsers(int $qty) {
		$users = [];
		for ($x=1; $x<=$qty; $x++) {
			$users[] = $this->createFakeUser();
		}
		return $users;
	}
	
	/**
	 * @return array
	 */
	protected function createFakeUser() {
		return User::create([
			'name' => $this->faker->name,
			'email' => $this->faker->email,
			'password' => $this->faker->password
		]);
	}
	
	/**
	 * @return array
	 */
	protected function createFakeAccount() {
		return Account::create([
			'name' => $this->faker->company,
		]);
	}
	
	/**
	 *
	 */
	protected function setUpCompanyLedgersAndJournals()
	{
		/*
		|--------------------------------------------------------------------------
		| These would probably be pretty standard
		|--------------------------------------------------------------------------
		*/
		
		$this->company_assets_ledger = Ledger::create([
			'name' => 'Company Assets',
			'type' => 'asset'
        ]);
		
		$this->company_liability_ledger = Ledger::create([
			'name' => 'Company Liabilities',
			'type' => 'liability'
        ]);
		
		$this->company_equity_ledger = Ledger::create([
			'name' => 'Company Equity',
			'type' => 'equity'
        ]);
		
		$this->company_income_ledger = Ledger::create([
			'name' => 'Company Income',
			'type' => 'income'
        ]);
		
		$this->company_expense_ledger = Ledger::create([
			'name' => 'Company Expenses',
			'type' => 'expense'
        ]);
		
		
		/*
		|--------------------------------------------------------------------------
		| This can be a bit confusing, becasue we are creating a new "company journal"
		| Really this is just a table with a bunch of obects that we attach journals to
		| for the company.
		|--------------------------------------------------------------------------
		*/
		
		$this->company_ar_journal = CompanyJournal::create(['name'=>'Accounts Receivable'])->initJournal();
		$this->company_ar_journal->assignToLedger($this->company_assets_ledger);
		
		$this->company_cash_journal = CompanyJournal::create(['name'=>'Cash'])->initJournal();
		$this->company_cash_journal->assignToLedger($this->company_assets_ledger);
		
		$this->company_income_journal = CompanyJournal::create(['name'=>'Company Income'])->initJournal();
		$this->company_income_journal->assignToLedger($this->company_income_ledger);
	}

	protected function initializeLedgerAndJournals() {
		$this->company_assets_ledger = Ledger::where([
			'name' => 'Company Assets',
			'type' => 'asset'
		])->first();

		$this->company_liability_ledger = Ledger::where([
			'name' => 'Company Liabilities',
			'type' => 'liability'
		])->first();

		$this->company_equity_ledger = Ledger::where([
			'name' => 'Company Equity',
			'type' => 'equity'
		])->first();

		$this->company_income_ledger = Ledger::where([
			'name' => 'Company Income',
			'type' => 'income'
		])->first();

		$this->company_expense_ledger = Ledger::where([
			'name' => 'Company Expenses',
			'type' => 'expense'
		])->first();


		/*
		|--------------------------------------------------------------------------
		| This can be a bit confusing, becasue we are creating a new "company journal"
		| Really this is just a table with a bunch of obects that we attach journals to
		| for the company.
		|--------------------------------------------------------------------------
		*/

		$this->company_ar_journal = CompanyJournal::where('name', 'Accounts Receivable')->first()->journal;

		$this->company_cash_journal = CompanyJournal::where('name', 'Cash')->first()->journal;

		$this->company_income_journal = CompanyJournal::where('name', 'Company Income')->first()->journal;
	}
	
	
}